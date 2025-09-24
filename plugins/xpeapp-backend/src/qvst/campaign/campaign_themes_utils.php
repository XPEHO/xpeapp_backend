<?php
/**
 * Utilitaires pour la gestion des thèmes multiples d'une campagne
 */

global $wpdb;

function getThemesForCampaign($campaign_id) {
    global $wpdb;
    $table_name_campaign_themes = $wpdb->prefix . 'qvst_campaign_themes';
    $table_name_theme = $wpdb->prefix . 'qvst_theme';
    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT t.* FROM $table_name_campaign_themes ct INNER JOIN $table_name_theme t ON ct.theme_id = t.id WHERE ct.campaign_id = %d",
            $campaign_id
        )
    );
}
// en anglais
function setThemesForCampaign($campaign_id, $theme_ids) {
    global $wpdb;
    $table_name_campaign_themes = $wpdb->prefix . 'qvst_campaign_themes';
    // Delete old associations
    $wpdb->delete($table_name_campaign_themes, array('campaign_id' => $campaign_id));
    // Add new associations
    foreach ($theme_ids as $theme_id) {
        $wpdb->insert($table_name_campaign_themes, array('campaign_id' => $campaign_id, 'theme_id' => $theme_id));
    }
}
