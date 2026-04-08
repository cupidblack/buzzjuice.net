<?php
require MG_myCRED_GITHUB_ADMIN_HOOKS_DIR . 'mycred-hook-approved-pull-request.php';
class Mycred_Github_Admin {

	private static  $instance = null;

	private function __construct() {

	  add_filter('mycred_setup_hooks', array( $this, 'mg_register_approved_pull_request_hook' ), 10, 2);
	  add_action('mycred_load_hooks', 'mg_mycred_load_approved_pull_request_hook', 95);

	  add_action('admin_menu', array( $this, 'mg_extra_setting_info_menu_github' ));

	  update_option('github_hook_url', admin_url('admin-ajax.php') . '?action=pull_request_hook_action');

	  add_action('wp_ajax_mg_refresh_repositories_action', array( $this, 'mg_update_user_repositories' ));
	  add_action('wp_ajax_mg_disconnect_action', array( $this, 'mg_disconnect_action' ));


	  add_action('admin_enqueue_scripts', array( $this, 'mg_admin_assets' ));
	}
	public  function mg_admin_assets() {
	  wp_enqueue_style('Mycred_Github_Admin_style', MG_myCRED_GITHUB_STYLE_URL . 'main.css', array(), '1.0');
	  wp_enqueue_style('Mycred_Github_Admin_select2_style', MG_myCRED_GITHUB_STYLE_URL . 'select2.min.css' , array(), '1.0');

	  wp_enqueue_script('Mycred_Github_Admin_select2_script', MG_myCRED_GITHUB_JS_URL . 'select2.min.js' , array(), '1.0');
	  wp_enqueue_script('Mycred_Github_Admin_script', MG_myCRED_GITHUB_JS_URL . 'github_form_actions_javascript.js', array(), '1.0');
	}

	public  function mg_register_approved_pull_request_hook( $installed ) {
	  $installed['approved_pull_request_hook'] = array(
		  'title'       => esc_html(__('Points for approved pull request', 'mycred-toolkit')),
		  'description' => esc_html(__('Award Points when Pull Request Approved', 'mycred-toolkit')),
		  'callback'      => array( 'MG_myCRED_Hook_Approved_Pull_Request' )
	  );
	  return $installed;
	}

  /**
   * Add Submenu to myCred menu for github settings page
   */
	public function mg_extra_setting_info_menu_github() {
	  $page_title = esc_html(__('Github Hooks', 'mycred-toolkit'));
	  $menu_title = esc_html(__('Github Hooks', 'mycred-toolkit'));
	  $capability = 'manage_options';
	  $menu_slug  = 'github_hooks';
	  $function   = array( $this, 'github_account_info' );
	  $position   = 4;
	
		if ( function_exists( 'mycred_add_main_submenu' ) ) {
	  
			mycred_add_main_submenu( 
			$page_title, 
			$menu_title, 
			$capability, 
			$menu_slug, 
			$function, 
			$position
			);

		} else {

			add_submenu_page(
			MYCRED_SLUG,
			$page_title, 
			$menu_title, 
			$capability, 
			$menu_slug, 
			$function, 
			$position
			);

		}
	
	  add_action('admin_init', array( $this, 'mg_update_github_account_info' ));
	}

  /**
   * Register github setting
   */
	public function mg_update_github_account_info() {
	  register_setting('github-account-info-setting', 'github_account_info');
	  register_setting('github-account-info-setting', 'github_account_selected_repositories');

	  add_action('add_option_github_account_info', array( $this, 'mg_get_user_repositories_when_token_valid' ), 12, 2);
	  add_action('update_option_github_account_info', array( $this, 'mg_get_user_repositories_when_token_valid' ), 12, 2);
	  add_action('update_option_github_account_selected_repositories', array( $this, 'mg_handle_update_repositories_hooks' ), 10, 2);
	  add_action('add_option_github_account_selected_repositories', array( $this, 'mg_register_repository_hook' ), 12, 2);
	  add_action('update_option_github_repositories_hooks', array( $this, 'mg_repositories_hooks_updated' ), 10, 2);
	}

  /**
   * Render github setting form
   */
	public function github_account_info() {
	  $this->mg_render_template('github_settings_form.php');
	}

  /**
   * Request user repositories with his token and save repositories in github_repositories option
   */
	public function mg_get_user_repositories_when_token_valid() {
	  $client = new GitHubClient();
	  $client->setAuthType($client::GITHUB_AUTH_TYPE_OAUTH);
	  $client->setOauthToken(get_option('github_account_info')['token']);
	  $repositories = array();
		try {
		  $repositoriesList = $client->repos->listUserRepositories(get_option('github_account_info')['username']);
			foreach ($repositoriesList as $key => $value) {
				$repositories[ $key ] = $value->getName();
			}
		  update_option('github_repositories', $repositories);
		  MG_myCRED_Toolkit_Github::mg_add_flash_notice(esc_html(__('Repositories fetched successfully', 'mycred-toolkit')), 'success');
		} catch (Exception $e) {
		  $messages = explode('||', $e->getMessage());
		  $code = $messages[0];
		  $content = explode('%%', $messages[1])[0];
		  MG_myCRED_Toolkit_Github::mg_logger(sprintf('Exception during get user repositories: with message %s ', $content));
		  $message = MG_myCRED_Toolkit_Github::mg_errors_messages_mapper(__FUNCTION__, $code);
		  MG_myCRED_Toolkit_Github::mg_add_flash_notice($message, 'error');
		}
	}

  /**
   * Update user repositories and handle any changes in selected repositories and created hooks
   */
	public function mg_update_user_repositories() {

	  $this->mg_get_user_repositories_when_token_valid();
	  $newRepositories = get_option('github_repositories');
	  $hooks = get_option('github_repositories_hooks');
	  $hooks = array_filter($hooks, function ( $hook ) use ( $newRepositories ) {
			return in_array($hook['repository'], array_values($newRepositories));
		});
	  $selectedRepositories = get_option('github_account_selected_repositories');
	  $selectedRepositories = array_filter($selectedRepositories, function ( $repository ) use ( $newRepositories ) {
			return in_array($repository, array_values($newRepositories));
		});
	  update_option('github_repositories_hooks', $hooks);
	  update_option('github_account_selected_repositories', $selectedRepositories);
	}

  /**
   * Create empty github_repositories_hooks option after to store created hooks 
   */
	public function mg_register_repository_hook() {
	  update_option('github_repositories_hooks', array());
	}

  /**
   * Handle changes in selected_repositories option to delete hooks for unselected repositories and add hooks to new selected repositories
   * @param $old_value old value of github_account_selected_repositories
   * @param $value new value of github_account_selected_repositories
   */
	public function mg_handle_update_repositories_hooks( $old_value, $value ) {
	  $old_value = ( is_array($old_value) ) ? $old_value : array();
	  $value = ( is_array($value) ) ? $value : array();
	  $newRepositories = array_diff($value, $old_value);

	  $oldRepositories = array_diff($old_value, $value);
		if (count($oldRepositories)) {
		  $this->mg_delete_repositories_hooks($this->mg_get_hooks_to_delete($oldRepositories));
		}
		if (count($newRepositories)) {
		  $this->mg_add_repositories_hooks($newRepositories);
		}
	
	  $successMessage = esc_html(__('Hooks updated successfully', 'mycred-toolkit'));
	  MG_myCRED_Toolkit_Github::mg_add_flash_notice($successMessage , 'success');
	}

  /**
   * Map array of repositories to array of hooks 
   * @param $oldRepositories one dimensional array of repositories names that will be deleted
   * @return two dimensional array of repositories hooks that will be deleted ,array format [["repository"=>"","hooks_id"=>""]]
   */
	public function mg_get_hooks_to_delete( $oldRepositories ) {
	  $oldHooks = get_option('github_repositories_hooks');
	  $oldHooks = array_filter($oldHooks, function ( $hook ) use ( $oldRepositories ) {
			return in_array($hook['repository'], $oldRepositories);
		});
	  return $oldHooks;
	}

  /**
   * Create repository hooks and save hooks in github_repositories_hooks option as array with format [["repository"=>"","hooks_id"=>""]]
   * @param $repositories one dimensional array of repositories to create hooks
   * @param $hooks one dimensional array of hooks names
   */
	public function mg_add_repositories_hooks( $repositories, $hooks = array( 'pull_request' ) ) {
	  $client = new GitHubClient();
	  $client->setAuthType($client::GITHUB_AUTH_TYPE_OAUTH);
	  $client->setOauthToken(get_option('github_account_info')['token']);
	  $existedHooks = get_option('github_repositories_hooks');
		if ($repositories) {

			foreach ($repositories as $repo) {
				try {
				  $resp = $client->repos->hooks->createHook(get_option('github_account_info')['username'], $repo, $hooks, get_option('github_hook_url'));
				  $existedHooks[] = array(
					  'hooks_id' => json_decode($resp)->id,
					  'repository' => $repo
				  );
				} catch (Exception $e) {
				  $selectedRepositories = get_option('github_account_selected_repositories');
				  $selectedRepositories = array_filter($selectedRepositories, function ( $repository ) use ( $repo ) {
						return $repository != $repo;
					});
				  update_option('github_account_selected_repositories', $selectedRepositories);
				  $messages = explode('||', $e->getMessage());
				  $code = $messages[0];
				  $content = explode('%%', $messages[1])[0];
				  MG_myCRED_Toolkit_Github::mg_logger(sprintf('Exception during create hook on %s repository : with message %s ', $repo, $content));
				  $message = MG_myCRED_Toolkit_Github::mg_errors_messages_mapper(__FUNCTION__, $code, $content, $repo);
				  MG_myCRED_Toolkit_Github::mg_add_flash_notice($message, 'error');
				}
			}
		  update_option('github_repositories_hooks', $existedHooks);
		}
	}

  /**
   * Delete repositories hooks and update github_repositories_hooks option
   * @param two dimensional array of repositories hooks that will be deleted ,array format [["repository"=>"","hooks_id"=>""]]
   */
	public function mg_delete_repositories_hooks( $repositoriesHooks ) {
	  $client = new GitHubClient();
	  $client->setAuthType($client::GITHUB_AUTH_TYPE_OAUTH);
	  $client->setOauthToken(get_option('github_account_info')['token']);
	  $existedHooks = get_option('github_repositories_hooks');
		if (count($repositoriesHooks)) {
			foreach ($repositoriesHooks as $hook) {
				try {
				  $client->repos->hooks->deleteHook(get_option('github_account_info')['username'], $hook['repository'], $hook['hooks_id']);
				  $existedHooks = array_filter($existedHooks, function ( $existedHook ) use ( $hook ) {
						return $existedHook['repository'] !== $hook['repository'];
					});
				} catch (Exception $e) {
				  $messages = explode('||', $e->getMessage());
				  $code = $messages[0];
				  $content = explode('%%', $messages[1])[0];
				  MG_myCRED_Toolkit_Github::mg_logger(sprintf('Exception during delete hook with id= %s on %s repository : with message %s ', $hook['hooks_id'], $hook['repository'], $content));
					if ($code == 401) {
					$message = MG_myCRED_Toolkit_Github::mg_errors_messages_mapper(__FUNCTION__, $e->getMessage());
					MG_myCRED_Toolkit_Github::mg_add_flash_notice($message, 'error');
					}
				}
			}
		  update_option('github_repositories_hooks', $existedHooks);
		}
	}
	public function mg_repositories_hooks_updated() {
	}
  /**
   * Reset all options And delete created hooks
   */
	public function mg_disconnect_action() {
	  $this->mg_delete_repositories_hooks(get_option('github_repositories_hooks'));
	  $githubOptions = array(
		  'github_account_info',
		  'github_repositories_hooks',
		  'github_account_selected_repositories',
		  'github_repositories'
	  );
		foreach ($githubOptions as $option) {
		  delete_option($option);
		}
	  $successMessage = esc_html(__('Disconnected successfully, all hooks deleted', 'mycred-toolkit'));
	  MG_myCRED_Toolkit_Github::mg_add_flash_notice($successMessage , 'success');
	}

	public function refresh_repositories_action_javascript() {
	  $this->mg_render_template('refresh_repositories_action_javascript.php');
	}


  /**
   *  Render template from admin templetes directory
   * @param $template_name with extension
   * @return include for the file
   */
	public function mg_render_template( $template_name ) {
	  return include MG_myCRED_GITHUB_ADMIN_TEMPLATES_DIR . $template_name;
	}

  /**
   *  create object of Mycred_Github_Admin if not exist
   * @return ogject of Mycred_Github_Admin
   */
	public static function getInstance() {
		if (!self::$instance instanceof self) {
		  self::$instance = new self();
		}
	  return self::$instance;
	}
}
Mycred_Github_Admin::getInstance();
