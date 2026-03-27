<?php

/**
 * License handler for Easy Digital Downloads
 *
 * This class should simplify the process of adding license information
 * to new EDD extensions.
 *
 * @version 4.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

if (!class_exists('WIDGETOPTS_License')) :

	/**
	 * WIDGETOPTS_License Class
	 */
	class WIDGETOPTS_License
	{
		private $file;
		private $license;
		private $item_name;
		private $item_id;
		private $item_shortname;
		private $version;
		private $author;
		private $api_url = 'https://widget-options.com/edd-sl-api/';

		/**
		 * Class constructor
		 *
		 * @param string  $_file
		 * @param string  $_item
		 * @param string  $_version
		 * @param string  $_author
		 * @param string  $_optname
		 * @param string  $_api_url
		 */
		function __construct($_file, $_item, $_version, $_author, $_optname = null, $_api_url = null)
		{

			$this->file           = $_file;

			if (is_numeric($_item)) {
				$this->item_id    = absint($_item);
			} else {
				$this->item_name  = $_item;
			}

			$this->item_shortname = 'widgetopts_' . preg_replace('/[^a-zA-Z0-9_\s]/', '', str_replace(' ', '_', strtolower($this->item_name)));
			$this->version        = $_version;
			$this->license        = trim(get_option($this->item_shortname . '_license_key', ''));
			$this->author         = $_author;
			$this->api_url        = is_null($_api_url) ? $this->api_url : $_api_url;

			/**
			 * Allows for backwards compatibility with old license options,
			 * i.e. if the plugins had license key fields previously, the license
			 * handler will automatically pick these up and use those in lieu of the
			 * user having to reactive their license.
			 */
			if (!empty($_optname)) {
				$opt = get_option($_optname, false);

				if (isset($opt) && empty($this->license)) {
					$this->license = trim($opt);
				}
			}

			// Setup hooks
			$this->includes();
			$this->hooks();
		}

		/**
		 * Include the updater class
		 *
		 * @access  private
		 * @return  void
		 */
		private function includes()
		{
			if (!class_exists('EDD_SL_Plugin_Updater')) {
				require_once 'EDD_SL_Plugin_Updater.php';
			}
		}

		/**
		 * Setup hooks
		 *
		 * @access  private
		 * @return  void
		 */
		private function hooks()
		{

			// Check that license is valid once per week
			// add_action( 'edd_weekly_scheduled_events', array( $this, 'weekly_license_check' ) );

			// Add Daily check
			add_filter('cron_schedules', function ($schedules) {
				// Fix Rescheduling error from old versions
				if (!isset($schedules['hourly'])) {
					$schedules['hourly'] = array(
						'interval' => 3600,
						'display' => esc_html__('Once Hourly'),
					);
				}

				return $schedules;
			});
			if (!wp_next_scheduled('wo_license_cron')) {
				wp_schedule_event(time(), 'hourly', 'wo_license_cron');
			}
			add_action('wo_license_cron', array($this, 'weekly_license_check'));

			// For testing license notices, uncomment this line to force checks on every page load
			// add_action( 'admin_init', array( $this, 'weekly_license_check' ) );

			// Updater
			add_action('admin_init', array($this, 'auto_updater'), 0);

			// Display notices to admins
			add_action('admin_notices', array($this, 'notices'));

			add_action('after_plugin_row_' . plugin_basename($this->file), array($this, 'plugin_row_license_missing'), 10, 2);
		}

		/**
		 * Auto updater
		 *
		 * @access  private
		 * @return  void
		 */
		public function auto_updater()
		{
			$betas = widgetopts_get_option('enabled_betas', array());

			$args = array(
				'version'   => $this->version,
				'license'   => $this->license,
				'author'    => $this->author
			);

			if (!empty($this->item_id)) {
				$args['item_id']   = $this->item_id;
			} else {
				$args['item_name'] = $this->item_name;
			}

			// Setup the updater
			$edd_updater = new EDD_SL_Plugin_Updater(
				$this->api_url,
				$this->file,
				$args
			);
		}


		/**
		 * Activate the license key
		 *
		 * @access  public
		 * @return  void
		 */
		public function activate_license($license = '', $nonce = '')
		{

			if (empty($license)) {
				return;
			}

			if (empty($nonce) || !wp_verify_nonce($nonce, 'widgetopts_license_nonce')) {
				return;
			}

			if (!current_user_can('manage_options')) {
				return;
			}

			$details = get_option($this->item_shortname . '_license_active');

			if (is_object($details) && 'valid' === $details->license) {
				return;
			}

			$license = sanitize_text_field($license);

			if (empty($license)) {
				return;
			}

			$license_data = $this->check_license_key($license, 'activate_license');

			if ($license_data === false) {
				return;
			}

			// Tell WordPress to look for updates
			set_site_transient('update_plugins', null);

			if (function_exists('is_multisite') && is_multisite() && is_object($license_data) && 'valid' === $license_data->license) {
				if ($license_data->license_limit > 0) {
					$license_data = (object) array(
						'success' 		=> '',
						'license' 		=> 'invalid',
						'item_name' 	=> urlencode($this->item_name),
						'error'			=> 'multisite'
					);
				}
			}
			// print_r( $license_data );
			update_option($this->item_shortname . '_license_active', $license_data);
		}

		private function check_license_key($license, $edd_action = 'activate_license')
		{
			if (empty($license)) {
				return false;
			}

			$item_names = [$this->item_name, $this->item_name . ' - Lifetime'];
			$url = home_url();
			$license_data = null;

			foreach ($item_names as $item) {
				// Data to send to the API
				$api_params = array(
					'edd_action' => $edd_action,
					'license'    => $license,
					'item_name'  => urlencode($item),
					'url'        => $url
				);

				// Call the API
				$response = wp_remote_post(
					$this->api_url,
					array(
						'timeout'   => 15,
						'sslverify' => false,
						'body'      => $api_params
					)
				);

				// Make sure there are no errors
				if (is_wp_error($response)) {
					continue;
				}

				$license_data = json_decode(wp_remote_retrieve_body($response));

				if (is_object($license_data) && isset($license_data->license) && 'valid' === $license_data->license) {
					break;
				}
			}

			if (is_null($license_data)) {
				return false;
			}

			return $license_data;
		}

		/**
		 * Deactivate the license key
		 *
		 * @access  public
		 * @return  void
		 */
		public function deactivate_license($license = '', $nonce = '')
		{

			if (empty($license)) {
				return;
			}

			if (!current_user_can('manage_options')) {
				return;
			}

			$item_names = [$this->item_name, $this->item_name . ' - Lifetime'];

			// License statuses considered safe for deleting settings:
			// - 'deactivated'  → The user explicitly deactivated the license.
			// - 'revoked'      → License was manually revoked by the provider.
			// - 'disabled'     → License was disabled (e.g., fraud, violation).
			// - 'site_inactive'→ License is valid but not active on this site.
			// - 'expired'      → License is expired; safe because user initiated deactivation.
			$safe_to_proceed_status = ['deactivated', 'revoked', 'disabled', 'site_inactive', 'expired'];
			$status = '';

			foreach ($item_names as $item) {
				// Data to send to the API
				$api_params = array(
					'edd_action' => 'deactivate_license',
					'license'    => $license,
					'item_name'  => urlencode($item),
					'url'        => home_url()
				);

				// Call the API
				$response = wp_remote_post(
					$this->api_url,
					array(
						'timeout'   => 15,
						'sslverify' => false,
						'body'      => $api_params
					)
				);

				// Make sure there are no errors
				if (is_wp_error($response)) {
					continue;
				}

				// Decode the license data
				$license_data = json_decode(wp_remote_retrieve_body($response));
				$expired = isset($license_data->expires) && is_int($license_data->expires) ? $license_data->expires < time() : false;
				if (
					is_object($license_data) && isset($license_data->license) &&
					(in_array($license_data->license, $safe_to_proceed_status) ||
						($license_data->license == 'invalid' && isset($license_data->error) && in_array($license_data->error, $safe_to_proceed_status)) ||
						($license_data->license == 'failed' && $expired))
				) {
					delete_option($this->item_shortname . '_license_active');
					$status = 'deactivated';
					break;
				} else {
					$status = $license_data->license;
				}
			}

			return $status;
		}


		/**
		 * Check if license key is valid once per week
		 *
		 * @access  public
		 * @since   2.5
		 * @return  void
		 */
		public function weekly_license_check()
		{

			if (empty($this->license)) {
				return;
			}

			$license_data = $this->check_license_key($this->license, 'check_license');

			// make sure the response came back okay
			if ($license_data === false) {
				return false;
			}

			update_option($this->item_shortname . '_license_active', $license_data);
		}


		/**
		 * Admin notices for errors
		 *
		 * @access  public
		 * @return  void
		 */
		public function notices()
		{

			static $showed_invalid_message;

			// if( empty( $this->license ) ) {
			// 	return;
			// }

			if (!current_user_can('manage_options')) {
				return;
			}

			$messages = array();

			$license = get_option($this->item_shortname . '_license_active');

			if (!is_object($license) || 'valid' !== $license->license) {
				$messages[] = sprintf(esc_html__('Uh oh! Your license has expired or is not marked as active. In order to continue using Widget Options, please renew your license at %shttps://widget-options.com/account/?view=subscriptions%s. If this is an error please reach out to us at %ssupport@widget-options.com%s.', 'widget-options'), '<a href="https://widget-options.com/account/?view=subscriptions" target="_blank">', '</a>', '<a href="mailto:support@widget-options.com">', '</a>');

				$showed_invalid_message = true;
			} else if (function_exists('is_multisite') && is_multisite() && is_object($license) && 'valid' !== $license->license && empty($showed_invalid_message) && isset($license->error) && $license->error == 'multisite') {

				$messages[] = sprintf(
					__('<strong>You have incompatible license key for Multisite installation</strong>. Please <a href="%s">contact support</a> to upgrade your license.', 'widget-options'),
					esc_url('https://widget-options.com/contact/')
				);

				$showed_invalid_message = true;
			} else if (is_object($license) && 'valid' !== $license->license && empty($showed_invalid_message)) {

				$messages[] = sprintf(
					__('You have invalid or expired license keys for Widget Options Extended. Please go to the <a href="%s">Licenses page</a> to correct this issue.', 'widget-options'),
					esc_url(admin_url('options-general.php?page=widgetopts_plugin_settings'))
				);

				$showed_invalid_message = true;
			}

			if (!empty($messages)) {

				foreach ($messages as $message) {

					echo '<div class="error widget-options-error">';
					echo '<p>' . $message . '</p>';
					echo '</div>';
				}
			}
		}

		/**
		 * Displays message inline on plugin row that the license key is missing
		 *
		 * @access  public
		 * @since   4.1
		 * @return  void
		 */
		public function plugin_row_license_missing($plugin_data, $version_info)
		{

			static $showed_imissing_key_message;

			$license = get_option($this->item_shortname . '_license_active');

			if ((!is_object($license) || 'valid' !== $license->license) && empty($showed_imissing_key_message[$this->item_shortname])) {
				$message = sprintf(esc_html__('%sRegister%s your copy of Widget Options Extended to receive access to automatic upgrades and support. Need a license key? %sPurchase one now%s.', 'widget-options'), '<a href="' . esc_url(admin_url('options-general.php?page=widgetopts_plugin_settings')) . '">', '</a>', '<a href="https://widget-options.com/" target="_blank">', '</a>');
				echo '</tr><tr class="plugin-update-tr"><td colspan="5" class="plugin-update"><div class="update-message notice inline notice-warning notice-alt"><p>' . $message . '</p></div></td>';
			}
		}

		/**
		 * Adds this plugin to the beta page
		 *
		 * @access  public
		 * @param   array $products
		 * @since   2.6.11
		 * @return  void
		 */
		public function register_beta_support($products)
		{
			$products[$this->item_shortname] = $this->item_name;

			return $products;
		}
	}

endif; // end class_exists check
