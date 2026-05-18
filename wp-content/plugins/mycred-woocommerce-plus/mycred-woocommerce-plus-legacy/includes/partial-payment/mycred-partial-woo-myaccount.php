<?php
// No dirrect access
if ( ! defined( 'MYCRED_WOOPLUS_VERSION' ) ) {
	exit;
}

if ( ! function_exists( 'get_url_segment' ) ) {
	function get_url_segment( $segment ) {

		if ( isset( $_SERVER['HTTP_HOST'] ) && isset($_SERVER['REQUEST_URI']) ) {
			$url = 'http://' . sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) . sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
			$end = explode( '/', $url );

			$total = count( $end );

			return $end[ $total - $segment ];
		}
	}
}

/**
 * Setup Custom Endpoint
 *
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_woo_partial_setup_my_account' ) ) :
	function mycred_woo_partial_setup_my_account( $flush = false ) {

		$my_account_setup = mycred_part_woo_account_settings();
		if ( '' == $my_account_setup['slug'] ) {
			return;
		}

		add_rewrite_endpoint( $my_account_setup['slug'], EP_ROOT | EP_PAGES );

		if ( $flush ) {
			flush_rewrite_rules();
			return;
		}

		add_filter( 'woocommerce_account_menu_items', 'mycred_woo_partial_account_menu' );
		add_action( 'woocommerce_account_' . $my_account_setup['slug'] . '_endpoint', 'mycred_woo_partial_account_menu_content' );
		add_filter( 'the_title', 'mycred_woo_partial_account_title' );

	}
endif;
add_action( 'mycred_init', 'mycred_woo_partial_setup_my_account' );

/**
 * Add My Account Menu
 *
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_woo_partial_account_menu' ) ) :
	function mycred_woo_partial_account_menu( $items ) {

		$my_account_setup = mycred_part_woo_account_settings();
		if ( '' == $my_account_setup['slug'] ) {
			return $items;
		}

		// Remove the logout menu item.
		$logout = $items['customer-logout'];
		unset( $items['customer-logout'] );

		// Insert your custom endpoint.
		$items[ $my_account_setup['slug'] ] = $my_account_setup['title'];

		// Insert back the logout item.
		$items['customer-logout'] = $logout;

		return $items;

	}
endif;

/**
 * Add My Account Menu Content
 *
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_woo_partial_account_menu_content' ) ) :
	function mycred_woo_partial_account_menu_content() {
		add_filter( 'get_pagenum_link', 'woo_partial_get_pagenum_link', 10, 2 );
		$my_account_setup = mycred_part_woo_account_settings();
		
		if ( '' == $my_account_setup['slug'] ) {
			return;
		}

		$args = array(
			'user_id'  => get_current_user_id(),
			'number'   => empty($my_account_setup['number']) ? 10 : $my_account_setup['number'],
			'page_arg' => 'sheet',
			'paged'    => woo_partial_get_url_lat_segment(),
			'ctype'=> $my_account_setup['point_type'],
		);
		
		/**
		* Filter mycred_woo_reward_reference
		* 
		* @since 1.0
		**/
		$reward_ref = apply_filters( 'mycred_woo_reward_reference', 'reward', 0, MYCRED_DEFAULT_TYPE_KEY );
		/**
		* Filter mycred_woo_references
		* 
		* @since 1.0
		**/

		$references = apply_filters( 'mycred_woo_references', array( 'partial_payment', 'partial_payment_refund', 'woocommerce_payment', 'store_sale', 'woocommerce_refund', 'store_sale_refund', $reward_ref ) );
		
		if ( 'store' == $my_account_setup['show'] ) {
			$args['ref'] = 'partial_payment_refund,partial_payment';
		} elseif ( '' != $my_account_setup['show'] ) {
			$args['ref'] = preg_replace('/\s+/', '', $my_account_setup['show']);
		}

		
		/**
		* Filter mycred_woo_account_args
		* 
		* @since 1.0
		**/
		$account_history = new myCRED_Query_Log( apply_filters( 'mycred_woo_account_args', $args ) );
		
		echo '<style type="text/css">.mycred-history-wrapper ul li { list-style-type: none; display: inline; padding: 0 6px; }</style>';

		if ( '' != $my_account_setup['desc'] ) {
			echo wp_kses_post( '<div id="account-history-description">' . mycred_wcp_kses_html( wpautop( wptexturize( $my_account_setup['desc'] ) ) ) . '</div>' ); 
		}

		// pagination code start

		$big          = 999999999; // need an unlikely integer
		$current_page = woo_partial_get_url_segment( 2 );

		if ( '/archives/%post_id%' == get_option( 'permalink_structure' ) ) {

			$current_page = explode( '?', woo_partial_get_url_segment( 1 ) );
			$current_page = $current_page[0];
		}
		if ( ! is_numeric( $current_page ) ) {
			$current_page = 1;
		}

		// pagination code end

		?>
		<div class="mycred-history-wrapper">
			<?php 
			/**
			* Filter mycred_woo_points_history
			* 
			* @since 1.0
			**/ 
			if ( apply_filters( 'mycred_woo_points_history', true ) ) { 
				?>
			<form class="form-inline" role="form" method="get" action="">
				<div class="mycred-point-history-pagination">
					<?php
					// if ( $my_account_setup['nav'] == 1 ) $account_history->front_navigation( 'top', 10 );
					if ( '' == get_option( 'permalink_structure' ) ) {
						if ( 1 == $my_account_setup['nav'] ) {
							$account_history->front_navigation( 'top', 10 );
						}
					} else {
						if ( 1 == $my_account_setup['nav'] ) {
							echo wp_kses_post( mycred_wcp_kses_html( paginate_links(
								array(
									'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
									'format'    => '',
									'show_all'  => true,
									'prev_next' => true,
									'prev_text' => '« ' . __( 'Previous', 'mycredpartwoo' ),
									'next_text' => __( 'Next', 'mycredpartwoo' ) . ' »',
									'current'   => $current_page,
									'total'     => $account_history->max_num_pages,
								)
							) ) );
						}
					}
					?>
				</div>
				<?php $account_history->display(); ?>
				<div class="mycred-point-history-pagination">
					<?php
					// if ( $my_account_setup['nav'] == 1 ) $account_history->front_navigation( 'bottom', 10 );
					if ( '' == get_option( 'permalink_structure' ) ) {

						if ( 1 == $my_account_setup['nav'] ) {
							$account_history->front_navigation( 'bottom', 10 );
						}
					} else {
						if ( 1 == $my_account_setup['nav'] ) {
							echo wp_kses_post( mycred_wcp_kses_html( paginate_links(
								array(
									'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
									'format'    => '',
									'show_all'  => true,
									'prev_next' => true,
									'prev_text' => '« ' . __( 'Previous', 'mycredpartwoo' ),
									'next_text' => __( 'Next', 'mycredpartwoo' ) . ' »',
									'current'   => $current_page,
									'total'     => $account_history->max_num_pages,
								)
							) ) );
						}
					}
					?>
				</div>
			</form>
				<?php
			} else {
				/**
				* Action mycred_woo_after_points_history
				* 
				* @since 1.0
				**/
				do_action( 'mycred_woo_after_points_history' );
			}
			?>
		</div>
		<?php
	}
endif;

if ( ! function_exists( 'woo_partial_get_url_segment' ) ) :
	function woo_partial_get_url_segment( $segment ) {

		if ( isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI']) ) {
			$url = 'http://' . sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) . sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
			$end = explode( '/', $url );

			$total = count( $end );

			return $end[ $total - $segment ];
		} else {
			return '';
		}
	}
endif;

if ( ! function_exists( 'woo_partial_get_pagenum_link' ) ) :
	function woo_partial_get_pagenum_link( $result, $pagenum ) {

		if ( get_the_ID() == get_option( 'woocommerce_myaccount_page_id' ) ) {

			$end_url = woo_partial_get_url_lat_segment();

			global $wp;

			$current_url = home_url( $wp->request );

			$segment = woo_partial_get_url_segment( 3 );

			if ( 0 == $end_url ) {
				$current_url = $current_url . '/' . $pagenum;
			} else {
				$current_url = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) . $segment . '/' . $pagenum;
			}

			if ( empty( $_SERVER['QUERY_STRING'] ) ) {

				if ( '/%postname%/' == get_option( 'permalink_structure' ) ) {

					return $current_url;

				} elseif ( '/archives/%post_id%' == get_option( 'permalink_structure' ) ) {

					$segment = get_url_segment( 1 );
					return get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) . '/' . $pagenum . '/?' . $segment;
					
				} else {
				
					return get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) . '/' . $pagenum . '/?' . $segment;
				}
			} else {

				if ( '/%postname%/' == get_option( 'permalink_structure' ) ) {

					return get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) . $pagenum . '?' . sanitize_text_field(wp_unslash($_SERVER['QUERY_STRING']));

				} elseif ( '/%year%/%monthnum%/%postname%/' == get_option( 'permalink_structure' ) ) {

					return get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) . '/' . $pagenum . '/?' . sanitize_text_field(wp_unslash($_SERVER['QUERY_STRING']));

				} else {

					return get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) . '/' . $pagenum . '/?' . sanitize_text_field(wp_unslash($_SERVER['QUERY_STRING']));

				}
			}
		} else {
			return $result;
		}
	}
endif;


/**
 * Add My Account Menu Title
 *
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_woo_partial_account_title' ) ) :
	function mycred_woo_partial_account_title( $title ) {

		$my_account_setup = mycred_part_woo_account_settings();
		if ( '' == $my_account_setup['slug'] || ! function_exists( 'is_account_page' ) ) {
			return $title;
		}

		global $wp_query;

		$is_endpoint = array_key_exists( $my_account_setup['slug'], $wp_query->query_vars );

		if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {

			$title = $my_account_setup['title'];

			remove_filter( 'the_title', 'mycred_woo_partial_account_title' );

		}

		return $title;

	}
endif;
