<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'myCred_Toolkit_Memberpress_Core' ) ) :
	final class myCred_Toolkit_Memberpress_Core {

   

		// Instnace
		protected static $_instance = null;

		/**
		 * Setup Instance
		 * @since 1.0.4
		 * @version 1.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Define
		 * @since 1.0.4
		 * @version 1.0
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Require File
		 * @since 1.0.4
		 * @version 1.0
		 */
		public function file( $required_file ) {
			if ( file_exists( $required_file ) ) {
				require_once $required_file;
			}
		}

		/**
		 * Construct
		 * @since 1.0.4
		 * @version 1.0
		 */
		public function __construct() {
			$this->define_constants();
			$this->init();
		}

		/**
		 * Initialize
		 * @since 1.0
		 * @version 1.0
		 */
		private function init() {

			$this->file( ABSPATH . 'wp-admin/includes/plugin.php' );

			if ( is_plugin_active('mycred/mycred.php') && is_plugin_active('memberpress/memberpress.php') ) {

				$this->includes();

				add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) );
				add_filter( 'mycred_setup_hooks', array( $this, 'MycredMembr_purchase_a_specific_subs_product' ) );
				add_action( 'mycred_load_hooks', array( $this, 'mycred_load_memberpress_hook_file' ) );
				add_filter( 'mycred_all_references', array( $this, 'memberpress_register_refrences' ) );

			}

			add_action( 'admin_notices', array( $this, 'mycred_memberpress_plugin_notices' ) );
		}

		/**
		 * Define Constants
		 * @since 1.1.1
		 * @version 1.0
		 */
		private function define_constants() {

		   
			$this->define( 'mycred_memberpress', __FILE__ );
			$this->define( 'mycred_memberpress_ROOT_DIR', plugin_dir_path( mycred_memberpress ) );
			$this->define( 'mycred_memberpress_ASSETS_DIR_URL', plugin_dir_url( mycred_memberpress ) . 'assets/' );
			$this->define( 'mycred_memberpress_INCLUDES_DIR', mycred_memberpress_ROOT_DIR . 'includes/' );
		}

		/**
		 * Include Plugin Files
		 * @since 1.1.1
		 * @version 1.0
		 */

		public function includes() {

			$this->file( mycred_memberpress_INCLUDES_DIR . 'function.php' );
		}

		/**
		 * Include Hook Files
		 * @since 1.1.1
		 * @version 1.0
		 */
		public function mycred_load_memberpress_hook_file() {

			// Quiz
			$this->file( mycred_memberpress_INCLUDES_DIR . 'mycred-memberpress-product.php' );
		}

		public function admin_script( $hook ) {    
		
			if (isset($_GET['page']) && $_GET['page'] === 'mycred-hooks') {

				wp_enqueue_style( 'mycred-memberpress-style', mycred_memberpress_ASSETS_DIR_URL . 'css/style.css' );
				wp_enqueue_script( 'mycred-memberpress-script', mycred_memberpress_ASSETS_DIR_URL . 'js/script.js', array( 'jquery' ) );
				
			}
		}

		public function MycredMembr_purchase_a_specific_subs_product( $installed ) {
		
			// Add a custom hook
			 $installed['specific_product'] = array(
				 'title'       => __( 'MemberPress: Purchase Subscription Product', 'mycred-toolkit' ),
				 'description' => __( 'MemberPress: Purchase Subscription Product', 'mycred-toolkit' ),
				 'callback'    => array( 'MycredMembr_PurchaseASpecificSubsProduct' )
			 );

			return $installed;
		}

		public function memberpress_register_refrences( $list ) {
			
			//General quiz reference
			$list['memberpress_product'] = __( 'Purchase Subscription Product (MemberPress)', 'mycred-toolkit' );
			$list['product_recurring']   = __( 'Reccuring payment (MemberPress)', 'mycred-toolkit' );

			return $list;
		}

		public function mycred_memberpress_plugin_notices() {
 
			$msg = __( 'need to be active and installed to use myCred Memberpress plugin.', 'mycred-toolkit' );
			
			if ( !is_plugin_active('mycred/mycred.php') ) {
				printf( '<div class="notice notice-error"><p><a href="https://wordpress.org/plugins/mycred/">%1$s</a> %2$s</p></div>', esc_html__( 'myCred', 'mycred-toolkit' ), esc_html( $msg ) );
			}
		}
	}
endif;

function mycred_memberpress_core() {

	return myCred_Toolkit_Memberpress_Core::instance();
}

mycred_memberpress_core();

	add_action('plugins_loaded', function () {

		//Purchase OneTime/ Recurring Subscription
		if (!class_exists('MycredMembr_PurchaseSubsProduct')) :
			class MycredMembr_PurchaseSubsProduct extends myCRED_Hook {

				/**
				 * Construct
				 * @param $hook_prefs
				 * @param string $type
				 */
				function __construct( $hook_prefs, $type = 'mycred_default' ) {
					parent::__construct( array(
						'id'       => 'onetime_recurr',
						'defaults' => array(
							'creds'   => 1,
							'log'     => '%plural% for Purchasing OneTime or Recurring Product',
							'limit'   => '1/d',
							'product'   => 'recurring_checkbox'
						)
					), $hook_prefs, $type );
				}

				/**
				 * Run
				 * @since 0.1
				 * @version 1.1
				 */
				public function run() {

					// WordPress
					add_action( 'mepr-event-non-recurring-transaction-completed', array( $this, 'one_time_recurr_product' ), 10, 2 );
					add_action( 'mepr-event-recurring-transaction-completed', array( $this, 'recurring_subscription' ), 10, 2 );
				}

				public function recurring_subscription( $event, $user_login = '' ) {
					die('recurring_subscription');
					$prefs = $this->prefs;

					if ($prefs['product'] == 'recurring') {
						$user_ID = get_current_user_id();

						$transaction = $event->get_data();

						$member_user = $transaction;

						$user_subscription = $member_user->product_id;

						$is_first_real_payment = false;

						if ($this->core->exclude_user($user_ID)) {
return;
						}

						$is_first_real_payment = false;

						if ($transaction->amount > 0.00) {
							$is_first_real_payment = true;
						}
						if ($is_first_real_payment) {
							// Check for exclusion
							if ($this->core->exclude_user($user_ID)) {
return;
							}

							// Limit
							if (!$this->over_hook_limit('', 'recurring_subscription', $user_ID)) {
								$this->core->add_creds(
									'recurring_subscription',
									$user_ID,
									$this->prefs['creds'],
									$this->prefs['log'],
									0,
									'',
									$this->mycred_type
								);
							}
						}
					} else {
						return;
					}
				}

				/**
				 * Login Hook
				 * @since 0.1
				 * @version 1.3
				 */

				public function one_time_recurr_product( $event, $user_login = '' ) {
					
					$user_ID = get_current_user_id();

					$prefs = $this->prefs;

					$transaction = $event->get_data();

					$member_user = $transaction;

					$user_subscription = $member_user->product_id;
;

					$is_first_real_payment = false;

					if ($transaction->amount > 0.00) {
						$is_first_real_payment = true;
					}
					if ($is_first_real_payment) {
						// Check for exclusion
						if ($this->core->exclude_user($user_ID)) {
return;
						}

						// Limit
						if (!$this->over_hook_limit('', 'onetime_subscription', $user_ID)) {
							$this->core->add_creds(
								'onetime_subscription',
								$user_ID,
								$this->prefs['creds'],
								$this->prefs['log'],
								0,
								'',
								$this->mycred_type
							);
						}
					} else {
						return;
					}
				}
				
				/**
				 * Preference for Login Hook
				 * @since 0.1
				 * @version 1.2
				 */
				public function preferences() {
					$prefs = $this->prefs;

					?>
					<div class="hook-instance">
						<div class="row">
							<div class="col-lg-2 col-md-6 col-sm-6 col-xs-12">
								<div class="form-group">
									<label for="<?php echo $this->field_id( 'creds' ); ?>"><?php echo $this->core->plural(); ?></label>
									<input type="text" name="<?php echo $this->field_name( 'creds' ); ?>" id="<?php echo $this->field_id( 'creds' ); ?>" value="<?php echo $this->core->number( $prefs['creds'] ); ?>" class="form-control" />
								</div>
							</div>
							<div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
								<div class="form-group">
									<label for="<?php echo $this->field_id( 'limit' ); ?>"><?php esc_html_e( 'Limit', 'mycred-toolkit' ); ?></label>
									<?php echo $this->hook_limit_setting( $this->field_name( 'limit' ), $this->field_id( 'limit' ), esc_attr( $prefs['limit'] ) ); ?>
								</div>
							</div>
							<div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
								<div class="form-group">
									<label for="<?php echo $this->field_id( 'recuring' ); ?>"><?php esc_html_e( 'Check for recurring', 'mycred-toolkit' ); ?></label>
									<br>
									<input type="checkbox" name="<?php echo $this->field_name('product'); ?>" value="recurring" 
																			<?php
																			if ($prefs['product'] == 'recurring') {
										echo "checked='checked'";}
																			?>
									id="<?php echo $this->field_id( 'recuring' ); ?>">
								</div>
							</div>
							<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
								<div class="form-group">
									<label for="<?php echo $this->field_id( 'log' ); ?>"><?php esc_html_e( 'Log Template', 'mycred-toolkit' ); ?></label>
									<input type="text" name="<?php echo $this->field_name( 'log' ); ?>" id="<?php echo $this->field_id( 'log' ); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control" />
									<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
								</div>
							</div>
						</div>
					</div>
			
					<?php
				}

				/**
				 * Sanitise Preferences
				 * @since 1.6
				 * @version 1.0
				 */
				function sanitise_preferences( $data ) {

					if ( isset( $data['limit'] ) && isset( $data['limit_by'] ) ) {
						$limit = sanitize_text_field( $data['limit'] );
						if ( $limit == '' ) {
$limit = 0;
						}
						$data['limit'] = $limit . '/' . $data['limit_by'];
						unset( $data['limit_by'] );
					}

					return $data;
				}
			}
		endif;
	});