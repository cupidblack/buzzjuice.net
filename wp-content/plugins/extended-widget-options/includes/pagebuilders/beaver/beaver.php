<?php

/**
 * Plugin Name: Widget Options for Beaver Builder Pro
 * Plugin URI: https://widget-options.com/
 * Description: <strong>Requires <a href="https://wordpress.org/plugins/widget-options/" target="_blank">Widget Options Plugin</a></strong>! Extend functionalities to Beaver Builder for more visibility restriction options.
 * Version: 1.0
 * Author: Phpbits Creative Studio
 * Author URI: https://phpbits.net/
 * Text Domain: widget-options
 * Domain Path: languages
 *
 * @category Widgets
 * @author Jeffrey Carandang
 * @version 1.0
 */
// Exit if accessed directly.
if (!defined('ABSPATH')) exit;
if (!class_exists('WP_Widget_Options_Beaver')) :

	/**
	 * Main WP_Widget_Options_Beaver Class.
	 *
	 * @since 1.0
	 */
	class WP_Widget_Options_Beaver
	{

		public static function init()
		{
			$class = __CLASS__;
			new $class;
		}

		function __construct()
		{
			global $widget_options;

			add_filter('fl_builder_register_settings_form', array(&$this, 'widgetopts_beaver_settings'), 10, 2);
			add_action('fl_builder_control_widgetopts-beaver-tabnav', array(&$this, 'fl_widgetopts_beaver_tabnav'), 1, 4);
			add_action('wp_enqueue_scripts', array(&$this, 'fl_widgetopts_beaver_scripts'));
			add_action('fl_builder_control_widgetopts-select2', array(&$this, 'fl_widgetopts_beaver_select2'), 1, 4);
			add_action('admin_notices', array(&$this, 'widgetopts_plugin_check'));

			add_action('fl_builder_control_widgetopts-beaver-legacy', array(&$this, 'fl_widgetopts_beaver_legacy_control'), 1, 4);
			add_filter('fl_builder_is_node_visible', array(&$this, 'widgetopts_beaver_is_node_visible'), 10, 2);
			add_filter('fl_builder_module_attributes', array(&$this, 'widgetopts_beaver_module_attributes'), 10, 2);
			add_filter('fl_builder_render_css', array(&$this, 'widgetopts_beaver_render_css'), 10, 2);
			add_filter('fl_builder_render_module_content', array(&$this, 'widgetopts_beaver_render_module_content'), 10, 2);
			// add_action( 'fl_builder_before_render_row', array( &$this, 'widgetopts_beaver_before_render_module' ) );
		}

		function widgetopts_beaver_settings($form, $id)
		{
			if (!isset($form['widgetopts']) && !is_admin()) {
				//fix not registering global values
				if (!function_exists('widgetopts_register_globals')) {
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/globals.php';
					widgetopts_register_globals();
				}

				global $widget_options, $widgetopts_taxonomies, $widgetopts_types, $widgetopts_categories;
				$widgetopts_pages 		= widgetopts_global_pages();


				$sections 	= array();
				$pages      = (!empty($widgetopts_pages))       ? $widgetopts_pages         : array();
				$taxonomies = (!empty($widgetopts_taxonomies))  ? $widgetopts_taxonomies    : array();
				$types      = (!empty($widgetopts_types))       ? $widgetopts_types         : array();
				$categories = (!empty($widgetopts_categories))  ? $widgetopts_categories    : array();

				$get_terms = array();
				if (!empty($widget_options['settings']['taxonomies']) && is_array($widget_options['settings']['taxonomies'])) {
					foreach ($widget_options['settings']['taxonomies'] as $tax_opt => $vall) {
						$tax_name = 'widgetopts_taxonomy_' . str_replace('-', '__', $tax_opt);
						// global $$tax_name;
						$get_terms[$tax_opt] = $GLOBALS[$tax_name];
					}
				}

				// print_r( $pages ); die();

				$sections['widgetopts-fields'] = array(
					'fields' 	  => array(
						'widgetopts-tabnav' => array(
							'type'          => 'widgetopts-beaver-tabnav',
						)
					)
				);

				if (isset($widget_options['visibility']) && 'activate' == $widget_options['visibility']) {
					$visibility_fld = array();

					$visibility_fld['widgetopts_visibility_show'] = array(
						'type'          => 'select',
						'label' 		=> __('Show or Hide', 'widget-options'),
						'options'       => array(
							'hide'      => __('Hide on Selected Pages', 'widget-options'),
							'show'      => __('Show on Selected Pages', 'widget-options')
						)
					);

					if (isset($widget_options['settings']['visibility']) && isset($widget_options['settings']['visibility']['post_type']) && '1' == $widget_options['settings']['visibility']['post_type']) {

						if (!empty($pages)) {
							$pages_array 	= array();
							foreach ($pages as $page) {
								$pages_array[$page->ID] = $page->post_title;
							}

							$visibility_fld['widgetopts_visibility_pages'] = array(
								'type'				=> 'widgetopts-select2',
								'label'				=> __('Pages', 'widget-options'),
								'options'			=> $pages_array,
								'multi-select' 		=> true,
								'description' 		=> __('Click to search or select', 'widget-options'),
							);
						}

						if (!empty($types)) {
							$types_array = array();
							foreach ($types as $ptype => $type) {
								$types_array[$ptype] = $type->labels->name;
							}

							$visibility_fld['widgetopts_visibility_types'] = array(
								'type'				=> 'widgetopts-select2',
								'label'				=> __('Post Types', 'widget-options'),
								'options'			=> $types_array,
								'multi-select' 		=> true,
								'description' 		=> __('Click to search or select', 'widget-options')
							);
						}
					}

					if (isset($widget_options['settings']['visibility']) && isset($widget_options['settings']['visibility']['taxonomies']) && '1' == $widget_options['settings']['visibility']['taxonomies']) {
						if (!empty($widget_options['settings']['taxonomies']) && is_array($widget_options['settings']['taxonomies'])) {
							foreach ($widget_options['settings']['taxonomies'] as $tax_opt => $vallue) {
								$term_array = array();
								if (!empty($get_terms)) {
									foreach ($get_terms[$tax_opt] as $get_term) {
										$term_array[$get_term->term_id] = $get_term->name;
									}

									$visibility_fld['widgetopts_visibility_tax_' . $tax_opt] = array(
										'type'				=> 'widgetopts-select2',
										'label'				=> $taxonomies[$tax_opt]->label,
										'options'			=> $term_array,
										'multi-select' 		=> true,
										'description' 		=> __('Click to search or select', 'widget-options')
									);
								}
							}
						}

						if (!empty($categories)) {
							$cat_array = array();
							foreach ($categories as $cat) {
								$cat_array[$cat->cat_ID] = $cat->cat_name;
							}

							$visibility_fld['widgetopts_visibility_tax_category'] = array(
								'type'				=> 'widgetopts-select2',
								'label'				=> __('Categories', 'widget-options'),
								'options'			=> $cat_array,
								'multi-select' 		=> true,
								'description' 		=> __('Click to search or select', 'widget-options')
							);
						}

						if (!empty($taxonomies)) {
							$tax_array = array();
							foreach ($taxonomies as $taxonomy) {
								$tax_array[$taxonomy->name] = $taxonomy->label;
							}

							$visibility_fld['widgetopts_visibility_taxonomies'] = array(
								'type'				=> 'widgetopts-select2',
								'label'				=> __('Taxonomies', 'widget-options'),
								'options'			=> $tax_array,
								'multi-select' 		=> true,
								'description' 		=> __('Click to search or select', 'widget-options')
							);
						}
					}

					if (isset($widget_options['settings']['visibility']) && isset($widget_options['settings']['visibility']['misc']) && '1' == $widget_options['settings']['visibility']['misc']) {
						$visibility_fld['widgetopts_visibility_misc'] = array(
							'type'				=> 'widgetopts-select2',
							'label'				=> __('Miscellaneous', 'widget-options'),
							'options'			=> array(
								'home'      =>  __('Home/Front', 'widget-options'),
								'blog'      =>  __('Blog', 'widget-options'),
								'archives'  =>  __('Archives', 'widget-options'),
								'404'       =>  __('404', 'widget-options'),
								'search'    =>  __('Search', 'widget-options')
							),
							'multi-select' 	=> true,
							'description' 	=> __('Click to search or select', 'widget-options')
						);
					}

					$sections['widgetopts-visibility'] = array(
						'fields' 	  =>  $visibility_fld
					);
				}

				if (isset($widget_options['roles']) && 'activate' == $widget_options['roles']) {
					$roles_fld = array();

					$roles  = wp_roles()->roles;
					$roles  = apply_filters('editable_roles', $roles);

					$user_array = array();
					foreach ($roles as $role_name => $role_info) {
						$user_array[$role_name] = $role_info['name'];
					}
					$user_array['guests'] = __('Guests', 'widget-options');


					$roles_fld['widgetopts_roles_show'] = array(
						'type'          => 'select',
						'label' 		=> __('Show or Hide', 'widget-options'),
						'options'       => array(
							'hide'      => __('Hide on Selected Roles', 'widget-options'),
							'show'      => __('Show on Selected Roles', 'widget-options')
						)
					);

					$roles_fld['widgetopts_visibility_roles'] = array(
						'type'				=> 'widgetopts-select2',
						'label'				=> __('Select User Roles', 'widget-options'),
						'options'			=> $user_array,
						'multi-select' 		=> true,
						'description' 		=> __('Click to search or select', 'widget-options')
					);

					$sections['widgetopts-roles'] = array(
						'fields' 	  =>  $roles_fld
					);
				}

				if (isset($widget_options['alignment']) && 'activate' == $widget_options['alignment']) {
					$alignment_fld = array();

					$alignment_fld['widgetopts_alignment_large'] = array(
						'type'          => 'select',
						'label' 		=> __('Large Devices Alignment', 'widget-options'),
						'options'       => array(
							''      		=> __('Default', 'widget-options'),
							'center'      	=> __('Center', 'widget-options'),
							'left'      	=> __('Left', 'widget-options'),
							'right'      	=> __('Right', 'widget-options'),
							'justify'      	=> __('Justify', 'widget-options'),
						)
					);

					$alignment_fld['widgetopts_alignment_medium'] = array(
						'type'          => 'select',
						'label' 		=> __('Medium Devices Alignment', 'widget-options'),
						'options'       => array(
							''      		=> __('Default', 'widget-options'),
							'center'      	=> __('Center', 'widget-options'),
							'left'      	=> __('Left', 'widget-options'),
							'right'      	=> __('Right', 'widget-options'),
							'justify'      	=> __('Justify', 'widget-options'),
						)
					);

					$alignment_fld['widgetopts_alignment_small'] = array(
						'type'          => 'select',
						'label' 		=> __('Small Devices Alignment', 'widget-options'),
						'options'       => array(
							''      		=> __('Default', 'widget-options'),
							'center'      	=> __('Center', 'widget-options'),
							'left'      	=> __('Left', 'widget-options'),
							'right'      	=> __('Right', 'widget-options'),
							'justify'      	=> __('Justify', 'widget-options'),
						)
					);

					$sections['widgetopts-alignment'] = array(
						'fields' 	  =>  $alignment_fld
					);
				}

				if (isset($widget_options['dates']) && 'activate' == $widget_options['dates']) {
					$dates_fld = array();

					$dates_fld['widgetopts_visibility_days_show'] = array(
						'type'          => 'select',
						'label' 		=> __('Show or Hide', 'widget-options'),
						'options'       => array(
							'hide'      => __('Hide on Selected Days', 'widget-options'),
							'show'      => __('Show on Selected Days', 'widget-options')
						)
					);

					$dates_fld['widgetopts_visibility_days_day'] = array(
						'type'				=> 'widgetopts-select2',
						'label'				=> __('Days', 'widget-options'),
						'options'			=> array(
							'monday'     =>  __('Monday', 'widget-options'),
							'tuesday'    =>  __('Tuesday', 'widget-options'),
							'wednesday'  =>  __('Wednesday', 'widget-options'),
							'thursday'   =>  __('Thursday', 'widget-options'),
							'friday'     =>  __('Friday', 'widget-options'),
							'saturday'   =>  __('Saturday', 'widget-options'),
							'sunday'     =>  __('Sunday', 'widget-options')
						),
						'multi-select' 	=> true,
						'description' 	=> __('Click to search or select', 'widget-options')
					);

					$dates_fld['widgetopts_visibility_dates_show'] = array(
						'type'          => 'select',
						'label' 		=> __('Show or Hide', 'widget-options'),
						'options'       => array(
							'hide'      => __('Hide on Selected Dates', 'widget-options'),
							'show'      => __('Show on Selected Dates', 'widget-options')
						)
					);

					$dates_fld['widgetopts_visibility_dates_from'] = array(
						'type'				=> 'text',
						'label'				=> __('From', 'widget-options'),
						'class'         	=> 'widgetopts-datepicker',
					);
					$dates_fld['widgetopts_visibility_dates_to'] = array(
						'type'				=> 'text',
						'label'				=> __('To', 'widget-options'),
						'class'         	=> 'widgetopts-datepicker',
					);
					$sections['widgetopts-dates'] = array(
						'fields' 	  =>  $dates_fld
					);
				}

				if (isset($widget_options['animation']) && 'activate' == $widget_options['animation']) {
					$anim_fld = array();

					$anim_fld['widgetopts_animation_type'] = array(
						'type'				=> 'select',
						'label'				=> __('Animation Type', 'widget-options'),
						'options'			=> array(
							''     				=>  __('none', 'widget-options'),
							'bounce'     		=>  __('bounce', 'widget-options'),
							'flash'    	 		=>  __('flash', 'widget-options'),
							'pulse'  	 		=>  __('pulse', 'widget-options'),
							'rubberBand' 		=>  __('rubberBand', 'widget-options'),
							'shake'      		=>  __('shake', 'widget-options'),
							'swing'   	 		=>  __('swing', 'widget-options'),
							'tada'     	 		=>  __('tada', 'widget-options'),
							'wobble'     		=>  __('wobble', 'widget-options'),
							'jello'    	 		=>  __('jello', 'widget-options'),

							'bounceIn'  	 	=>  __('bounceIn', 'widget-options'),
							'bounceInDown' 	 	=>  __('bounceInDown', 'widget-options'),
							'bounceInLeft'   	=>  __('bounceInLeft', 'widget-options'),
							'bounceInRight'  	=>  __('bounceInRight', 'widget-options'),
							'bounceInUp'     	=>  __('bounceInUp', 'widget-options'),

							'fadeIn'     		=>  __('fadeIn', 'widget-options'),
							'fadeInDown'    	=>  __('fadeInDown', 'widget-options'),
							'fadeInDownBig'  	=>  __('fadeInDownBig', 'widget-options'),
							'fadeInLeft' 		=>  __('fadeInLeft', 'widget-options'),
							'fadeInLeftBig'     =>  __('fadeInLeftBig', 'widget-options'),
							'fadeInRight'   	=>  __('fadeInRight', 'widget-options'),
							'fadeInRightBig'    =>  __('fadeInRightBig', 'widget-options'),
							'fadeInUp'     		=>  __('fadeInUp', 'widget-options'),
							'fadeInUpBig'    	=>  __('fadeInUpBig', 'widget-options'),

							'flip'  	 		=>  __('flip', 'widget-options'),
							'flipInX' 			=>  __('flipInX', 'widget-options'),
							'flipInY'      		=>  __('flipInY', 'widget-options'),
							'flipOutX'   		=>  __('flipOutX', 'widget-options'),
							'flipOutY'     		=>  __('flipOutY', 'widget-options'),

							'lightSpeedIn'     	=>  __('lightSpeedIn', 'widget-options'),
							'lightSpeedOut'    	=>  __('lightSpeedOut', 'widget-options'),

							'rotateIn'  	 	=>  __('rotateIn', 'widget-options'),
							'rotateInDownLeft' 	=>  __('rotateInDownLeft', 'widget-options'),
							'rotateInDownRight' =>  __('rotateInDownRight', 'widget-options'),
							'rotateInUpLeft'   	=>  __('rotateInUpLeft', 'widget-options'),
							'rotateInUpRight'   =>  __('rotateInUpRight', 'widget-options'),

							'slideInUp'  	 	=>  __('slideInUp', 'widget-options'),
							'slideInDown' 		=>  __('slideInDown', 'widget-options'),
							'slideInLeft'      	=>  __('slideInLeft', 'widget-options'),
							'slideInRight'   	=>  __('slideInRight', 'widget-options'),

							'zoomIn'     	 	=>  __('zoomIn', 'widget-options'),
							'zoomInDown'  	 	=>  __('zoomInDown', 'widget-options'),
							'zoomInLeft' 		=>  __('zoomInLeft', 'widget-options'),
							'zoomInRight'      	=>  __('zoomInRight', 'widget-options'),
							'zoomInUp'   	 	=>  __('zoomInUp', 'widget-options'),

							'hinge'     	 	=>  __('hinge', 'widget-options'),
							'rollIn'     	 	=>  __('rollIn', 'widget-options'),
						),
						'description'       => __('The type of animation for this event.', 'widget-options'),
					);

					$anim_fld['widgetopts_animation_event'] = array(
						'type'          => 'select',
						'label' 		=> __('Animation Event', 'widget-options'),
						'options'       => array(
							'enters'      	=> __('Element Enters Screen', 'widget-options'),
							'onScreen'      => __('Element In Screen', 'widget-options'),
							'pageLoad'      => __('Page Load', 'widget-options')
						),
						'description'       => __('The event that triggers the animation.', 'widget-options'),
					);

					$anim_fld['widgetopts_animation_speed'] = array(
						'type'				=> 'text',
						'label'				=> __('Animation Speed', 'widget-options'),
						'description'       => __('How many <strong>seconds</strong> the incoming animation should lasts. ', 'widget-options'),
					);

					$anim_fld['widgetopts_animation_offset'] = array(
						'type'				=> 'text',
						'label'				=> __('Screen Offset', 'widget-options'),
						'description'       => __('How many <strong>pixels</strong> above the bottom of the screen must the widget be before animating. ', 'widget-options'),
					);

					$anim_fld['widgetopts_animation_hidden'] = array(
						'type'				=> 'select',
						'label'				=> __('Hide Before Animation ', 'widget-options'),
						'options'       => array(
							'no'      	=> __('Disabled', 'widget-options'),
							'yes'      	=> __('Enable', 'widget-options')
						),
						'description'       => __('Hide widget before animating.', 'widget-options'),
					);

					$anim_fld['widgetopts_animation_delay'] = array(
						'type'				=> 'text',
						'label'				=> __('Animation Delay', 'widget-options'),
						'description'       => __('Number of <strong>seconds</strong> after the event to start the animation.', 'widget-options'),
					);

					$sections['widgetopts-animation'] = array(
						'fields' 	  =>  $anim_fld
					);
				}

				if (isset($widget_options['styling']) && 'activate' == $widget_options['styling']) {
					$styling_fld = array();

					$styling_fld['widgetopts_styling_bgimage'] = array(
						'type'				=> 'photo',
						'label'				=> __('Background Image', 'widget-options'),
					);

					$styling_fld['widgetopts_styling_bgcolor'] = array(
						'type'				=> 'color',
						'label'				=> __('Background Color', 'widget-options'),
						'default'		  => '',
						'show_reset'	  => true,
					);

					$styling_fld['widgetopts_styling_hover_bgcolor'] = array(
						'type'				=> 'color',
						'label'				=> __('Hover Background Color', 'widget-options'),
						'default'		  => '',
						'show_reset'	  => true,
					);

					$styling_fld['widgetopts_styling_heading'] = array(
						'type'				=> 'color',
						'label'				=> __('Headings Color', 'widget-options'),
						'default'		  => '',
						'show_reset'	  => true,
					);

					$styling_fld['widgetopts_styling_text'] = array(
						'type'				=> 'color',
						'label'				=> __('Text Color', 'widget-options'),
						'default'		  => '',
						'show_reset'	  => true,
					);

					$styling_fld['widgetopts_styling_links'] = array(
						'type'				=> 'color',
						'label'				=> __('Links Color', 'widget-options'),
						'default'		  => '',
						'show_reset'	  => true,
					);

					$styling_fld['widgetopts_styling_links_hover'] = array(
						'type'				=> 'color',
						'label'				=> __('Links Hover Color', 'widget-options'),
						'default'		  => '',
						'show_reset'	  => true,
					);

					$styling_fld['widgetopts_styling_border'] = array(
						'type'				=> 'color',
						'label'				=> __('Border Color', 'widget-options'),
						'default'		  => '',
						'show_reset'	  => true,
					);

					$styling_fld['widgetopts_styling_border_style'] = array(
						'type'          => 'select',
						'label' 		=> __('Border Style', 'widget-options'),
						'options'       => array(
							''      	  => __('Default', 'widget-options'),
							'solid'       => __('Solid', 'widget-options'),
							'dashed'      => __('Dashed', 'widget-options'),
							'dotted'      => __('Dotted', 'widget-options'),
							'double'      => __('Double', 'widget-options'),
						),
					);

					$styling_fld['widgetopts_styling_border_width'] = array(
						'type'				=> 'text',
						'label'				=> __('Border Width', 'widget-options'),
						'description'	  	=> 'px',
						'maxlength'	  		=> '3',
						'size'		  		=> '5',
					);

					$sections['widgetopts-styling'] = array(
						'fields' 	  =>  $styling_fld
					);
				}

				if (isset($widget_options['links']) && 'activate' == $widget_options['links']) {
					$links_fld = array();

					$links_fld['widgetopts_link_url'] = array(
						'type'				=> 'link',
						'label'				=> __('Link', 'widget-options'),
						'preview'         => array(
							'type'            => 'none',
						),
						'connections'     => array('url'),
					);

					$links_fld['widgetopts_link_target'] = array(
						'type'          => 'select',
						'label' 		=> __('Link Target', 'widget-options'),
						'default'       => '_self',
						'options'       => array(
							'_self'      	  => __('Same Window', 'widget-options'),
							'_blank'       => __('New Window', 'widget-options'),
						),
					);

					$links_fld['widgetopts_link_nofollow'] = array(
						'type'          => 'select',
						'label' 		=> __('Link No Follow', 'widget-options'),
						'default'       => 'no',
						'options'       => array(
							'yes'      	  => __('Yes', 'widget-options'),
							'no'       => __('No', 'widget-options'),
						),
					);

					$links_fld['widgetopts_link_hide_url'] = array(
						'type'          => 'select',
						'label' 		=> __('Show or Hide', 'widget-options'),
						'options'       => array(
							'hide'      => __('Hide on Target URL', 'widget-options'),
							'show'      => __('Show on Target URL', 'widget-options')
						)
					);

					$links_fld['widgetopts_link_target_url'] = array(
						'type'				=> 'textarea',
						'label'				=> __('Target URL', 'widget-options'),
						'description'   	=> __('Enter one URL per line. You can use * as wildcard url, for example <strong>sample-page/*</strong> to target all subpages of "sample-page"', 'fl-builder'),
						'rows'          	=> '6'
					);

					$sections['widgetopts-links'] = array(
						'fields' 	  =>  $links_fld
					);
				}

				if ((isset($widget_options['logic']) && 'activate' == $widget_options['logic']) || (isset($widget_options['fixed']) && 'activate' == $widget_options['fixed']) || (isset($widget_options['acf']) && 'activate' == $widget_options['acf'])) {
					$settings_fld = array();

					if (isset($widget_options['fixed']) && 'activate' == $widget_options['fixed']) {
						$settings_fld['widgetopts_settings_fixed'] = array(
							'type'				=> 'select',
							'label'				=> __('Fixed Widget', 'widget-options'),
							'options'       => array(
								'no'      	=> __('Disabled', 'widget-options'),
								'yes'      	=> __('Enable', 'widget-options')
							),
							'description'       => __('Enable to fixed widget on scroll', 'widget-options'),
						);
					}

					if (isset($widget_options['logic']) && 'activate' == $widget_options['logic']) {
						// Get available snippets for dropdown
						$snippet_options = array('' => __('— No Logic (Always Show) —', 'widget-options'));
						if (class_exists('WidgetOpts_Snippets_CPT')) {
							$snippets = WidgetOpts_Snippets_CPT::get_all_snippets();
							foreach ($snippets as $snippet) {
								$snippet_options[$snippet['id']] = $snippet['title'];
							}
						}

						$snippet_description = __('Select a logic snippet to control when this widget is displayed.', 'widget-options');
						if (current_user_can('manage_options')) {
							$snippet_description .= '<br><a href="' . admin_url('edit.php?post_type=widgetopts_snippet') . '" target="_blank" style="display:inline-block;margin-top:5px;padding:3px 8px;background:#0073aa;color:#fff;text-decoration:none;border-radius:3px;font-size:11px;">' . __('Manage Snippets', 'widget-options') . ' →</a>';
						}

						// Hidden field to ensure BB includes legacy logic value in settings
						$settings_fld['widgetopts_settings_logic'] = array(
							'type'    => 'hidden',
							'default' => '',
						);

						// Hidden flag: set to '1' when user clicks Clear button
						$settings_fld['widgetopts_logic_cleared'] = array(
							'type'    => 'hidden',
							'default' => '',
						);

						// Widget Options version stamp
						$settings_fld['widgetopts_wopt_version'] = array(
							'type'    => 'hidden',
							'default' => '',
						);

						// Per-node legacy logic display (shown only when this node has legacy code)
						$settings_fld['widgetopts_legacy_logic_display'] = array(
							'type' => 'widgetopts-beaver-legacy',
						);

						$settings_fld['widgetopts_logic_snippet_id'] = array(
							'type'          => 'widgetopts-select2',
							'label' 		=> __('Display Logic Snippet', 'widget-options'),
							'options'       => $snippet_options,
							'description' 	=> $snippet_description
						);
					}

					//ACF
					if (isset($widget_options['acf']) && 'activate' == $widget_options['acf']) {
						$fields = array('' => __('Select Field', 'widget-options'));
						if (function_exists('acf_get_field_groups')) {
							$groups = acf_get_field_groups();
							if (is_array($groups)) {
								foreach ($groups as $group) {
									$fields_group = acf_get_fields($group);
									if (!empty($fields_group)) {
										foreach ($fields_group as $k => $fg) {
											$fields[$fg['key']] = $fg['label'];
										}
									}
								}
							}
						} else {
							$groups = apply_filters('acf/get_field_groups', array());
							if (is_array($groups)) {
								foreach ($groups as $group) {
									$fields_group = apply_filters('acf/field_group/get_fields', array(), $group['id']);
									if (!empty($fields_group)) {
										foreach ($fields_group as $k => $fg) {
											$fields[$fg['key']] = $fg['label'];
										}
									}
								}
							}
						}
						$settings_fld['widgetopts_acf_visibility'] = array(
							'type'          => 'select',
							'label' 		=> __('Show or Hide based on Advanced Custom Field', 'widget-options'),
							'options'       => array(
								'hide'      => __('Hide when Condition\'s Met', 'widget-options'),
								'show'      => __('Show when Condition\'s Met', 'widget-options')
							)
						);
						$settings_fld['widgetopts_acf_field'] = array(
							'type'				=> 'select',
							'label'				=> __('Select ACF Field', 'widget-options'),
							'options'			=> $fields
						);
						$settings_fld['widgetopts_acf_condition'] = array(
							'type'          => 'select',
							'label' 		=> __('Select Condition', 'widget-options'),
							'options'       => array(
								''      	 =>  __('Select Condition', 'widget-options'),
								'equal'      =>  __('Is Equal To', 'widget-options'),
								'not_equal'  =>  __('Is Not Equal To', 'widget-options'),
								'contains'   =>  __('Contains', 'widget-options'),
								'not_contains'   =>  __('Does Not Contain', 'widget-options'),
								'empty'      =>  __('Is Empty', 'widget-options'),
								'not_empty'  =>  __('Is Not Empty', 'widget-options')
							)
						);
						$settings_fld['widgetopts_acf_value'] = array(
							'type'          => 'textarea',
							'label' 		=> __('Conditional Value', 'widget-options'),
							'description' 	=> __('Add your Conditional Value here if you selected Equal to, Not Equal To or Contains on the selection above.', 'widget-options')
						);
					}

					$sections['widgetopts-settings'] = array(
						'fields' 	  =>  $settings_fld
					);
				}

				$form['widgetopts'] = array(
					'title' 	=>	__('Widget Options', 'widget-options'),
					'sections' 	=>  $sections
				);
			}

			return $form;
		}

		function fl_widgetopts_beaver_tabnav($name, $value, $field, $settings)
		{
			global $widget_options;
?>
			<div class="fl-builder-widgetopts-tab">
				<?php if (isset($widget_options['visibility']) && 'activate' == $widget_options['visibility']) { ?>
					<a onclick="widgetoptsBeaverModule.navClick(event)" href="#fl-builder-settings-section-widgetopts-visibility" class="widgetopts-s-active"><span class="dashicons dashicons-visibility"></span>
						<span><?php _e('Visibility', 'widget-options'); ?></span>
					</a>
				<?php } ?>

				<?php if (isset($widget_options['alignment']) && 'activate' == $widget_options['alignment']) { ?>
					<a onclick="widgetoptsBeaverModule.navClick(event)" href="#fl-builder-settings-section-widgetopts-alignment"><span class="dashicons dashicons-editor-aligncenter"></span>
						<span><?php _e('Alignment', 'widget-options'); ?></span>
					</a>
				<?php } ?>

				<?php if (isset($widget_options['roles']) && 'activate' == $widget_options['roles']) { ?>
					<a onclick="widgetoptsBeaverModule.navClick(event)" href="#fl-builder-settings-section-widgetopts-roles"><span class="dashicons dashicons-admin-users"></span>
						<span><?php _e('Roles', 'widget-options'); ?></span>
					</a>
				<?php } ?>
				<?php if (isset($widget_options['dates']) && 'activate' == $widget_options['dates']) { ?>
					<a onclick="widgetoptsBeaverModule.navClick(event)" href="#fl-builder-settings-section-widgetopts-dates"><span class="dashicons dashicons-calendar-alt"></span>
						<span><?php _e('Dates', 'widget-options'); ?></span>
					</a>
				<?php } ?>
				<?php if (isset($widget_options['animation']) && 'activate' == $widget_options['animation']) { ?>
					<a onclick="widgetoptsBeaverModule.navClick(event)" href="#fl-builder-settings-section-widgetopts-animation"><span class="dashicons dashicons-image-rotate-right"></span>
						<span><?php _e('Animations', 'widget-options'); ?></span>
					</a>
				<?php } ?>
				<?php if (isset($widget_options['styling']) && 'activate' == $widget_options['styling']) { ?>
					<a onclick="widgetoptsBeaverModule.navClick(event)" href="#fl-builder-settings-section-widgetopts-styling"><span class="dashicons dashicons-art"></span>
						<span><?php _e('Styling', 'widget-options'); ?></span>
					</a>
				<?php } ?>
				<?php if (isset($widget_options['links']) && 'activate' == $widget_options['links']) { ?>
					<a onclick="widgetoptsBeaverModule.navClick(event)" href="#fl-builder-settings-section-widgetopts-links"><span class="dashicons dashicons-admin-links"></span>
						<span><?php _e('Link', 'widget-options'); ?></span>
					</a>
				<?php } ?>
				<?php if ((isset($widget_options['logic']) && 'activate' == $widget_options['logic']) || (isset($widget_options['fixed']) && 'activate' == $widget_options['fixed']) || (isset($widget_options['acf']) && 'activate' == $widget_options['acf'])) { ?>
					<a onclick="widgetoptsBeaverModule.navClick(event)" href="#fl-builder-settings-section-widgetopts-settings"><span class="dashicons dashicons-admin-generic"></span>
						<span><?php _e('Settings', 'widget-options'); ?></span>
					</a>
				<?php } ?>
			</div>
		<?php }

		function fl_widgetopts_beaver_scripts()
		{
			if (class_exists('FLBuilderModel') && FLBuilderModel::is_builder_active()) {
				$js_dir  = WIDGETOPTS_PLUGIN_URL . 'assets/js/';
				$css_dir = WIDGETOPTS_PLUGIN_URL . 'assets/css/';

				// Use minified libraries if SCRIPT_DEBUG is turned off
				$suffix  = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

				wp_enqueue_style('widgetopts-beaver-css', $css_dir . 'beaver-widgetopts.css', array(), WIDGETOPTS_VERSION);
				wp_enqueue_style('widgetopts-beaver-select2-css', $css_dir . 'select2.min.css', array(), WIDGETOPTS_VERSION);
				wp_enqueue_style('widgetopts-jquery-ui', $css_dir . '/jqueryui/1.11.4/themes/ui-lightness/jquery-ui.css', array(), WIDGETOPTS_VERSION);
				wp_enqueue_style('jquery-ui');

				wp_enqueue_script(
					'beaver-widgetopts',
					$js_dir . 'jquery.widgetopts.beaver' . $suffix . '.js',
					array('jquery', 'jquery-ui-datepicker'),
					WIDGETOPTS_VERSION,
					true
				);
				wp_enqueue_script(
					'beaver-widgetopts-select2',
					$js_dir . 'select2.min.js',
					array('jquery', 'beaver-widgetopts'),
					WIDGETOPTS_VERSION,
					true
				);
				wp_enqueue_script(
					'beaver-widgetopts-s2',
					$js_dir . 'select2-settings' . $suffix . '.js',
					array('jquery', 'beaver-widgetopts'),
					WIDGETOPTS_VERSION,
					true
				);
			}
		}

		function fl_widgetopts_beaver_get_fld_options($settings, $field, $options = array())
		{
			if (!is_object($settings) && !is_array($settings)) return $options;

			foreach ($settings as $key => $val) {
				if ($key === $field) {
					if (is_array($val)) {
						foreach ($val as $v) {
							$options[$v] = $v;
						}
					} else {
						$options[$val] = $val;
					}
				} else {
					if (is_array($val) || is_object($val)) {
						$options = $this->fl_widgetopts_beaver_get_fld_options($val, $field, $options);
					}
				}
			}

			return array_unique($options);
		}

		function fl_widgetopts_beaver_select2($name, $value, $field, $settings)
		{
			$options = ($field['options']) ? $field['options'] : array();

			if (isset($field['options_from_field'])) {
				$options_field = $field['options_from_field'];
				$post_data = FLBuilderModel::get_post_data();
				$parent_settings = $post_data['node_settings'];

				$options = $this->fl_widgetopts_beaver_get_fld_options($parent_settings, $options_field, $options);
			}

			// Create attributes
			$attributes = '';
			if (isset($field['attributes']) && is_array($field['attributes'])) {
				foreach ($field['attributes'] as $key => $val) {
					$attributes .= $key . '="' . $val . '" ';
				}
			}

			if (!empty($options) && $value && is_array($value)) {
				uksort($options, function ($key1, $key2) use ($value) {
					return array_search($key1, $value) <=> array_search($key2, $value);
				});
			}
			if (!isset($field['class'])) {
				$field['class'] = '';
			}

			// Show the select field
		?>
			<select name="<?php echo $name;
							if (isset($field['multi-select'])) echo '[]'; ?>" class="widgetopts-select2 <?php echo $field['class']; ?>" <?php if (isset($field['multi-select'])) echo 'multiple ';
																																		echo $attributes; ?> placeholder="<?php _e('Click to search or select', 'widget-options'); ?>">
				<option value=""><?php echo isset($options['']) ? esc_html(is_array($options['']) ? $options['']['label'] : $options['']) : ''; ?></option>
				<?php
				foreach ($options as $option_key => $option_val) :

					if ($option_key === '') {
						continue;
					}

					if (is_array($option_val) && isset($option_val['premium']) && $option_val['premium'] && true === FL_BUILDER_LITE) {
						continue;
					}

					$label = is_array($option_val) ? $option_val['label'] : $option_val;

					if (is_array($value) && in_array($option_key, $value)) {
						$selected = ' selected="selected"';
					} else if (!is_array($value) && selected($value, $option_key, true)) {
						$selected = ' selected="selected"';
					} else {
						$selected = '';
					}

				?>
					<option value="<?php echo $option_key; ?>" <?php echo $selected; ?>><?php echo $label; ?></option>
				<?php endforeach; ?>
			</select>
			<?php }

		/**
		 * Custom control: show legacy logic readonly textarea + warning per-node
		 */
		function fl_widgetopts_beaver_legacy_control($name, $value, $field, $settings)
		{
			// Don't show if snippet_id is already set (migration done)
			if (!empty($settings->widgetopts_logic_snippet_id)) {
				return;
			}

			// Only render if this specific node has legacy logic code
			$legacy_code = '';
			if (isset($settings->widgetopts_settings_logic) && !empty($settings->widgetopts_settings_logic)) {
				$legacy_code = $settings->widgetopts_settings_logic;
			} elseif (isset($settings->widgetopts_settings_logic_backup) && !empty($settings->widgetopts_settings_logic_backup)) {
				$legacy_code = $settings->widgetopts_settings_logic_backup;
			}

			if (empty($legacy_code)) {
				return;
			}

			$migration_url = admin_url('options-general.php?page=widgetopts_migration');
			?>
			<div class="widgetopts-legacy-logic-wrap" style="margin-bottom:15px;">
				<p><strong><?php _e('Legacy Display Logic Code', 'widget-options'); ?></strong></p>
				<textarea readonly="readonly" rows="4" style="width:100%;background:#f9f2f4;color:#c7254e;font-family:monospace;font-size:12px;cursor:not-allowed;resize:vertical;"><?php echo esc_textarea($legacy_code); ?></textarea>
				<p style="margin-top:8px;padding:8px 12px;background:#fff3cd;border-left:4px solid #ffc107;color:#856404;font-size:12px;">
					<?php _e('This module uses legacy inline display logic. Please migrate it to the new snippet-based system.', 'widget-options'); ?>
					<?php if (current_user_can(WIDGETOPTS_MIGRATION_PERMISSIONS)) : ?>
						<br><a href="<?php echo esc_url($migration_url); ?>" target="_blank"><?php _e('Go to Migration Page', 'widget-options'); ?> &rarr;</a>
					<?php endif; ?>
				</p>
				<button type="button" class="fl-builder-button fl-builder-button-primary widgetopts-bb-clear-legacy" style="margin-top:8px;padding:5px 15px;background:#dc3545;color:#fff;border:none;border-radius:3px;cursor:pointer;font-size:12px;" onclick="(function(btn){var form=jQuery(btn).closest('form,.fl-builder-settings');if(form.length){form.find('input[name=widgetopts_settings_logic]').val('');form.find('input[name=widgetopts_logic_cleared]').val('1');jQuery(btn).closest('.widgetopts-legacy-logic-wrap').slideUp();form.find('#fl-field-widgetopts_logic_snippet_id').show();}})( this)">
					<?php _e('Clear Legacy Logic', 'widget-options'); ?>
				</button>
			</div>
			<script>
			jQuery(function(){
				var wrap = jQuery('.widgetopts-legacy-logic-wrap');
				if (wrap.length && wrap.is(':visible')) {
					wrap.closest('form,.fl-builder-settings').find('#fl-field-widgetopts_logic_snippet_id').hide();
				}
			});
			</script>
			<?php
		}

		function widgetopts_beaver_is_node_visible($is_visible, $node)
		{

			//return if editing
			if (class_exists('FLBuilderModel') && FLBuilderModel::is_builder_active()) {
				return $is_visible;
			}

			global $widget_options;

			$settings 	= $node->settings;
			$hidden     = false;
			$visibility_opts    = isset($settings->widgetopts_visibility_show) ? $settings->widgetopts_visibility_show : 'hide';

			$tax_opts   = (isset($widget_options['settings']) && isset($widget_options['settings']['taxonomies_keys'])) ? $widget_options['settings']['taxonomies_keys'] : array();
			$is_misc    = ('activate' == $widget_options['visibility'] && isset($widget_options['settings']['visibility']) && isset($widget_options['settings']['visibility']['misc'])) ? true : false;
			$is_types   = ('activate' == $widget_options['visibility'] && isset($widget_options['settings']['visibility']) && isset($widget_options['settings']['visibility']['post_type'])) ? true : false;
			$is_tax     = ('activate' == $widget_options['visibility'] && isset($widget_options['settings']['visibility']) && isset($widget_options['settings']['visibility']['taxonomies'])) ? true : false;
			$is_inherit = ('activate' == $widget_options['visibility'] && isset($widget_options['settings']['visibility']) && isset($widget_options['settings']['visibility']['inherit'])) ? true : false;

			// echo '<pre>';
			// print_r( $settings );
			// echo '</pre>';

			//roles
			if (isset($widget_options['roles']) && 'activate' == $widget_options['roles']) {
				global $current_user;

				$roles	=  $settings->widgetopts_visibility_roles ?? [];

				// unset($roles['options']);
				$current_user->role = (isset($current_user->caps) && !empty($current_user->caps)) ? array_keys($current_user->caps) : array('guests');

				$roles_opts = isset($settings->widgetopts_roles_show) ? $settings->widgetopts_roles_show : 'hide';

				if ($roles_opts == 'hide' && is_array($roles) && in_array($current_user->role[0], $roles)) {
					$hidden = true; //hide if exists on hidden roles
				} elseif ($roles_opts == 'show' && is_array($roles) && !in_array($current_user->role[0], $roles)) {
					$hidden = true; //hide if doesn't exists on visible roles
				}

				//do return to bypass other tabs conditions
				$hidden = apply_filters('widgetopts_elementor_visibility_roles', $hidden);
				if ($hidden) {
					return false;
				}
			}

			//pages
			if ($is_misc && ((is_home() && is_front_page()) || is_front_page())) {
				if (isset($settings->widgetopts_visibility_misc) && is_array($settings->widgetopts_visibility_misc) && in_array('home', $settings->widgetopts_visibility_misc) && $visibility_opts == 'hide') {
					$hidden = true; //hide if checked on hidden pages
				} elseif ((!isset($settings->widgetopts_visibility_misc) || (isset($settings->widgetopts_visibility_misc) && is_array($settings->widgetopts_visibility_misc) && !in_array('home', $settings->widgetopts_visibility_misc))) && $visibility_opts == 'show') {
					$hidden = true; //hide if not checked on visible pages
				}

				//do return to bypass other conditions
				$hidden = apply_filters('widgetopts_beaver_visibility_home', $hidden);
				if ($hidden) {
					return false;
				}
			} elseif ($is_misc && is_home()) {
				//NOT CHECKED YET
				if (isset($settings->widgetopts_visibility_misc) && is_array($settings->widgetopts_visibility_misc) && in_array('blog', $settings->widgetopts_visibility_misc) && $visibility_opts == 'hide') {
					$hidden = true; //hide if checked on hidden pages
				} elseif ((!isset($settings->widgetopts_visibility_misc) || (isset($settings->widgetopts_visibility_misc) && is_array($settings->widgetopts_visibility_misc) && !in_array('blog', $settings->widgetopts_visibility_misc))) && $visibility_opts == 'show') {
					$hidden = true; //hide if not checked on visible pages
				}

				//do return to bypass other conditions
				$hidden = apply_filters('widgetopts_beaver_visibility_blog', $hidden);
				if ($hidden) {
					return false;
				}
			} elseif ($is_tax && is_tag()) {

				if (!isset($settings->widgetopts_visibility_taxonomies) || (isset($settings->widgetopts_visibility_taxonomies) && !is_array($settings->widgetopts_visibility_taxonomies))) {
					$settings->widgetopts_visibility_taxonomies = array();
				}

				if (in_array('post_tag', $settings->widgetopts_visibility_taxonomies) && $visibility_opts == 'hide') {
					$hidden = true; //hide to all tags
				} elseif (in_array('post_tag', $settings->widgetopts_visibility_taxonomies) && $visibility_opts == 'show') {
					$hidden = false; //hide to all tags
				}
				//
				// //do return to bypass other conditions
				$hidden = apply_filters('widgetopts_beaver_visibility_tags', $hidden);
				if ($hidden) {
					return false;
				}
			} elseif ($is_tax && is_tax()) {
				$term = get_queried_object();
				$term_lists = array();
				//taxonomies page
				if (!isset($settings->widgetopts_visibility_taxonomies) || (isset($settings->widgetopts_visibility_taxonomies) && !is_array($settings->widgetopts_visibility_taxonomies))) {
					$settings->widgetopts_visibility_taxonomies = array();
				}

				if (isset($settings->{'widgetopts_visibility_tax_' . $term->taxonomy})) {
					$term_lists = $settings->{'widgetopts_visibility_tax_' . $term->taxonomy};
				}
				// print_r( $widget_options['settings']['visibility']['tax_terms'] );

				if (is_array($term_lists) && $visibility_opts == 'hide' && in_array($term->term_id, $term_lists)) {
					$hidden = true; //hide if exists on hidden pages
				} elseif (is_array($term_lists) && $visibility_opts == 'show' && !in_array($term->term_id, $term_lists)) {
					$hidden = true; //hide if doesn't exists on visible pages
				} elseif (in_array($term->taxonomy, $settings->widgetopts_visibility_taxonomies) && $visibility_opts == 'hide') {
					$hidden = true; //hide to all tags
				} elseif (in_array($term->taxonomy, $settings->widgetopts_visibility_taxonomies) && $visibility_opts == 'show') {
					$hidden = false; //hide to all tags
				} elseif (in_array($term->taxonomy, $settings->widgetopts_visibility_taxonomies) && $visibility_opts == 'hide' && in_array($term->term_id, $term_lists)) {
					$hidden = true; //hide if exists on hidden pages
				} elseif (in_array($term->taxonomy, $settings->widgetopts_visibility_taxonomies) && $visibility_opts == 'show' && in_array($term->term_id, $term_lists)) {
					$hidden = false; //hide if doesn't exists on visible pages
				}

				// elseif( empty( $settings->widgetopts_visibility_taxonomies ) && $visibility_opts == 'show' ){
				//     $hidden = true; //hide if checked on hidden pages
				// }

				// if( in_array( $term->taxonomy, $settings->widgetopts_visibility_taxonomies ) && $visibility_opts == 'hide' ){
				//     $hidden = true; //hide to all tags
				// }elseif( !in_array( $term->taxonomy, $settings->widgetopts_visibility_taxonomies ) && $visibility_opts == 'show' ){
				//     $hidden = true; //hide to all tags
				// }

				//do return to bypass other conditions
				$hidden = apply_filters('widgetopts_beaver_visibility_taxonomies', $hidden);
				if ($hidden) {
					return false;
				}
			} elseif ($is_misc && is_archive()) {
				//archives page
				if (isset($settings->widgetopts_visibility_misc) && is_array($settings->widgetopts_visibility_misc) && in_array('archives', $settings->widgetopts_visibility_misc) && $visibility_opts == 'hide') {
					$hidden = true; //hide if checked on hidden pages
				} elseif ((!isset($settings->widgetopts_visibility_misc) || (isset($settings->widgetopts_visibility_misc) && is_array($settings->widgetopts_visibility_misc) && !in_array('archives', $settings->widgetopts_visibility_misc))) && $visibility_opts == 'show') {
					$hidden = true; //hide if not checked on visible pages
				}

				//do return to bypass other conditions
				$hidden = apply_filters('widgetopts_beaver_visibility_archives', $hidden);
				if ($hidden) {
					return false;
				}
			} elseif ($is_misc && is_404()) {
				//404 page
				if (isset($settings->widgetopts_visibility_misc) && is_array($settings->widgetopts_visibility_misc) && in_array('404', $settings->widgetopts_visibility_misc) && $visibility_opts == 'hide') {
					$hidden = true; //hide if checked on hidden pages
				} elseif ((!isset($settings->widgetopts_visibility_misc) || (isset($settings->widgetopts_visibility_misc) && is_array($settings->widgetopts_visibility_misc) && !in_array('404', $settings->widgetopts_visibility_misc))) && $visibility_opts == 'show') {
					$hidden = true; //hide if not checked on visible pages
				}

				//do return to bypass other conditions
				$hidden = apply_filters('widgetopts_beaver_visibility_404', $hidden);
				if ($hidden) {
					return false;
				}
			} elseif ($is_misc && is_search()) {
				if (isset($settings->widgetopts_visibility_misc) && is_array($settings->widgetopts_visibility_misc) && in_array('search', $settings->widgetopts_visibility_misc) && $visibility_opts == 'hide') {
					$hidden = true; //hide if checked on hidden pages
				} elseif ((!isset($settings->widgetopts_visibility_misc) || (isset($settings->widgetopts_visibility_misc) && is_array($settings->widgetopts_visibility_misc) && !in_array('search', $settings->widgetopts_visibility_misc))) && $visibility_opts == 'show') {
					$hidden = true;
				}

				//do return to bypass other conditions
				$hidden = apply_filters('widgetopts_beaver_visibility_search', $hidden);
				if ($hidden) {
					return false;
				}
			} elseif (is_single() && !is_page()) {
				global $wp_query;
				$post = $wp_query->post;

				if (!isset($settings->widgetopts_visibility_types) || ($is_types && !isset($settings->widgetopts_visibility_types)) || !is_array($settings->widgetopts_visibility_types)) {
					$settings->widgetopts_visibility_types = array();
				}

				if ($visibility_opts == 'hide' && in_array($post->post_type, $settings->widgetopts_visibility_types)) {
					$hidden = true; //hide if exists on hidden pages
				} elseif ($visibility_opts == 'show' && !in_array($post->post_type, $settings->widgetopts_visibility_types)) {
					$hidden = true; //hide if doesn't exists on visible pages
				}

				// do return to bypass other conditions
				$hidden = apply_filters('widgetopts_beaver_visibility_single', $hidden);


				// $taxonomy_names  = get_post_taxonomies( $post->ID );
				// $array_intersect = array_intersect( $tax_opts, $taxonomy_names );

				if (!isset($settings->widgetopts_visibility_tax_category)) {
					$settings->widgetopts_visibility_tax_category = array();
				}

				if (isset($settings->widgetopts_visibility_tax_category) && !empty($settings->widgetopts_visibility_tax_category)) {
					$cats	= wp_get_post_categories($post->ID);

					if (is_array($cats) && !empty($cats)) {
						$checked_cats   = $settings->widgetopts_visibility_tax_category;
						$intersect      = array_intersect($cats, $checked_cats);
						if (!empty($intersect) && $visibility_opts == 'hide') {
							$hidden = true;
						} elseif (!empty($intersect) && $visibility_opts == 'show') {
							$hidden = false;
						}

						$hidden = apply_filters('widgetopts_beaver_visibility_single_category', $hidden);
					}
				}

				if ($hidden) {
					return false;
				}
			} elseif ($is_types && is_page()) {
				global $wp_query;

				$post = $wp_query->post;

				//do post type condition first
				if (isset($settings->widgetopts_visibility_types) && is_array($settings->widgetopts_visibility_types) && in_array('page', $settings->widgetopts_visibility_types)) {

					if (!is_array($settings->widgetopts_visibility_types)) {
						$settings->widgetopts_visibility_types = array();
					}

					if ($visibility_opts == 'hide' && in_array('page', $settings->widgetopts_visibility_types)) {
						$hidden = true; //hide if exists on hidden pages
					} elseif ($visibility_opts == 'show' && !in_array('page', $settings->widgetopts_visibility_types)) {
						$hidden = true; //hide if doesn't exists on visible pages
					}
				} else {
					// print_r( $settings['widgetopts_pages'] );
					//do per pages condition
					if (!isset($settings->widgetopts_visibility_pages) || (isset($settings->widgetopts_visibility_pages) && !is_array($settings->widgetopts_visibility_pages))) {
						$settings->widgetopts_visibility_pages = array();
					}

					if ($visibility_opts == 'hide' && in_array($post->ID, $settings->widgetopts_visibility_pages)) {
						$hidden = true; //hide if exists on hidden pages
					} elseif ($visibility_opts == 'show' && !in_array($post->ID, $settings->widgetopts_visibility_pages)) {
						$hidden = true; //hide if doesn't exists on visible pages
					}
				}

				// //do return to bypass other conditions
				$hidden = apply_filters('widgetopts_beaver_visibility_page', $hidden);
				if ($hidden) {
					return false;
				}
			}

			//days & date
			if ('activate' == $widget_options['dates'] && isset($widget_options['settings']['dates'])) {
				if (isset($widget_options['settings']['dates']) && isset($widget_options['settings']['dates']['days'])) {
					//days
					$today      = current_time('l', 1);
					$today      = strtolower($today);
					$days       = isset($settings->widgetopts_visibility_days_day) ? $settings->widgetopts_visibility_days_day : array();
					$days_opts  = isset($settings->widgetopts_visibility_days_show) ? $settings->widgetopts_visibility_days_show : 'hide';

					if (is_array($days) && $days_opts == 'hide' && in_array($today, $days)) {
						$hidden = true; //hide if exists on hidden days
					} elseif (is_array($days) && $days_opts == 'show' && !in_array($today, $days)) {
						$hidden = true; //hide if doesn't exists on visible days
					}

					//do return to bypass other conditions
					$hidden = apply_filters('widgetopts_beaver_visibility_days', $hidden);
					if ($hidden) {
						return false;
					}
					//end days
				}

				if (isset($widget_options['settings']['dates']) && isset($widget_options['settings']['dates']['date_range'])) {
					//date
					$todate         = date('Y-m-d H:i');
					// $dates_on       = isset( $settings['widgetopts_dates'] ) ? $settings['widgetopts_dates'] : '';;
					$dates_opts     = isset($settings->widgetopts_visibility_dates_show) ? $settings->widgetopts_visibility_dates_show : 'hide';

					if (isset($settings->widgetopts_visibility_dates_to) && !empty($settings->widgetopts_visibility_dates_to)) {
						if (!isset($settings->widgetopts_visibility_dates_from)) {
							$settings->widgetopts_visibility_dates_from = date('Y-m-d H:i', strtotime('-3 day'));
						}

						$valid_range = widgetopts_date_in_range($settings->widgetopts_visibility_dates_from, $settings->widgetopts_visibility_dates_to, $todate);

						if ($dates_opts == 'hide' && $valid_range) {
							$hidden = true; //hide if exists on hidden days
						} elseif ($dates_opts == 'show' && !$valid_range) {
							$hidden = true; //hide if doesn't exists on visible days
						}

						//do return to bypass other conditions
						$hidden = apply_filters('widgetopts_beaver_visibility_dates', $hidden);
						if ($hidden) {
							return false;
						}
					}
					//end dates
				}
			}

			//target URL
			//widgetopts_link_target_url
			if (isset($widget_options['urls']) && 'activate' == $widget_options['urls']) {
				if (isset($settings->widgetopts_link_target_url) && !empty($settings->widgetopts_link_target_url)) {
					$is_url = '';
					if (isset($settings->widgetopts_link_hide_url)) {
						$is_url = $settings->widgetopts_link_hide_url;
					}
					if (function_exists('widgetopts_checkurl')) {
						$checked_urls = widgetopts_checkurl($settings->widgetopts_link_target_url, "\n");

						if (!empty($is_url)) {
							if ('hide' == $is_url && $checked_urls) {
								$hidden = true;
							}
							if ('show' == $is_url && !$checked_urls) {
								$hidden = true;
							}

							//do return to bypass other conditions
							$hidden = apply_filters('widgetopts_beaver_visibility_urls', $hidden);
							if ($hidden) {
								return false;
							}
						}
					}
				}
			}

			//ACF
			if (isset($widget_options['acf']) && 'activate' == $widget_options['acf']) {
				if (isset($settings->widgetopts_acf_field) && !empty($settings->widgetopts_acf_field)) {
					$acf = get_field_object($settings->widgetopts_acf_field);
					if ($acf && is_array($acf)) {
						$acf_visibility    = isset($settings->widgetopts_acf_visibility) ? $settings->widgetopts_acf_visibility : 'hide';
						//handle repeater fields
						if (isset($acf['value'])) {
							if (is_array($acf['value'])) {
								$acf['value'] = implode(', ', array_map(function ($acf_array_value) {
									if (!is_array($acf_array_value)) return $acf_array_value;
									$acf_implode = implode(',', array_filter($acf_array_value));
									return $acf_implode;
								}, $acf['value']));
							}
						}

						switch ($settings->widgetopts_acf_condition) {
							case 'equal':
								if (isset($acf['value'])) {
									if ('show' == $acf_visibility && $acf['value'] == $settings->widgetopts_acf_value) {
										$hidden = false;
									} else if ('show' == $acf_visibility && $acf['value'] != $settings->widgetopts_acf_value) {
										$hidden = true;
									} else if ('hide' == $acf_visibility && $acf['value'] == $settings->widgetopts_acf_value) {
										$hidden = true;
									} else if ('hide' == $acf_visibility && $acf['value'] != $settings->widgetopts_acf_value) {
										$hidden = false;
									}
								}
								break;
							case 'not_equal':
								if (isset($acf['value'])) {
									if ('show' == $acf_visibility && $acf['value'] == $settings->widgetopts_acf_value) {
										$hidden = true;
									} else if ('show' == $acf_visibility && $acf['value'] != $settings->widgetopts_acf_value) {
										$hidden = false;
									} else if ('hide' == $acf_visibility && $acf['value'] == $settings->widgetopts_acf_value) {
										$hidden = false;
									} else if ('hide' == $acf_visibility && $acf['value'] != $settings->widgetopts_acf_value) {
										$hidden = true;
									}
								}
								break;
							case 'contains':
								if (isset($acf['value'])) {
									if ('show' == $acf_visibility && strpos($acf['value'], $settings->widgetopts_acf_value) !== false) {
										$hidden = false;
									} else if ('show' == $acf_visibility && strpos($acf['value'], $settings->widgetopts_acf_value) === false) {
										$hidden = true;
									} else if ('hide' == $acf_visibility && strpos($acf['value'], $settings->widgetopts_acf_value) !== false) {
										$hidden = true;
									} else if ('hide' == $acf_visibility && strpos($acf['value'], $settings->widgetopts_acf_value) === false) {
										$hidden = false;
									}
								}
								break;
							case 'not_contains':
								if (isset($acf['value'])) {
									if ('show' == $acf_visibility && strpos($acf['value'], $settings->widgetopts_acf_value) !== false) {
										$hidden = true;
									} else if ('show' == $acf_visibility && strpos($acf['value'], $settings->widgetopts_acf_value) === false) {
										$hidden = false;
									} else if ('hide' == $acf_visibility && strpos($acf['value'], $settings->widgetopts_acf_value) !== false) {
										$hidden = false;
									} else if ('hide' == $acf_visibility && strpos($acf['value'], $settings->widgetopts_acf_value) === false) {
										$hidden = true;
									}
								}
								break;
							case 'empty':
								if ('show' == $acf_visibility && empty($acf['value'])) {
									$hidden = false;
								} else if ('show' == $acf_visibility && !empty($acf['value'])) {
									$hidden = true;
								} elseif ('hide' == $acf_visibility && empty($acf['value'])) {
									$hidden = true;
								} else if ('hide' == $acf_visibility && !empty($acf['value'])) {
									$hidden = false;
								}
								break;
							case 'not_empty':
								if ('show' == $acf_visibility && empty($acf['value'])) {
									$hidden = true;
								} else if ('show' == $acf_visibility && !empty($acf['value'])) {
									$hidden = false;
								} elseif ('hide' == $acf_visibility && empty($acf['value'])) {
									$hidden = false;
								} else if ('hide' == $acf_visibility && !empty($acf['value'])) {
									$hidden = true;
								}
								break;

							default:
								# code...
								break;
						}
						// //do return to bypass other conditions
						$hidden = apply_filters('widgetopts_beaver_visibility_acf', $hidden);
						if ($hidden) {
							return false;
						}
					}
				}
			}

			//widget logic
			if (isset($widget_options['logic']) && 'activate' == $widget_options['logic']) {
				// New snippet-based system
				if (isset($settings->widgetopts_logic_snippet_id) && !empty($settings->widgetopts_logic_snippet_id)) {
					$snippet_id = $settings->widgetopts_logic_snippet_id;
					if (class_exists('WidgetOpts_Snippets_API')) {
						$result = WidgetOpts_Snippets_API::execute_snippet($snippet_id);
						if ($result === false) {
							return false;
						}
					}
				}
				// Legacy support for old inline logic
				elseif (isset($settings->widgetopts_settings_logic) && !empty($settings->widgetopts_settings_logic)) {
					// Flag that legacy migration is needed
					if (!get_option('wopts_display_logic_migration_required', false)) {
						update_option('wopts_display_logic_migration_required', true);
					}

					$display_logic = stripslashes(trim($settings->widgetopts_settings_logic));
					$display_logic = apply_filters('widget_options_logic_override', $display_logic);
					$display_logic = apply_filters('extended_widget_options_logic_override', $display_logic);
					if ($display_logic === false) {
						return false;
					}
					if ($display_logic === true) {
						return true;
					}
					$display_logic = htmlspecialchars_decode($display_logic, ENT_QUOTES);
					try {
						if (!widgetopts_safe_eval($display_logic)) {
							return false;
						}
					} catch (ParseError $e) {
						return false;
					}
				}
			}

			return $is_visible;
		}

		function widgetopts_beaver_module_attributes($attrs, $module)
		{
			global $widget_options;

			$settings = $module->settings;

			if (isset($widget_options['animation']) && 'activate' == $widget_options['animation']) {

				if (isset($settings->widgetopts_animation_type) && !empty($settings->widgetopts_animation_type)) {
					$attrs['class'][] 				= 'widgetopts-animate';
					$attrs['data-animation-type'] 	= $settings->widgetopts_animation_type;

					if (isset($settings->widgetopts_animation_hidden) && !empty($settings->widgetopts_animation_hidden)) {
						$attrs['class'][] 				= 'widgetopts-animate-hide';
					}
				}

				if (isset($settings->widgetopts_animation_event) && !empty($settings->widgetopts_animation_event)) {
					$attrs['data-animation-event'] 	= $settings->widgetopts_animation_event;
				}

				if (isset($settings->widgetopts_animation_speed) && !empty($settings->widgetopts_animation_speed)) {
					$attrs['data-animation-speed'] 	= $settings->widgetopts_animation_speed;
				}

				if (isset($settings->widgetopts_animation_offset) && !empty($settings->widgetopts_animation_offset)) {
					$attrs['data-animation-offset'] = $settings->widgetopts_animation_offset;
				}

				if (isset($settings->widgetopts_animation_delay) && !empty($settings->widgetopts_animation_delay)) {
					$attrs['data-animation-delay'] = $settings->widgetopts_animation_delay;
				}
			}
			//return if editing
			if (class_exists('FLBuilderModel') && FLBuilderModel::is_builder_active()) {
			} else {
				if (isset($widget_options['fixed']) && 'activate' == $widget_options['fixed']) {
					if (isset($settings->widgetopts_settings_fixed) && 'yes' == $settings->widgetopts_settings_fixed) {
						$attrs['class'][] 	= 'widgetopts-fixed-this';
					}
				}
				if (isset($widget_options['links']) && 'activate' == $widget_options['links']) {
					if (isset($settings->widgetopts_link_url) && !empty($settings->widgetopts_link_url)) {
						$attrs['class'][] 	= 'widgetopts-links-this';
					}
				}
			}
			// echo '<pre>';
			// print_r( $settings );
			// echo '</pre>';

			return $attrs;
		}

		function widgetopts_beaver_render_css($css, $nodes)
		{
			global $widget_options;

			if (isset($nodes['modules'])) {
				foreach ($nodes['modules'] as $module) {
					if (FLBuilderModel::is_node_visible($module)) {
						$settings 		 = $module->settings;
						$global_settings = FLBuilderModel::get_global_settings();
						// echo $module->node . '<br />';
						// 
						if (isset($widget_options['alignment']) && 'activate' == $widget_options['alignment']) {
							if (isset($settings->widgetopts_alignment_large) && !empty($settings->widgetopts_alignment_large)) {
								$css .= 'body .fl-node-' . $module->node . ', body .fl-node-' . $module->node . ' p,  body .fl-node-' . $module->node . ' .fl-node-content .fl-heading{ text-align: ' . $settings->widgetopts_alignment_large . '; }';
							}

							if (isset($settings->widgetopts_alignment_medium) && !empty($settings->widgetopts_alignment_medium)) {
								$css .= '@media (max-width: ' . $global_settings->medium_breakpoint . 'px) { ';
								$css .= 'body .fl-node-' . $module->node . ', body .fl-node-' . $module->node . ' p,  body .fl-node-' . $module->node . ' .fl-node-content .fl-heading{ text-align: ' . $settings->widgetopts_alignment_medium . '; }';
								$css .= ' }';
							}

							if (isset($settings->widgetopts_alignment_small) && !empty($settings->widgetopts_alignment_small)) {
								$css .= '@media (max-width: ' . $global_settings->responsive_breakpoint . 'px) { ';
								$css .= 'body .fl-node-' . $module->node . ', body .fl-node-' . $module->node . ' p,  body .fl-node-' . $module->node . ' .fl-node-content .fl-heading{ text-align: ' . $settings->widgetopts_alignment_small . '; }';
								$css .= ' }';
							}
						}

						if (isset($widget_options['styling']) && 'activate' == $widget_options['styling']) {

							if (isset($settings->widgetopts_styling_bgimage_src) && !empty($settings->widgetopts_styling_bgimage_src)) {
								$css .= 'body .fl-node-' . $module->node . '{ background-image: url("' . $settings->widgetopts_styling_bgimage_src . '"); background-size: cover; -webkit-background-size: cover; -moz-background-size: cover; -o-background-size: cover; background-position: center center; } ';
							}

							if (isset($settings->widgetopts_styling_bgcolor) && !empty($settings->widgetopts_styling_bgcolor)) {
								$css .= 'body .fl-node-' . $module->node . '{ background-color: #' . $settings->widgetopts_styling_bgcolor . '; }';
							}

							if (isset($settings->widgetopts_styling_hover_bgcolor) && !empty($settings->widgetopts_styling_hover_bgcolor)) {
								$css .= 'body .fl-node-' . $module->node . ':hover{ background-color: #' . $settings->widgetopts_styling_hover_bgcolor . '; }';
							}

							if (isset($settings->widgetopts_styling_heading) && !empty($settings->widgetopts_styling_heading)) {
								$css .= 'body .fl-node-' . $module->node . ' h1,body .fl-node-' . $module->node . ' h2, body .fl-node-' . $module->node . ' h3, body .fl-node-' . $module->node . ' h4, body .fl-node-' . $module->node . ' h5,body .fl-node-' . $module->node . ' h6{ color: #' . $settings->widgetopts_styling_heading . '; }';
							}

							if (isset($settings->widgetopts_styling_text) && !empty($settings->widgetopts_styling_text)) {
								$css .= 'body .fl-node-' . $module->node . '{ color: #' . $settings->widgetopts_styling_text . '; }';
							}

							if (isset($settings->widgetopts_styling_links) && !empty($settings->widgetopts_styling_links)) {
								$css .= 'body .fl-node-' . $module->node . ' a{ color: #' . $settings->widgetopts_styling_links . '; }';
							}

							if (isset($settings->widgetopts_styling_links_hover) && !empty($settings->widgetopts_styling_links_hover)) {
								$css .= 'body .fl-node-' . $module->node . ' a:hover{ color: #' . $settings->widgetopts_styling_links_hover . '; }';
							}

							if (isset($settings->widgetopts_styling_border) && !empty($settings->widgetopts_styling_border)) {
								$css .= 'body .fl-node-' . $module->node . '{ border-color: #' . $settings->widgetopts_styling_border . '; }';
							}

							if (isset($settings->widgetopts_styling_border_style) && !empty($settings->widgetopts_styling_border_style)) {
								$css .= 'body .fl-node-' . $module->node . '{ border-style: ' . $settings->widgetopts_styling_border_style . '; }';
							}

							if (isset($settings->widgetopts_styling_border_width) && !empty($settings->widgetopts_styling_border_width)) {
								$css .= 'body .fl-node-' . $module->node . '{ border-width: ' . $settings->widgetopts_styling_border_width . 'px; }';
							}
						}

						if (isset($widget_options['links']) && 'activate' == $widget_options['links']) {
							if (isset($settings->widgetopts_link_url) && !empty($settings->widgetopts_link_url)) {
								$css .= 'body .widgetopts-links-this{ position: relative; }';
							}
						}
					}
				}
			}
			return $css;
		}

		function widgetopts_beaver_render_module_content($out, $module)
		{
			global $widget_options;
			if (isset($widget_options['links']) && 'activate' == $widget_options['links']) {
				if (FLBuilderModel::is_node_visible($module)) {
					$settings 		 = $module->settings;
					if (isset($settings->widgetopts_link_url) && !empty($settings->widgetopts_link_url)) {
						$out = '<a href="' . $settings->widgetopts_link_url  . '" ' . ((isset($settings->widgetopts_link_target)) ? 'target="' . $settings->widgetopts_link_target . '"' : '') . ' ' . ((isset($settings->widgetopts_link_nofollow) && 'yes' == $settings->widgetopts_link_nofollow) ? 'rel="nofollow"' : '') . ' class="widgetopts-custom-wlink"></a>' . $out;
					}
				}
			}
			return $out;
		}

		function widgetopts_plugin_check()
		{
			if (!defined('WIDGETOPTS_PLUGIN_NAME')) { ?>
				<div class="widgetopts_activated_notice notice-error notice" style="box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);">
					<p>
						<?php _e('<strong>Widget Options Plugin</strong> is required for the <em>Widget Options for Beaver Builder</em> to work properly. Please get the plugin <a href="https://wordpress.org/plugins/widget-options/" target="_blank">here</a>. Thanks!', 'widget-options'); ?>
					</p>
				</div>
<?php }
		}
	}

	if (function_exists('is_multisite') && is_multisite()) {
		new WP_Widget_Options_Beaver();
	} else {
		add_action('plugins_loaded', array('WP_Widget_Options_Beaver', 'init'));
	}

	/**
	 * Protect legacy display logic from modification/injection on BB save.
	 * Always restores the DB value for widgetopts_settings_logic per node.
	 * Prevents code injection via console AND data loss before migration.
	 * 
	 * @since 5.1
	 */
	add_filter('fl_builder_before_save_layout', function($data) {
		if (!is_array($data) || !class_exists('FLBuilderModel')) {
			return $data;
		}

		$post_id = FLBuilderModel::get_post_id();
		if (!$post_id) {
			return $data;
		}

		// Build map: node_id => old widgetopts_settings_logic from DB
		$old_logic = array();
		foreach (array('_fl_builder_data', '_fl_builder_draft') as $mk) {
			$old_nodes = get_post_meta($post_id, $mk, true);
			if (is_array($old_nodes)) {
				foreach ($old_nodes as $nid => $node) {
					if (is_object($node) && isset($node->settings) && is_object($node->settings)
						&& !empty($node->settings->widgetopts_settings_logic)) {
						$old_logic[$nid] = $node->settings->widgetopts_settings_logic;
					}
				}
			}
		}

		// Protect each node
		foreach ($data as $nid => &$node) {
			if (!is_object($node) || !isset($node->settings) || !is_object($node->settings)) {
				continue;
			}

			$wopt_ver = isset($node->settings->widgetopts_wopt_version) ? $node->settings->widgetopts_wopt_version : '';
			$has_snippet = !empty($node->settings->widgetopts_logic_snippet_id);
			$has_legacy = !empty($node->settings->widgetopts_settings_logic);

			// If wopt_version >= 4.2: legacy logic is obsolete — clear it
			if ($wopt_ver !== '' && version_compare($wopt_ver, '5.3', '>=')) {
				if ($has_legacy) {
					$node->settings->widgetopts_settings_logic = '';
				}
				continue;
			}

			// Auto-clear legacy logic if snippet_id is already set (migration done)
			if ($has_snippet && $has_legacy) {
				$node->settings->widgetopts_settings_logic = '';
				$node->settings->widgetopts_wopt_version = WIDGETOPTS_VERSION;
				continue;
			}

			// User intentionally cleared legacy logic via Clear button
			if (!empty($node->settings->widgetopts_logic_cleared)) {
				$node->settings->widgetopts_settings_logic = '';
				unset($node->settings->widgetopts_logic_cleared);
				$node->settings->widgetopts_wopt_version = WIDGETOPTS_VERSION;
				continue;
			}

			$old_val = isset($old_logic[$nid]) ? $old_logic[$nid] : '';
			$new_val = isset($node->settings->widgetopts_settings_logic) ? $node->settings->widgetopts_settings_logic : '';

			if ($new_val !== $old_val) {
				if ($old_val !== '') {
					$node->settings->widgetopts_settings_logic = $old_val;
				} else {
					unset($node->settings->widgetopts_settings_logic);
				}
			}
		}

		return $data;
	}, 10, 1);

endif;
