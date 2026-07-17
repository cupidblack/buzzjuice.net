<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if (!class_exists('MG_Connect_Client_With_Github')) :
	class MG_Connect_Client_With_Github {
  
		private static  $instance = null;
	  public $authorizeURL      = MG_myCRED_GITHUB_AUTHORIZE_URL;
	  public $tokenURL          = MG_myCRED_GITHUB_TOKEN_URL;
	  public $apiURLBase        = MG_myCRED_GITHUB_API_URL_BASE;
	  public $clientID;
	  public $clientSecret;
	  public $redirectUri;
	  public $default_error_message;
	  public $errorMessage;
		private function __construct() {
		  $this->default_error_message = esc_html(__('Some things happened wrong please try later', 'mycred-toolkit'));
		  $this->redirectUri           = admin_url('admin-ajax.php') . '?action=mg_github_callback';
		  add_action('wp_ajax_mg_github_callback', array( $this, 'mg_github_callback' ));
		  add_shortcode('connect-with-github', array( $this, 'mg_shortcode_connect_with_github' ));
		}
	  /**
	   * 
	   */
		public function mg_set_app_settings() {
		  $this->clientID          = get_option('github_app_client_id');
		  $this->clientSecret      = get_option('github_app_client_secret');
		}
	  /**
	   * Construct connect with github shortcode
	   */
		public function mg_shortcode_connect_with_github() {
			if (get_option('github_app_client_id') && get_option('github_app_client_secret')) {
			  $buttonText = get_user_meta(get_current_user_id(), 'github_user_name', true) ? esc_html(__('Github Reconnect', 'mycred-toolkit')) : esc_html(__('Github Connect', 'mycred-toolkit'));
			  $helpMessage = get_user_meta(get_current_user_id(), 'github_user_name', true) ? esc_html(__('Click Reconnect if you update your github personal info', 'mycred-toolkit')) : '';
				if (isset($_SESSION['github_acount_connection_error'])) {
					$errorMessage = $_SESSION['github_acount_connection_error'];
					unset($_SESSION['github_acount_connection_error']);
				} else {
				  $errorMessage = '';
				}
			  return '<div>
               <a href="' . $this->mg_getAuthorizeURL() . '">' . $buttonText . '</a>
               <div>' . $helpMessage . '</div>
               <div>' . $errorMessage . '</div>
              </div> ';
			}
		  return '';
		}
	  /**
	   *  The callback function which will call by github to handle Request access token then request user data and save it 
	   */
		public function mg_github_callback() {
		  $get_code = sanitize_text_field($_GET['code']);
			if (isset($get_code)) {
				try {
					$token = $this->mg_getAccessToken($get_code);
					$user =  $this->mg_get_user_data($token);
					$this->mg_handle_save_user_data($user);
					wp_redirect(( get_option('github_connect_redirect_url') ) ? get_option('github_connect_redirect_url') : home_url());
					exit();
				} catch (Exception $e) {
				  MG_myCRED_Toolkit_Github::mg_logger($e->getMessage());
				  $_SESSION['github_acount_connection_error'] = $this->default_error_message;
				  wp_redirect(( get_option('github_connect_redirect_url') ) ? get_option('github_connect_redirect_url') : home_url());
				  exit();
				  wp_die();
				}
			} else {
			  MG_myCRED_Toolkit_Github::mg_logger(esc_html('The github callback URL does not contain code parameter '));
			  $_SESSION['github_acount_connection_error'] = $this->default_error_message;
			}
		  wp_die();
		}

	  /**
	   * Get the authorize URL
	   *
	   * @returns a string
	   */
		public function mg_getAuthorizeURL() {
		  $this->mg_set_app_settings();
		  return $this->authorizeURL . '?' . http_build_query(array(
			  'client_id' => $this->clientID,
			  'redirect_uri' => $this->redirectUri,
			  'scope' => 'user:email'
		  ));
		}

	  /**
	   * Exchange client_id , client_secret and code for an access token
	   */
		public function mg_getAccessToken( $oauth_code ) {
		  $this->mg_set_app_settings();
		  $response = $this->mg_apiRequest($this->tokenURL . '?' . http_build_query(array(
			  'client_id' => $this->clientID,
			  'client_secret' => $this->clientSecret,
			  'code' => $oauth_code
		  )));
			if ($response->access_token) {
			  return $response->access_token;
			} else {
			  throw new Exception(sprintf('Empty token for code= % token for user id= %', esc_attr( $oauth_code ), esc_attr( get_current_user_id() )  ));
			}
		}
	  /**
	   * REquest $user data from access token
	   */
		public function mg_get_user_data( $token ) {
		  $user = $this->mg_apiRequest($token);
			if (!empty($user)) {
			  return $user;
			} else {
			  throw new Exception(sprintf('Empty User for % token', esc_attr( $token ) ));
			}
		}
	  /**
	   * Handle save github username as metadata for current user
	   */
		public function mg_handle_save_user_data( $user ) {
			if ($user->login) {
			  update_user_meta(get_current_user_id(), 'github_user_name', $user->login);
			} else {
			  throw new Exception(sprintf('Empty Username Property for %', esc_attr( json_decode($user) ) ));
			}
		}

	  /**
	   * Make an API request
	   *
	   * @return API results
	   */
		public function mg_apiRequest( $access_token_url ) {
		  $apiURL = filter_var($access_token_url, FILTER_VALIDATE_URL) ? $access_token_url : $this->apiURLBase . 'user?access_token=' . $access_token_url;
		  $context  = stream_context_create(array(
			  'http' => array(
				  'user_agent' => 'myCred githup plugin',
				  'header' => 'Accept: application/json'
			  )
		  ));
		  $body = wp_remote_retrieve_body( wp_remote_get($apiURL) );
		  parse_str(urldecode($body), $result);
		  $response = json_encode($result, JSON_UNESCAPED_UNICODE);
		  return $response ? json_decode($response) : $response;
		}

		public static function getInstance() {
			if (!self::$instance instanceof self) {
			  self::$instance = new self();
			}
		  return self::$instance;
		}
	}
endif;
$instance = MG_Connect_Client_With_Github::getInstance();
