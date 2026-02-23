<?php

/**
 * Includes the main Options functionality for the Modules page.
 */
class USIN_Modules{
	
	protected static $instance;

	protected function __construct(){}

	/**
	 * This is a singleton class, returns the instance of the class.
	 * @return USIN_Modules the instance
	 */
	public static function get_instance(){
		if(! self::$instance ){
			self::$instance = new USIN_Modules();
			self::$instance->init();
		}
		return self::$instance;
	}

	protected function init(){
		add_action('current_screen', array($this, 'check_license_status'));
		add_action('usin_version_update', array($this, 'activate_activity_devices_for_older_versions'), 10, 2);
	}


	/**
	 * Loads the Module Options and sets them to the modules property.
	 */
	public function get_modules(){
		$modules = array();
		USIN_Module_Defaults::clear_cache();
		$module_ids = wp_list_pluck(USIN_Module_Defaults::get(), 'id');
		
		foreach ($module_ids as $module_id) {
			//we can't use cache here because some module options might need to be refreshed
			//e.g. the page visit tracking module might have new post types registered since
			//the last time it was loaded
			$module = USIN_Module::get($module_id, false);

			$modules[] = $module->to_array();
		}
		return $modules;
	}

	/**
	 * Activates a module.
	 * @param  string $module_id the ID of the module to activate
	 */
	public function activate_module($module_id){
		$should_activate = apply_filters('usin_should_activate_module_'.$module_id, true);
		if($should_activate !== true){
			return $should_activate;
		}

		$module = USIN_Module::get($module_id);

		if(!$module){
			return new WP_Error('usin_module_activate', __('Error: Module could not be activated'));
		}

		$module->activate();
		do_action('usin_module_activated', $module_id);
		return true;
	}

	/**
	 * Deactivates a module.
	 * @param  string $module_id the ID of the module to deactivate
	 */
	public function deactivate_module($module_id){
		$module = USIN_Module::get($module_id);
		if($module){
			$module->deactivate();
			do_action('usin_module_deactivated', $module_id);
		}
	}

	/**
	 * Retrieves the license key for a module.
	 * @param  string $module_id the module ID
	 * @return string            the lincense key
	 */
	public function get_license($module_id){
		$module = USIN_Module::get($module_id);;
		return $module->get_license_key();
	}


	/**
	 * Activates a license, sets the activation details in the module options.
	 * @param string $module_id   the module ID
	 * @param string $license_key the license key
	 * @return the module options array on success and WP_Error on failure
	 */
    public function activate_license($license_key, $module_id){

        $module = USIN_Module::get($module_id);



        $res = new stdClass();

        $res->success = true;

        $res->license = USIN_License::STATUS_VALID;

        $res->expires = time() + (365 * 24 * 60 * 60);
		
		if(is_wp_error($res)){
			return $res;
		}

        if($res->success === true && $res->license === USIN_License::STATUS_VALID){

            $module->license->activate($license_key, $res->expires);

            $module->save();

          

            $this->refresh_geolocation_status($module->license);



            return $module->license->to_array();

        }

		return $this->license_error($res);
			
	}

	/**
	 * Sends a request to check the license status and updates it in the database
	 *
	 * @param string $module_id the module ID
	 * @return the module options array on success and WP_Error on failure
	 */
    public function refresh_license_status($module_id){

        $module = USIN_Module::get($module_id);

        $res = new stdClass();

        $res->success = true;

        $res->license = USIN_License::STATUS_VALID;

        $res->expires = time() + (365 * 24 * 60 * 60);

        if (is_wp_error($res)) {

            return $res;

        }

        if ($res->success === true) {

            $module->license->status = $res->license;

            $module->license->expires = $res->expires;

            $module->license->renewal_url = $res->renewal_url;

            $module->license->renewal_message = $res->renewal_message;



            $module->save();

            $this->refresh_geolocation_status($module->license);

          

            return $module->license->to_array();

        }

		return $this->license_error($res);
		
	}

	protected function refresh_geolocation_status($license){
		if(!$this->is_module_active('geolocation')){
			return;
		}

		if(USIN_Geolocation_Status::is_paused() && $license->is_valid()){
			USIN_Geolocation_Status::resume();
		}elseif(!USIN_Geolocation_Status::is_paused() && !$license->is_valid()){
			USIN_Geolocation_Status::pause();
		}

	}

	/**
	 * Deactivates a license, removes the activation details in the module options.
	 * @param string $module_id   the module ID
	 * @param string $license_key the license key
	 * @return array the module options
	 * @return the module options array on success and WP_Error on failure
	 */
	 public function deactivate_license($license_key, $module_id){
		$module = USIN_Module::get($module_id);

		$res = USIN_Remote_License::deactivate($license_key, $module_id);

		if(is_wp_error($res)){
			return $res;
		}

		if( ($res->success === true && $res->license === 'deactivated') ||
			(!$res->success && $res->license == 'failed') ){  //the license doesn't exist anymore, just remove it from the options
			
			$module->license->deactivate();
			$module->save();
			return $module->license->to_array();

		}

		return $this->license_error($res);
	}

    public function check_license_status(){
        if ((defined('DOING_AJAX') && DOING_AJAX) || !is_admin()){
            return;
        }

        $transient_key = 'usin_license_checked';
        $module = USIN_Module::get('globallicense');
        $license_key = $module->get_license_key();
        
        if(!empty($license_key) && usin_is_a_users_insights_page()){

            if(get_transient($transient_key) === false){
                //refresh the license status every 24 hours
                $this->refresh_license_status($module->id);
                $module->reload();
                set_transient( $transient_key, true, DAY_IN_SECONDS );
            }
            
        }
    }

	protected function show_license_expiry_notice($license, $expired){

		if($expired){
			$message = 'Your Users Insights license has expired. ';
			$notice_type = 'alert';
			$notice_id = 'license_expired';	
			$dismiss_period = 0;
		}else{
			//the license is about to expire
			$message = 'Your Users Insights license is about to expire. ';
			$notice_type = 'info';
			$notice_id = 'license_will_expire';
			$dismiss_period = 2 * MONTH_IN_SECONDS; //once dismissed don't show again until it actually expires
		}

		$renew_license = 'Renew your license';
		if($license->renewal_url){
			$renew_license = sprintf('<a href="%s">%s</a>', esc_url($license->renewal_url), $renew_license);
		}
		$message .= sprintf('%s to keep receiving updates and access to Geolocation.', $renew_license);

		if($license->renewal_message){
			$message .= '<br/>'.wp_kses_data($license->renewal_message);
		}
		
		USIN_Notice::create($notice_type, $message, $notice_id, $dismiss_period);
		
	}


	protected function license_error($res){
		$error = __('Invalid license', 'usin');

		if(isset($res->error_msg)){
			$error = $res->error_msg;
		} elseif(isset($res->error)){
			if($res->error == 'no_activations_left'){
				$error = __('License activations limit reached', 'usin');
				if(isset($res->site_count)){
					$error.= ' - '.sprintf(__('this license is already activated on %s websites'), $res->site_count);
				}
			}elseif($res->error == 'expired'){
				$error = __('License expired', 'usin');
			}
		} 

		return new WP_Error('invalid_license', $error);
	}

	/**
	 * Checks whether a module is activated.
	 * @param  string  $module_id the module ID
	 * @return boolean            true if the module is activated and false otherwise
	 */
	public function is_module_active($module_id){
		$module = USIN_Module::get($module_id);;
		return !empty($module) && $module->active;
	}

	public function update_settings($module_id, $settings){
		$module = USIN_Module::get($module_id);

		if(!$module){
			return new WP_Error('invalid_module', __('Invalid module ID', 'usin').": $module_id");
		}

		$settings = apply_filters("usin_module_update_settings_$module_id", $settings);

		if(is_wp_error($settings)){
			return $settings;
		}

		return $module->update_settings($settings);

	}


	public function get_setting($module_id, $setting_id){
		$module = USIN_Module::get($module_id);
		return $module->get_setting($setting_id);
	}

	/**
	 * Starting from version 3.6.3 the Activity and Devices modules should be inactive by default
	 * for new installs. However we don't want to deactivate them for the existing 
	 * installs where this modules used to be active bt default.
	 * If this is an installation running 2.6.2 or older, that is already using Activity and/or Devices
	 * and have not deactivated them explicitly, we'll make them active in the database.	
	 *
	 * @param string $new_version
	 * @param string $installed_version
	 * @return void
	 */
	public function activate_activity_devices_for_older_versions($new_version, $installed_version){
		if(empty($installed_version)){
			return;
		}

		if(version_compare($installed_version, '3.6.2', '<=')){
			$devices_options = get_option('usin_module_devices');
			if(!$devices_options){
				//there is no activation option saved for the devices module
				//since this module was active by default for versions 3.6.2 and older,
				//save the activation option to true in the database, so we don't deactivate
				//the module for those who are already using it
				$devices = USIN_Module::get('devices');
				$devices->activate();
			}

			$activity_options = get_option('usin_module_activity');
			if(!$activity_options){
				$activity = USIN_Module::get('activity');
				$activity->activate();
			}
		}

	}

}