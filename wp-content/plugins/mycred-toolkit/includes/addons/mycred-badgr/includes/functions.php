<?php
/**
 * User login and Authentication
 * @param $email
 * @param $password
 * @param bool $create_issuer
 * @since 1.0
 * @version 1.0
 */
function mycred_br_login_and_auth( $email, $password, $create_issuer = true ) {
	if ( empty( $email ) || empty( $password ) ) {
		echo 'Both fields are required';
		die;
	}

	$current_user = wp_get_current_user();

	$user_name = $current_user->user_login; 

	$site_url = get_site_url();

	//Access Token Request
	$response = myCRED_Badgr_API::request_access_token( $email, $password );

	if ( $response['response']['code'] == 200 ) {
		$result = array();
		$result['result'] = 'accessed';
		$result['access_token'] = json_decode( $response['body'] )->access_token;
		$result['refresh_token'] = json_decode( $response['body'] )->refresh_token;

		//Authenticating User
		$response = myCRED_Badgr_API::authenticate( $result['access_token'], $result['refresh_token'] );

		if ( $response['response']['code'] != 200 ) {
			echo json_encode( json_decode( $response['body'])->error_description ); 
			die;
		}

		//Authenticated now create issuer
		if ( $response['response']['code'] == 200 ) {
		}
		{
			$result['entity_id'] = json_decode( $response['body'] )->result[0]->entityId;

		if ( $create_issuer ) {
			$description = "$user_name is the issuer of $site_url";

			$response = myCRED_Badgr_API::create_issuer_profile( $user_name, $description, $site_url, '', $email, $result['access_token'] );

			//Issuer Created
			if ( $response['response']['code'] = 201 ) {
				$result['issuer_entity_id'] = json_decode( $response['body'] )->result[0]->entityId;
				echo json_encode( $result );
				die;
			}

			if ( $response['response']['code'] != 200 ) {
				echo json_encode( json_decode($response['body'])->error_description ); 
				die;
			}
		} else {
			echo json_encode( $result );
			die;
		}    
		}
	}

	if ( $response['response']['code'] != 200 ) {
		echo json_encode( json_decode($response['body'])->error_description ); 
		die;
	}
}
