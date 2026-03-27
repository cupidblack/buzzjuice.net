<?php

/**
 * Install Function
 *
 * @copyright   Copyright (c) 2016, Jeffrey Carandang
 * @since       3.0
 */
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

//check if free version is activated
if (!function_exists('widgetopts_upgraded')) {
	add_action('admin_notices', 'widgetopts_upgraded');
	function widgetopts_upgraded()
	{
		if (is_plugin_active('widget-options/plugin.php')) { ?>
			<div class="widgetopts_activated_notice notice-error notice" style="box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);">
				<p>
					<?php _e('Please deactivate <strong>Widget Options</strong> Plugin, it may cause issue with the extended plugin version. Thanks!', 'widget-options'); ?>
				</p>
			</div>
<?php }
	}
}

//add settings link on plugin page
if (!function_exists('widgetopts_filter_plugin_actions')) {
	add_action('plugin_action_links_' . plugin_basename(WIDGETOPTS_PLUGIN_FILE), 'widgetopts_filter_plugin_actions');
	function widgetopts_filter_plugin_actions($links)
	{
		if (!is_array($links)) {
			$links = array();
		}
		$links[]  = '<a href="' . esc_url(admin_url('options-general.php?page=widgetopts_plugin_settings')) . '">' . __('Settings', 'widget-options') . '</a>';
		return $links;
	}
}

//register default values
if (!function_exists('widgetopts_register_defaults')) {
	register_activation_hook(WIDGETOPTS_PLUGIN_FILE, function () {
		add_option('Activated_Plugin', WIDGETOPTS_PLUGIN_FILE);
		update_option('widgetopts_force_migration_rescan', 1);
		update_option('widgetopts_legacy_migration_notice_token', (string) time());
		widgetopts_register_defaults();
	});

	add_action('admin_init', function () {
		if (is_admin() && get_option('widgetopts_force_migration_rescan')) {
			delete_option('widgetopts_force_migration_rescan');
			if (class_exists('WidgetOpts_Snippets_Migration')) {
				WidgetOpts_Snippets_Migration::rescan_and_update_flag();
			} elseif (class_exists('WidgetOpts_Snippets_CPT')) {
				delete_option('wopts_display_logic_migration_required');
				WidgetOpts_Snippets_CPT::scan_for_legacy_logic();
			}
		}

		if (is_admin() && get_option('Activated_Plugin') == WIDGETOPTS_PLUGIN_FILE) {
			delete_option('Activated_Plugin');
			exit(wp_redirect(admin_url('options-general.php?page=widgetopts_plugin_settings')));
		}
	});

	add_action('plugins_loaded', 'widgetopts_register_defaults');
	function widgetopts_register_defaults()
	{
		if (is_admin()) {
			if (!get_option('_widgetopts_default_registered_')) {
				//activate free version modules
				add_option('widgetopts_tabmodule-visibility', 'activate');
				add_option('widgetopts_tabmodule-devices', 'activate');
				add_option('widgetopts_tabmodule-alignment', 'activate');
				add_option('widgetopts_tabmodule-hide_title', 'activate');
				add_option('widgetopts_tabmodule-classes', 'activate');
				add_option('widgetopts_tabmodule-logic', 'activate');
				add_option('widgetopts_tabmodule-state', 'activate');
				//add_option('widgetopts_tabmodule-classic_widgets_screen', 'activate');
				//add free version settings
				$defaults = array(
					'visibility' 	=> 	array(
						'post_type'		=> '1',
						'taxonomies'	=> '1',
						'misc'			=> '1'
					),
					'classes' 		=> 	array(
						'id'			=> '1',
						'type'			=> 'both'
					),
				);

				//upgraded settings from previous version
				$options    = get_option('extwopts_class_settings');
				if (isset($options['class_field'])) {
					$defaults['classes']['type'] = $options['class_field'];
				}
				if (isset($options['classlists'])) {
					$defaults['classes']['classlists'] = $options['classlists'];
				}

				add_option('widgetopts_tabmodule-settings', serialize($defaults));

				add_option('_widgetopts_default_registered_', '1');
				delete_option('widgetopts_settings');
				delete_transient('widgetopts_tabs_transient'); //remove transient for settings
			}

			//add pro features separately to avoid conflict from free upgrades
			if (!get_option('_extended_widgetopts_default_registered_') || !get_option('_extended_widgetopts_pro_registered_')) {
				add_option('widgetopts_tabmodule-links', 'activate');
				add_option('widgetopts_tabmodule-fixed', 'activate');
				add_option('widgetopts_tabmodule-columns', 'activate');
				add_option('widgetopts_tabmodule-roles', 'activate');
				add_option('widgetopts_tabmodule-dates', 'activate');
				add_option('widgetopts_tabmodule-styling', 'activate');
				add_option('widgetopts_tabmodule-animation', 'activate');
				add_option('widgetopts_tabmodule-taxonomies', 'activate');
				add_option('widgetopts_tabmodule-disable_widgets', 'activate');
				add_option('widgetopts_tabmodule-permission', 'activate');
				add_option('widgetopts_tabmodule-shortcodes', 'activate');

				$extend_defaults = array(
					'fixed' 	=> array(
						'disable_width' 	=> '768',
						'disable_height' 	=> '200',
						'stop'				=>	'.site-footer'
					),
					'dates'		=> array(
						'days'			=>	'1',
						'date_range'	=>	'1'
					),
					'styling' 	=> array(
						'widgets' 	=> '1',
						'forms' 	=> '1',
						'other' 	=> '1'
					),
					'taxonomies'		=> array(
						'category'			=>	'1'
					),
				);

				//add previous version settings value to the new option
				$tax_opts    = get_option('extwopts_taxonomy_settings');
				if (!empty($tax_opts) && is_array($tax_opts)) {
					foreach ($tax_opts as $key => $value) {
						$extend_defaults['taxonomies'][$key] = '1';
					}
				}

				$perm_opts   = get_option('extwopts_general_settings');
				if (!empty($perm_opts) && is_array($perm_opts)) {
					foreach ($perm_opts as $k => $v) {
						if (is_array($v) && !empty($v)) {
							$extend_defaults['permission'][$k] = '1';
						}
					}
				}

				if (!get_option('widgetopts_tabmodule-settings')) {
					add_option('widgetopts_tabmodule-settings', serialize($extend_defaults));
				} else {
					$opts  = unserialize(get_option('widgetopts_tabmodule-settings'));
					$opts  = is_array($opts) ? $opts : array();
					$merge = array_merge($opts, $extend_defaults);

					update_option('widgetopts_tabmodule-settings', serialize($merge));
				}

				add_option('_extended_widgetopts_default_registered_', '1');
				add_option('_extended_widgetopts_pro_registered_', '1');
				delete_option('widgetopts_settings');
				delete_transient('widgetopts_tabs_transient'); //remove transient for settings
			}

			//reregister license on different option name
			if (!get_option('_widgetopts_license_registered_')) {
				$license = get_option('widgetopts_license_key');
				if (!empty($license)) {
					update_option('widgetopts_extended_widget_options_license_key', $license);

					update_option('widgetopts_license_keys', array('extended' => $license));
				}

				$license_data = get_option('widgetopts_license_data');
				if (!empty($license_data)) {
					update_option('widgetopts_extended_widget_options_license_active', $license_data);
				}

				add_option('_widgetopts_license_registered_', '1');
			}

			//make sure to delete previous pages cache
			if (!get_option('widgetopts_removed_global_pages')) {
				delete_option('widgetopts_global_pages');
				add_option('widgetopts_removed_global_pages', 1);
			}
		}
	}
}

?>