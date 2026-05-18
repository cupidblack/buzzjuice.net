<?php
add_filter( 'mycred_setup_hooks', 'register_mycred_woo_product_referral_hook' );

function register_mycred_woo_product_referral_hook( $installed ) {
	$installed['product_referral'] = array(
		'title'       => __( 'woocommerce product referral', 'mycredpartwoo' ),
		'description' => __( 'let registered customers earn points by recommending products to their own networks of friends and family.', 'mycredpartwoo' ),
		'callback'    => array( 'MyCRED_Hook_WOO_Product_Referral' ),
	);
	return $installed;
}

/**
 * Add product_referral in mycred reference log
 */
add_filter( 'mycred_all_references', 'mycred_add_product_referral_reference' );

function mycred_add_product_referral_reference( $references ) {
	$references['product_referral'] = __( 'woocommerce product referral', 'mycredpartwoo' );
	return $references;
}

/**
 * Hook for affiliations
 *
 * @since   1.4
 * @version 1.3.1
 */
add_action( 'mycred_load_hooks', 'mycred_load_woo_product_referral_hook', 95 );

function mycred_load_woo_product_referral_hook() {

	if ( ! class_exists( 'MyCRED_Hook_WOO_Product_Referral' ) ) :

		class MyCRED_Hook_WOO_Product_Referral extends myCRED_Hook {

			public $ref_key  = '';
			public $limit_by = array();

			/**
			 * Construct
			 */
			public function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {
				parent::__construct(
					array(
						'id'       => 'product_referral',
						'defaults' => array(
							'referrer' => array(
								'creds'    => 10,
								'log'      => '%plural% for product referrer %product_name%',
								'limit'    => 1,
								'limit_by' => 'total',
							),
							'referee'  => array(
								'creds'    => 10,
								'log'      => '%plural% for product purchase',
								'limit'    => 1,
								'limit_by' => 'total',
							),
							'setup'    => array(
								'links' => 'username',
							),
						),
					),
					$hook_prefs,
					$type
				);

				// Let others play with the limit by
				/**
				* Action mycred_woo_product_ref_limit_by
				* 
				* @since 1.0
				**/
				$this->limit_by = apply_filters(
					'mycred_woo_product_ref_limit_by',
					array(
						'total' => __( 'Total', 'mycredpartwoo' ),
						'daily' => __( 'Per Day', 'mycredpartwoo' ),
					),
					$this
				);
				// Let others play with the ref key
				/**
				* Filter mycred_woo_product_ref_key
				* 
				* @since 1.0
				**/
				$this->ref_key           = apply_filters( 'mycred_woo_product_ref_key', 'productref', $this );
				$this->cookie_name       = get_option( 'mycred_wooplus_referral_cookie_name' );
				$this->is_cookie_expire  = get_option( 'mycred_wooplus_referral_cookie_is_expire' );
				$this->cookie_expiration = get_option( 'mycred_wooplus_referral_cookie_expiration' );
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );
			}

			public function enqueue_scripts() {
				global $post;
				if ( has_shortcode( $post->post_content, 'mycred_woocommerce_referral' ) ) {
					wp_enqueue_script( 'mycred-woo-product-referral', plugins_url( 'assets/js/mycred-woo-product-referral.js', MYCRED_WOOPLUS_THIS ), array( 'jquery' ), MYCRED_WOOPLUS_VERSION, true );
				}
			}

			/**
			 * Run
			 *
			 * @since 1.4
			 * @version 1.2.1
			 */
			public function run() {
				//
				// Register Shortcodes
				add_filter( 'mycred_woocommerce_referral_' . $this->mycred_type, array( $this, 'shortcode_woo_product_referral_link' ), 10, 2 );
				add_filter( 'mycred_woo_product_ref_keys', array( $this, 'add_key' ) );
				//
				// Logged in users do not get points
				// if ( is_user_logged_in() && apply_filters( 'mycred_affiliate_allow_members', false ) === false ) return;
				//
				// Points for visits
				if ( 0 != $this->prefs['referrer']['creds'] || 0 != $this->prefs['referee']['creds'] ) { // at least one of them
					add_action( 'mycred_woo_product_referred', array( $this, 'set_referral_cookie' ) );

					add_action( 'woocommerce_thankyou', array( $this, 'referrer_order_purchase' ), 10, 1 );
				}
			}

			/**
			 * Add Referral Key
			 *
			 * @since 1.5.3
			 * @version 1.0
			 */
			public function add_key( $keys ) {
				if ( ! isset( $_GET[ $this->ref_key ] ) || isset( $_COOKIE[ $this->cookie_name . $this->mycred_type ] ) ) {
					return $keys;
				}

				if ( ! in_array( $this->ref_key, $keys ) ) {
					$keys[] = $this->ref_key;
				}

				return $keys;
			}

			public function shortcode_woo_product_referral_link( $content = '', $atts = '' ) {
				
				ob_start();
				
				$attr = shortcode_atts(
					array(
						'url'        => '',
						'user_id'    => '',
						'product_id' => '',
					),
					$atts
				);

				$url 		= esc_url( $attr['url'] );
				$user_id 	= esc_attr( $attr['user_id'] );
				$product_id = esc_attr( $attr['product_id'] );
				
				$product_url  = '';
				$ref_url      = '';
				$product_args = array(
					'limit' => -1,
				);
				$products     = wc_get_products( $product_args );
				if ( ! is_user_logged_in() && '' == $user_id ) {
					return $url;
				}

				if ( '' == $user_id ) {
					$user_id = get_current_user_id();
				}

				if ( '' != $product_id ) {
					$url = mycred_get_permalink( $product_id );
				}
				
				?>

				<h3 class="mycred_woo_generate"><?php esc_html_e( 'Generate Link' ); ?></h3>
				<p class="mycred_woo_affiliate_id"><?php esc_html_e( 'your affiliate id is: ' . $this->get_ref_id( $user_id ) ); ?></p>
				<p class="mycred_woo_referral_url"><?php esc_html_e( 'your referral url is: ' . $this->get_ref_link( $user_id, $url ) ); ?></p>
				
				<?php
				
				if ( 
					isset( $_POST['mycred-woo-product-referral'] ) && 
					isset( $_POST['custom_nonce'] ) && 
					wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['custom_nonce'] ) ), 'custom_nonce' ) 
				) {
					$url = mycred_get_permalink( sanitize_text_field(wp_unslash($_POST['mycred-woo-product-referral'])) );
					$ref_url = $this->get_ref_link( $user_id, $url );
				}
			
				if ( ! empty( $products ) ):?>
					<form method="post" name="mycred-woo-product-referral-form"> 
						<?php wp_nonce_field( 'custom_nonce', 'custom_nonce' );?>
						<select name="mycred-woo-product-referral" class="mycred-woo-product-referral">
							<?php foreach ( $products as $product ):?>
							<option value="<?php echo esc_attr($product->get_id()); ?>" <?php ( isset( $_POST['mycred-woo-product-referral'] ) ? selected( sanitize_text_field(wp_unslash($_POST['mycred-woo-product-referral'])), $product->get_id() ) : '' ); ?>><?php echo esc_attr($product->get_title()); ?></option>
							<?php endforeach;?>
						</select>
						<div class="form-row form-row-wide mycred_woo_product_url">
							<label class="mycred_product_url" for="mycred_product_url"><?php esc_html_e( 'product url' ); ?></label>
							<input class="input-text mycred_product_url" readonly type="text" name="mycred_product_url" value="<?php echo esc_url($url); ?>" >
						</div>
						<div class="form-row form-row-wide">
							<label  for="mycred_product_referral_url"><?php esc_html_e( 'product referral url' ); ?></label>
							<input class="input-text mycred_product_referral_url" readonly type="text" name="mycred_product_referral_url" value="<?php echo esc_url( $ref_url ); ?>" >
						</div>
						<input type="submit" value="Generate Link" />
					</form>
				<?php endif;

				return ob_get_clean();
			
			}

			public function set_referral_cookie() {
				$product_id = '';
				if ( is_product() ) {
					$product    = wc_get_product( get_the_ID() );
					$product_id = $product->get_id();
				}
				if ( ! isset( $_GET[ $this->ref_key ] ) || empty( $_GET[ $this->ref_key ] ) || isset( $_COOKIE[ $this->cookie_name . $this->mycred_type . '[' . $product_id . ']' ] ) ) {
					return;
				}

				if ( ! headers_sent() ) {
					if ( 'yes' == $this->is_cookie_expire ) {
						$time       = strtotime( '+' . $this->cookie_expiration . ' day' );
						/**
						* Filter mycred_woo_product_referrer_cookie
						* 
						* @since 1.0
						**/
						$expiration = apply_filters( 'mycred_woo_product_referrer_cookie', $time, false, $this );
					} else {
						$expiration = time() + ( 10 * 365 * 24 * 60 * 60 );
					}
					setcookie( $this->cookie_name . $this->mycred_type . '[' . $product_id . ']', sanitize_text_field(wp_unslash($_GET[ $this->ref_key ])), $expiration, COOKIEPATH, COOKIE_DOMAIN );
				}
			}

			public function referrer_order_purchase( $order_id ) {
				$order   = wc_get_order( $order_id );
				$user_id = $order->get_user_id() != null ? $order->get_user_id() : false;
				$items   = $order->get_items();
				foreach ( $items as $item ) {
					$item_id = $item->get_product_id();
					if ( isset( $_COOKIE[ $this->cookie_name . $this->mycred_type ] ) && is_array( $_COOKIE[ $this->cookie_name . $this->mycred_type ] ) ) {
						if ( array_key_exists( $item_id, $_COOKIE[ $this->cookie_name . $this->mycred_type ] ) ) {
							$referrer_id = $this->get_user_id_from_ref_id( sanitize_text_field(wp_unslash($_COOKIE[ $this->cookie_name . $this->mycred_type ][ $item_id ])) );
							if ( $referrer_id == $user_id ) {
								continue;
							}
							if ( ! $this->core->exclude_user( $referrer_id ) ) {
								if ( $referrer_id && isset( $_COOKIE[ $this->cookie_name . $this->mycred_type ][ $item_id ] ) && $order->get_meta( 'mycred_order_product_referrer' ) != $item_id ) {
									if ( $this->ref_counts( $referrer_id, 'referrer' ) ) {
										$this->prefs['referrer']['log'] = str_replace( '%product_name%', $item->get_name(), $this->prefs['referrer']['log'] );
										$this->core->add_creds(
											'product_referral',
											$referrer_id,
											$this->prefs['referrer']['creds'],
											$this->prefs['referrer']['log'],
											time(),
											$this->mycred_type
										);
										$order->update_meta_data( 'mycred_order_product_referrer', $item_id );
									}
								}
							}
							if ( $user_id && isset( $_COOKIE[ $this->cookie_name . $this->mycred_type ][ $item_id ] ) && $order->get_meta( 'mycred_order_product_referee' ) != $item_id ) { // prevent add creds when refresh thank u
								if ( ! $this->core->exclude_user( $user_id ) ) {
									// If referral counts
									if ( $this->ref_counts( $user_id, 'referee' ) ) {
										$this->core->add_creds(
											'product_referral',
											$user_id,
											$this->prefs['referee']['creds'],
											$this->prefs['referee']['log'],
											time(),
											$this->mycred_type
										);
										$order->update_meta_data( 'mycred_order_product_referee', $item_id );
									}
								}
							}
							// unset cookie
							setcookie( $this->cookie_name . $this->mycred_type . '[' . $item_id . ']', '', time() - 3600 * 24, COOKIEPATH, COOKIE_DOMAIN );
						}
					}
				}
			}

			/**
			 * Get Ref Link
			 * Returns a given users referral id with optional url appended.
			 *
			 * @since 1.4
			 * @version 1.0.1
			 */
			public function get_ref_link( $user_id = '', $url = '' ) {
				//
				// User ID is required
				if ( empty( $user_id ) || 0 === $user_id ) {
					return '';
				}
				//
				// Get Ref ID
				$ref_id = $this->get_ref_id( $user_id );
				if ( null === $ref_id ) {
					return '';
				}
				if ( ! empty( $url ) ) {
					$link = add_query_arg( array( $this->ref_key => $ref_id ), $url );
				} else {
					$link = add_query_arg( array( $this->ref_key => $ref_id ) );
				}
				/**
				* Filter mycred_woo_product_get_ref_link
				* 
				* @since 1.0
				**/
				return apply_filters( 'mycred_woo_product_get_ref_link', esc_url( $link ), $user_id, $url, $this );
			}

			/**
			 * Get Ref ID
			 * Returns a given users referral ID.
			 *
			 * @since 1.4
			 * @version 1.1
			 */
			public function get_ref_id( $user_id ) {

				$ref_id = null;

				// Link format
				switch ( $this->prefs['setup']['links'] ) {

					case 'username':
						$user = get_userdata( $user_id );
						if ( isset( $user->user_login ) ) {
							$ref_id = urlencode( $user->user_login );
						}

						break;

					case 'numeric':
												$ref_id = absint( $user_id );
						break;
				}

				/**
				* Filter mycred_woo_product_get_ref_id
				* 
				* @since 1.0
				**/
				return apply_filters( 'mycred_woo_product_get_ref_id', $ref_id, $user_id, $this );
			}

			/**
			 * Get User ID from Ref ID
			 *
			 * @since 1.4
			 * @version 1.0.1
			 */
			public function get_user_id_from_ref_id( $string = '' ) {
								global $wpdb;
								$user_id = null;
				switch ( $this->prefs['setup']['links'] ) {
					case 'username':
						$ref_id = sanitize_text_field( urldecode( $string ) );
						$user   = get_user_by( 'login', $ref_id );
						if ( isset( $user->ID ) ) {
							$user_id = $user->ID;
						}

						break;

					case 'numeric':
						$ref_id  = absint( $string );
						$user_id = $ref_id;
						break;
				}

				// Make sure if the referring user is excluded we do not do anything
				if ( null !== $user_id && $this->core->exclude_user( $user_id ) ) {
					$user_id = null;
				}

				/**
				* Filter mycred_woo_product_ref_get_user_id
				* 
				* @since 1.0
				**/
				return apply_filters( 'mycred_woo_product_ref_get_user_id', $user_id, $string, $this );
			}

			/**
			 * Ref Counts
			 * Checks to see if this referral counts.
			 *
			 * @since 1.4
			 * @version 1.2.1
			 */
			public function ref_counts( $user_id, $instance ) {

				$reply = true;
				if ( 'referrer' == $instance ) {
					$ref = 'product_referral';
				}
				if ( 'referee' == $instance ) {
					$ref = 'product_referral';
				}

				if ( $this->over_hook_limit( $instance, $ref, $user_id ) ) {
					$reply = false;
				}	
				/**
				* Filter mycred_woo_product_ref_counts
				* 
				* @since 1.0
				**/
				return apply_filters( 'mycred_woo_product_ref_counts', $reply, $this );
			}

			/**
			 * Preference for Affiliate Hook
			 *
			 * @since 1.4
			 * @version 1.1
			 */
			public function preferences() {

				$prefs = $this->prefs;
				
				?>
				<div class="hook-instance">
					<h3><?php esc_html_e( 'Points on first sale for referrer', 'mycredpartwoo' ); ?></h3>
					<div class="row">
						<div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo esc_attr($this->field_id( array( 'referrer' => 'creds' ) )); ?>"><?php echo esc_attr($this->core->plural()); ?></label>
								<input type="text" name="<?php echo esc_attr($this->field_name( array( 'referrer' => 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'referrer' => 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['referrer']['creds'] )); ?>" class="form-control" />
							</div>
						</div>
						<div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo esc_attr($this->field_id( array( 'referrer', 'limit' ) )); ?>"><?php esc_html_e( 'Limit', 'mycredpartwoo' ); ?></label>
								
								<?php echo wp_kses(
						$this->hook_limit_setting( $this->field_name( array( 'referrer', 'limit' ) ), $this->field_id( array( 'referrer', 'limit' ) ), $prefs['referrer']['limit'] ),
						array(
							'div' => array(
								'class' => array()
							),
							'input' => array(
								'type' => array(),
								'size' => array(),
								'class' => array(),
								'name' => array(),
								'id' => array(),
								'value' => array()
							),
							'select' => array(
								'name' => array(),
								'id' => array(),
								'class' => array()
							),
							'option' => array(
								'value' => array(),
								'selected' => array()
							)
						) 
					); 
				?>
							</div>
						</div>
						<div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo esc_attr($this->field_id( array( 'referrer' => 'log' ) )); ?>"><?php esc_html_e( 'Log template', 'mycredpartwoo' ); ?></label>
								<input type="text" name="<?php echo esc_attr($this->field_name( array( 'referrer' => 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'referrer' => 'log' ) )); ?>" value="<?php echo esc_attr( $prefs['referrer']['log'] ); ?>" class="form-control" />
								<span class="description"><?php echo wp_kses_post( mycred_wcp_kses_html( $this->available_template_tags( array( 'general' ) ) ) ); ?></span>
								<span class="description"> <?php esc_html_e( 'add %product_name% in log template represent value of referred product in log' ); ?></span>

							</div>

						</div>

					</div>

				</div>
				<div class="hook-instance">
					<h3><?php esc_html_e( 'Points on first sale for referee', 'mycredpartwoo' ); ?></h3>
					<div class="row">
						<div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo esc_attr($this->field_id( array( 'referee' => 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
								<input type="text" name="<?php echo esc_attr($this->field_name( array( 'referee' => 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'referee' => 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['referee']['creds'] )); ?>" class="form-control" />
							</div>
						</div>
						<div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
							<div class="form-group test">
								<label for="<?php echo esc_attr($this->field_id( array( 'referee', 'limit' ) )); ?>"><?php esc_html_e( 'Limit', 'mycredpartwoo' ); ?></label>			                 
								<?php echo wp_kses(
						$this->hook_limit_setting( $this->field_name( array( 'referee', 'limit' ) ), $this->field_id( array( 'referee', 'limit' ) ), $prefs['referee']['limit'] ),
						array(
							'div' => array(
								'class' => array()
							),
							'input' => array(
								'type' => array(),
								'size' => array(),
								'class' => array(),
								'name' => array(),
								'id' => array(),
								'value' => array()
							),
							'select' => array(
								'name' => array(),
								'id' => array(),
								'class' => array()
							),
							'option' => array(
								'value' => array(),
								'selected' => array()
							)
						) 
					); 
				?>
							</div>
						</div>
						<div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo esc_attr($this->field_id( array( 'referee' => 'log' ) )); ?>"><?php esc_html_e( 'Log template', 'mycredpartwoo' ); ?></label>
								<input type="text" name="<?php echo esc_attr($this->field_name( array( 'referee' => 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'referee' => 'log' ) )); ?>" value="<?php echo esc_attr( $prefs['referee']['log'] ); ?>" class="form-control" />
								<span class="description"><?php echo wp_kses_post( mycred_wcp_kses_html( $this->available_template_tags( array( 'general' ) ) ) ); ?></span>
							</div>
						</div>
					</div>

				</div>
				<div class="hook-instance">
					<h3><?php esc_html_e( 'Referral Links', 'mycredpartwoo' ); ?></h3>
					<div class="row">
						<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo esc_attr($this->field_id( array( 'setup' => 'links' ) )); ?>-numeric"><input type="radio" name="<?php echo esc_attr($this->field_name( array( 'setup' => 'links' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'setup' => 'links' ) )); ?>-numeric" <?php checked( $prefs['setup']['links'], 'numeric' ); ?> value="numeric" /> <?php esc_html_e( 'Assign numeric referral IDs to each user.', 'mycredpartwoo' ); ?></label>
								<span class="description"><?php sprintf( '%s: %s', esc_html__( 'Example', 'mycredpartwoo' ), esc_url( add_query_arg( array( $this->ref_key => 1 ), home_url( '/' ) ) ) ); ?></span>
							</div>
						</div>
						<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo esc_attr($this->field_id( array( 'setup' => 'links' ) )); ?>-username"><input type="radio" name="<?php echo esc_attr($this->field_name( array( 'setup' => 'links' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'setup' => 'links' ) )); ?>-username" <?php checked( $prefs['setup']['links'], 'username' ); ?> value="username" /> <?php esc_html_e( 'Assign usernames as IDs for each user.', 'mycredpartwoo' ); ?></label>
								<span class="description"><?php sprintf( '%s: %s', esc_html__( 'Example', 'mycredpartwoo' ), esc_url( add_query_arg( array( $this->ref_key => 'john+doe' ), home_url( '/' ) ) ) ); ?></span>
							</div>
						</div>
					</div>
					<div class="row">

						<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
							<div class="form-group">
								<label><?php esc_html_e( 'Available Shortcode', 'mycredpartwoo' ); ?></label>
								<p class="form-control-static">
									<a href="https://codex.mycred.me/chapter-iv/enhancements/mycred-woocommerce-plus/woocommerce-product-referral/#shortcode" target="_blank">[mycred_woocommerce_referral]</a>
								</p>
							</div>
						</div>
					</div>
				</div>
				<?php
				// do_action('mycred_affiliate_prefs', $prefs, $this);
			}

			/**
			 * Sanitise Preference
			 *
			 * @since 1.4
			 * @version 1.1
			 */
			public function sanitise_preferences( $data ) {
				if ( isset( $data['referrer']['limit'] ) && isset( $data['referrer']['limit_by'] ) ) {
					$limit = sanitize_text_field( $data['referrer']['limit'] );
					if ( '' == $limit ) {
						$limit = 0;
					}
					$data['referrer']['limit'] = $limit . '/' . $data['referrer']['limit_by'];
					unset( $data['referrer']['limit_by'] );
				}

				if ( isset( $data['referee']['limit'] ) && isset( $data['referee']['limit_by'] ) ) {
					$limit = sanitize_text_field( $data['referee']['limit'] );
					if ( '' == $limit ) {
						$limit = 0;
					}
					$data['referee']['limit'] = $limit . '/' . $data['referee']['limit_by'];
					unset( $data['referee']['limit_by'] );
				}
								/**
								* Filter mycred_woo_product_referrer_save_pref
								* 
								* @since 1.0
								**/
								return apply_filters( 'mycred_woo_product_referrer_save_pref', $data );
			}

		}

		endif;
}

add_action( 'mycred_pre_init', 'mycred_load_woo_product_referred', 90 );

function mycred_load_woo_product_referred() {
	add_action( 'template_redirect', 'mycred_detect_referred_order_sale' );
}

function mycred_detect_referred_order_sale() {

	/**
	* Action mycred_woo_product_referred
	* 
	* @since 1.0
	**/
	do_action( 'mycred_woo_product_referred' );

	/**
	* Filter mycred_woo_product_ref_keys
	* 
	* @since 1.0
	**/
	$keys = apply_filters( 'mycred_woo_product_ref_keys', array() );
	if ( ! empty( $keys ) ) {
		wp_safe_redirect( remove_query_arg( $keys ), 301 );
		exit;
	}
}
