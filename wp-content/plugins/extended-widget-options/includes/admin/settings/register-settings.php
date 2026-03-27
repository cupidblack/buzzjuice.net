<?php

/**
 * Register Settings
 * @since   4.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Get an option
 *
 * Looks to see if the specified setting exists, returns default if not
 *
 * @since 4.1
 * @global $widget_options Array of all the Widget Options
 * @return mixed
 */
if (!function_exists('widgetopts_get_option')) :
	function widgetopts_get_option($key = '', $default = false)
	{
		global $widget_options;

		$value = !empty($widget_options[$key]) ? $widget_options[$key] : $default;
		$value = apply_filters('widgetopts_get_option', $value, $key, $default);

		return apply_filters('widgetopts_get_option_' . $key, $value, $key, $default);
	}
endif;

/**
 * Update an option
 *
 * Updates an widgetopts setting value in both the db and the global variable.
 * Warning: Passing in an empty, false or null string value will remove
 *          the key from the widget_options array.
 *
 * @since 4.1
 * @param string $key The Key to update
 * @param string|bool|int $value The value to set the key to
 * @global $widget_options Array of all the Widget Options
 * @return boolean True if updated, false if not.
 */
if (!function_exists('widgetopts_update_option')) :
	function widgetopts_update_option($key = '', $value = false)
	{

		// If no key, exit
		if (empty($key)) {
			return false;
		}

		if (empty($value)) {
			$remove_option = widgetopts_delete_option($key);
			return $remove_option;
		}

		// First let's grab the current settings
		$options = get_option('widgetopts_settings');

		// Let's let devs alter that value coming in
		$value = apply_filters('widgetopts_update_option', $value, $key);

		// Next let's try to update the value
		$options[$key] = $value;

		$did_update = update_option('widgetopts_settings', $options);
		// If it updated, let's update the global variable
		if ($did_update) {
			global $widget_options;
			$widget_options[$key] = $value;
		}
		return $did_update;
	}
endif;

/**
 * Remove an option
 *
 * Removes widget options setting value in both the db and the global variable.
 *
 * @since 4.1
 * @param string $key The Key to delete
 * @global $widget_options Array of all the Widget Options
 * @return boolean True if removed, false if not.
 */
if (!function_exists('widgetopts_delete_option')) :
	function widgetopts_delete_option($key = '')
	{
		// If no key, exit
		if (empty($key)) {
			return false;
		}
		// First let's grab the current settings
		$options = get_option('widgetopts_settings');

		// Next let's try to update the value
		if (isset($options[$key])) {
			unset($options[$key]);
		}
		$did_update = update_option('widgetopts_settings', $options);

		// If it updated, let's update the global variable
		if ($did_update) {
			global $edd_options;
			$edd_options = $options;
		}
		return $did_update;
	}
endif;

/**
 * Remove option from database
 *
 * @since 4.1
 * @param string $prefix The Prefix to delete
 * @return boolean True if removed, false if not.
 */
if (!function_exists('widgetopts_delete_option_from_db')) :
	function widgetopts_delete_option_from_db($prefix = '')
	{
		// check if $prefix is not empty to avoid removing all options
		// also check it widgetopts is defined to avoid conflicts
		if (!empty($prefix) && strpos($prefix, 'widgetopts') !== false) {
			global $wpdb;
			$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '{$prefix}%'");
		}
	}
endif;

/**
 * Get Settings
 *
 * Retrieves all plugin settings
 *
 * @since 4.1
 * @return array WIDGETOPTS settings
 */
if (!function_exists('widgetopts_get_settings')) :
	function widgetopts_get_settings()
	{
		if (is_multisite()) {
			$settings = get_blog_option(get_current_blog_id(), 'widgetopts_settings');
		} else {
			$settings = get_option('widgetopts_settings');
		}

		if (empty($settings)) {

			// Update old settings with new single option
			$opts_settings 		= get_option('widgetopts_tabmodule-settings');
			//fallback to prevent error
			if (is_serialized($opts_settings)) {
				$opts_settings = maybe_unserialize($opts_settings);
			}

			$settings 			= !empty($opts_settings) ?  $opts_settings : array();
			$visibility 		= array('visibility' 		=> get_option('widgetopts_tabmodule-visibility'));
			$devices 			= array('devices' 			=> get_option('widgetopts_tabmodule-devices'));
			$alignment 			= array('alignment' 		=> get_option('widgetopts_tabmodule-alignment'));
			$columns 			= array('columns' 			=> get_option('widgetopts_tabmodule-columns'));
			$dates 				= array('dates' 			=> get_option('widgetopts_tabmodule-dates'));
			$styling 			= array('styling' 			=> get_option('widgetopts_tabmodule-styling'));
			$roles 				= array('roles' 			=> get_option('widgetopts_tabmodule-roles'));
			$hide_title 		= array('hide_title' 		=> get_option('widgetopts_tabmodule-hide_title'));
			$classes 			= array('classes' 			=> get_option('widgetopts_tabmodule-classes'));
			$logic 				= array('logic' 			=> get_option('widgetopts_tabmodule-logic'));
			$links 				= array('links' 			=> get_option('widgetopts_tabmodule-links'));
			$fixed 				= array('fixed' 			=> get_option('widgetopts_tabmodule-fixed'));
			$taxonomies 		= array('taxonomies' 		=> get_option('widgetopts_tabmodule-taxonomies'));
			$animation 			= array('animation' 		=> get_option('widgetopts_tabmodule-animation'));
			$shortcodes 		= array('shortcodes' 		=> get_option('widgetopts_tabmodule-shortcodes'));
			$cache 				= array('cache' 			=> get_option('widgetopts_tabmodule-cache'));
			$siteorigin 		= array('siteorigin' 		=> get_option('widgetopts_tabmodule-siteorigin'));
			$search 			= array('search' 			=> get_option('widgetopts_tabmodule-search'));
			$disable_widgets 	= array('disable_widgets' 	=> get_option('widgetopts_tabmodule-disable_widgets'));
			$permission 		= array('permission' 		=> get_option('widgetopts_tabmodule-permission'));
			$move 				= array('move' 			=> get_option('widgetopts_tabmodule-move'));
			$clone 				= array('clone' 			=> get_option('widgetopts_tabmodule-clone'));
			$elementor 			= array('elementor' 		=> get_option('widgetopts_tabmodule-elementor'));
			$widget_area 		= array('widget_area' 		=> get_option('widgetopts_tabmodule-widget_area'));
			$import_export 		= array('import_export' 	=> get_option('widgetopts_tabmodule-import_export'));
			$urls 				= array('urls' 			=> get_option('widgetopts_tabmodule-urls'));
			$beaver 			= array('beaver' 			=> get_option('widgetopts_tabmodule-beaver'));
			$acf 				= array('acf' 				=> get_option('widgetopts_tabmodule-acf'));
			$state 				= array('state' 			=> get_option('widgetopts_tabmodule-state'));
			$classic_widgets_screen = array('state' 			=> get_option('widgetopts_tabmodule-classic_widgets_screen'));
			$page_and_post_block = array('hide_page_and_post_block' 			=> get_option('widgetopts_tabmodule-hide_page_and_post_block'));
			$custom_sidebar = array('custom_sidebar' 			=> get_option('widgetopts_tabmodule-custom_sidebar'));
			//switch value and key
			$tax   = (isset($settings['taxonomies'])) ? $settings['taxonomies'] : array();
			if (!empty($tax)) {
				$new_tax_opts = array();
				foreach ($tax as $tax_k => $tax_v) {
					$new_tax_opts[] = $tax_k;
				}
				$settings['taxonomies_keys'] = $new_tax_opts;
			} else {
				$settings['taxonomies_keys'] = array();
			}

			$settings = array_merge(array('settings' => $settings), $visibility, $devices, $alignment, $columns, $dates, $styling, $roles, $hide_title, $classes, $logic, $links, $fixed, $taxonomies, $animation, $shortcodes, $cache, $siteorigin, $search, $disable_widgets, $permission, $move, $clone, $elementor, $widget_area, $import_export, $urls, $beaver, $acf, $state, $classic_widgets_screen, $page_and_post_block, $custom_sidebar);

			// Let's let devs alter that value coming in
			$settings = apply_filters('widgetopts_update_settings', $settings);

			update_option('widgetopts_settings', $settings);
		}

		$default = array('settings' => array(), 'visibility' => '', 'devices' => '', 'alignment' => '', 'columns' => '', 'dates' => '', 'styling' => '', 'roles' => '', 'hide_title' => '', 'classes' => '', 'logic' => '', 'links' => '', 'fixed' => '', 'taxonomies' => '', 'animation' => '', 'shortcodes' => '', 'cache' => '', 'siteorigin' => '', 'search' => '', 'disable_widgets' => '', 'permission' => '', 'move' => '', 'clone' => '', 'elementor' => '', 'widget_area' => '', 'import_export' => '', 'urls' => '', 'beaver' => '', 'acf' => '', 'state' => '', 'sliding' => '', 'classic_widgets_screen' => '', 'hide_page_and_post_block' => 'activate', 'custom_sidebar' => '');
		$settings = shortcode_atts($default, $settings);

		return apply_filters('widgetopts_get_settings', $settings);
	}
endif;

/**
 * Unregister custom sidebar
 *
 * @since 5.1.6
 * @param array $value The new widget option settings value
 * @global $widget_options Array of all the Widget Options
 */
if (!function_exists('widgetopts_unregister_custom_sidebar')) :
	function widgetopts_unregister_custom_sidebar($value = '')
	{
		global $widget_options;

		if (!isset($widget_options['custom_sidebar'])) {
			$widget_options['custom_sidebar'] = '';
		}

		$custom_sidebar = (isset($widget_options['settings']['custom_sidebar'])) ? $widget_options['settings']['custom_sidebar'] : array();

		if (is_array($custom_sidebar) && is_iterable($custom_sidebar) && is_countable($custom_sidebar) && count($custom_sidebar)) {
			foreach ($custom_sidebar as $cs) {
				if (!isset($cs['id']) || empty($cs['id'])) continue;
				$exist = false;
				if ($widget_options['custom_sidebar'] == 'activate') {
					if (!empty($value) && isset($value['custom_sidebar']) && is_array($value['custom_sidebar']) && is_iterable($value['custom_sidebar'])) {
						foreach ($value['custom_sidebar'] as $ncs) {
							if (!isset($cs['status']) || $cs['status'] != 1) continue;

							if ($cs['id'] == $ncs['id']) {
								$exist = true;
								break;
							}
						}
					}
				}

				if ($exist === false) {
					unregister_sidebar($cs['id']);
				}
			}
		}
	}
endif;
