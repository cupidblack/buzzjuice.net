<?php


if ( ! class_exists( 'MyCred_WOO_DISPLAY_PRODUCTS_BY_RANK' ) ) {

	class MyCred_WOO_DISPLAY_PRODUCTS_BY_RANK {

		/**
		 * Mwdpbr
		 */
		public function __construct() {

			add_action( 'admin_init', array( $this, 'mwdpbr_add_metabox' ) );
			add_action( 'save_post_mycred_rank', array( $this, 'mwdpbr_save_woo_cat_post' ), 10, 2 );
			add_action( 'woocommerce_product_query', array( $this, 'mwpbr_removing_products_from_shop_page' ), 10, 2 );
			add_action( 'wp', array( $this, 'mwpbr_retrict_single_product_page' ), 6 );

		}

		public function mwdpbr_add_metabox() {
			
			if ( 'yes' == get_option( 'mycred_wooplus_show_ranks' ) ) {
			
				add_meta_box(
					'mwdpbr_prod_by_ranks',
					'Display products from categories by Ranks',
					array( $this, 'mwdpbr_prod_by_ranks_callback' ),
					'mycred_rank',
					'normal',
					'low'
				);
			
			}
		
		}

		public function mwdpbr_save_woo_cat_post( $post_ID, $post ) {
		
			if ( 
				isset( $_POST['mwdpbr_woo_ranks_cats'] ) &&
				wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mwdpbr_woo_ranks_cats'] ) ), 'mwdpbr_woo_ranks_cats' )
			) {
				
				if ( isset( $_POST['woo_ranks_cats'] ) ) {

					$categories = mycred_sanitize_array( wp_unslash( $_POST['woo_ranks_cats'] ) );
					update_post_meta( $post_ID, 'mycred_woo_ranks_cats', $categories );
				
				} 
				else {
					update_post_meta( $post_ID, 'mycred_woo_ranks_cats', null );
				}
			
			}
		
		}

		public function mwpbr_removing_products_from_shop_page( $meta_query, $query ) {
		
			if ( is_user_logged_in() ) {
		
				$current_user_roles_array = wp_get_current_user()->roles;
		
				if ( ! in_array( 'administrator', $current_user_roles_array ) ) {
		
					$categories = $this->mwpbr_restricted_categories_from_users_rank();

					$tax_query = $meta_query->get( 'tax_query' );

					$tax_query[] = array(
						'relation' => 'AND',
						array(
							'taxonomy' => 'product_cat',
							'field' => 'slug',
							'terms' => $categories,
							'operator' => 'NOT IN'
						),
					);
		
					$meta_query->set( 'tax_query', $tax_query );
		
				}
		
			}
		
		}

		public function mwpbr_retrict_single_product_page() {
			
			global $wp_query;
			
			$categories = $this->mwpbr_restricted_categories_from_users_rank();
			
			if ( is_product() ) {
			
				$product_id  = $wp_query->post->ID;
				$term_object = wp_get_post_terms( $product_id, 'product_cat', array( 'field' => 'slug' ) );
				foreach ( $term_object as $term ) {
		
					$category = $term->slug;
		
					if ( is_array( $categories ) && in_array( $category, $categories ) ) {
						wp_safe_redirect( site_url() );
					}
		
				}
			
			}
		
		}

		public function mwpbr_retrict_product_category_page() {
			
			$categories = $this->mwpbr_restricted_categories_from_users_rank();
		
			if ( is_product_category( $categories ) ) {
				wp_safe_redirect( site_url() );
			}
		
		}

		public function mwdpbr_prod_by_ranks_callback( $post ) {
			
			$selected_cats = get_post_meta( $post->ID, 'mycred_woo_ranks_cats', true );

			wp_nonce_field( 'mwdpbr_woo_ranks_cats', 'mwdpbr_woo_ranks_cats' );
			
			?>
			<table width="100%">
				<tr>
					<td colspan="10" >
						<p style="font-weight: 600;">
						<?php echo esc_html__( 'You can select the categories to hide the related products from users.', 'mycredpartwoo' );?>
						</p>
					</td>
				</tr>
				<tr>
					<td style="width: 25%"><?php echo esc_html__( 'WooCommerce Product Categories', 'mycredpartwoo' ); ?></td>
					<td>
						<?php 

						$categories = array();

						foreach ( $this->mwdpbr_get_woocommerce_cats() as $key => $category ) {
							$categories[ $category->slug ] = $category->name;
						}

						mycred_create_select_field( 
			                $categories, 
			                $selected_cats,
			                array(
			                    'name'     => 'woo_ranks_cats[]',
			                    'id'       => 'mwdpbr_categories_ranks',
			                    'class'    => 'mycred-select2',
								'multiple' => 'multiple',
								'style'    => 'width: 69%'
			                )
			            );
						?>
					</td>
				</tr>
			</table>
			<script>
				<?php $select_category = __( 'Select categories', 'mycredpartwoo' ); ?>
				jQuery(document).ready(function($) {
					$('.mwdpbr_categories_ranks').select2({
						placeholder: '<?php mycred_wcp_kses_html( $select_category ); ?>'
					});
				});
			</script>
			<?php

		}

		private function mwdpbr_get_woocommerce_cats() {
			
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

		private function mwpbr_restricted_categories_from_users_rank() {
			
			$user_id      = get_current_user_id();
			$user_rank    = get_user_meta( $user_id, 'mycred_rank', true );
			$user_rank_id = $user_rank;
			$categories   = get_post_meta( $user_rank_id, 'mycred_woo_ranks_cats', true );

			return $categories;
		
		}
	
	}

}
$MyCred_WOO_DISPLAY_PRODUCTS_BY_RANK = new MyCred_WOO_DISPLAY_PRODUCTS_BY_RANK();
