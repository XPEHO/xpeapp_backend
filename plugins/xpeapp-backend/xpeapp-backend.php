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
include_once 'src/notification/fcm_log.php';
include_once 'src/notification/notification_helpers.php';

// Include all endpoints API
/// Notification XpeApp
include_once 'src/notification/post_notifications.php';

/// Question
use XpeApp\qvst\questions\get_list_of_qvst_questions;
use XpeApp\qvst\questions\get_qvst_question_by_id;
use XpeApp\qvst\questions\get_qvst_resume_by_id;
use XpeApp\qvst\questions\get_qvst_questions_by_theme;
use XpeApp\qvst\questions\post_qvst_question;
use XpeApp\qvst\questions\delete_qvst_question;
use XpeApp\qvst\questions\import_qvst_questions;
use XpeApp\qvst\questions\get_questions_by_campaign_and_user;
use XpeApp\qvst\questions\get_questions_by_campaign;
use XpeApp\qvst\questions\put_qvst_question;
use XpeApp\qvst\questions\post_questions_answers;
use XpeApp\qvst\questions\post_open_answers;

/// Campaign
use XpeApp\qvst\campaign\get_list_of_campaigns;
use XpeApp\qvst\campaign\get_active_campaign;
use XpeApp\qvst\campaign\get_campaign_progress;
use XpeApp\qvst\campaign\get_stats_of_campaign;
use XpeApp\qvst\campaign\get_campaign_analysis;
use XpeApp\qvst\campaign\post_campaign;
use XpeApp\qvst\campaign\put_campaign_status;
use XpeApp\qvst\campaign\get_csv_file_campaign;
use XpeApp\qvst\campaign\campaign_reminder;	
// Utilities for managing multiple themes in a campaign
include_once 'src/qvst/campaign/campaign_themes_utils.php';

/// Theme
use XpeApp\qvst\themes\get_list_of_themes;

/// Answer repository
include 'src/qvst/answer_repository/get_answers_repo_list.php';
include 'src/qvst/answer_repository/put_answer_repo.php';

/// User
use XpeApp\qvst\user\get_user;
use XpeApp\qvst\user\put_user;
use XpeApp\qvst\user\get_user_infos;
// Agenda
// Notifications agenda
include_once 'src/agenda/agenda_notifications.php';
// Event Types
use XpeApp\agenda\events_types\get_events_types_by_id;
use XpeApp\agenda\events_types\get_all_events_types;
use XpeApp\agenda\events_types\post_events_types;
use XpeApp\agenda\events_types\put_events_types;
use XpeApp\agenda\events_types\delete_events_types;
// Events
use XpeApp\agenda\events\get_all_events;
use XpeApp\agenda\events\post_events;
use XpeApp\agenda\events\get_events_by_id;
use XpeApp\agenda\events\delete_events;
use XpeApp\agenda\events\put_events;
// Birthday
use XpeApp\agenda\birthday\get_all_birthday;
use XpeApp\agenda\birthday\post_birthday;
use XpeApp\agenda\birthday\get_birthday_by_id;
use XpeApp\agenda\birthday\delete_birthday;
use XpeApp\agenda\birthday\put_birthday;

// Storage
use XpeApp\storage\get_all_folders_or_images_by_folder;
use XpeApp\storage\post_image;
use XpeApp\storage\delete_image;
use XpeApp\storage\get_image;

// Idea Box
use XpeApp\idea_box\post_idea;
use XpeApp\idea_box\get_all_ideas;
use XpeApp\idea_box\get_idea_by_id;
use XpeApp\idea_box\put_idea_status;
use XpeApp\idea_box\delete_idea_by_id;


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
		$user_permission_for_idea_box = get_field('liste_des_droits_possibles_de_la_boite_à_idées', 'user_' . $current_user);
		$user_permission_for_agenda = get_field('liste_des_droits_possibles_de_agenda', 'user_' . $current_user);
		// if $param is null, we authorize all users
		if ($param == null) {
			return true;
		} else {
			if($user_permission_for_qvst == null && $user_permission_for_user == null && $user_permission_for_agenda == null && $user_permission_for_idea_box == null) {
				xpeapp_log(Xpeapp_Log_Level::Warn, "Unauthorized user \"$current_user\" tried to access an API route.");
				return false;
			}
			return in_array($param, $user_permission_for_qvst) || in_array($param, $user_permission_for_user) || in_array($param, $user_permission_for_agenda) || in_array($param, $user_permission_for_idea_box);
		}
	}

	function xpeapp_init_rest_api()
	{
		$qvst_id_pattern = '(?P<id>[\d]+)';
		$campaign_id_pattern = '(?P<id>[\d]+)';
		$userQvstParameter = 'userQvst';
		$adminQvstParameter = 'adminQvst';
		$userOfIdeaBoxParameter = 'userOfIdeaBox';
		$adminOfIdeaBoxParameter = 'adminOfIdeaBox';
		$editPasswordParameter = 'editPassword';
		$userImageParameter = 'userImageParameter';
		$adminImageParameter = 'adminImageParameter';
		$endpoint_namespace = 'xpeho/v1';
		$userAgenda = 'userAgenda';
		$adminAgenda = 'adminAgenda';

		// === QVST Endpoints Constants ===
		$qvst_base = '/qvst/';
		$qvst_campaigns_base = '/qvst/campaigns/';

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
				'callback' => [get_user::class, 'api_get_user'],
				'permission_callback' => function () {
					return $this->secure_endpoint_with_parameter(null);
				}
			)
		);
		// POST NOTIFICATION
		// In: title, message, redirection
		// Out: Success or error message
		register_rest_route(
			$endpoint_namespace,
			'/notifications',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => 'apiPostNotification',
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
				'callback' => [get_list_of_qvst_questions::class, 'api_get_qvst'],
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
				'callback' => [post_qvst_question::class, 'api_post_qvst'],
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
				'callback' => [import_qvst_questions::class, 'api_import_qvst'],
				'permission_callback' => function () use ($adminQvstParameter) {
					return $this->secure_endpoint_with_parameter($adminQvstParameter);
				}
			)
		);

		// === QVST Question ===
		register_rest_route(
			$endpoint_namespace,
			$qvst_base . $qvst_id_pattern,
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => [get_qvst_question_by_id::class, 'api_get_qvst_by_id'],
				'permission_callback' => function () use ($userQvstParameter) {
					return $this->secure_endpoint_with_parameter($userQvstParameter);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			$qvst_base . $qvst_id_pattern . ':resume',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => [get_qvst_resume_by_id::class, 'api_get_qvst_resume_by_id'],
				'permission_callback' => function () use ($adminQvstParameter) {
					return $this->secure_endpoint_with_parameter($adminQvstParameter);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			$qvst_base . $qvst_id_pattern . ':update',
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
			$qvst_base . $qvst_id_pattern . ':delete',
			array(
				'methods' => WP_REST_Server::DELETABLE,
				'callback' => [delete_qvst_question::class, 'api_delete_qvst'],
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
				'callback' => [get_list_of_themes::class, 'api_get_qvst_themes'],
				'permission_callback' => function () use ($adminQvstParameter) {
					return $this->secure_endpoint_with_parameter($adminQvstParameter);
				}
			)
		);
		// Questions of the theme
		register_rest_route(
			$endpoint_namespace,
			$qvst_base . 'themes/' . $qvst_id_pattern . '/questions',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => [get_qvst_questions_by_theme::class, 'api_get_qvst_questions_by_theme_id'],
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
			$qvst_base . 'answers_repo/' . $qvst_id_pattern . ':update',
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
				'callback' => [get_list_of_campaigns::class, 'api_get_campaigns'],
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
				'callback' => [post_campaign::class, 'api_post_campaign'],
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
				'callback' => [get_active_campaign::class, 'get_open_campaign'],
				'permission_callback' => function () use ($userQvstParameter) {
					return $this->secure_endpoint_with_parameter($userQvstParameter);
				}
			)
		);

		// === Campaign ===
		register_rest_route( // Resource: qAndA Pair
			$endpoint_namespace,
			$qvst_campaigns_base . $campaign_id_pattern . ':questions',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => [get_questions_by_campaign::class, 'api_get_questions_by_campaign_id'],
				'permission_callback' => function () use ($userQvstParameter) {
					return $this->secure_endpoint_with_parameter($userQvstParameter);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			$qvst_campaigns_base . $campaign_id_pattern . ':stats',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => [get_stats_of_campaign::class, 'api_get_qvst_stats_by_campaign_id'],
				'permission_callback' => function () use ($adminQvstParameter) {
					return $this->secure_endpoint_with_parameter($adminQvstParameter);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			$qvst_campaigns_base . $campaign_id_pattern . ':analysis',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => [get_campaign_analysis::class, 'apiGetCampaignAnalysis'],
				'permission_callback' => function () use ($adminQvstParameter) {
					return $this->secure_endpoint_with_parameter($adminQvstParameter);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			$qvst_campaigns_base . $campaign_id_pattern . '/status:update',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => [put_campaign_status::class, 'api_update_campaign_status'],
				'permission_callback' => function () use ($adminQvstParameter) {
					return $this->secure_endpoint_with_parameter($adminQvstParameter);
				}
			)
		);
		register_rest_route( // Resource: qAndA Pair
			$endpoint_namespace,
			$qvst_campaigns_base . $campaign_id_pattern . '/questions',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => [get_questions_by_campaign_and_user::class, 'api_get_questions_by_campaign_id_and_user_id'],
				'permission_callback' => function () use ($userQvstParameter) {
					return $this->secure_endpoint_with_parameter($userQvstParameter);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			$qvst_campaigns_base . $campaign_id_pattern . '/questions:answer',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => [post_questions_answers::class, 'api_post_qvst_answers'],
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
				'callback' => [get_campaign_progress::class, 'api_get_campaign_progress'],
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
				'callback' => [put_user::class, 'apiUpdateUserPassword'],
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
				'callback' => [get_user_infos::class, 'apiGetUserInfos'],
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
				'callback' => [post_open_answers::class, 'postOpenAnswers'],
				'permission_callback' => function () use ($userQvstParameter) {
					return $this->secure_endpoint_with_parameter($userQvstParameter);
				}
			)
		);

		// Route pour récupérer le fichier CSV d'une campagne
        register_rest_route(
            $endpoint_namespace,
            '/qvst/campaigns/csv',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [get_csv_file_campaign::class, 'apiGetCsvFileCampaign'],
                'permission_callback' => function () use ($userQvstParameter) {
                    return $this->secure_endpoint_with_parameter($userQvstParameter);
                }
            )
        );

		// === Agenda ===
		$id_patern = '(?P<id>[\d]+)';
		$events_types_endpoint = '/agenda/events-types/';
		$events_types_endpoint_with_id = $events_types_endpoint.$id_patern;
		$events_endpoint = '/agenda/events/';
		$events_endpoint_with_id = $events_endpoint.$id_patern;
		$birthday_endpoint = '/agenda/birthday/';
		$birthday_endpoint_with_id = $birthday_endpoint.$id_patern;

		// Events types
		register_rest_route(
			$endpoint_namespace,
			$events_types_endpoint_with_id,
			array(
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [get_events_types_by_id::class, 'getEventsTypesById'],
				'permission_callback' => function () use ($userAgenda) {
					return $this->secure_endpoint_with_parameter($userAgenda);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			$events_types_endpoint,
			array(
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [get_all_events_types::class, 'getAllEventsTypes'],
				'permission_callback' => function () use ($userAgenda) {
					return $this->secure_endpoint_with_parameter($userAgenda);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			$events_types_endpoint,
			array(
				'methods' => \WP_REST_Server::CREATABLE,
				'callback' => [post_events_types::class, 'postEventsTypes'],
				'permission_callback' => function () use ($adminAgenda) {
					return $this->secure_endpoint_with_parameter($adminAgenda);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			$events_types_endpoint_with_id,
			array(
				'methods' => \WP_REST_Server::EDITABLE,
				'callback' => [put_events_types::class, 'putEventsTypes'],
				'permission_callback' => function () use ($adminAgenda) {
					return $this->secure_endpoint_with_parameter($adminAgenda);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			$events_types_endpoint_with_id,
			array(
				'methods' => \WP_REST_Server::DELETABLE,
				'callback' => [delete_events_types::class, 'deleteEventsTypes'],
				'permission_callback' => function () use ($adminAgenda) {
					return $this->secure_endpoint_with_parameter($adminAgenda);
				}
			)
		);

		// Events
		register_rest_route(
			$endpoint_namespace,
			$events_endpoint,
			array(
				'methods' => \WP_REST_Server::CREATABLE,
				'callback' => [post_events::class, 'postEvents'],
				'permission_callback' => function () use ($adminAgenda) {
					return $this->secure_endpoint_with_parameter($adminAgenda);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			$events_endpoint,
			array(
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [get_all_events::class, 'getAllEvents'],
				'permission_callback' => function () use ($userAgenda) {
					return $this->secure_endpoint_with_parameter($userAgenda);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			$events_endpoint_with_id,
			array(
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [get_events_by_id::class, 'getEventsById'],
				'permission_callback' => function () use ($userAgenda) {
					return $this->secure_endpoint_with_parameter($userAgenda);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			$events_endpoint_with_id,
			array(
				'methods' => \WP_REST_Server::DELETABLE,
				'callback' => [delete_events::class, 'deleteEvents'],
				'permission_callback' => function () use ($adminAgenda) {
					return $this->secure_endpoint_with_parameter($adminAgenda);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			$events_endpoint_with_id,
			array(
				'methods' => \WP_REST_Server::EDITABLE,
				'callback' => [put_events::class, 'putEvents'],
				'permission_callback' => function () use ($adminAgenda) {
					return $this->secure_endpoint_with_parameter($adminAgenda);
				}
			)
		);

		// Birthday
		register_rest_route(
			$endpoint_namespace,
			$birthday_endpoint,
			array(
				'methods' => \WP_REST_Server::CREATABLE,
				'callback' => [post_birthday::class, 'postBirthday'],
				'permission_callback' => function () use ($adminAgenda) {
					return $this->secure_endpoint_with_parameter($adminAgenda);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			$birthday_endpoint,
			array(
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [get_all_birthday::class, 'getAllBirthdays'],
				'permission_callback' => function () use ($userAgenda) {
					return $this->secure_endpoint_with_parameter($userAgenda);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			$birthday_endpoint_with_id,
			array(
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [get_birthday_by_id::class, 'getBirthdayById'],
				'permission_callback' => function () use ($userAgenda) {
					return $this->secure_endpoint_with_parameter($userAgenda);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			$birthday_endpoint_with_id,
			array(
				'methods' => \WP_REST_Server::DELETABLE,
				'callback' => [delete_birthday::class, 'deleteBirthday'],
				'permission_callback' => function () use ($adminAgenda) {
					return $this->secure_endpoint_with_parameter($adminAgenda);
				}
			)
		);
		register_rest_route(
			$endpoint_namespace,
			$birthday_endpoint_with_id,
			array(
				'methods' => \WP_REST_Server::EDITABLE,
				'callback' => [put_birthday::class, 'putBirthday'],
				'permission_callback' => function () use ($adminAgenda) {
					return $this->secure_endpoint_with_parameter($adminAgenda);
				}
			)
		);

		// === Storage ===
		// Route pour uploader une image
		register_rest_route(
			$endpoint_namespace,
			'/image-storage',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => [post_image::class, 'apiPostImage'],
				'permission_callback' => function () use ($adminImageParameter) {
					return $this->secure_endpoint_with_parameter($adminImageParameter);
				}
		));

		// Route pour récupérer une image par son dossier et son nom de fichier
		register_rest_route(
			$endpoint_namespace,
			'/image-storage/(?P<folder>[^/]+)/(?P<filename>[^/]+)',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => [get_image::class, 'apiGetImage'],
				'permission_callback' => function () use ($userImageParameter) {
					return $this->secure_endpoint_with_parameter($userImageParameter);
				}
			)
		);
		// Route pour récupérer toutes les dossiers ou les images d'un dossier
		register_rest_route(
			$endpoint_namespace,
			'/image-storage',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => [get_all_folders_or_images_by_folder::class, 'apiGetAllFoldersOrImagesByFolder'],
				'permission_callback' => function () use ($userImageParameter) {
					return $this->secure_endpoint_with_parameter($userImageParameter);
				}
			)
		);

		// Route pour supprimer une image par son ID
		register_rest_route(
			$endpoint_namespace,
			'/image-storage/(?P<id>[\d]+)',
			array(
				'methods' => WP_REST_Server::DELETABLE,
				'callback' => [delete_image::class, 'apiDeleteImage'],
				'permission_callback' => function () use ($adminImageParameter) {
					return $this->secure_endpoint_with_parameter($adminImageParameter);
				}
			)
		);

		// === IDEA BOX ===
		// Route pour soumettre une idée
		register_rest_route(
			$endpoint_namespace,
			'/ideas',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => [post_idea::class, 'apiPostIdea'],
				'permission_callback' => function () use ($userOfIdeaBoxParameter) {
					return $this->secure_endpoint_with_parameter($userOfIdeaBoxParameter);
				}
			)
		);

		// Route pour récupérer toutes les idées
		register_rest_route(
			$endpoint_namespace,
			'/ideas',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => [get_all_ideas::class, 'apiGetAllIdeas'],
				'permission_callback' => function () use ($adminOfIdeaBoxParameter) {
					return $this->secure_endpoint_with_parameter($adminOfIdeaBoxParameter);
				}
			)
		);

		// Route pour récupérer une idée par son ID
		register_rest_route(
			$endpoint_namespace,
			'/ideas/(?P<id>[\d]+)',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => [get_idea_by_id::class, 'apiGetIdeaById'],
				'permission_callback' => function () use ($adminOfIdeaBoxParameter) {
					return $this->secure_endpoint_with_parameter($adminOfIdeaBoxParameter);
				}
			)
		);

		// Route pour supprimer une idée par son ID
		register_rest_route(
			$endpoint_namespace,
			'/ideas/(?P<id>[\d]+)',
			array(
				'methods' => WP_REST_Server::DELETABLE,
				'callback' => [delete_idea_by_id::class, 'deleteIdeaById'],
				'permission_callback' => function () use ($adminOfIdeaBoxParameter) {
					return $this->secure_endpoint_with_parameter($adminOfIdeaBoxParameter);
				}
			)
		);

		// Route pour mettre à jour le status d'une idée
		register_rest_route(
			$endpoint_namespace,
			'/ideas/(?P<id>[\d]+)/status',
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => [put_idea_status::class, 'apiPutIdeaStatus'],
				'permission_callback' => function () use ($adminOfIdeaBoxParameter) {
					return $this->secure_endpoint_with_parameter($adminOfIdeaBoxParameter);
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