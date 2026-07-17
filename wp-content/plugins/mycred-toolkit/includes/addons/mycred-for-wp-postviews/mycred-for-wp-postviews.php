<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'mycred-toolkit' ) ) :
	final class myCRED_Toolkit_WP_PostViews {

		

		// Instnace
		protected static $_instance = null;

		// Current session
		public $session             = null;

		public $slug                = '';
		public $domain              = '';
		public $plugin              = null;
		public $plugin_name         = '';

		/**
		 * Setup Instance
		 * @since 1.0
		 * @version 1.0.4
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.0.4
		 */
		public function __clone() {
 _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.1' ); }

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.0.4
		 */
		public function __wakeup() {
 _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.1' ); }

		/**
		 * Define
		 * @since 1.0
		 * @version 1.0.4
		 */
		private function define( $name, $value, $definable = true ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Require File
		 * @since 1.0
		 * @version 1.0.4
		 */
		public function file( $required_file ) {
			if ( file_exists( $required_file ) ) {
				require_once $required_file;
			}
		}

		/**
		 * Construct
		 * @since 1.0
		 * @version 1.0.4
		 */
		public function __construct() {

			$this->slug        = 'mycred-wp-postviews';
			$this->plugin      = plugin_basename( __FILE__ );
			$this->domain      = 'mycred-toolkit';
			$this->plugin_name = 'myCRED for WP-PostViews';
			$this->define_constants();

			add_filter( 'mycred_setup_hooks', array( $this, 'register_hook' ) );
			add_action( 'mycred_init', array( $this, 'load_textdomain' ) );
			add_action( 'mycred_all_references', array( $this, 'add_badge_support' ) );
			add_action( 'mycred_load_hooks', 'mycred_load_wp_postviews_hook' );
		}

		/**
		 * Define Constants
		 * @since 1.0
		 * @version 1.0.4
		 */
		public function define_constants() {

			$this->define( 'MYCRED_WP_POSTVIEWS_SLUG', $this->slug );
			$this->define( 'MYCRED_DEFAULT_TYPE_KEY', 'mycred_default' );
		}

		/**
		 * Includes
		 * @since 1.0
		 * @version 1.0.4
		 */
		public function includes() { }

		/**
		 * Load Textdomain
		 * @since 1.0
		 * @version 1.0.4
		 */
		public function load_textdomain() {

			// Load Translation
			$locale = apply_filters( 'plugin_locale', get_locale(), $this->domain );

			load_textdomain( $this->domain, WP_LANG_DIR . '/' . $this->slug . '/' . $this->domain . '-' . $locale . '.mo' );
			load_plugin_textdomain( $this->domain, false, dirname( $this->plugin ) . '/lang/' );
		}

		/**
		 * Register Hook
		 * @since 1.0
		 * @version 1.0.4
		 */
		public function register_hook( $installed ) {

			if ( ! function_exists( 'wp_postview_cache_count_enqueue' ) ) {
return $installed;
			}

			$installed['wppostviews'] = array(
				'title'       => __( 'WP-PostViews', 'mycred-toolkit' ),
				'description' => __( 'Allows you to reward authors points for gaining post views.', 'mycred-toolkit' ),
				'callback'    => array( 'myCRED_Hook_WP_Postviews' )
			);

			return $installed;
		}

		/**
		 * Add Badge Support
		 * @since 1.0
		 * @version 1.0.4
		 */
		public function add_badge_support( $references ) {

			if ( ! function_exists( 'wp_postview_cache_count_enqueue' ) ) {
return $references;
			}

			$references['postview'] = __( 'Post View (WP-PostViews)', 'mycred-toolkit' );

			return $references;
		}
	}
endif;

function mycred_toolkit_wp_postviews_plugin() {
	return myCRED_Toolkit_WP_PostViews::instance();
}
mycred_toolkit_wp_postviews_plugin();

/**
 * WP Post Views Hook
 * @since 1.0
 * @version 1.0.4
 */
if ( ! function_exists( 'mycred_load_wp_postviews_hook' ) ) :
	function mycred_load_wp_postviews_hook() {

		if ( class_exists( 'myCRED_Hook_WP_Postviews' ) || ! function_exists( 'wp_postview_cache_count_enqueue' ) ) {
return;
		}

		class myCRED_Hook_WP_Postviews extends myCRED_Hook {

			/**
			 * Construct
			 */
			function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

				parent::__construct( array(
					'id'       => 'wppostviews',
					'defaults' => array(
						'creds'  => 1,
						'log'    => '%plural% for post view',
						'limit'  => '0/x'
					)
				), $hook_prefs, $type );
			}

			/**
			 * Hook into WP Postviews
			 * @since 1.0
			 * @version 1.0.4
			 */
			public function run() {

				add_action( 'postviews_increment_views', array( $this, 'new_view' ) );
				add_action( 'postviews_increment_views_ajax', array( $this, 'new_view_ajax' ), 1 );
			}

			/**
			 * New View
			 * @since 1.0
			 * @version 1.0.4
			 */
			public function new_view() {

				global $post;
				$this->process_new_view( $post );
			}

			/**
			 * New View Ajax
			 * @since 1.0
			 * @version 1.0.4
			 */
			public function new_view_ajax() {

				$postviewsid = isset($_GET['postviews_id']) ? absint($_GET['postviews_id']) : 0;
				$post_id = intval( $postviewsid );

				$post = get_post( $post_id );
				if ( isset( $post->ID ) ) {
					$this->process_new_view( $post );
				}
			}

			/**
			 * Process View
			 * @since 1.0
			 * @version 1.0.4
			 */
			protected function process_new_view( $post ) {

				$data = array( 'ref_type' => 'post' );

				// Check for exclusions
				if ( $this->core->exclude_user( $post->post_author ) ) {
return;
				}

				// Make sure this is unique
				if ( $this->core->has_entry( 'postview', $post->ID, $post->post_author, $data, $this->mycred_type ) ) {
return;
				}

				// Payout if not over limit
				if ( ! $this->over_hook_limit( '', 'postview', $post->post_author ) ) {
					$this->core->add_creds(
						'postview',
						$post->post_author,
						$this->prefs['creds'],
						$this->prefs['log'],
						$post->ID,
						$data,
						$this->mycred_type
					);
				}
			}

			/**
			 * Preferences
			 * @since 1.0
			 * @version 1.0.4
			 */
			public function preferences() {

				$prefs = $this->prefs;

				?>
<div class="hook-instance">
	<label class="subheader"><?php esc_html_e( 'Receiving Post View', 'mycred-toolkit' ); ?></label>
	<div class="row">
		<div class="col-lg-2 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>"><?php echo esc_html( $this->core->plural() ); ?></label>
				<input type="text" name="<?php echo esc_attr( $this->field_name( 'creds' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>" value="<?php echo esc_attr( $this->core->number( $prefs['creds'] ) ); ?>" size="8" class="form-control" />
			</div>
		</div>
		<div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr( $this->field_id( 'limit' ) ); ?>"><?php esc_html_e( 'Limit', 'mycred-toolkit' ); ?></label>
				<?php 
				echo wp_kses( $this->hook_limit_setting( $this->field_name( 'limit' ), $this->field_id( 'limit' ), esc_attr( $prefs['limit'] ) ),
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
				<label for="<?php echo esc_attr( $this->field_id( 'log' ) ); ?>"><?php esc_html_e( 'Log template', 'mycred-toolkit' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $this->field_name( 'log' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo wp_kses_post( $this->core->available_template_tags( array( 'general', 'post' ) ) ); ?></span>
			</div>
		</div>
	</div>
</div>
<?php
			}

			/**
			 * Sanitise Preferences
			 * @since 1.0
			 * @version 1.0.4
			 */
			public function sanitise_preferences( $data ) {

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
	}
endif;
