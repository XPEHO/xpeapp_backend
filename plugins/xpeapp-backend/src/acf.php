<?php

/**
 * Registers local ACF fields for user permissions.
 */
function register_local_acf_fields() {
	
	acf_add_local_field_group(array(
		'key' => 'user_permissions',
		'title' => 'User Permissions',
		'fields' => array(),
		'location' => array(
			array(
				array(
					'param' => 'user_form',
					'operator' => '==',
					'value' => 'all',
				),
			),
		),
	));

	acf_add_local_field(array(
		'key' => 'liste_des_droits_possibles_de_qvst',
		'label' => 'Liste des droits possibles de QVST',
		'name' => 'liste_des_droits_possibles_de_qvst',
		'type' => 'select',
		'instructions' => '',
		'required' => 0,
		'conditional_logic' => 0,
		'wrapper' => array(
			'width' => '',
			'class' => '',
			'id' => '',
		),
		'choices' => array(
			'answerQvst' => 'Répondre à une campagne de QVST',
			'analytics' => 'Droit d\'accès aux statistiques de QVST',
			// Note: You can add new permissions here
		),
		'default_value' => array(),
		'allow_null' => 0,
		'multiple' => 1,
		'ui' => 1,
		'ajax' => 1,
		'return_format' => 'value',
		'placeholder' => '',
		'parent' => 'user_permissions',
	));
}

/**
 * Displays the output of local custom fields.
 *
 * This function retrieves all local custom fields using acf_get_local_fields() and displays their details in a table format.
 */
function custom_fields_display_output() {
	$fields = acf_get_local_fields();

    echo '<div class="wrap">';
	echo '<h1>XpeApp Local Custom fields</h1>';
	
	foreach($fields as $field_key => $field_value) {
		echo '<table class="wp-list-table widefat fixed striped">';
		echo '<thead><tr><th>Field</th><th>Value</th></tr></thead><tbody>';

		echo '<tr><td>Label</td><td>' . $field_value['label'] . '</td></tr>';
		echo '<tr><td>Type</td><td>' . $field_value['type'] . '</td></tr>';
		echo '<tr><td>Parent</td><td>' . $field_value['parent'] . '</td></tr>';
		echo '<tr><td>Required</td><td>' . ($field_value['required'] ? 'Yes' : 'No') . '</td></tr>';
		echo '<tr><td>Choices</td><td>';
		foreach ($field_value['choices'] as $choice_key => $choice_value) {
			echo $choice_key . ': ' . $choice_value . '<br>';
		}
		echo '</td></tr>';
		echo '<tr><td>Multiple</td><td>' . ($field_value['multiple'] ? 'Yes' : 'No') . '</td></tr>';

		echo '</tbody></table>';
	}
    echo '</div>';
}
