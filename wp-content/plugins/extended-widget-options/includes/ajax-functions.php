<?php

/**
 * AJAX Functions
 *
 * Process AJAX actions.
 *
 * @copyright   Copyright (c) 2016, Jeffrey Carandang
 * @since       4.0
 */
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Save Options
 *
 * @since 1.0
 * @return void
 */
function widgetopts_ajax_save_settings()
{
	$response = array('errors' => array());

	if (!isset($_POST['method'])) return;
	if (!isset($_POST['nonce'])) return;

	if (!wp_verify_nonce($_POST['nonce'], 'widgetopts-settings-nonce')) {
		return;
	}

	switch ($_POST['method']) {
		case 'activate':
		case 'deactivate':
			if (!isset($_POST['module'])) return;

			//update options
			update_option('widgetopts_tabmodule-' . sanitize_text_field($_POST['module']), sanitize_text_field($_POST['method']));

			//update global variable
			widgetopts_update_option(sanitize_text_field($_POST['module']), sanitize_text_field($_POST['method']));

			if ($_POST['module'] == 'custom_sidebar' && $_POST['method'] == 'deactivate') {
				widgetopts_unregister_custom_sidebar();
			}
			break;

		case 'save':
			$response['messages'] = array(__('Settings saved successfully.', 'widget-options'));
			if (!isset($_POST['data'])) return;
			parse_str($_POST['data']['--widgetopts-form-serialized-data'], $params);
			$sanitized = widgetopts_sanitize_array($params);

			//switch value and key
			$tax   = (isset($sanitized['taxonomies'])) ? $sanitized['taxonomies'] : array();
			if (!empty($tax) && is_array($tax)) {
				$new_tax_opts = array();
				foreach ($tax as $tax_k => $tax_v) {
					$new_tax_opts[] = $tax_k;
				}
				$sanitized['taxonomies_keys'] = $new_tax_opts;
			} else {
				$sanitized['taxonomies_keys'] = array();
			}

			/* this part will handle the activation/deactivation of module from another module  */
			if (isset($sanitized['module_list']) && !empty($sanitized['module_list'])) {
				if (is_array($sanitized['module_list'])) {
					foreach ($sanitized['module_list'] as $mkey => $module) {
						$method = 'deactivate';
						if (isset($sanitized['module_activation'][$mkey])) {
							$method = 'activate';
						}

						//update options
						update_option('widgetopts_tabmodule-' . $mkey, $method);
						//update global variable
						widgetopts_update_option($mkey, $method);
					}
				}

				unset($sanitized['module_list']);
				if (isset($sanitized['module_activation'])) {
					unset($sanitized['module_activation']);
				}
			}

			update_option('widgetopts_tabmodule-settings', maybe_serialize($sanitized));

			widgetopts_unregister_custom_sidebar($sanitized);

			//reset options
			widgetopts_update_option('settings', $sanitized);
			// delete_option( 'widgetopts_settings' );
			break;

		case 'deactivate_license':
			global $extended_license;
			$license = sanitize_text_field($_POST['data']['license-data']);
			if (!empty($license)) {

				if (isset($_POST['data']['shortname'])) {
					$item_shortname = sanitize_text_field($_POST['data']['shortname']);
				} else {
					$item_shortname = 'widgetopts_' . preg_replace('/[^a-zA-Z0-9_\s]/', '', str_replace(' ', '_', strtolower(WIDGETOPTS_PLUGIN_NAME)));
				}
				switch ($item_shortname) {
					case 'widgetopts_sliding_widget_options':
						global $widgetopts_sliding_license;
						$data = $widgetopts_sliding_license->deactivate_license($license);
						break;

					default:
						$data = $extended_license->deactivate_license($license);
						break;
				}

				$response['button'] = sanitize_text_field($_POST['data']['button']);
				if ($data == 'deactivated') {

					$optiondata = get_option('widgetopts_license_keys');
					$name = str_replace('widgetopts-license-btn-', '', sanitize_text_field($_POST['data']['button']));
					$optiondata[$name] = '';
					update_option('widgetopts_license_keys', $optiondata);

					//remove license key on option
					delete_option($item_shortname . '_license_key');

					$response['messages'] = array(__('Successfully Deactivated.', 'widget-options'));
				} else {
					$response['messages'] = array(__('Deactivation Failed.', 'widget-options'));
				}
				$response['success'] = $data;
			}

			break;
		case 'clear_cache':
			widgetopts_delete_option_from_db('_transient_timeout_widgetopts-cache');
			widgetopts_delete_option_from_db('_transient_widgetopts-cache');
			break;

		case 'delete_widgetopts_update_transient':
			update_option('widgetopts_pro_upgrade', 0);
			break;

		default:
			# code...
			break;
	}
	$response['source'] 	= 'WIDGETOPTS_Response';
	$response['response'] 	= 'success';
	$response['closeModal'] = true;
	$response 				= (object) $response;

	//let devs do there action
	do_action('widget_options_before_ajax_print', sanitize_text_field($_POST['method']));

	echo json_encode($response);
	die();
}
add_action('wp_ajax_widgetopts_ajax_settings',  'widgetopts_ajax_save_settings');

function widgetopts_ajax_validate_expression()
{
	if (!wp_verify_nonce($_POST['nonce'], 'widgetopts-expression-nonce')) {
		echo json_encode(['response' => 'failed', 'message' => 'Security check failed. Please refresh the page and try again.']);
		die();
	}

	if (!isset($_POST['expression']) || empty(trim($_POST['expression']))) {
		echo json_encode(['response' => 'success', 'message' => 'Expression is empty, but this will be considered as valid.', 'valid' => true]);
		die();
	}

	$expression = sanitize_text_field($_POST['expression']);
	$result = widgetopts_validate_expression($expression);

	echo json_encode(['response' => 'success', 'message' => $result['message'], 'valid' => $result['valid']]);
	die();
}
add_action('wp_ajax_widgetopts_ajax_validate_expression', 'widgetopts_ajax_validate_expression');
