<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'myCRED_Badgr_API' ) ) :
	class myCRED_Badgr_API {

		/**
		 * myCRED_Badgr_API constructor.
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct() {
		}

		/**
		 * Badgr Request Access Token
		 * @param $username
		 * @param $user_password
		 * @return mixed
		 * @since 1.0
		 * @version 1.0
		 */
		public static function request_access_token( $username, $user_password ) {
			$url = 'https://api.badgr.io/o/token';

			$body = array(
				'username'  =>  $username,
				'password'  =>  $user_password
			);

			$response = wp_remote_post( $url, array(
				'method'      => 'POST',
				'body'        => $body,
			)
			);

			return $response;
		}

		/**
		 * Badgr Authenticates User
		 * @param $token
		 * @param string $refresh_token
		 * @return mixed
		 * @since 1.0
		 * @version 1.0
		 */
		public static function authenticate( $token, $refresh_token = '' ) {
		
			$url = 'https://api.badgr.io/v2/users/self';

			$headers = array(
				'Authorization' => "Bearer $token",
			);

			$response = wp_remote_post( $url, array(
				'method'    =>  'GET',
				'headers'   =>  $headers,
			)
			);

			//Refresh access token if expired
			if ( $response['response']['code'] == 401 ) {
			return self::refresh_access_token( $refresh_token );
			}

			return $response;
		}

		/**
		 * Badgr Refresh Access Token
		 * @param $refresh_token
		 * @return mixed
		 * @since 1.0
		 * @version 1.0
		 */
		public static function refresh_access_token( $refresh_token ) {
			$url = 'https://api.badgr.io/o/token';

			$body = array(
				'grant_type'    => 'refresh_token',
				'refresh_token' =>  $refresh_token
			);

			$headers = array(
				'Content-Type' => 'application/x-www-form-urlencoded',
			);

			$response = wp_remote_post( $url, array(
				'method'    =>  'POST',
				'headers'   =>  $headers,
				'body'      =>  $body,
			),
			);

			return $response;
		}

		/**
		 * Badgr Creates Issuer
		 * @param $name
		 * @param $description
		 * @param $issuer_profile_url
		 * @param string $image
		 * @param $email
		 * @param $access_token
		 * @return mixed
		 * @since 1.0
		 * @version 1.0
		 */
		public static function create_issuer_profile( $name, $description, $issuer_profile_url, $image = '', $email, $access_token ) {
			$url = 'https://api.badgr.io/v2/issuers';

			$body = array(
				'name'          =>  $name,
				'description '  =>  $description,
				'url'           =>  $issuer_profile_url,
				'email'         =>  $email
			);

			$headers = array(
				'Accept'        =>  'application/json',
				'Content-Type'  =>  'application/json',
				'Authorization' => "Bearer $access_token"
			);

			$response = wp_remote_post( $url, array(
				'method'    =>  'POST',
				'headers'   =>  $headers,
				'body'      =>  json_encode( $body ),
			),
			);

			return $response;
		}

		/**
		 * Badgr Creates Badge
		 * @param $name
		 * @param $description
		 * @param $image
		 * @param $criteria_url
		 * @param $badge_id
		 * @param $issuer_entity_id
		 * @param $access_token
		 * @param string $refresh_token
		 * @return mixed
		 * @since 1.0
		 * @version 1.0
		 */
		public static function create_badge( $name, $description, $image, $criteria_url, $badge_id, $issuer_entity_id, $access_token, $refresh_token = '' ) {
			$url = "https://api.badgr.io/v2/issuers/$issuer_entity_id/badgeclasses";

			$headers = array(
				'Accept'        =>  'application/json',
				'Content-Type'  =>  'application/json',
				'Authorization' => "Bearer $access_token"
			);

			$body = array(
				'name'              =>  $name,
				'description'       =>  $description,
				'image'             =>  $image,
				'criteriaUrl'       =>  $criteria_url,
				'issuerOpenBadgeId' =>  $badge_id
			);

			$response = wp_remote_post( $url, array(
				'method'    =>  'POST',
				'headers'   =>  $headers,
				'body'      =>  json_encode( $body ),
			)
			);

			return $response;
		}

		/**
		 * Issues Assertion/ Badge to Recipient
		 * @param $badge_entity_id
		 * @param $admin_entity_id
		 * @param $issuer_entity_id
		 * @param $admin_access_token
		 * @param $recipient_email
		 * @param $issued_on
		 * @return mixed
		 * @since 1.0
		 * @version 1.0
		 */
		public static function issue_assertion( $badge_entity_id, $admin_entity_id, $issuer_entity_id, $admin_access_token, $recipient_email, $issued_on ) {
			$url = "https://api.badgr.io/v2/badgeclasses/$badge_entity_id/assertions";

			$headers = array(
				'Accept'        =>  'application/json',
				'Content-Type'  =>  'application/json',
				'Authorization' =>  "Bearer $admin_access_token"
			);

			$body = array(
				'entityType'            =>  'Assertion',
				'entityId'              =>  $badge_entity_id,
				'openBadgeId'           =>  "https://api.badgr.io/public/badges/$badge_entity_id",
				'recipient'             => array(
					'identity'              =>  $recipient_email,
					'hashed'                =>  true,
					'type'                  =>  'email',
				),
				'issuedOn'              =>  $issued_on
			);

			$response = wp_remote_post( $url, array(
				'method'    =>  'POST',
				'headers'   =>  $headers,
				'body'      =>  json_encode( $body ),
			),
			);

			return $response;
		}
	}
endif;
