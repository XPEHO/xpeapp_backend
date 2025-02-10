<?php

/**
 * @package           XpeApp Backend
 * @since             1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       XpeApp Backend
 * Description:       Defines the REST API endpoints and the backend logic for XpeApp. The endpoints are authenticated using jwt.
 * Version:           1.0.0
 * Author:            XPEHO
 * Text Domain:       xpeapp-backend
 * Requires Plugins: jwt-authentication-for-wp-rest-api, advanced-custom-fields
 */

require 'vendor/autoload.php';

include 'src/logging.php';
include 'src/acf.php';

// Include all endpoints API
/// Notification XpeApp
include 'src/post_notifications.php';

/// Question
include 'src/qvst/questions/get_list_of_qvst_questions.php';
include 'src/qvst/questions/get_qvst_question_by_id.php';
include 'src/qvst/questions/get_qvst_resume_by_id.php';
include 'src/qvst/questions/get_qvst_questions_by_theme.php';
include 'src/qvst/questions/post_qvst_question.php';
include 'src/qvst/questions/delete_qvst_question.php';
include 'src/qvst/questions/import_qvst_questions.php';
include 'src/qvst/questions/get_questions_by_campaign_and_user.php';
include 'src/qvst/questions/get_questions_by_campaign.php';
include 'src/qvst/questions/put_qvst_question.php';
include 'src/qvst/questions/post_questions_answers.php';
include_once 'src/qvst/questions/post_open_answers.php';

/// Campaign
include 'src/qvst/campaign/get_list_of_campaigns.php';
include 'src/qvst/campaign/get_active_campaign.php';
include 'src/qvst/campaign/get_campaign_progress.php';
include 'src/qvst/campaign/get_stats_of_campaign.php';
include 'src/qvst/campaign/post_campaign.php';
include 'src/qvst/campaign/put_campaign_status.php';

/// Theme
include 'src/qvst/themes/get_list_of_themes.php';

/// Answer repository
include 'src/qvst/answer_repository/get_answers_repo_list.php';
include 'src/qvst/answer_repository/put_answer_repo.php';

/// User
include 'src/qvst/user/get_user.php';
include_once 'src/qvst/user/put_user.php';
include_once 'src/qvst/user/get_user_infos.php';

class Xpeapp_Backend {

	/**
	 * Secure an API endpoint by checking if the current user has the required permission.
	 *
	 * This function checks if the current user has the specified permission ($param) to access an API endpoint.
	 * If $param is null, the function returns true, allowing all users to access the endpoint.
	 * Otherwise, it checks if the current user has the required permission by checking if $param is in the user's role array.
	 * 
	 * To give a user a permission, you need to add the permission to the user's profile using the Advanced Custom Fields (ACF) plugin.
	 * See further documentation at https://yaki.xpeho.fr/bookstack/books/wordpress/page/comment-securiser-les-endpoints-de-wordpress
	 *
	 * @param string|null $param The permission required to access the API endpoint. If null, all users are authorized.
	 * @return bool True if the user is authorized, false otherwise.
	 */
	function secure_endpoint_with_parameter($param)
	{
		$current_user = get_current_user_id();
		$user_permission_for_qvst = get_field('liste_des_droits_possibles_de_qvst', 'user_' . $current_user);
		$user_permission_for_user = get_field('liste_des_droits_possibles_de_utilisateur', 'user_' . $current_user);
		// if $param is null, we authorize all users
		if ($param == null) {
			return true;
		} else {
			if($user_permission_for_qvst == null && $user_permission_for_user == null) {
				xpeapp_log(Xpeapp_Log_Level::Warn, "Unauthorized user \"$current_user\" tried to access an API route.");
				return false;
			}
			return in_array($param, $user_permission_for_qvst) || in_array($param, $user_permission_for_user);
		}
	}

	function xpeapp_init_rest_api()
	{
		$userQvstParameter = 'userQvst';
		$adminQvstParameter = 'adminQvst';
		$editPasswordParameter = 'editPassword';
		$endpoint_namespace = 'xpeho/v1';

		// GET USER 
		// In: Header(email:)
		// Out: User ID

		// XpeappRestRoute(
		// 	namespace:"xpeho/v1",
		// 	route: "/user",
		//  verb: GET,
		//  permisson: allowAll,
		//  actions: "log the action, log the error")
		register_rest_route(
			$endpoint_namespace,
			'/user',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => 'api_get_user',
				'permission_callback' => function () {
					return $this->secure_endpoint_with_parameter(null);
				}
			)
		);
		// POST NOTIFICATION
		// In: ???? TODO
		// Out: Nothing
		register_rest_route(
			$endpoint_namespace,
			'/notifications',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => 'api_post_notification',
				'permission_callback' => function () use ($adminQvstParameter) {
					return $this->secure_endpoint_with_parameter($adminQvstParameter);
				}
			)
		);

		// === QVST Questions ===
		// GET QVST
		// In: Nothing
		// Out: QVST
		register_rest_route(
			$endpoint_namespace,
			'/qvst',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => 'api_get_qvst',
				'permission_callback' => function () use ($userQvstParameter) {
					return $this->secure_endpoint_with_parameter($userQvstParameter);
				}
			)
		);
		register_rest_route(
			/*

			*/
			$endpoint_namespace,
			'/qvst:add',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => 'api_post_qvst',
				'permission_callback' => function () use ($adminQvstParameter) {
					return $this->secure_endpoint_with_parameter($adminQvstParameter);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			'/qvst:import',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => 'api_import_qvst',
				'permission_callback' => function () use ($adminQvstParameter) {
					return $this->secure_endpoint_with_parameter($adminQvstParameter);
				}
			)
		);

		// === QVST Question ===
		register_rest_route(
			$endpoint_namespace,
			'/qvst/(?P<id>[\d]+)',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => 'api_get_qvst_by_id',
				'permission_callback' => function () use ($userQvstParameter) {
					return $this->secure_endpoint_with_parameter($userQvstParameter);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			'/qvst/(?P<id>[\d]+):resume',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => 'api_get_qvst_resume_by_id',
				'permission_callback' => function () use ($adminQvstParameter) {
					return $this->secure_endpoint_with_parameter($adminQvstParameter);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			'/qvst/(?P<id>[\d]+):update',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => 'api_update_question',
				'permission_callback' => function () use ($adminQvstParameter) {
					return $this->secure_endpoint_with_parameter($adminQvstParameter);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			'/qvst/(?P<id>[\d]+):delete',
			array(
				'methods' => WP_REST_Server::DELETABLE,
				'callback' => 'api_delete_qvst',
				'permission_callback' => function () use ($adminQvstParameter) {
					return $this->secure_endpoint_with_parameter($adminQvstParameter);
				}
			)
		);

		// === Themes ===
		register_rest_route(
			$endpoint_namespace,
			'/qvst/themes',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => 'api_get_qvst_themes',
				'permission_callback' => function () use ($adminQvstParameter) {
					return $this->secure_endpoint_with_parameter($adminQvstParameter);
				}
			)
		);
		// Questions of the theme
		register_rest_route(
			$endpoint_namespace,
			'/qvst/themes/(?P<id>[\d]+)/questions',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => 'api_get_qvst_questions_by_theme_id',
				'permission_callback' => function () use ($adminQvstParameter) {
					return $this->secure_endpoint_with_parameter($adminQvstParameter);
				}
			)
		);

		// === Answers Repos ===
		register_rest_route(
			$endpoint_namespace,
			'/qvst/answers_repo',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => 'api_get_answers_repo',
				'permission_callback' => function () use ($adminQvstParameter) {
					return $this->secure_endpoint_with_parameter($adminQvstParameter);
				}
			)
		);

		// === Answers Repo ===
		register_rest_route(
			$endpoint_namespace,
			'/qvst/answers_repo/(?P<id>[\d]+):update',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => 'api_update_answers_repo',
				'permission_callback' => function () use ($adminQvstParameter) {
					return $this->secure_endpoint_with_parameter($adminQvstParameter);
				}
			)
		);

		// === Campaigns ===
		register_rest_route(
			$endpoint_namespace,
			'/qvst/campaigns',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => 'api_get_campaigns',
				'permission_callback' => function () use ($userQvstParameter) {
					return $this->secure_endpoint_with_parameter($userQvstParameter);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			'/qvst/campaigns:add',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => 'api_post_campaign',
				'permission_callback' => function () use ($adminQvstParameter) {
					return $this->secure_endpoint_with_parameter($adminQvstParameter);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			'/qvst/campaigns:active',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => 'get_open_campaign',
				'permission_callback' => function () use ($userQvstParameter) {
					return $this->secure_endpoint_with_parameter($userQvstParameter);
				}
			)
		);

		// === Campaign ===
		register_rest_route( // Resource: qAndA Pair
			$endpoint_namespace,
			'/qvst/campaigns/(?P<id>[\d]+):questions',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => 'api_get_questions_by_campaign_id',
				'permission_callback' => function () use ($userQvstParameter) {
					return $this->secure_endpoint_with_parameter($userQvstParameter);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			'/qvst/campaigns/(?P<id>[\d]+):stats',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => 'api_get_qvst_stats_by_campaign_id',
				'permission_callback' => function () use ($adminQvstParameter) {
					return $this->secure_endpoint_with_parameter($adminQvstParameter);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			'/qvst/campaigns/(?P<id>[\d]+)/status:update',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => 'api_update_campaign_status',
				'permission_callback' => function () use ($adminQvstParameter) {
					return $this->secure_endpoint_with_parameter($adminQvstParameter);
				}
			)
		);
		register_rest_route( // Resource: qAndA Pair
			$endpoint_namespace,
			'/qvst/campaigns/(?P<id>[\d]+)/questions',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => 'api_get_questions_by_campaign_id_and_user_id',
				'permission_callback' => function () use ($userQvstParameter) {
					return $this->secure_endpoint_with_parameter($userQvstParameter);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			'/qvst/campaigns/(?P<id>[\d]+)/questions:answer',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => 'api_post_qvst_answers',
				'permission_callback' => function () use ($userQvstParameter) {
					return $this->secure_endpoint_with_parameter($userQvstParameter);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			'/campaign-progress',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => 'api_campaign_progress',
				'permission_callback' => function () use ($userQvstParameter) {
					return $this->secure_endpoint_with_parameter($userQvstParameter);
				}
			)
		);
		// Route pour mettre à jour le mot de passe de l'utilisateur
        register_rest_route(
            $endpoint_namespace,
            '/update-password',
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => 'apiUpdateUserPassword',
                'permission_callback' => function () use ($editPasswordParameter) {
                    return $this->secure_endpoint_with_parameter($editPasswordParameter);
                }
            )
        );

		// Route pour récupérer les informations de l'utilisateur
        register_rest_route(
            $endpoint_namespace,
            '/user-infos',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => 'apiGetUserInfos',
                'permission_callback' => function () {
                    return $this->secure_endpoint_with_parameter(null);
                }
            )
        );
		
		// Route pour enregistrer la réponse dans une table de champ libre
		register_rest_route(
			$endpoint_namespace,
			'/qvst/open-answers',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => 'postOpenAnswers',
				'permission_callback' => function () use ($userQvstParameter) {
					return $this->secure_endpoint_with_parameter($userQvstParameter);
				}
			)
		);
		
	}

	function xpeapp_menu_page()
	{
		add_menu_page(
			"XpeApp Backend",
			"XpeApp Backend",
			"manage_options",
			"xpeapp-backend",
			function () { $this->xpeappBackendPage(); },
			"dashicons-admin-generic",
			200
		);
	}

	function on_plugin_activation() {
		xpeapp_create_log_database();
		xpeapp_log(Xpeapp_Log_Level::Info, "Activating xpeapp-backend");
	}

	function xpeappBackendPage()
	{
		echo "<h1>XpeApp Backend</h1>";
		echo "<p><a href='https://github.com/xpeho/xpeapp_back'>https://github.com/xpeho/xpeapp_back</a></p>";
		xpeapp_logging_console_output();
		custom_fields_display_output();
	}

	function run() {
		add_action('admin_menu', function () { $this->xpeapp_menu_page(); });
		add_action('rest_api_init', function () { $this->xpeapp_init_rest_api(); });
		register_activation_hook(__FILE__, function () { $this->on_plugin_activation(); });
		add_action('acf/init', function () { register_local_acf_fields(); });
	}

}


$xpeapp_backend = new Xpeapp_Backend();
$xpeapp_backend->run();