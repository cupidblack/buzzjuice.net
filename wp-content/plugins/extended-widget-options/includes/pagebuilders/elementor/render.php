<?php

/**
 * Extends funtionality to Elementor Pagebuilder
 *
 *
 * @copyright   Copyright (c) 2017, Jeffrey Carandang
 * @since       4.3
 */
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

if (!function_exists('widgetopts_elementor_render')) {
	add_action('elementor/widget/render_content', 'widgetopts_elementor_render', 10, 2);
	function widgetopts_elementor_render($content, $widget)
	{
		if (!Elementor\Plugin::$instance->editor->is_edit_mode()) {
			global $widget_options;
			$settings 	= $widget->get_settings();

			$hidden     = false;
			$placeholder 		= '<div class="widgetopts-placeholder-e"></div>';
			$visibility_opts    = isset($settings['widgetopts_visibility']) ? $settings['widgetopts_visibility'] : 'hide';

			$tax_opts   = (isset($widget_options['settings']) && isset($widget_options['settings']['taxonomies_keys'])) ? $widget_options['settings']['taxonomies_keys'] : array();
			$is_misc    = ('activate' == $widget_options['visibility'] && isset($widget_options['settings']['visibility']) && isset($widget_options['settings']['visibility']['misc'])) ? true : false;
			$is_types   = ('activate' == $widget_options['visibility'] && isset($widget_options['settings']['visibility']) && isset($widget_options['settings']['visibility']['post_type'])) ? true : false;
			$is_tax     = ('activate' == $widget_options['visibility'] && isset($widget_options['settings']['visibility']) && isset($widget_options['settings']['visibility']['taxonomies'])) ? true : false;
			$is_inherit = ('activate' == $widget_options['visibility'] && isset($widget_options['settings']['visibility']) && isset($widget_options['settings']['visibility']['inherit'])) ? true : false;


			//roles
			if (isset($widget_options['roles']) && 'activate' == $widget_options['roles'] && isset($settings['widgetopts_roles']) && is_array($settings['widgetopts_roles'])) {
				global $current_user;

				$roles	=  $settings['widgetopts_roles'];

				// unset($roles['options']);
				$current_user->role = (isset($current_user->caps) && !empty($current_user->caps)) ? array_keys($current_user->caps) : array('guests');

				$roles_opts = isset($settings['widgetopts_visibility_roles']) ? $settings['widgetopts_visibility_roles'] : 'hide';
				if ($roles_opts == 'hide' && in_array($current_user->role[0], $roles)) {
					$hidden = true; //hide if exists on hidden roles
				} elseif ($roles_opts == 'show' && !in_array($current_user->role[0], $roles)) {
					$hidden = true; //hide if doesn't exists on visible roles
				}

				//do return to bypass other tabs conditions
				$hidden = apply_filters('widgetopts_elementor_visibility_roles', $hidden);
				if ($hidden) {
					return $placeholder;
				}
			}
			//end roles

			//pages
			if ($is_misc && ((is_home() && is_front_page()) || is_front_page())) {
				if (isset($settings['widgetopts_misc']) && is_array($settings['widgetopts_misc']) && in_array('home', $settings['widgetopts_misc']) && $visibility_opts == 'hide') {
					$hidden = true; //hide if checked on hidden pages
				} elseif ((!isset($settings['widgetopts_misc']) || (isset($settings['widgetopts_misc']) && is_array($settings['widgetopts_misc']) && !in_array('home', $settings['widgetopts_misc']))) && $visibility_opts == 'show') {
					$hidden = true; //hide if not checked on visible pages
				}

				//do return to bypass other conditions
				$hidden = apply_filters('widgetopts_elementor_visibility_home', $hidden);
				if ($hidden) {
					return $placeholder;
				}
			} elseif ($is_misc && is_home()) {
				if (isset($settings['widgetopts_misc']) && is_array($settings['widgetopts_misc']) && in_array('blog', $settings['widgetopts_misc']) && $visibility_opts == 'hide') {
					$hidden = true; //hide if checked on hidden pages
				} elseif ((!isset($settings['widgetopts_misc']) || (isset($settings['widgetopts_misc']) && is_array($settings['widgetopts_misc']) && !in_array('blog', $settings['widgetopts_misc']))) && $visibility_opts == 'show') {
					$hidden = true; //hide if not checked on visible pages
				}

				//do return to bypass other conditions
				$hidden = apply_filters('widgetopts_elementor_visibility_blog', $hidden);
				if ($hidden) {
					return $placeholder;
				}
			} elseif ($is_tax && is_category() && is_array($tax_opts) && in_array('category', $tax_opts)) {
				//category page
				if (!isset($settings['widgetopts_tax_category'])) {
					$settings['widgetopts_tax_category'] = array();
				}
				if (!isset($settings['widgetopts_taxonomies'])) {
					$settings['widgetopts_taxonomies'] = array();
				}

				$cat_lists = $settings['widgetopts_tax_category'];
				if (!in_array('category', $settings['widgetopts_taxonomies']) && $visibility_opts == 'hide' && in_array(get_query_var('cat'), $cat_lists)) {
					$hidden = true; //hide if exists on hidden pages
				} elseif (!in_array('category', $settings['widgetopts_taxonomies']) && $visibility_opts == 'show' && !in_array(get_query_var('cat'), $cat_lists)) {
					$hidden = true; //hide if doesn't exists on visible pages
				} elseif (in_array('category', $settings['widgetopts_taxonomies']) && $visibility_opts == 'hide') {
					$hidden = true; //hide to all categories
				} elseif (in_array('category', $settings['widgetopts_taxonomies']) && $visibility_opts == 'show') {
					$hidden = false; //hide to all categories
				}
				//
				// //do return to bypass other conditions
				$hidden = apply_filters('widgetopts_elementor_visibility_categories', $hidden);
				if ($hidden) {
					return $placeholder;
				}
			} elseif ($is_tax && is_tag() && is_array($tax_opts) && in_array('post_tag', $tax_opts)) {
				if (!isset($settings['widgetopts_tax_post_tag'])) {
					$settings['widgetopts_tax_post_tag'] = array();
				}
				if (!isset($settings['widgetopts_taxonomies'])) {
					$settings['widgetopts_taxonomies'] = array();
				}

				$tag_lists = $settings['widgetopts_tax_post_tag'];
				if (!in_array('post_tag', $settings['widgetopts_taxonomies'])  && $visibility_opts == 'hide' && in_array(get_query_var('tag_id'), $tag_lists)) {
					$hidden = true; //hide if exists on hidden pages
				} elseif (!in_array('post_tag', $settings['widgetopts_taxonomies'])  && $visibility_opts == 'show' && !in_array(get_query_var('tag_id'), $tag_lists)) {
					$hidden = true; //hide if doesn't exists on visible pages
				} elseif (in_array('post_tag', $settings['widgetopts_taxonomies']) && $visibility_opts == 'hide') {
					$hidden = true; //hide to all tags
				} elseif (in_array('post_tag', $settings['widgetopts_taxonomies']) && $visibility_opts == 'show') {
					$hidden = false; //hide to all tags
				}
				//
				// //do return to bypass other conditions
				$hidden = apply_filters('widgetopts_elementor_visibility_tags', $hidden);
				if ($hidden) {
					return $placeholder;
				}
			} elseif ($is_tax && is_tax()) {
				//taxonomies page
				$term = get_queried_object();
				$term_lists = array();

				if (isset($settings['widgetopts_tax_community-category']) && is_array($settings['widgetopts_tax_community-category'])) {
					$term_lists = $settings['widgetopts_tax_community-category'];
				}

				if (!isset($settings['widgetopts_taxonomies'])) {
					$settings['widgetopts_taxonomies'] = array();
				}
				// print_r( $term_lists );
				if (isset($settings['widgetopts_taxonomies']) && is_array($settings['widgetopts_taxonomies']) && !in_array($term->taxonomy, $settings['widgetopts_taxonomies']) && $visibility_opts == 'hide' && in_array($term->term_id, $term_lists)) {
					$hidden = true; //hide if exists on hidden pages
				} elseif (isset($settings['widgetopts_taxonomies']) && is_array($settings['widgetopts_taxonomies']) && !in_array($term->taxonomy, $settings['widgetopts_taxonomies']) && $visibility_opts == 'show' && !in_array($term->term_id, $term_lists)) {
					$hidden = true; //hide if doesn't exists on visible pages
				} elseif (is_array($settings['widgetopts_taxonomies']) && in_array($term->taxonomy, $settings['widgetopts_taxonomies']) && $visibility_opts == 'hide') {
					$hidden = true; //hide to all tags
				} elseif (is_array($settings['widgetopts_taxonomies']) && in_array($term->taxonomy, $settings['widgetopts_taxonomies']) && $visibility_opts == 'show') {
					$hidden = false; //hide to all tags
				} elseif (is_array($settings['widgetopts_taxonomies']) && in_array($term->taxonomy, $settings['widgetopts_taxonomies']) && $visibility_opts == 'hide' && in_array($term->term_id, $term_lists)) {
					$hidden = true; //hide if exists on hidden pages
				} elseif (is_array($settings['widgetopts_taxonomies']) && in_array($term->taxonomy, $settings['widgetopts_taxonomies']) && $visibility_opts == 'show' && in_array($term->term_id, $term_lists)) {
					$hidden = false; //hide if doesn't exists on visible pages
				} elseif (!isset($settings['widgetopts_taxonomies']) && $visibility_opts == 'show') {
					$hidden = true; //hide if checked on hidden pages
				}

				//do return to bypass other conditions
				$hidden = apply_filters('widgetopts_elementor_visibility_taxonomies', $hidden);
				if ($hidden) {
					return $placeholder;
				}
			} elseif ($is_misc && is_archive()) {
				//archives page
				if (isset($settings['widgetopts_misc']) && is_array($settings['widgetopts_misc']) && in_array('archives', $settings['widgetopts_misc']) && $visibility_opts == 'hide') {
					$hidden = true; //hide if checked on hidden pages
				} elseif ((!isset($settings['widgetopts_misc']) || (isset($settings['widgetopts_misc']) && is_array($settings['widgetopts_misc']) && !in_array('archives', $settings['widgetopts_misc']))) && $visibility_opts == 'show') {
					$hidden = true; //hide if not checked on visible pages
				}

				//do return to bypass other conditions
				$hidden = apply_filters('widgetopts_elementor_visibility_archives', $hidden);
				if ($hidden) {
					return $placeholder;
				}
			} elseif (is_post_type_archive()) {
				//post type archives
				$current_type_archive = get_post_type();
				if ('elementor_library' == $current_type_archive) {
					global $wp_query;
					$current_type_archive = $wp_query->query['post_type'];
				}

				if (!isset($settings['widgetopts_types']) || ($is_types && !isset($settings['widgetopts_types']))) {
					$settings['widgetopts_types'] = array();
				}
				if (is_array($settings['widgetopts_types'])) {
					if ($visibility_opts == 'hide' && in_array($current_type_archive, $settings['widgetopts_types'])) {
						$hidden = true; //hide if exists on hidden pages
					} elseif ($visibility_opts == 'show' && !in_array($current_type_archive, $settings['widgetopts_types'])) {
						$hidden = true; //hide if doesn't exists on visible pages
					}
				}

				//do return to bypass other conditions
				$hidden = apply_filters('widgetopts_elementor_visibility_post_type_archive', $hidden);
				if ($hidden) {
					return $placeholder;
				}
			} elseif ($is_misc && is_404()) {
				//404 page
				if (isset($settings['widgetopts_misc']) && is_array($settings['widgetopts_misc']) && in_array('404', $settings['widgetopts_misc']) && $visibility_opts == 'hide') {
					$hidden = true; //hide if checked on hidden pages
				} elseif ((!isset($settings['widgetopts_misc']) || (isset($settings['widgetopts_misc']) && is_array($settings['widgetopts_misc']) && !in_array('404', $settings['widgetopts_misc']))) && $visibility_opts == 'show') {
					$hidden = true; //hide if not checked on visible pages
				}

				//do return to bypass other conditions
				$hidden = apply_filters('widget_options_visibility_404', $hidden);
				if ($hidden) {
					return $placeholder;
				}
			} elseif ($is_misc && is_search()) {
				if (isset($settings['widgetopts_misc']) && is_array($settings['widgetopts_misc']) && in_array('search', $settings['widgetopts_misc']) && $visibility_opts == 'hide') {
					$hidden = true; //hide if checked on hidden pages
				} elseif ((!isset($settings['widgetopts_misc']) || (isset($settings['widgetopts_misc']) && is_array($settings['widgetopts_misc']) && !in_array('search', $settings['widgetopts_misc']))) && $visibility_opts == 'show') {
					$hidden = true;
				}

				//do return to bypass other conditions
				$hidden = apply_filters('widgetopts_elementor_visibility_search', $hidden);
				if ($hidden) {
					return $placeholder;
				}
			} elseif (is_single() && !is_page()) {
				global $wp_query;
				$post = $wp_query->post;

				if (!isset($settings['widgetopts_types']) || ($is_types && !isset($settings['widgetopts_types'])) || !is_array($settings['widgetopts_types'])) {
					$settings['widgetopts_types'] = array();
				}

				if ($visibility_opts == 'hide' && in_array($post->post_type, $settings['widgetopts_types'])) {
					$hidden = true; //hide if exists on hidden pages
				} elseif ($visibility_opts == 'show' && !in_array($post->post_type, $settings['widgetopts_types'])) {
					$hidden = true; //hide if doesn't exists on visible pages
				}

				// do return to bypass other conditions
				$hidden = apply_filters('widgetopts_elementor_visibility_single', $hidden);


				$taxonomy_names  = get_post_taxonomies($post->ID);
				$array_intersect = array_intersect($tax_opts, $taxonomy_names);

				if (!isset($settings['widgetopts_tax_category'])) {
					$settings['widgetopts_tax_category'] = array();
				}
				//
				if (!empty($array_intersect)) {
					foreach ($array_intersect  as $tax_key => $tax_value) {
						if (in_array($tax_value, $tax_opts) && isset($settings['widgetopts_tax_' . $tax_value]) && !empty($settings['widgetopts_tax_' . $tax_value])) {
							$term_list = wp_get_post_terms($post->ID, $tax_value, array("fields" => "ids"));
							//
							if (is_array($term_list) && !empty($term_list)) {
								$checked_terms  = $settings['widgetopts_tax_' . $tax_value];
								$intersect      = array_intersect($term_list, $checked_terms);
								if (!empty($intersect) && $visibility_opts == 'hide') {
									$hidden = true;
								} elseif (!empty($intersect) && $visibility_opts == 'show') {
									$hidden = false;
								}
							}
						}
						// do return to bypass other conditions
						$hidden = apply_filters('widgetopts_elementor_visibility_single_' . $tax_value, $hidden);
					}
				}


				if ($hidden) {
					return $placeholder;
				}
			} elseif ($is_types && is_page()) {
				global $wp_query;

				$post = $wp_query->post;

				//do post type condition first
				if (isset($settings['widgetopts_types'])) {

					if (!is_array($settings['widgetopts_types'])) {
						$settings['widgetopts_types'] = array();
					}

					if ($visibility_opts == 'hide' && in_array('page', $settings['widgetopts_types'])) {
						$hidden = true; //hide if exists on hidden pages
					} elseif ($visibility_opts == 'show' && !in_array('page', $settings['widgetopts_types'])) {
						$hidden = true; //hide if doesn't exists on visible pages
					}
				} else {
					// print_r( $settings['widgetopts_pages'] );
					//do per pages condition
					if (!isset($settings['widgetopts_pages'])) {
						$settings['widgetopts_pages'] = array();
					}

					// //add parent inherit option
					if ($is_inherit && $post->post_parent && in_array($post->post_parent, $settings['widgetopts_pages'])) {
						$settings['widgetopts_pages'][] = $post->ID;
					}

					if ($visibility_opts == 'hide' && in_array($post->ID, $settings['widgetopts_pages'])) {
						$hidden = true; //hide if exists on hidden pages
					} elseif ($visibility_opts == 'show' && !in_array($post->ID, $settings['widgetopts_pages'])) {
						$hidden = true; //hide if doesn't exists on visible pages
					}
				}

				// //do return to bypass other conditions
				$hidden = apply_filters('widgetopts_elementor_visibility_page', $hidden);
				if ($hidden) {
					return $placeholder;
				}
			}

			//days & date
			if ('activate' == $widget_options['dates'] && isset($widget_options['settings']['dates'])) {
				if (isset($widget_options['settings']['dates']) && isset($widget_options['settings']['dates']['days'])) {
					//days
					$today      = current_time('l', 1);
					$today      = strtolower($today);
					$days_on    = isset($settings['widgetopts_days']) ? $settings['widgetopts_days'] : '';
					$days       = isset($settings['widgetopts_days_list']) ? $settings['widgetopts_days_list'] : array();
					$days_opts  = isset($settings['widgetopts_visibility_days']) ? $settings['widgetopts_visibility_days'] : 'hide';

					if ($days_on) {

						if ($days_opts == 'hide' && in_array($today, $days)) {
							$hidden = true; //hide if exists on hidden days
						} elseif ($days_opts == 'show' && !in_array($today, $days)) {
							$hidden = true; //hide if doesn't exists on visible days
						}

						//do return to bypass other conditions
						$hidden = apply_filters('widgetopts_elementor_visibility_days', $hidden);
						if ($hidden) {
							return $placeholder;
						}
					}
					//end days
				}

				if (isset($widget_options['settings']['dates']) && isset($widget_options['settings']['dates']['date_range'])) {
					//date
					$todate         = date('Y-m-d H:i');
					$dates_on       = isset($settings['widgetopts_dates']) ? $settings['widgetopts_dates'] : '';;
					$dates_opts     = isset($settings['widgetopts_visibility_dates']) ? $settings['widgetopts_visibility_dates'] : 'hide';

					if (isset($settings['widgetopts_dates_end'])) {
						if (!isset($settings['widgetopts_dates_start'])) {
							$settings['widgetopts_dates_start'] = date('Y-m-d H:i', strtotime('-3 day'));
						}

						$valid_range = widgetopts_date_in_range($settings['widgetopts_dates_start'], $settings['widgetopts_dates_end'], $todate);

						if ($dates_opts == 'hide' && $valid_range) {
							$hidden = true; //hide if exists on hidden days
						} elseif ($dates_opts == 'show' && !$valid_range) {
							$hidden = true; //hide if doesn't exists on visible days
						}

						//do return to bypass other conditions
						$hidden = apply_filters('widgetopts_elementor_visibility_dates', $hidden);
						if ($hidden) {
							return $placeholder;
						}
					}
					//end dates
				}
			}

			if (isset($widget_options['state']) && 'activate' == $widget_options['state']) {
				if (isset($settings['widgetopts_roles_state']) && !empty($settings['widgetopts_roles_state'])) {
					//do state action here
					if ($settings['widgetopts_roles_state'] == 'out' && is_user_logged_in()) {
						return $placeholder;
					} else if ($settings['widgetopts_roles_state'] == 'in' && !is_user_logged_in()) {
						return $placeholder;
					}
				}
			}

			//widget logic
			if ('activate' == $widget_options['logic']) {
				// New snippet-based system
				if (isset($settings['widgetopts_logic_snippet_id']) && !empty($settings['widgetopts_logic_snippet_id'])) {
					$snippet_id = $settings['widgetopts_logic_snippet_id'];
					if (class_exists('WidgetOpts_Snippets_API')) {
						$result = WidgetOpts_Snippets_API::execute_snippet($snippet_id);
						if ($result === false) {
							return $placeholder;
						}
					}
				}
				// Legacy support for old inline logic
				elseif (isset($settings['widgetopts_logic']) && !empty($settings['widgetopts_logic'])) {
					// Flag that legacy migration is needed
					if (!get_option('wopts_display_logic_migration_required', false)) {
						update_option('wopts_display_logic_migration_required', true);
					}

					$display_logic = stripslashes(trim($settings['widgetopts_logic']));
					$display_logic = apply_filters('widget_options_logic_override', $display_logic);
					$display_logic = apply_filters('extended_widget_options_logic_override', $display_logic);
					if ($display_logic === false) {
						return $placeholder;
					}
					if ($display_logic === true) {
						return $content;
					}
					$display_logic = htmlspecialchars_decode($display_logic, ENT_QUOTES);
					try {
						if (!widgetopts_safe_eval($display_logic)) {
							return $placeholder;
						}
					} catch (ParseError $e) {
						return $placeholder;
					}
				}
			}

			//apply ACF conditions
			if (isset($widget_options['acf']) && 'activate' == $widget_options['acf']) {
				if (isset($settings['widgetopts_acf_field']) && !empty($settings['widgetopts_acf_field'])) {
					$acf = get_field_object($settings['widgetopts_acf_field']);

					if ($acf && is_array($acf)) {
						$acf_visibility    = isset($settings['widgetopts_acf_visibility']) ? $settings['widgetopts_acf_visibility'] : 'hide';
						//handle repeater fields
						if (isset($acf['value'])) {
							if (is_array($acf['value'])) {
								$acf['value'] = implode(', ', array_map(function ($acf_array_value) {
									$acf_implode = '';
									if (is_array($acf_array_value)) {
										$acf_implode = implode(',', array_filter($acf_array_value));
									} else {
										$acf_implode = $acf_array_value;
									}
									return $acf_implode;
								}, $acf['value']));
							}
						}

						switch ($settings['widgetopts_acf_condition']) {
							case 'equal':
								if (isset($acf['value'])) {
									if ('show' == $acf_visibility && $acf['value'] == $settings['widgetopts_acf']) {
										$hidden = false;
									} else if ('show' == $acf_visibility && $acf['value'] != $settings['widgetopts_acf']) {
										$hidden = true;
									} else if ('hide' == $acf_visibility && $acf['value'] == $settings['widgetopts_acf']) {
										$hidden = true;
									} else if ('hide' == $acf_visibility && $acf['value'] != $settings['widgetopts_acf']) {
										$hidden = false;
									}
								}
								break;
							case 'not_equal':
								if (isset($acf['value'])) {
									if ('show' == $acf_visibility && $acf['value'] == $settings['widgetopts_acf']) {
										$hidden = true;
									} else if ('show' == $acf_visibility && $acf['value'] != $settings['widgetopts_acf']) {
										$hidden = false;
									} else if ('hide' == $acf_visibility && $acf['value'] == $settings['widgetopts_acf']) {
										$hidden = false;
									} else if ('hide' == $acf_visibility && $acf['value'] != $settings['widgetopts_acf']) {
										$hidden = true;
									}
								}
								break;
							case 'contains':
								if (isset($acf['value'])) {
									if ('show' == $acf_visibility && strpos($acf['value'], $settings['widgetopts_acf']) !== false) {
										$hidden = false;
									} else if ('show' == $acf_visibility && strpos($acf['value'], $settings['widgetopts_acf']) === false) {
										$hidden = true;
									} else if ('hide' == $acf_visibility && strpos($acf['value'], $settings['widgetopts_acf']) !== false) {
										$hidden = true;
									} else if ('hide' == $acf_visibility && strpos($acf['value'], $settings['widgetopts_acf']) === false) {
										$hidden = false;
									}
								}
								break;
							case 'not_contains':
								if (isset($acf['value'])) {
									if ('show' == $acf_visibility && strpos($acf['value'], $settings['widgetopts_acf']) !== false) {
										$hidden = true;
									} else if ('show' == $acf_visibility && strpos($acf['value'], $settings['widgetopts_acf']) === false) {
										$hidden = false;
									} else if ('hide' == $acf_visibility && strpos($acf['value'], $settings['widgetopts_acf']) !== false) {
										$hidden = false;
									} else if ('hide' == $acf_visibility && strpos($acf['value'], $settings['widgetopts_acf']) === false) {
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
						$hidden = apply_filters('widgetopts_elementor_visibility_acf', $hidden);
						if ($hidden) {
							return $placeholder;
						}
					}
				}
			}

			//apply link widget options
			if ('activate' == $widget_options['links']) {
				$link_on    = isset($settings['widgetopts_links']) ? $settings['widgetopts_links'] : '';
				if ($link_on && isset($settings['widgetopts_links_target']) && !empty($settings['widgetopts_links_target'])) {
					$link = '<a href="';
					if (isset($settings['widgetopts_links_http']) && 'on' == $settings['widgetopts_links_http']) {
						$link .= $settings['widgetopts_links_target'];
					} else {
						$link .= widgetopts_addhttp($settings['widgetopts_links_target']);
					}
					$link .= '"';

					//add newtab
					if (isset($settings['widgetopts_links_newtab']) && 'on' == $settings['widgetopts_links_newtab']) {
						$link .= ' target="_blank"';
					}

					//add nofollow
					if (isset($settings['widgetopts_links_nofollow']) && 'on' == $settings['widgetopts_links_nofollow']) {
						$link .= ' rel="nofollow"';
					}

					$link .= ' class="widgetopts-el-link"></a>';

					$content = apply_filters('widgetopts_elementor_widget_link', $link) . $content;
				}
			}
		}

		return $content;
	}
}

if (!function_exists('widgetopts_elementor_before_render')) {
	add_action('elementor/frontend/widget/before_render', 'widgetopts_elementor_before_render', 10, 2);
	function widgetopts_elementor_before_render($element)
	{
		global $widget_options;
		$enabled = array('button', 'button_plus', 'eael-creative-button', 'cta');
		if (in_array($element->get_name(), $enabled)) {
			if (isset($widget_options['sliding']) && 'activate' == $widget_options['sliding']) {
				$settings = $element->get_settings();
				if (isset($settings['widgetopts_open_sliding']) && 'on' == $settings['widgetopts_open_sliding']) {
					$element->add_render_attribute('button', 'class', 'sl-widgetopts-open');
				}
			}
		}

		if (isset($widget_options['fixed']) && 'activate' == $widget_options['fixed']) {
			$settings = $element->get_settings();
			if (isset($settings['widgetopts_fixed']) && 'yes' == $settings['widgetopts_fixed']) {
				$element->add_render_attribute('_wrapper', 'class', 'widgetopts-fixed-this');
			}
		}
	}
}

if (!function_exists('widgetopts_elementor_extra_js')) {
	add_action('wp_footer', 'widgetopts_elementor_extra_js');
	function widgetopts_elementor_extra_js()
	{ ?>
		<script type="text/javascript">
			(function($, window, document, undefined) {
				if (jQuery('.widgetopts-placeholder-e').length > 0) {
					// jQuery('.elementor-column-wrap:has(.widgetopts-placeholder-e)').hide();
					jQuery('.elementor-section:has(.widgetopts-placeholder-e)').each(function() {
						var pTop = jQuery(this).find('.elementor-element-populated').css('padding-top');
						var pBot = jQuery(this).find('.elementor-element-populated').css('padding-bottom');
						var pHeight = jQuery(this).find('.elementor-element-populated').innerHeight();
						var vert = pHeight - (parseFloat(pTop) + parseFloat(pBot));

						if (typeof vert !== 'undefined' && vert < 5) {
							jQuery(this).hide();
						} else {
							jQuery(this).find('.widgetopts-placeholder-e').each(function() {
								jQuery(this).closest('.elementor-element').hide();

								var countEl = jQuery(this).closest('.elementor-column').find('.elementor-element').length;
								var countHolder = jQuery(this).closest('.elementor-column').find('.widgetopts-placeholder-e').length;
								if (countEl == countHolder) {
									jQuery(this).closest('.elementor-column').hide();
								}
							}).promise().done(function() {
								var sTop = jQuery(this).closest('.elementor-section').css('padding-top');
								var sBot = jQuery(this).closest('.elementor-section').css('padding-bottom');
								var sHeight = jQuery(this).closest('.elementor-section').innerHeight();
								var svert = sHeight - (parseFloat(sTop) + parseFloat(sBot));

								if (typeof svert !== 'undefined' && svert < 5) {
									jQuery(this).closest('.elementor-section').hide();
								}
							});
						}
					});
				}
			})(jQuery, window, document);
		</script>
<?php }
}

// if( !function_exists( 'widgetopts_elementor_before_element_render' ) ){
// 	add_action( 'elementor/frontend/element/before_render', 'widgetopts_elementor_before_element_render', 10, 2 );
// 	function widgetopts_elementor_before_element_render( $element ){
// 		global $widget_options;
// 		if( 'activate' == $widget_options['fixed'] ){
// 			$settings = $element->get_settings();
// 			print_r( $element->get_name() );
// 			if( isset( $settings['widgetopts_fixed'] ) && 'on' == $settings['widgetopts_fixed'] ){
// 				$element->add_render_attribute( '_wrapper', 'class', 'widgetopts-fixed-this' );
// 			}
// 		}
// 	}
// }
?>