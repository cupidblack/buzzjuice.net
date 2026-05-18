<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MWP_Product_Referral_Module' ) ) :
	class MWP_Product_Referral_Module extends MWP_Module {

		public $ref_key;
		public $tracking_key;
		public $badge_enabled = false;
		public $rank_enabled  = false;
		
		public function __construct() {

			parent::__construct( 'MWP_Product_Referral_Module', array(
				'module_name' => 'mwp_product_referral',
				'defaults'    => array(
					'can_cookie_expire'      => 0,
					'cookie_expiration_days' => 1,
					'ref_cookie_name'        => 'mycred_woo_product_ref',
					'referral_link_type'     => 'id',
					'enable_myaccount_tab'   => false,
					'tab_label'              => 'Product Referral'
				),
				'add_tab'     => true,
				'title'       => __('Product Referral' , 'mycred-woocommerce-plus'),
				'icon'        => 'dashicons-networking',
				'tab_pos'     => 14
			) );

			$this->ref_key      = apply_filters( 'mwp_product_referral_key', 'productref', $this );
			$this->tracking_key = apply_filters( 'mwp_product_tracking_key', 'tracking', $this );

			$this->pre_init();

		}

		public function pre_init() {
			
			$this->load_hook();
			add_action( 'mycred_pre_init',   							  array( $this, 'load_product_referred' ), 90 );
			add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'process_referrals' ), 10 );
			// Hook for legacy checkout
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'process_referrals' ), 10, 1 );

		}

		public function module_init() {

			$this->badge_enabled = class_exists( 'myCRED_Badge_Module' );
			$this->rank_enabled  = class_exists( 'myCRED_Ranks_Module' );

			add_filter( 'mycred_setup_hooks',    		  		  		array( $this, 'setup_hook' ) );
			add_filter( 'mycred_all_references', 		  		  		array( $this, 'register_hook_refrence' ) );
			add_filter( 'mycred_parse_log_entry_product_referral',      array( $this, 'parse_template_tags' ), 10, 2 );
			add_shortcode( 'mycred_woocommerce_product_referral', 		array( $this, 'render_product_referral' ) );
			add_action( 'mycred_front_enqueue', 				  		array( $this, 'register_assets' ) );
			add_action( 'wp_ajax_mwp_generate_product_referral',  	    array( $this, 'generate_product_referral' ) );
			add_action( 'wp_ajax_nopriv_mwp_generate_product_referral', array( $this, 'generate_product_referral' ) );

			if ( $this->prefs['enable_myaccount_tab'] ) {

				$slug = str_replace( ' ', '-', strtolower( $this->prefs['tab_label'] ) );

				add_filter( 'woocommerce_account_menu_items',             array( $this, 'add_tab' ) );
				add_action( 'init',                           		      array( $this, 'url_rewrite_for_tab' ) );
				add_filter( 'query_vars',                     		      array( $this, 'add_tab_query_vars' ), 0 );
				add_action( 'woocommerce_account_' . $slug . '_endpoint', array( $this, 'render_tab' ) );
				

			}

		}

		public function get_users_badges( $user_id ) {

			$cache_key = 'mwp_restrict_products_users_badges';
			$badge_ids = wp_cache_get( $cache_key );

			if ( false === $badge_ids ) {

				$badge_ids 	  = array();
				$users_badges = mycred_get_user_meta( $user_id, 'mycred_badge_ids', '', true );

				if ( ! empty( $users_badges ) ) {

					$badge_ids = array_keys( $users_badges );

				}

				wp_cache_set( $cache_key, $badge_ids );

			}

			return apply_filters( 'mwp_get_users_badge_ids', $badge_ids, $user_id );
		}

		public function get_products_prefs() {

			$cache_key = 'mwp_restrict_products_prefs';
			$prefs     = wp_cache_get( $cache_key );

			if ( false === $prefs ) {

				global $wpdb;

				$prefs = array();

				$products_prefs = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM %i WHERE meta_key = %s",
						$wpdb->prefix . 'postmeta',
						'mwp_restrict_product_pref'
					)
				);

				if ( ! empty( $products_prefs ) ) {

					foreach ( $products_prefs as $key => $meta ) {

						if ( ! empty( $meta->meta_value ) ) {

							$pref = unserialize( $meta->meta_value );

							if ( ! empty( $pref['enable'] ) ) {

								$prefs[ $meta->post_id ] = $pref;

							}

						}

					}
				}

				wp_cache_set( $cache_key, $prefs );

			}

			return $prefs;
		}

		public function get_users_ranks( $user_id ) {

			$cache_key = 'mwp_restrict_products_users_ranks';
			$rank_ids  = wp_cache_get( $cache_key );

			if ( false === $rank_ids ) {

				$rank_ids = array();

				$mycred_account = mycred_get_account( $user_id );

				// Get the rank for each type
				if ( ! empty( $mycred_account->balance ) ) {
					foreach ( $mycred_account->balance as $type_id => $balance ) {

						if ( ! empty( $balance->rank->post_id ) ) {

							$rank_ids[] = $balance->rank->post_id;

						}

					}
				}

				wp_cache_set( $cache_key, $rank_ids );

			}

			return apply_filters( 'mwp_get_users_rank_ids', $rank_ids, $user_id );
		}

		public function register_assets() {

			wp_register_script( 'mwp-product-referral-script', plugins_url( 'assets/js/product-referral-script.js', MYCRED_WOOPLUS_THIS ), array( 'jquery', 'mycred-select2-script' ), MYCRED_WOOPLUS_VERSION );

			wp_localize_script( 'mwp-product-referral-script', 'mwp_product_ref_data',
				array( 
					'ajaxurl' => admin_url( 'admin-ajax.php' )
				)
			);

		}
		
		public function admin_settings( $core ) {

			$settings = $core->woocommerce[ $this->module_name ];

			$this->load_template( 
				'product_referral_settings', 
				'product-referral-module/admin-settings.php',
				array( 'settings' => $settings ) 
			);
			
		}

		public function sanitize_settings( $new_data, $data, $core ) {

			$new_data[ $this->module_name ]['enable_myaccount_tab'] = isset( $data[ $this->module_name ]['enable_myaccount_tab'] ) ? 1 : 0;

			$new_data[ $this->module_name ]['tab_label'] = !empty( $data[ $this->module_name ]['tab_label'] ) ? sanitize_text_field( $data[ $this->module_name ]['tab_label'] ) : __( 'Product Referral', 'mycred-woocommerce-plus' );

			$new_data[ $this->module_name ]['ref_cookie_name'] = !empty( $data[ $this->module_name ]['ref_cookie_name'] ) ? sanitize_text_field( $data[ $this->module_name ]['ref_cookie_name'] ) : 'mycred_woo_product_ref';

			$new_data[ $this->module_name ]['can_cookie_expire'] = isset( $data[ $this->module_name ]['can_cookie_expire'] ) ? 1 : 0;

			$new_data[ $this->module_name ]['cookie_expiration_days'] = !empty( $data[ $this->module_name ]['cookie_expiration_days'] ) ? absint( $data[ $this->module_name ]['cookie_expiration_days'] ) : 1;

			$new_data[ $this->module_name ]['referral_link_type'] = !empty( $data[ $this->module_name ]['referral_link_type'] ) ? sanitize_key( $data[ $this->module_name ]['referral_link_type'] ) : 'id';


			return $new_data;

		}

		public function setup_hook( $installed ) {
			
			$installed['product_referral'] = array(
				'title'       => __( 'Woocommerce Product Referral', 'mycred-woocommerce-plus' ),
				'description' => __( 'Let registered customers earn points by recommending products to their own networks of friends and family.', 'mycred-woocommerce-plus' ),
				'callback'    => array( 'MWP_Product_Referral_Hook' ),
			);

			return $installed;

		}

		public function register_hook_refrence( $references ) {
			
			$references['product_referral']         = __( 'Woocommerce Product Referral (Referrer)', 'mycred-woocommerce-plus' );
			$references['product_referral_referee'] = __( 'Woocommerce Product Referral (Referee)', 'mycred-woocommerce-plus' );
			
			return $references;

		}

		public function parse_template_tags( $content, $log_entry ) {

			if ( function_exists( 'wc_get_product' ) && ! empty( $log_entry->data ) ) {
				
				$data = maybe_unserialize( $log_entry->data );

				if ( ! empty( $data['product_id'] ) ) {

					$product = wc_get_product( absint( $data['product_id'] ) );

					$content = str_replace( 
						'%product_name%', 
						'<a href="' . esc_url( $product->get_permalink() ) . '" target="_blank">' . esc_html( $product->get_title() ) . '</a>', 
						$content 
					);

				}

			}
			
			return $content;

		}

		public function load_hook() {
			
			if ( file_exists( MYCRED_WOOPLUS_HOOKS_DIR . 'class-mwp-product-referral-hook.php' ) ) {
				include_once( MYCRED_WOOPLUS_HOOKS_DIR . 'class-mwp-product-referral-hook.php' );
			}

		}

		public function load_product_referred() {

			add_action( 'template_redirect', array( $this, 'detect_referral' ) );
			
		}

		public function detect_referral() {

			if ( 
				! empty( $_GET[ $this->ref_key ] ) && 
				! empty( $_GET[ $this->tracking_key ] )
			) {

				$ref_id        = sanitize_text_field( $_GET[ $this->ref_key ] );
				$tracking_key  = sanitize_text_field( $_GET[ $this->tracking_key ] );
				$tracking_data = json_decode( mycred_decode_values( $tracking_key ) );
				$page_id       = get_the_ID();
				$cookie_key    = $this->prefs['ref_cookie_name'] . '_' . $tracking_data->product_id;

				if ( 
					empty( $tracking_data->product_id ) || 
					empty( $tracking_data->user_ref ) ||
					$tracking_data->product_id != $page_id ||
					$tracking_data->user_ref != $ref_id ||
					! empty( $_COOKIE[ $cookie_key ] ) ||
					$this->is_referrer_current_user( $ref_id )
				) {
					$this->safe_redirect();
				}	

				$expiration = time() + ( 10 * 365 * 24 * 60 * 60 );

				if ( $this->prefs['can_cookie_expire'] ) {
					$expiration = strtotime( '+' . $this->prefs['cookie_expiration_days'] . ' day' );
				}

				$cookie_data = array(
					'ref_id'       => $ref_id,
					'tracking_key' => $tracking_key
				);

				setcookie( $cookie_key, json_encode( $cookie_data ), $expiration, COOKIEPATH, COOKIE_DOMAIN );

				$this->safe_redirect();

			}
			
		}

		public function safe_redirect() {
			
			wp_safe_redirect( remove_query_arg( array( $this->ref_key, $this->tracking_key ) ), 301 );
			exit;

		}

		public function is_referrer_current_user( $ref_id ) {

			$same_user = false;
			
			if ( is_user_logged_in() ) {
				
				$user_ref_id = absint( $ref_id );

				if ( $this->prefs['referral_link_type'] != 'id' ) {
					
					$user        = get_user_by( 'login', $ref_id );
					$user_ref_id = $user->ID;

				}

				if ( $user_ref_id == get_current_user_id() ) {	
					$same_user = true;
				}

			}

			return $same_user;

		}

		public function render_product_referral( $atts, $content = '' ) {

			$atts = shortcode_atts( array(
				'user_id'    => 'current',
				'product_id' => 0
			), $atts, 'mycred_woocommerce_product_referral' );

			if ( ( empty( $atts['user_id'] ) || $atts['user_id'] == 'current' ) && is_user_logged_in() ) {
				$atts['user_id'] = get_current_user_id();
			}

			if ( empty( $atts['user_id'] ) ) {
				return $content;
			}

			$user_id      = absint( $atts['user_id'] );
			$product_id   = absint( $atts['product_id'] );
			$product_url  = '';
			$referral_url = '';
			$product_name = '';
			$products 	  = $this->get_products();

			if ( ! empty( $product_id ) ) {

				$product = wc_get_product( $product_id );

				if ( ! empty( $product ) ) {

					$product_url  = $product->get_permalink();
					$product_name = $product->get_name();
					$referral_url = $this->get_ref_link( $user_id, $product_id, $product_url );

				}

			}

			wp_enqueue_style('dashicons');
			wp_enqueue_style( 'mwp-style' );
			wp_enqueue_style( 'mycred-select2-style' );
			wp_enqueue_script( 'mwp-product-referral-script' );
			
			ob_start();

			$this->load_template( 
				'product_referral_shortcode', 
				'product-referral-module/product-referral-shortcode.php',
				array(
					'product_id'   => $product_id,
					'product_url'  => esc_url( $product_url ),
					'referral_url' => esc_url( $referral_url ),
					'product_name' => $product_name,
					'user_id'      => $user_id,
					'products'     => $products
				) 
			);

			return ob_get_clean();
			
		}

		/**
		 * Get Ref Link
		 * Returns a given users referral id with optional url appended.
		 *
		 * @since 2.0
		 * @version 1.0
		 */
		public function get_ref_link( $user_id, $product_id, $url = '' ) {

			$ref_id = $this->get_ref_id( $user_id );

			if ( null === $ref_id ) {
				return '';
			}

			if ( ! empty( $url ) ) {
				$link = add_query_arg( array( $this->ref_key => $ref_id ), $url );
			} 
			else {
				$link = add_query_arg( array( $this->ref_key => $ref_id ) );
			}

			$link = add_query_arg( 
				array( 
					$this->tracking_key => $this->generate_tracking( $ref_id, $product_id ) 
				), 
				$link 
			);

			return apply_filters( 'mwp_product_ref_link', $link, $ref_id, $user_id, $url, $this );
			
		}

		/**
		 * Get Ref ID
		 * Returns a given users referral ID.
		 *
		 * @since 2.0
		 * @version 1.0
		 */
		public function get_ref_id( $user_id ) {

			$ref_id = null;

			if ( $this->prefs['referral_link_type'] == 'id' ) {
				
				$ref_id = $user_id;

			}
			elseif( $this->prefs['referral_link_type'] == 'username' ) {

				$user = get_userdata( $user_id );

				if ( isset( $user->user_login ) ) {
					$ref_id = urlencode( $user->user_login );
				}

			}

			return apply_filters( 'mwp_ref_id', $ref_id, $user_id, $this );

		}

		public function generate_tracking( $ref_id, $product_id ) {

			$data = array(
				'user_ref' 	 => $ref_id, 
				'product_id' => $product_id,
				'timestamp'  => time()
			);

			return mycred_encode_values( json_encode( $data ) );

		}

		public function get_products() {
		    $products = array( '0' => __( 'Select Product', 'mycred-woocommerce-plus' ) );
		    $restricted_products = array();
		    $user_id = get_current_user_id();
		    $users_badge_ids = array();
		    $users_rank_ids = array();
		    
		    $options = get_option('mycred_pref_woo');
			$mwp_restrict_products = $options['mwp_restrict_products'];
			
			
		    
		    if ( $mwp_restrict_products['enable'] == 1 ) { 
		        if ( $this->badge_enabled ) {
		            $users_badge_ids = $this->get_users_badges( $user_id );
		        }

		        if ( $this->rank_enabled ) {
		            $users_rank_ids = $this->get_users_ranks( $user_id );
		        }

		        $products_prefs = $this->get_products_prefs();

		        if ( ! empty( $products_prefs ) ) {
		            foreach ( $products_prefs as $prod_id => $prefs ) {
		                if ( $this->badge_enabled && ! empty( $prefs['type'] ) && $prefs['type'] == 'badge' ) {
		                    if ( ! array_intersect( $users_badge_ids, $prefs['selected_ids'] ) ) {
		                        $restricted_products[] = $prod_id;
		                    }
		                } elseif ( $this->rank_enabled && ! empty( $prefs['type'] ) && $prefs['type'] == 'rank' ) {
		                    if ( ! array_intersect( $users_rank_ids, $prefs['selected_ids'] ) ) {
		                        $restricted_products[] = $prod_id;
		                    }
		                }
		            }
		        }
		    }

		    $raw_products = wc_get_products( array( 'limit' => -1, 'post_status' => 'publish' ) );

		    if ( ! empty( $raw_products ) ) {
		        foreach ( $raw_products as $product ) {
		            if ( ! in_array( $product->get_id(), $restricted_products ) ) {
		                $products[ $product->get_id() ] = $product->get_title();
		            }
		        }
		    }

		    return $products;
		}

		public function add_tab( $items ) {

			$slug 			= str_replace( ' ', '-', strtolower( $this->prefs['tab_label'] ) );
			$items[ $slug ] = $this->prefs['tab_label'];

			return $items;

		}

		public function url_rewrite_for_tab() {
			
			$slug = str_replace( ' ', '-', strtolower( $this->prefs['tab_label'] ) );
			
			add_rewrite_endpoint( $slug, EP_ROOT | EP_PAGES );

		}

		public function add_tab_query_vars( $vars ) {

			$vars[] = str_replace( ' ', '-', strtolower( $this->prefs['tab_label'] ) );

			return $vars;
			
		}

		public function render_tab() {

			$this->load_template(
				'product_referral_generator_tab',
				'product-referral-module/front-product-referral-tab.php',
				array()
			);
			
		}

		public function tab_title( $title, $id ) {

			global $wp_query;

			$slug = str_replace( ' ', '-', strtolower( $this->prefs['tab_label'] ) );

			if ( is_account_page() && isset( $wp_query->query_vars[ $slug ] ) ) {

				return __( 'Product Referral Generator', 'mycred-woocommerce-plus' );

			}

			return $title;

		}

		public function generate_product_referral() {

			if ( 
				empty( $_POST['nonce'] ) || 
				empty( $_POST['prod_id'] ) || 
				! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'mwp_product_referral_nonce' ) || 
				( empty( $_POST['uid'] ) && ! is_user_logged_in() )
			) {
				wp_send_json( array( 'status' => 'fail', 'msg' => 'Something went wrong try later.' ) );
			}

			$product_id = absint( $_POST['prod_id'] );
			$user_id    = get_current_user_id();

			if ( ! empty( $_POST['uid'] ) ) {
				$user_id = absint( $_POST['uid'] );
			}

			$product = wc_get_product( $product_id );

			if ( empty( $product ) ) {
				wp_send_json( array( 'status' => 'fail', 'msg' => 'Something went wrong try later.' ) );
			}

			$product_url  = $product->get_permalink();
			$referral_url = $this->get_ref_link( $user_id, $product_id, $product_url );
			
			$data = array(
				'product_url'  => $product_url,
				'referral_url' => $referral_url
			);

			wp_send_json( array( 'status' => 'success', 'data' => $data ) );

		}

		public function process_referrals( $order ) {

			// Check if $order is not an object, assume it's an order ID and retrieve the order object for Block and legacy cart hooks

			if ( ! is_object( $order ) ) {
		        $order = wc_get_order( $order );
		    }

			$referral_data = array();
			$order_items   = $order->get_items();
			
			foreach( $order_items as $product ) {

				$product_id = $product->get_product_id();				
				$cookie_key = $this->prefs['ref_cookie_name'] . '_' . $product_id;

				if ( ! empty( $_COOKIE[ $cookie_key ] ) ) {

					$raw_ref_data = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_key ] ) );
					$ref_data     = json_decode( $raw_ref_data );

					if ( ! empty( $ref_data->ref_id ) && ! empty( $ref_data->tracking_key ) ) {
						
						$ref_id        = sanitize_text_field( $ref_data->ref_id );
						$tracking_key  = sanitize_text_field( $ref_data->tracking_key );
						$tracking_data = json_decode( mycred_decode_values( $tracking_key ) );

						if ( 
							! empty( $tracking_data->user_ref ) &&
							! empty( $tracking_data->product_id ) && 
							$tracking_data->user_ref == $ref_id &&
							$tracking_data->product_id == $product_id &&
							! $this->is_referrer_current_user( $ref_id )
						) {

							$referrer_id = absint( $ref_id );

							if ( $this->prefs['referral_link_type'] != 'id' ) {
								
								$user        = get_user_by( 'login', $ref_id );
								$referrer_id = $user->ID;

							}

							$referral_data[ $product_id ] = array(
								'referrer_id' => $referrer_id,
								'data'        => $tracking_data
							);

							setcookie( $cookie_key, "", time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
							
						}	

					}

				}

			}

			if ( ! empty( $referral_data ) ) {
				
				$order->update_meta_data( 'mwp_referral_data', $referral_data );
				$order->save();

			}

		}

	}
endif;

function mwp_load_product_referral_module() {

	$module = new MWP_Product_Referral_Module();
	$module->load();

}
mwp_load_product_referral_module();