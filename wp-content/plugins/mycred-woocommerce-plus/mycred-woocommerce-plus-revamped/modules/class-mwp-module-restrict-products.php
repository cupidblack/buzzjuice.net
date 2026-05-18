<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MWP_Restrict_Products_Module' ) ) :
	class MWP_Restrict_Products_Module extends MWP_Module {

		public $badge_enabled = false;
		public $rank_enabled  = false;
		
		public function __construct() {

			parent::__construct( 'MWP_Restrict_Products_Module', array(
				'module_name' => 'mwp_restrict_products',
				'defaults'    => array(
					'enable' => 0
				),
				'add_tab'     => true,
				'title'       => __('Restrict Products', 'mycred-woocommerce-plus'),
				'icon'        => 'dashicons-privacy',
				'tab_pos'     => 11
			) );
			
		}

		public function module_init() {

			if ( ! $this->prefs['enable']  ) {
				return;
			}

			$this->badge_enabled = class_exists( 'myCRED_Badge_Module' );
			$this->rank_enabled  = class_exists( 'myCRED_Ranks_Module' );

			add_action( 'mycred_admin_enqueue', array( $this, 'register_admin_assets' ) );
			add_action( 'admin_init', 			array( $this, 'add_metabox' ) );
			add_action( 'save_post_product' ,   array( $this, 'save_product_settings' ), 10, 2 );
			add_action( 'save_post_mycred_rank', 	array( $this, 'mwdpbr_save_woo_cat_post_rank' ), 10, 2 );
			add_action( 'save_post_mycred_badge', 	array( $this, 'mwdpbr_save_woo_cat_post_badge' ), 10, 2 );
			add_action( 'pre_get_posts', 			array( $this, 'restrict_products' ) );
			// Restrict related products
			add_filter( 'woocommerce_related_products', array( $this, 'restrict_related_products' ), 10, 3 );
			add_action( 'pre_get_posts', 			array( $this, 'restrict_products_by_category' ) );


		}


		public function restrict_products_by_category( $query ) {

			if ( is_admin() || ! $query->is_main_query() ) {
				return;
			}

			$restricted_categories	= array();
			$user_id             	= get_current_user_id();
			$all_ranks				= $this->get_ranks();
			$all_rank_ids 			= array();
			$all_ranks_cat 			= array();
			$all_term_ids 			= array(); 
			$term_ids 				= array();
			$user_rank_ids  		= array();
			$user_rank_cat			= array();
			

			$all_badges = $this->get_badges();
			$all_badges_ids = array();
			$all_badges_cat = array();
			$user_badge_cat	= array();

			foreach ($all_badges as $badge) {
			    if ($badge instanceof WP_Post && $badge->post_type === 'mycred_badge') {
			        $all_badges_ids[] = $badge->ID;
			    }
			}

			foreach ( $all_ranks as $rank_info ) {
				if ( isset( $rank_info['ranks'] ) && is_array( $rank_info['ranks'] ) ) {
					$all_rank_ids = array_merge( $all_rank_ids, wp_list_pluck( $rank_info['ranks'], 'ID' ) );
				}
			}

			foreach ( $all_rank_ids as $key => $rank_id ) {
				$cats = mycred_get_post_meta( $rank_id, 'mycred_woo_ranks_cats', true);
				if ( $cats ) {
					$all_ranks_cat = array_merge($all_ranks_cat, $cats);
				}
			}

			foreach ( $all_badges_ids as $key => $badges_id ) {
				$cats = mycred_get_post_meta( $badges_id, 'mycred_woo_badges_cats', true);
				if ( $cats ) {
					$all_badges_cat = array_merge($all_badges_cat, $cats);
				}
			}


			

			foreach ( $all_ranks_cat as $cat_slug ) {
				$term = get_term_by('slug', $cat_slug, 'product_cat'); 
				if ($term) {
					$all_term_ids[] = $term->term_id; 
				}
			}

			foreach ( $all_badges_cat as $cat_slug ) {
				$term = get_term_by('slug', $cat_slug, 'product_cat'); 
				if ($term) {
					$all_term_ids[] = $term->term_id; 
				}
			}

			
			if ( $this->rank_enabled ) {
				$user_rank_ids = $this->get_users_ranks( $user_id );
			}

			if ( $this->badge_enabled ) {
				$users_badge_ids = $this->get_users_badges( $user_id );
			}

			if ( $user_rank_ids ) {
				foreach ( $user_rank_ids as $key => $rank_id ) {
					$cats = mycred_get_post_meta( $rank_id, 'mycred_woo_ranks_cats', true);
					if ( $cats ) {
						$user_rank_cat = array_merge($user_rank_cat, $cats);
					}
				}

				foreach ( $user_rank_cat as $cat_slug ) {
					$term = get_term_by('slug', $cat_slug, 'product_cat'); 
					if ($term) {
						$term_ids[] = $term->term_id; 
					}
				}

			}

			

			if ( $users_badge_ids ) {
				foreach ( $users_badge_ids as $key => $badges_id ) {
					$cats = mycred_get_post_meta( $badges_id, 'mycred_woo_badges_cats', true);
					
					if ( $cats ) {
						$user_badge_cat = array_merge($user_badge_cat, $cats);
					}
				}
				
				foreach ( $user_badge_cat as $cat_slug ) {
					$term = get_term_by('slug', $cat_slug, 'product_cat'); 
					if ($term) {
						$term_ids[] = $term->term_id; 
					}
				}

			}

			$final_terms = array_diff( $all_term_ids,$term_ids );

			$args = array(
				'post_type' => 'product', 
				'posts_per_page' => -1,
				'tax_query' => array(
					array(
						'taxonomy' => 'product_cat', 
						'field' => 'term_id',
						'terms' => $final_terms,
						'operator' => 'IN'
					)
				)
			);
			$products_query = new WP_Query($args);
			$product_ids = array();

			if ($products_query->have_posts()) {
				while ($products_query->have_posts()) {
					$products_query->the_post();
					$product_id = get_the_ID();
					$product_ids[] = $product_id;
				}
				wp_reset_postdata();
			}

			$product_ids = array_unique($product_ids);

			if ( $final_terms ) {
				$query->set( 'post__not_in', $product_ids );
			}
		}



		public function mwdpbr_save_woo_cat_post_badge( $post_ID, $post ) {

			if ( 
				isset( $_POST['mwdpbr_woo_badges_cats'] ) &&
				wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mwdpbr_woo_badges_cats'] ) ), 'mwdpbr_woo_badges_cats' )
			) {

				if ( isset( $_POST['woo_badges_cats'] ) ) {

					$categories = mycred_sanitize_array( wp_unslash( $_POST['woo_badges_cats'] ) );
					mycred_update_post_meta( $post_ID, 'mycred_woo_badges_cats', $categories );

				} 
				else {
					mycred_update_post_meta( $post_ID, 'mycred_woo_badges_cats', null );
				}

			}

		}

		public function mwdpbr_save_woo_cat_post_rank( $post_ID, $post ) {

			if ( 
				isset( $_POST['mwdpbr_woo_ranks_cats'] ) &&
				wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mwdpbr_woo_ranks_cats'] ) ), 'mwdpbr_woo_ranks_cats' )
			) {

				if ( isset( $_POST['woo_ranks_cats'] ) ) {

					$categories = mycred_sanitize_array( wp_unslash( $_POST['woo_ranks_cats'] ) );
					mycred_update_post_meta( $post_ID, 'mycred_woo_ranks_cats', $categories );

				} 
				else {
					mycred_update_post_meta( $post_ID, 'mycred_woo_ranks_cats', null );
				}

			}

		}

		public function register_admin_assets() {

			wp_register_script( 'mwp-restrict-product-script', plugins_url( 'assets/js/restrict-product-script.js', MYCRED_WOOPLUS_THIS ), array( 'jquery' ), MYCRED_WOOPLUS_VERSION );

		}

		public function admin_settings( $core ) {

			$settings = $core->woocommerce[ $this->module_name ];

			$this->load_template( 
				'restrict_product_settings', 
				'restrict-product-module/admin-settings.php',
				array( 
					'settings' => $settings
				) 
			);

		}

		public function sanitize_settings( $new_data, $data, $core ) {

			$new_data[ $this->module_name ]['enable'] = !empty( $data[ $this->module_name ]['enable'] ) ? 1 : 0;
			$new_data[ $this->module_name ]['enable_category'] = !empty( $data[ $this->module_name ]['enable_category'] ) ? 1 : 0;

			return $new_data;

		}

		public function add_metabox() {

			if ( ! $this->badge_enabled && ! $this->rank_enabled ) {
				return;
			}

			add_meta_box(
				'mycred_woo_badge_rank_custom',
				__( 'Restrict Product', 'mycred-woocommerce-plus' ),
				array( $this, 'restrict_product_metabox' ),
				'product',
				'side',
				'high'
			);

			add_meta_box(
				'mwdpbr_prod_by_ranks',
				'Display products from categories by Ranks',
				array( $this, 'restrict_category_metabox' ),
				'mycred_rank',
				'normal',
				'low'
			);

			add_meta_box(
				'mwdpbr_prod_by_badges',
				'Display products from categories by Badges',
				array( $this, 'restrict_category_metabox' ),
				'mycred_badge',
				'normal',
				'low'
			);


		}

		public function restrict_category_metabox( $post ) {

			wp_enqueue_style( 'mwp-admin-style' );
			wp_enqueue_script( 'mwp-restrict-product-script' );

			$settings = $this->mwdpbr_get_woocommerce_cats();

			$badges = $this->get_badges();
			$ranks  = $this->get_ranks();

			$this->load_template( 
				'restrict_product_metabox', 
				'restrict-product-module/restrict-category-metabox.php',
				array(
					'post'  => $post,
					'settings' => $settings,
					'ranks'    => $ranks,
					'badges'   => $badges
				) 
			);

		}

		public function restrict_product_metabox( $product ) {

			wp_enqueue_style( 'mwp-admin-style' );
			wp_enqueue_script( 'mwp-restrict-product-script' );

			$settings = $this->get_product_setting( $product->ID );

			$badges = $this->get_badges();
			$ranks  = $this->get_ranks();

			$this->load_template( 
				'restrict_product_metabox', 
				'restrict-product-module/restrict-product-metabox.php',
				array( 
					'product'  => $product,
					'settings' => $settings,
					'ranks'    => $ranks,
					'badges'   => $badges
				) 
			);

		}


		public function mwdpbr_get_woocommerce_cats() {

			$orderby    = 'name';
			$order      = 'asc';
			$hide_empty = false;

			$cat_args   = array(
				'orderby'    => $orderby,
				'order'      => $order,
				'hide_empty' => $hide_empty
			);

			$product_categories = get_terms( 'product_cat', $cat_args );

			return $product_categories;

		}


		public function get_product_setting( $id ) {

			$settings = shortcode_atts(
				array(
					'enable'       => false,
					'type'         => '',
					'selected_ids' => array()
				),
				mycred_get_post_meta( $id, 'mwp_restrict_product_pref', true )
			);

			if ( $settings['type'] == 'badge' && ! $this->badge_enabled ) {

				$settings['type']         = '';
				$settings['selected_ids'] = array();

			}
			elseif ( $settings['type'] == 'rank' && ! $this->rank_enabled ) {

				$settings['type']         = '';
				$settings['selected_ids'] = array();

			}

			return $settings;

		}

		public function get_badges() {

			$args = array(
				'post_type'      => 'mycred_badge',
				'post_status'    => 'publish',
				'posts_per_page' => -1
			);

			return get_posts( $args );

		}

		public function get_ranks() {

			$ranks = array();
			$types = mycred_get_types();

			foreach ( $types as $slug => $label ) {

				$args = array(
					'post_type'      => 'mycred_rank',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'meta_key'       => 'mycred_rank_min',
					'meta_query'     => array(
						'key'     => 'ctype',
						'value'   => $slug,
						'compare' => '='
					),
					'orderby'        => 'meta_value_num'
				);

				$type_ranks = get_posts( $args );

				if ( ! empty( $type_ranks ) ) {

					$ranks[] = array(
						'type' => array(
							'slug'  => $slug,
							'label' => $label
						),
						'ranks' => $type_ranks
					);

				}

			}

			return $ranks;

		}

		public function save_product_settings( $post_id, $post ) {

			if ( isset( $_POST['mwp-restrict-product-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mwp-restrict-product-nonce'] ) ), 'mwp_module_restrict_product_nonce' ) ) {

				if ( ! empty( $_POST['mwp_restrict_product_pref'] ) ) {

					$settings = array(
						'enable'       => 0,
						'type'   	   => '',
						'selected_ids' => array()
					);

					if ( ! empty( $_POST['mwp_restrict_product_pref']['enable'] ) ) {
						$settings['enable'] = 1;
					}

					if ( ! empty( $_POST['mwp_restrict_product_pref']['type'] ) ) {
						$settings['type'] = sanitize_key( wp_unslash( $_POST['mwp_restrict_product_pref']['type'] ) );
					}

					if ( ! empty( $_POST['mwp_restrict_product_pref']['selected_ids'] ) ) {

						foreach ( $_POST['mwp_restrict_product_pref']['selected_ids'] as $id ) {
							$settings['selected_ids'][] = absint( $id );
						}

					}

					mycred_update_post_meta( $post_id, 'mwp_restrict_product_pref', $settings );

				}

			}

		}


		public function restrict_products( $query ) {

			if ( is_admin() || ! $query->is_main_query() ) {
				return;
			}

			$restricted_products = array();
			$user_id             = get_current_user_id();
			$users_badge_ids     = array();
			$users_rank_ids      = array();

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

					}
					elseif( $this->rank_enabled && ! empty( $prefs['type'] ) && $prefs['type'] == 'rank' ) {

						if ( ! array_intersect( $users_rank_ids, $prefs['selected_ids'] ) ) {

							$restricted_products[] = $prod_id;

						}

					}

				}
			}

			if ( ! empty( $restricted_products ) ) {

				$query->set( 'post__not_in', $restricted_products );

			}
		}


		public function restrict_related_products( $related_posts, $product_id, $args ) {
		    $restricted_products = array();
		    $user_id             = get_current_user_id();
		    $users_badge_ids     = array();
		    $users_rank_ids      = array();

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

		    // Remove restricted products from related products
		    $related_posts = array_diff( $related_posts, $restricted_products );

		    return $related_posts;
		}


		//Get all product prefs
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

	}
endif;

function mwp_load_restrict_products_module() {

	$module = new MWP_Restrict_Products_Module();
	$module->load();

}
mwp_load_restrict_products_module();