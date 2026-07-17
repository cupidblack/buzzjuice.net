<?php
if (!defined('ABSPATH')) {
exit;
}

function mg_mycred_load_approved_pull_request_hook() {

	if (!class_exists('MG_myCRED_Hook_Approved_Pull_Request')) :
		class MG_myCRED_Hook_Approved_Pull_Request extends myCRED_Hook {
	  
			public function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

				parent::__construct(array(
					'id'       => 'approved_pull_request_hook',
					'defaults' => array(
						'creds' => 1,
						'log'   => '%plural% for Approved pull request',
						'limit' => '0/x'
					)
				), $hook_prefs, $type);
			}
		  /**
		   * Run
		   * @since 1.5
		   * @version 1.0
		   */
			public function run() {

			  add_action('wp_ajax_nopriv_pull_request_hook_action', array( $this, 'pull_request_hook_action' ));
			  add_action('wp_ajax_pull_request_hook_action', array( $this, 'pull_request_hook_action' ));
			}
		  /**
		   * Sanitise Preferences
		   * @since 1.6
		   * @version 1.0
		   */
			public function pull_request_hook_action() {
			  $payload=json_decode(json_decode('"' . sanitize_text_field($_REQUEST['payload']) . '"'));
			  $pull_request=$payload->pull_request;
				if ($pull_request->user) {
				  $user = get_users(array(
					  'meta_key' => 'github_user_name',
					  'meta_value' => $pull_request->user->login,
					  'number' => 1,
					  'count_total' => false
				  ))[0];
					if (!$this->over_hook_limit('', 'approved_pull_request_hook', $user->ID) && $this->mg_pull_request_is_approved($payload)) {
							$this->core->add_creds(
						'approved_pull_request_hook',
						$user->ID,
						$this->prefs['creds'],
						$this->prefs['log'],
						$user->ID,
						'',
						$this->mycred_type
							);
					}
				}
			wp_die();
			}
			public function mg_pull_request_is_approved( $payload ) {
			  $pull_request=$payload->pull_request;
				if (( ( $payload->action ) && $payload->action == 'closed' ) && ( ( $pull_request->merged ) && $pull_request->merged == true )) {
				  return true;
				}
			  return false;
			}
			function sanitise_preferences( $data ) {


				if (isset($data['limit']) && isset($data['limit_by'])) {
				  $limit = sanitize_text_field($data['limit']);
					if ($limit == '') {
		$limit = 0;
					}
				  $data['limit'] = $limit . '/' . $data['limit_by'];
				  unset($data['limit_by']);
				}
		
			  return $data;
			}

			public function preferences() {
			  $prefs = $this->prefs;
			  include MG_myCRED_GITHUB_ADMIN_HOOKS_TEMPLATES_DIR . 'approved_pull_request_options_form.php';
			}
		}

  endif;
}
