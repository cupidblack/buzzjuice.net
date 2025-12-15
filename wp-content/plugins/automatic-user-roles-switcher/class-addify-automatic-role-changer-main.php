<?php
/**
 * Plugin Name:             Automatic User Roles Switcher
 * Plugin URI:              https://woo.com/products/automatic-user-roles-switcher/
 * Description:             Easily manage your customers and offer tailored products and promotions by automatically switching user roles.
 * Version:                 1.3.1
 * Author:                  Addify
 * Domain Path:             /languages
 * Author URI:              https://woo.com/vendor/addify/
 * Support:                 https://woo.com/vendor/addify/
 * Text Domain:             addify_arc
 * WC requires at least:    3.0.9
 * WC tested up to:         10.*.*
 * Requires Plugins:        WooCommerce
 * Woo:                     8250222:f774187adbfe5aff6b109f94a1a2ea94
 * Woo: 8250222:f774187adbfe5aff6b109f94a1a2ea94

*/

defined( 'ABSPATH' ) || exit;

class Addify_Automatic_Role_Changer_Main {

	public function __construct() {

		$this->plugin_global_vars_defined();
		
		add_action( 'wp_loaded', array( $this, 'arc_text_domain' ) );
		
		add_action( 'init', array( $this, 'cron_job_user_history' ) );
		
		add_action( 'wp_ajax_nopriv_arc_add_customer_search', array( $this, 'arc_add_customer_search' ) );
		
		add_filter( 'woocommerce_email_classes', array( $this, 'include_new_email_file' ), 90, 1 );

		add_filter('post_updated_messages', array( $this, 'automatic_urs_custom_post_updated_messages' ) );
		
		if ( is_admin() ) {
			
			include_once AFARC_PLUGIN_DIR . 'class-addify-automatic-role-changer-admin.php';
			
			include_once AFARC_PLUGIN_DIR . '/includes/class-addify-automatic-role-changer-rules.php';
			
			include_once AFARC_PLUGIN_DIR . '/includes/class-addify-automatic-role-changer-user-profile.php';
		}

		add_action( 'woocommerce_order_status_completed', array( $this, 'automatic_role_changer_front_data' ), 100, 1 );
		
		add_filter( 'cron_schedules', array( $this, 'addf_arc_user_role_add_cron_interval' ) );
		
		add_action( 'addf_crone_time', array( $this, 'addify_remove_user_role' ) );

		add_action( 'wp_loaded', array( $this, 'addify_remove_user_role' ), PHP_INT_MAX );

		add_action( 'addf_2_crone_time', array( $this, 'addify_assign_user_role_on_membership' ) );

		add_action( 'init', array( $this, 'addify_assign_user_role_on_membership' ) );
		
		add_action( 'init', array( $this, 'addf_arc_user_role_schedule_call_back' ) );

		register_deactivation_hook( __FILE__, array( $this, 'arc_crone_deactivation' ) );

		add_action('before_woocommerce_init', array( $this, 'arc_HOPS_Compatibility' ) );
	}

	public function automatic_urs_custom_post_updated_messages( $messages ) {

		global $post;

		if ( 'automatic_rc' == $post->post_type ) {
			
			$messages['automatic_rc'] = array(

				0  => '',

				1  => 'post updated.',

				2  => 'post updated updated.',

				3  => 'post updated deleted.',

				4  => 'post updated updated.',

				5  => isset($_GET['revision']) ? sprintf('post restored to revision from %s', wp_post_revision_title((int) $_GET['revision'], false)) : false,

				6  => 'post updated published.',

				7  => 'post updated saved.',

				8  => 'post updated submitted.',

				9  => sprintf( 'post updated scheduled for: <strong>%1$s</strong>.', date_i18n(__('M j, Y @ G:i'), strtotime( $post->post_date ) )
				),

				10 => 'post updated draft updated.',
			);
		}

		return $messages;
	}

	public function af_urs_check_date( $rule_id ) {

		$date_match = false;

		$current_date       = gmdate('Y-m-d');

		$date_start_init = get_post_meta( $rule_id, 'date_start', true );

		$date_end_init   = get_post_meta( $rule_id, 'date_end', true );

		if ( ! empty( $date_start_init ) && strtotime( $date_start_init ) > strtotime( $current_date ) ) {
			
			return $date_match;

		} elseif ( ! empty( $date_end_init ) && strtotime( $date_end_init ) < strtotime( $current_date ) ) {

			return $date_match;

		}

		$date_match = true;

		return $date_match;
	}

	public function af_urs_get_members_posts() {

		$total_members = array();

		$af_all_members = array();

		$wc_all_members = array();

		if ( in_array( 'ultimate-memberships-woocommerce/woocommerce-ultimate-membership.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {

			$af_all_members = get_posts(

				array(

					'post_type'   => 'af_member',

					'post_status' => 'any',

					'numberposts' => -1,

					'fields'      => 'ids',
				)
			);

		}

		if ( in_array( 'woocommerce-memberships/woocommerce-memberships.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {

			$wc_all_members = get_posts(

				array(

					'post_type'   => 'wc_user_membership',

					'post_status' => 'any',

					'numberposts' => -1,

					'fields'      => 'ids',
				)
			);
		}

		$total_members = array_merge( $af_all_members, $wc_all_members );

		return $total_members;
	}

	public function user_membrship_ids() {

		if ( empty( $this->af_urs_get_members_posts() ) ) {
			
			return;
		}

		$user_membrship_ids = array(); 

		foreach ( $this->af_urs_get_members_posts() as $member_id) {
			
			if ( 'wc_user_membership' == get_post_type( $member_id ) ) {

				$wc_member_user_id  = wc_memberships_get_user_membership( $member_id )->get_user_id();

				$plan_id            = wc_memberships_get_user_membership( $member_id )->get_plan_id();

				$membership         = wc_memberships_get_user_membership( $member_id ); 
				
				$plan_start_date    = get_the_date('Y-m-d', $membership->get_id());

				$plan_status        = wc_memberships_get_user_membership( $member_id )->get_status();

				$arr = array(
					'plan_id' => $plan_id,
					'plan_status' => $plan_status,
					'plan_start_date' => $plan_start_date,
				);

				$user_membrship_ids[ $wc_member_user_id ][] = $arr;

			} else {

				$wc_member_user_id = get_post_meta( $member_id, 'afwum_member_user', true);

				$plan_id = get_post_meta( $member_id, 'af_member_plan', true );

				$plan_status = get_post_meta( $member_id, 'af_member_status', true );

				$af_member_since     = get_post_meta( $member_id, 'af_member_since', true );

				$arr = array(
					'plan_id' => $plan_id,
					'plan_status' => $plan_status,
					'plan_start_date' => $af_member_since,
				);

				$user_membrship_ids[ $wc_member_user_id ][] = $arr;
			}
		}

		return $user_membrship_ids;
	}

	public function addify_assign_user_role_on_membership() {

		if ( empty( $this->user_membrship_ids() ) || empty( $this->af_role_switcher_rules() ) ) {
			
			return;
		}

		$chose_options_init = '';

		foreach ( $this->af_role_switcher_rules() as $rule_id ) {

			if ( ! $this->af_urs_check_date( $rule_id ) ) {
				
				continue;
			}

			$multiple_roles                 = get_post_meta( $rule_id, 'multiple_roles', true );

			$from_select_user_from_switch   = (array) get_post_meta( $rule_id, 'from_select_user_from_switch', true );

			$chose_options_init             = get_post_meta( $rule_id, 'chose_options', true );

			$membership_plans               = (array) get_post_meta( $rule_id, 'specific_memberships', true);

			$af_memberships                 = get_post_meta( $rule_id, 'af_memberships', true);

			$number_of_days                 = get_post_meta( $rule_id, 'roles_duration', true );

			$af_membership_status           = (array) get_post_meta( $rule_id, 'af_membership_status', true);

			$grant_select_user_switch       = (array) get_post_meta( $rule_id, 'grant_select_user_from_switch', true );

			if ( 'memberships' != $chose_options_init ) {
				continue;
			}

			$user_current_role = '';

			$customer_email = '';

			foreach ( $this->user_membrship_ids() as $user_id => $plan_details) {

				$switch_to_user_role    = '';

				$user_data              = get_userdata($user_id);

				if ( ! $user_data ) {
					continue;
				}

				$customer_email         = $user_data->user_email;

				$user_role              = $user_data->roles;

				$user_current_role      = current( $user_data->roles );

				if ( 'gain' == $multiple_roles ) {
					
					if ( ! array_intersect( $user_role , $from_select_user_from_switch ) ) {
						
						continue 2;
					}
				}

				$role_change = false;

				$counter = 0;

				foreach ($plan_details as $plan_detail) {

					$plan_id        = $plan_detail['plan_id'];

					$plan_status    = $plan_detail['plan_status'];

					if ( 'all' == $af_memberships ) {

						if ( in_array( $plan_id, $membership_plans ) ) {
							
							$counter++;
						}

						if ( count( $membership_plans ) != $counter ) {

							continue;
						}

						$role_change = true;

					} else if ( 'any' == $af_memberships ) {

						if ( ! in_array( $plan_id, $membership_plans ) ) {

							continue;
						
						}

						$role_change = true;
					}
				}

				if ($role_change) {

					$total_match_role = array_intersect($grant_select_user_switch, $user_role);

					$is_user_role_add = false;

					if ( 'gain' == $multiple_roles  ) {

						if ( count( $total_match_role ) >= count( $grant_select_user_switch ) ) {
							
							continue;
						}

						foreach ($grant_select_user_switch as $select_user_role) {

							$user_data->add_role($select_user_role);

							$is_user_role_add = true;

						}

						$switch_to_user_role = implode( ',', $grant_select_user_switch );
					}

					$mem_rule_id        = $rule_id;

					$number_of_days     = ! empty( $number_of_days ) ? $number_of_days : 0;

					$current_date       = gmdate('Y-m-d');

					$new_history = (array) get_user_meta( $user_id, 'af_arc_data', true );
					
					$user_ = get_user_by('ID', $user_id);
					
					if ( $is_user_role_add ) {

						$new_history[]              = array(

							'switch_from_role'      => $user_current_role,

							'switch_to_role'        => $switch_to_user_role,

							'date_changed'          => $current_date,

							'switched'              => 'single_u' == $multiple_roles ? 'switch' : 'gain',

							'rule_id'               => $mem_rule_id,

							'reason_to_change'      => $chose_options_init,

							'switch_for_total_days' => $number_of_days,

							'arc_date_start'        => gmdate( 'Y-m-d' ),

							'arc_end_start'         => gmdate( 'Y-m-d' , strtotime('+' . $number_of_days . ' days') ),
						);

					}

					update_user_meta( $user_id, 'af_arc_data', $new_history );

					$to_purchase_product = $customer_email;

					do_action( 'addify_automayic_role_changed_email', $user_id );

				}
			}       
		}
	}

	public function arc_HOPS_Compatibility() {

		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}

	public function addf_arc_user_role_schedule_call_back() {
		
		if ( ! wp_next_scheduled( 'addf_crone_time' ) ) {

			wp_schedule_event( time(), 'every_minutes', 'addf_crone_time' );
		}

		if ( ! wp_next_scheduled( 'addf_2_crone_time' ) ) {

			wp_schedule_event( time(), 'every_2_minutes', 'addf_2_crone_time' );
		}
	}

	public function arc_crone_deactivation( $user ) {
		
		wp_clear_scheduled_hook( 'addf_crone_time' );

		wp_clear_scheduled_hook( 'addf_2_crone_time' );
	}

	public function addf_arc_user_role_add_cron_interval( $schedules ) {
		
		$schedules['every_minutes'] = array(

			'interval' => 3600,

			'display'  => 'Every 1 hour',

		);

		$schedules['every_2_minutes'] = array(

			'interval' => 120,

			'display'  => 'Every 2 minutes',

		);
		
		return $schedules;
	}

	public function af_role_switcher_rules() {

		$all_rules = array();

		$all_rules = get_posts(
			
			array(

				'post_type'   => 'automatic_rc',

				'post_status' => 'publish',

				'numberposts' => -1,

				'order_by'    => 'post_date',

				'fields'      => 'ids',

			)
		);

		return $all_rules;
	}

	public function addify_remove_user_role() {

		$all_users = get_users( 
			array( 
				'fields'   => 'ids',
				'meta_key' => 'af_arc_data',
			)
		);

		foreach ( $all_users as $user_id ) {

			$flag1 = false;

			$flag = false;

			$new_meta = get_user_meta( $user_id, 'af_arc_data', true );

			$user     = get_user_by( 'id', $user_id );

			if ( '' === $new_meta ) {
				continue;
			}

			$last_updated = end( $new_meta );

			$switch_for_total_days_history  = esc_attr( $last_updated['switch_for_total_days'] );
			$switch_from_user_role_historty = esc_attr( ucwords( str_replace( '_', ' ', $last_updated['switch_from_role'] ) ) );
			$switch_to_user_role_historty   = esc_attr( ucwords( str_replace( '_', ' ', $last_updated['switch_to_role'] ) ) );
			$reason_to_change_history       = esc_attr( ucwords( str_replace( '_', ' ', $last_updated['reason_to_change'] ) ) );
			$rule_id                        = isset( $last_updated['rule_id'] ) ? esc_attr( ucwords( str_replace( '_', ' ', $last_updated['rule_id'] ) ) ) : '';

			$date_changed_history           = esc_attr( gmdate('Y-m-d', strtotime( $last_updated['date_changed'] ) ) );

			if ( 'sub_prod' == $last_updated['reason_to_change'] ) {

				if ( ! in_array( 'woocommerce-subscriptions/woocommerce-subscriptions.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true )) {

					return;
				}

				$sub_specific_products      = (array) get_post_meta( $rule_id, 'sub_specific_products', true );

				$af_subscription_status     = (array) get_post_meta( $rule_id, 'af_subscription_status', true );

				$af_no_of_days              = get_post_meta( $rule_id, 'af_no_of_days', true );

				$subscriptions              = wcs_get_subscriptions(array( 'subscriptions_per_page' => -1 ));

				foreach ( $subscriptions as $sub_id => $subscription ) {

					$data = $subscription->get_data();

					if ( isset( $data['parent_id'] ) && 0 == $data['parent_id'] ) {
						return;
					}

					$order_info = wc_get_order( $data['parent_id'] );

					$items = $order_info ? $order_info->get_items() : array();

					foreach ( $items as $item ) {
						
						$item_info  = $item->get_data();

						$date       = new DateTime($data['date_created']);

						$onlyDate   = $date->format('Y-m-d');

						$sub_prod_id_in_order = $item_info['product_id'];

						if ( in_array( $sub_prod_id_in_order, $sub_specific_products ) ) {

							$af_subscription_status = implode( ',' , $af_subscription_status );

							$af_subscription_status = str_replace('wc-', '', $af_subscription_status );

							$af_subscription_status = explode(',' , $af_subscription_status);

							if ( in_array( $data['status'], $af_subscription_status ) ) {
								
								$flag1 = true;

							} else if ( in_array( 'days', $af_subscription_status ) && ! empty( $af_no_of_days ) ) {

								$onlyDate = gmdate('Y-m-d', strtotime($onlyDate . '+' . $af_no_of_days . ' days' ) );

								if ( strtotime( gmdate('Y-m-d') ) >= strtotime( $onlyDate ) ) {

									$flag1 = true;
								}
							}

							if ( $flag1 ) {
								
								$arc_date_start_history = isset( $last_updated['arc_date_start'] ) ? esc_attr( gmdate('Y-m-d', strtotime( $last_updated['arc_date_start'] ) ) ) : '';

								$arc_end_start_history =  isset( $last_updated['arc_end_start'] ) ? esc_attr( gmdate('Y-m-d', strtotime( $last_updated['arc_end_start'] ) ) ) : '';

								$switched = isset( $last_updated['switched'] ) ? $last_updated['switched'] : 'switch';

								if ( ! empty( get_post_meta( $rule_id, 'date_end', true ) ) ) {

									if ( strtotime( gmdate() ) <= strtotime(get_post_meta( $rule_id, 'date_end', true ) ) ) {

										$flag = true;
									}

								} else {

									$flag = true;
								}

								if ( $flag ) {
									
									if ( 'switch' == $switched ) {

										$user->remove_role( str_replace( ' ', '_', strtolower( $switch_to_user_role_historty ) ) );
										$user->add_role( str_replace( ' ', '_', strtolower( $switch_from_user_role_historty ) ) );

									} else {

										foreach (explode(',', str_replace( ' ', '_', strtolower( $switch_to_user_role_historty ) )) as $remove_role) {
											
											$user->remove_role( $remove_role );

										};
									}
								}
							}
						}
					}
				}

			} else if ( 'memberships' == $last_updated['reason_to_change'] ) {

				$specific_memberships = (array) get_post_meta($rule_id, 'specific_memberships', true);

				$af_membership_status = (array) get_post_meta($rule_id, 'af_membership_status', true);

				$mem_no_of_days = get_post_meta($rule_id, 'mem_no_of_days', true);

				foreach ( $specific_memberships as $specific_membership) {

					if ('af_membership_plan' == get_post_type($specific_membership) ) {
						
						$addify_member_array = get_posts(array(
							'post_type' => 'af_member',
							'numberposts' => -1,
							'fields' => 'ids',
							'post_status' => 'publish',
							'meta_key' => 'af_member_plan',
							'meta_value' => $specific_membership,
						));

						foreach ( $addify_member_array as $addify_member) {

							$flag1 = false;

							$member_user_id = get_post_meta( $addify_member, 'afwum_member_user', true);

							$user  = get_user_by('id', $member_user_id );
							
							if ( ! $user ) {
							
								continue;
							
							}
							
							$publish_date = get_post_meta($addify_member, 'af_member_since', true);
							
							$addify_membership_status = get_post_meta( $addify_member, 'af_member_status', true);

							if ( in_array($addify_membership_status, $af_membership_status) ) {
								
								$flag1 = true;

							} else if ( in_array( 'days', $af_membership_status ) && ! empty( $mem_no_of_days ) ) {

								$onlyDate = gmdate('Y-m-d', strtotime($publish_date . '+' . $mem_no_of_days . ' days' ) );

								if ( strtotime( gmdate('Y-m-d') ) >= strtotime( $onlyDate ) ) {

									$flag1 = true;
								}
							}

							if ( $flag1 ) {
								
								$arc_date_start_history = isset( $last_updated['arc_date_start'] ) ? esc_attr( gmdate('Y-m-d', strtotime( $last_updated['arc_date_start'] ) ) ) : '';

								$arc_end_start_history =  isset( $last_updated['arc_end_start'] ) ? esc_attr( gmdate('Y-m-d', strtotime( $last_updated['arc_end_start'] ) ) ) : '';

								$switched = isset( $last_updated['switched'] ) ? $last_updated['switched'] : 'switch';

								$flag = false;

								if ( ! empty( get_post_meta( $rule_id, 'date_end', true ) ) ) {

									if ( strtotime( gmdate() ) <= strtotime(get_post_meta( $rule_id, 'date_end', true ) ) ) {

										$flag = true;
									}

								} else {

									$flag = true;
								}

								$exploded_array = explode(',', $last_updated['switch_to_role']);

								if ( $flag ) {
									
									if ( 'switch' == $switched ) {

										$user->remove_role( str_replace( ' ', '_', strtolower( $switch_to_user_role_historty ) ) );
										$user->add_role( str_replace( ' ', '_', strtolower( $switch_from_user_role_historty ) ) );

									} else {

										foreach ( $exploded_array as $remove_role) {
											
											$user->remove_role( $remove_role ); 

										}
									}
								}
							}
						}

					} else {

						$wc_member_array = get_posts(array(
							'post_type' => 'wc_user_membership',
							'numberposts' => -1,
							'post_status' => 'any',
							'post_parent' => $specific_membership,
						));

						foreach ($wc_member_array as $value) {

							$post_status    =  $value->post_status;
							
							$post_id        = $value->ID;

							$post_start_date = get_post_meta($value->ID, '_start_date', true );

							if ( in_array( $post_status, $af_membership_status)) {
								
								$flag1 = true;

							} else if ( in_array( 'days', $af_membership_status ) && ! empty( $mem_no_of_days ) ) {

								$onlyDate = gmdate('Y-m-d', strtotime($post_start_date . '+' . $mem_no_of_days . ' days' ) );

								if ( strtotime( gmdate('Y-m-d') ) >= strtotime( $onlyDate ) ) {

									$flag1 = true;
								}
							}

							if ( $flag1 ) {

								$arc_date_start_history = isset( $last_updated['arc_date_start'] ) ? esc_attr( gmdate('Y-m-d', strtotime( $last_updated['arc_date_start'] ) ) ) : '';

								$arc_end_start_history =  isset( $last_updated['arc_end_start'] ) ? esc_attr( gmdate('Y-m-d', strtotime( $last_updated['arc_end_start'] ) ) ) : '';

								$switched = isset( $last_updated['switched'] ) ? $last_updated['switched'] : 'switch';

								$flag = false;

								if ( ! empty( get_post_meta( $rule_id, 'date_end', true ) ) ) {
									echo 'string';
									if ( strtotime( gmdate() ) <= strtotime(get_post_meta( $rule_id, 'date_end', true ) ) ) {

										$flag = true;
									}

								} else {

									$flag = true;
								}

								if ( $flag ) {

									if ( 'switch' == $switched ) {

										$user->remove_role( str_replace( ' ', '_', strtolower( $switch_to_user_role_historty ) ) );
										$user->add_role( str_replace( ' ', '_', strtolower( $switch_from_user_role_historty ) ) );

									} else {

										foreach (explode(',', str_replace( ' ', '_', strtolower( $switch_to_user_role_historty ) )) as $remove_role) {
											
											$user->remove_role( $remove_role );

										};
									}
								}
							}
						}
					}
				}

			} else {

				$arc_date_start_history = isset( $last_updated['arc_date_start'] ) ? esc_attr( gmdate('Y-m-d', strtotime( $last_updated['arc_date_start'] ) ) ) : '';

				$arc_end_start_history =  isset( $last_updated['arc_end_start'] ) ? esc_attr( gmdate('Y-m-d', strtotime( $last_updated['arc_end_start'] ) ) ) : '';

				$switched = isset( $last_updated['switched'] ) ? $last_updated['switched'] : 'switch';

				if ( ! empty( $arc_end_start_history ) && time() >= strtotime( $arc_end_start_history ) ) {

					if ( 'switch' == $switched ) {

						$user->remove_role( str_replace( ' ', '_', strtolower( $switch_to_user_role_historty ) ) );
						$user->add_role( str_replace( ' ', '_', strtolower( $switch_from_user_role_historty ) ) );

					} else {

						$user->remove_role( str_replace( ' ', '_', strtolower( $switch_to_user_role_historty ) ) );
					}
				}
			}
		}
	}

	public function ady_arc_price_range( $order_subtotal, $amount_start_init = 0.0, $amount_end_init = 0.0 ) {

		$price_range = false;
		
		if ( 0.0 === floatval( $amount_start_init ) && 0.0 === floatval( $amount_end_init ) ) {

			$price_range = false;

		} elseif ( 0.0 === floatval( $amount_start_init ) && $order_subtotal <= floatval( $amount_end_init ) ) {


			$price_range = true;

		} elseif ( 0.0 === floatval( $amount_end_init ) && $order_subtotal >= floatval( $amount_start_init ) ) {

			$price_range = true;

		} elseif ( $amount_start_init <= $order_subtotal && $order_subtotal <= $amount_end_init ) {

			$price_range = true;
		}
		
		return $price_range;
	}

	public function ady_arc_customer_total_spent( $customer_total_spent, $amount_start_init = 0.0, $amount_end_init = 0.0 ) {

		$total_spent = false;
		
		if ( 0.0 === floatval( $amount_start_init ) && 0.0 === floatval( $amount_end_init ) ) {

			$total_spent = false;

		} elseif ( 0.0 === floatval( $amount_start_init ) && $customer_total_spent <= floatval( $amount_end_init ) ) {


			$total_spent = true;

		} elseif ( 0.0 === floatval( $amount_end_init ) && $customer_total_spent >= floatval( $amount_start_init ) ) {

			$total_spent = true;

		} elseif ( $amount_start_init <= $customer_total_spent && $customer_total_spent <= $amount_end_init ) {

			$total_spent = true;
		}

		return $total_spent;
	}

	public function automatic_role_changer_front_data( $order_id = 0 ) {

		global $woocommerce, $post;

		$order                = wc_get_order( $order_id );

		$customer_id          = $order->get_user_id();
		
		$customer_total_spent = wc_get_customer_total_spent( $customer_id );
		
		$order_subtotal       = $order->get_subtotal();
		
		$customer_first_name  = $order->get_billing_first_name();
		
		$customer_last_name   = $order->get_billing_last_name();
		
		$customer_full_name   = $customer_first_name . ' ' . $customer_last_name;
		
		$customer_email       = $order->get_billing_email();
		
		$order_user           = $order->get_user();

		if ( ! $order_user ) {

			return;

		}

		$cu_emailarray      = (array) explode( '@', $customer_email );
		
		$email_durl         = $cu_emailarray[1];
		
		$current_date       = gmdate('Y-m-d');

		$items              = $order->get_items();

		$product_quantities = array();

		$products           = array();

		$products_categries = array();

		$products_tags      = array();

		foreach ( $items as $item ) {

			if ( ! in_array( $item->get_product_id(), array_keys( $product_quantities ) ) ) {

				$product_quantities[ $item->get_product_id() ] = $item->get_quantity();

			} else {

				$product_quantities[ $item->get_product_id() ] += $item->get_quantity();
			}

			$products[]         = $item->get_product_id();

			$products_categries = array_unique( array_merge( $products_categries, wc_get_product_term_ids( $item->get_product_id(), 'product_cat' ) ) );

			$products_tags      = array_unique( array_merge( $products_tags, wc_get_product_term_ids( $item->get_product_id(), 'product_tag' ) ) );
		}

		$products           = array_unique( $products );
		
		$user_current_role  = current( $order_user->roles );

		$all_rules          = get_posts(

			array(

				'post_type'   => 'automatic_rc',

				'post_status' => 'publish',

				'numberposts' => -1,

				'order_by'    => 'post_date',

				'fields'      => 'ids',

			)
		);

		foreach ( $all_rules as $rule_id ) {

			$sub_rule_id = 0;

			$date_start_init = get_post_meta( $rule_id, 'date_start', true );
			
			$date_end_init   = get_post_meta( $rule_id, 'date_end', true );

			$date_match = false;

			if ( ! empty( $date_start_init ) && strtotime( $date_start_init ) > strtotime( $current_date ) ) {

				continue;

			} elseif ( ! empty( $date_end_init ) && strtotime( $date_end_init ) < strtotime( $current_date ) ) {

				continue;

			}

			$select_cat_init                = get_post_meta( $rule_id, 'select_cat', true );
			
			$amount_start_init              = get_post_meta( $rule_id, 'amount_start', true );
			
			$amount_end_init                = get_post_meta( $rule_id, 'amount_end', true );
			
			$number_of_days                 = get_post_meta( $rule_id, 'roles_duration', true );

			$new_counter                    = get_post_meta( $rule_id, 'new_counter', true );
			
			$new_cbox_asq                   = get_post_meta( $rule_id, 'new_cbox', true );

			$multiple_roles                 = get_post_meta( $rule_id, 'multiple_roles', true );
			
			$domain_url                     = get_post_meta( $rule_id, 'domain_url', true );
			
			$domain_url                     = (array) explode( ',', $domain_url );
			
			$chose_options_init             = get_post_meta( $rule_id, 'chose_options', true );

			$arc_number_products            = get_post_meta( $rule_id, 'arc_number_products', true );

			$select_user_from_switch_init   = (array) get_post_meta( $rule_id, 'select_user_from_switch', true );
			
			$select_user_to_switch_init     = get_post_meta( $rule_id, 'select_user_to_switch', true );

			$grant_select_user_switch       = (array) get_post_meta( $rule_id, 'grant_select_user_from_switch', true );
			
			$from_select_user_switch        = (array) get_post_meta( $rule_id, 'from_select_user_from_switch', true );

			if ( 'single_u' === $multiple_roles ) {

				if ( ! in_array( $user_current_role, $select_user_from_switch_init ) ) {
					
					continue;
				}

			} elseif ( ! in_array( $user_current_role, $from_select_user_switch ) ) {

				continue;
			}

			$chose_product_gainrole_init = get_post_meta( $rule_id, 'chose_product_gainrole', true );

			$user_product_match = false;

			$sub_flag = false;

			switch ( $chose_options_init ) {

				case ( 'purchase_product' ):
					switch ( $new_cbox_asq ) {

						case 'any':
							if ( array_intersect( $chose_product_gainrole_init, $products ) ) {

								$user_product_match = true;

							}

							break;

						case 'all':
							if ( count( array_intersect( $chose_product_gainrole_init, $products ) ) == count( $chose_product_gainrole_init ) ) {

								$user_product_match = true;

							}

							break;

						case 'quantity':
							if ( array_sum( array_intersect_key( $product_quantities, array_flip( $chose_product_gainrole_init ) ) ) >= $new_counter ) {

								$user_product_match = true;

							}

							break;

						case 'products':
							if ( count( array_intersect_key( array_unique( $products ), $chose_product_gainrole_init ) ) >= $new_counter ) {

								$user_product_match = true;

							}

							break;
					}

					if ( ! $user_product_match ) {

						continue 2;
					}

					break;
				
				case ( 'price_range' ):
				$price_range_meet = $this->ady_arc_price_range( $order_subtotal, $amount_start_init, $amount_end_init );

					if ( ! $price_range_meet ) {

						continue 2;
					}

					break;
				
				case ( 'total_spend' ):
				$total_spent_meet = $this->ady_arc_customer_total_spent( $customer_total_spent, $amount_start_init, $amount_end_init );

					if ( ! $total_spent_meet ) {

						continue 2;

					}

					break;

				case ( 'product_cat_tag' ):
					if ( 'select_taxonomy_cat' == $select_cat_init ) {

						$select_product_category_init = (array) get_post_meta( $rule_id, 'select_product_category', true ) ;

						if ( ! empty( $select_product_category_init ) && ! array_intersect( $products_categries, $select_product_category_init ) ) {

							continue 2;
						}

					} elseif ( 'select_taxonomy_tag' == $select_cat_init ) {

						$select_product_tag_init = (array) get_post_meta( $rule_id, 'select_product_tag', true ) ;

						if ( ! empty( $select_product_tag_init ) && ! array_intersect( $products_tags, $select_product_tag_init ) ) {

							continue 2;
						}

					}

					break;
				
				case ( 'email_domain_v' ):
					if ( ! in_array( $email_durl, $domain_url ) ) {

						continue 2;
					}

					break;
				
				case 'number_products':
					if ( count( array_unique( $products ) ) < $arc_number_products ) {

						continue 2;
					}

					break;
				
				case 'sub_prod':
				$all_sub_prod_ids = array();

				$orders = wc_get_orders(
					array(
						'customer_id'   => $customer_id,

						'limit'         => -1,
					)
				);

					foreach ($orders as $order) {

						foreach ($order->get_items() as $order_item) {

							$order_item_data = $order_item->get_data();

							$product_id = $order_item_data['product_id'];

							$product_type = wc_get_product( $product_id )->get_type();

							if ( 'subscription' == $product_type || 'variable-subscription' == $product_type) {

								$all_sub_prod_ids[] = $product_id;
							}
						}
					}

				$all_sub_prod_ids = array_unique($all_sub_prod_ids);

				$sub_products = (array) get_post_meta($rule_id, 'sub_specific_products', true);

					if ( ! empty( $sub_products ) ) {

						if ('all' == get_post_meta($rule_id, 'sel_products', true) ) {

							if ( ! empty( $all_sub_prod_ids ) && (int) count(array_intersect( $sub_products, $all_sub_prod_ids )) == count( $sub_products ) ) {

								$sub_rule_id = $rule_id;

								break;
							
							} else {

								$sub_flag = true;
							}

						} else {

							foreach ($sub_products as $sub_products) {

								if ( ! empty( $all_sub_prod_ids ) && in_array( $sub_products, $all_sub_prod_ids ) ) {

									$sub_rule_id = $rule_id;

									break;
								
								} else {

									$sub_flag = true;
								}
							}
						}
					}

					break;
			}

			if ( $sub_flag ) {
				
				continue;
			}

			if ( 'gain' == $multiple_roles ) {

				foreach ($grant_select_user_switch as $select_user_role) {
					
					$order_user->add_role($select_user_role);
				}
				
				$switch_to_user_role = implode( ',', $grant_select_user_switch );

			} elseif ( 'single_u' == $multiple_roles ) {

				$order_user->set_role( $select_user_to_switch_init );
				
				$switch_to_user_role = implode( ',', (array) $select_user_to_switch_init );
			}

			$new = array();
			
			$number_of_days = ! empty( $number_of_days ) ? $number_of_days : 0;

			$rule_array = array();

			$new_history = array();
			
			$new[]       = array(

				'switch_from_role'          => $user_current_role,

				'switch_to_role'            => $switch_to_user_role,

				'date_changed'              => $current_date,

				'switched'                  => 'single_u' == $multiple_roles ? 'switch' : 'gain',

				'reason_to_change'          => $chose_options_init,

				'rule_id'                   => $sub_rule_id,

				'switch_for_total_days'     => $number_of_days,

				'arc_date_start'            => gmdate( 'Y-m-d' ),

				'arc_end_start'             => gmdate( 'Y-m-d' , strtotime('+' . $number_of_days . ' days') ),
			);

			$new_history = get_user_meta( $customer_id, 'af_arc_data', true );

			$new_history1 = ! empty( $new_history ) ? $new_history : array();
			
			$arr1 = array();
			
			$arr1 = array_merge( $new_history1, $new );

			update_user_meta( $customer_id, 'af_arc_data', $arr1 );

			$to_purchase_product = $customer_email;

			do_action( 'addify_automayic_role_changed_email', $customer_id );
		}       
	}

	public function include_new_email_file( $emails ) {
		
		require_once 'class-addify-role-change-email.php';
		
		$emails['adfy_arc_email_template'] = new Addify_Role_Change_Email();
		
		return $emails;
	}

	public function cron_job_user_history() {

		if ( ! wp_next_scheduled( 'addf_crone_time' ) ) {

			wp_schedule_event( time() + 15, 'addf_arc_user_role_add_cron_interval', 'addf_crone_time' );
		}
	}

	public function plugin_global_vars_defined() {
		
		if ( ! defined( 'AFARC_URL' ) ) {

			define( 'AFARC_URL', plugin_dir_url( __FILE__ ) );

		}
		
		if ( ! defined( 'AFARC_BASENAME' ) ) {

			define( 'AFARC_BASENAME', plugin_basename( __FILE__ ) );

		}
		
		if ( ! defined( 'AFARC_PLUGIN_DIR' ) ) {

			define( 'AFARC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

		}
	}

	public function arc_text_domain() {
		
		if ( function_exists( 'load_plugin_textdomain' ) ) {

			load_plugin_textdomain( 'addify_arc', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}
	}
}

new Addify_Automatic_Role_Changer_Main();
