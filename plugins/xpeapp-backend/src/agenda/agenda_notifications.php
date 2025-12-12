<?php

/**
 * Vérifier et envoyer les notifications pour les anniversaires du jour
 */
function checkAndSendBirthdayNotifications()
{
	global $wpdb;
	$table_name = $wpdb->prefix . 'agenda_birthday';
	
	// Récupérer les anniversaires du jour (même jour et mois, peu importe l'année)
	$today = current_time('mysql');
	$day = date('d', strtotime($today));
	$month = date('m', strtotime($today));
	
	$birthdays_today = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE DAY(birthdate) = %s AND MONTH(birthdate) = %s",
			$day,
			$month
		)
	);
	
	foreach ($birthdays_today as $birthday) {
		sendFcmNotification(
			"Joyeux anniversaire !",
			"Aujourd'hui c'est l'anniversaire de {$birthday->first_name} !"
		);
		xpeapp_log(Xpeapp_Log_Level::Info, "Sent birthday notification for: {$birthday->first_name}");
	}
}

/**
 * Vérifier et envoyer les notifications pour les événements du jour
 */
function checkAndSendEventNotifications()
{
	global $wpdb;
	$table_name = $wpdb->prefix . 'agenda_events';
	
	// Récupérer les événements qui commencent aujourd'hui
	$today = current_time('Y-m-d');
	
	$events_today = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE DATE(date) = %s",
			$today
		)
	);
	
	foreach ($events_today as $event) {
		sendFcmNotification(
			"Événement aujourd'hui !",
			"{$event->title}"
		);
		xpeapp_log(Xpeapp_Log_Level::Info, "Sent event notification for: {$event->title}");
	}
}

// Programmer les vérifications quotidiennes à 9h du matin
function scheduleDailyNotifications()
{
	// Anniversaires
	if (!wp_next_scheduled('xpeapp_check_birthdays')) {
		wp_schedule_event(strtotime('09:00:00'), 'daily', 'xpeapp_check_birthdays');
	}
	
	// Événements
	if (!wp_next_scheduled('xpeapp_check_events')) {
		wp_schedule_event(strtotime('09:00:00'), 'daily', 'xpeapp_check_events');
	}
}
add_action('wp', 'scheduleDailyNotifications');

// Hooks pour les événements programmés
add_action('xpeapp_check_birthdays', 'checkAndSendBirthdayNotifications');
add_action('xpeapp_check_events', 'checkAndSendEventNotifications');

// Cleanup lors de la désactivation du plugin
register_deactivation_hook(__FILE__, function() {
	wp_clear_scheduled_hook('xpeapp_check_birthdays');
	wp_clear_scheduled_hook('xpeapp_check_events');
});
