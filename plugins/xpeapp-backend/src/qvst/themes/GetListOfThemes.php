<?php

namespace XpeApp\qvst\themes;

class GetListOfThemes {
	public static function apiGetQvstThemes(\WP_REST_Request $request)
{
	xpeapp_log_request($request);
	
	// Utiliser la classe $wpdb pour effectuer une requête SQL
	global $wpdb;

	// Nom de la table personnalisée
	$table_name_theme = $wpdb->prefix . 'qvst_theme';

	$queryTheme = "
		SELECT 
			theme.id,
			theme.name
		FROM $table_name_theme theme
	";

	$resultsTheme = $wpdb->get_results($queryTheme);
	// Return all rows
	$data = array();
	foreach ($resultsTheme as $result) {
		$data[] = array(
			'id' => $result->id,
			'name' => $result->name
		);
	}
	return $data;
}
}