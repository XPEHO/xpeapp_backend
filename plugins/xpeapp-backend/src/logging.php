<?php

enum Xpeapp_Log_Level {
    case Info;
    case Warn;
    case Error;

    function string(): String {
        return match($this) {
            Xpeapp_Log_Level::Info => "Info",
            Xpeapp_Log_Level::Warn => "Warn",
            Xpeapp_Log_Level::Error => "Error"
        };
    }
}

function xpeapp_log(Xpeapp_Log_Level $log_level, String $message): void {
    xpeapp_log_text($log_level, $message);

    global $wpdb;
    $table_name = $wpdb->prefix . "xpeapp_log";
    $wpdb->insert(
        $table_name,
        array(
            'log_level' => $log_level->string(),
            'message' => $message,
            'log_time' => current_time('mysql')
        )
    );
}

function xpeapp_log_text(Xpeapp_Log_Level $log_level, String $message): void {
    $log_entry = "[" . current_time('mysql') . "] [" . $log_level->string() . "] " . $message . PHP_EOL;
    $log_file = plugin_dir_path(__FILE__) . '../../xpeapp.log';
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

function xpeapp_log_request(WP_REST_Request $request): void {
    xpeapp_log(Xpeapp_Log_Level::Info, $request->get_method() . " " . $request->get_route());
}

function xpeapp_create_log_database(): void {
    xpeapp_log_text(Xpeapp_Log_Level::Info, "Creating log database");

    /** @var wpdb $wpdb */
    global $wpdb;

    $table_name = $wpdb->prefix . "xpeapp_log";
    $charset_collate = $wpdb->get_charset_collate();

    foreach ($wpdb->get_col('SHOW TABLES', 0) as $table) {
        if ($table === $table_name) {
            xpeapp_log_text(Xpeapp_Log_Level::Warn, "Table $table_name already exists, "
            . "keeping it. This is not the first plugin activation.");
            return;
        }
    }

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        log_level varchar(10) NOT NULL,
        message text NOT NULL,
        log_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    xpeapp_log_text(Xpeapp_Log_Level::Info, "Log database created");
}

function xpeapp_logging_console_output(): void {
    /** @var wpdb $wpdb */
    global $wpdb;

    $table_name = $wpdb->prefix . "xpeapp_log";

    if (isset($_POST['clear_logs'])) {
        // Note: Intentionally does not clear from xpeapp.log
        $sql = "DELETE FROM $table_name WHERE true;";
        $wpdb->query($sql);
    }

    $logs = $wpdb->get_results("SELECT log_time, log_level, message FROM $table_name ORDER BY id DESC");

    // === HTML ===
    echo '<div class="wrap"><h1>XpeApp Logs</h1>';

    echo '<form method="post">';
    echo '<input type="hidden" name="clear_logs" value="1">';
    submit_button('Clear Logs');
    echo '</form>';

    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Time</th><th>Level</th><th>Message</th></tr></thead><tbody>';
    foreach ($logs as $log) {
        echo "<tr><td>{$log->log_time}</td><td>{$log->log_level}</td><td>{$log->message}</td></tr>";
    }
    echo '</tbody></table></div>';
}