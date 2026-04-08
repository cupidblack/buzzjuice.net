<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'mycred-toolkit' ) ) :
	final class myCRED_Toolkit_BP_Compliments {


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
		 * @version 1.0
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
		 * @version 1.0
		 */
		public function __clone() {
 _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0' ); }

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.0
		 */
		public function __wakeup() {
 _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0' ); }

		/**
		 * Define
		 * @since 1.0
		 * @version 1.0
		 */
		private function define( $name, $value, $definable = true ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Require File
		 * @since 1.0
		 * @version 1.0
		 */
		public function file( $required_file ) {
			if ( file_exists( $required_file ) ) {
				require_once $required_file;
			}
		}

		/**
		 * Construct
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct() {

			$this->slug        = 'mycred-buddypress-compliments';
			$this->plugin      = plugin_basename( __FILE__ );
			$this->domain      = 'mycred-toolkit';
			$this->plugin_name = 'myCRED for BuddyPress Compliments';

			$this->define_constants();

			add_filter( 'mycred_setup_hooks', array( $this, 'register_hook' ) );
			add_action( 'mycred_init', array( $this, 'load_textdomain' ) );
			add_action( 'mycred_all_references', array( $this, 'add_badge_support' ) );
			add_action( 'mycred_load_hooks', 'mycred_bp_compliments_load_hook' );
		}

		/**
		 * Define Constants
		 * @since 1.0
		 * @version 1.0
		 */
		public function define_constants() {

			$this->define( 'MYCRED_DEFAULT_TYPE_KEY', 'mycred_default' );
		}

		/**
		 * Includes
		 * @since 1.0
		 * @version 1.0
		 */
		public function includes() { }

		/**
		 * Load Textdomain
		 * @since 1.0
		 * @version 1.0
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
		 * @version 1.0
		 */
		public function register_hook( $installed ) {

			if ( ! function_exists( 'bp_compliments_init' ) ) {
return $installed;
			}

			$installed['bp-compliments'] = array(
				'title'       => __( 'BuddyPress Compliments', 'mycred-toolkit' ),
				'description' => __( 'Awards %_plural% to users for sending or receiving compliments.', 'mycred-toolkit' ),
				'callback'    => array( 'myCRED_Hook_BP_Compliments' )
			);

			return $installed;
		}

		/**
		 * Add Badge Support
		 * @since 1.0
		 * @version 1.0
		 */
		public function add_badge_support( $references ) {

			if ( ! function_exists( 'bp_compliments_init' ) ) {
return $references;
			}

			$references['giving_compliment']    = __( 'Giving a Compliment (BuddyPress Compliments)', 'mycred-toolkit' );
			$references['receiving_compliment'] = __( 'Receiving a Compliment (BuddyPress Compliments)', 'mycred-toolkit' );

			return $references;
		}
	}
endif;

function mycred_toolkit_bp_compliments_plugin() {
	return myCRED_Toolkit_BP_Compliments::instance();
}
mycred_toolkit_bp_compliments_plugin();

/**
 * Load BP Compliments Hook
 * Finally we need to load the hook class. It is recommended you use the mycred_pre_init
 * action hook because then the class will only load if myCRED is installed and will load
 * in the correct moment. I do recommend you still check if class exists myCRED_Hook in case
 * someone really nuts on customizing myCRED on your website. 
 * @since 1.0
 * @version 1.0.1
 */
if ( ! function_exists( 'mycred_bp_compliments_load_hook' ) ) :
	function mycred_bp_compliments_load_hook() {

		if ( class_exists( 'myCRED_Hook_BP_Compliments' ) || ! function_exists( 'bp_compliments_init' ) ) {
return;
		}

		class myCRED_Hook_BP_Compliments extends myCRED_Hook {

			/**
			 * Construct
			 */
			function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

				parent::__construct( array(
					'id'       => 'bp-compliments',
					'defaults' => array(
						'giving'    => array(
							'creds'     => 0,
							'log'       => '%plural% for giving a compliment',
							'limit'     => '0/x'
						),
						'receiving' => array(
							'creds'     => 0,
							'log'       => '%plural% for receiving compliments',
							'limit'     => '0/x'
						)
					)
				), $hook_prefs, $type );
			}

			/**
			 * Run
			 * This class method is fired of by myCRED when it's time to load all hooks.
			 * It should be used to "hook" into the plugin we want to add support for or the
			 * appropriate WordPress instances. Anything that must be loaded for this hook to work
			 * needs to be called here.
			 * @since 1.0
			 * @version 1.0
			 */
			public function run() {

				add_action( 'bp_compliments_after_save', array( $this, 'new_compliment' ) );
			}

			/**
			 * New Compliment
			 * Not sure but looking at the BuddyPress Compliments plugin, this seems to be the
			 * best place to detect new compliments being given. We get an object to play with that contains
			 * the senders and receives user IDs, which we need. Otherwise, how will we know who to give points to?
			 * @since 1.0
			 * @version 1.0
			 */
			public function new_compliment( $bp_compliments_object ) {

				// Can not award guests
				if ( ! is_user_logged_in() ) {
return;
				}

				// We start with the person giving the compliment
				if ( $this->prefs['giving']['creds'] != 0 && ! $this->core->exclude_user( $bp_compliments_object->sender_id ) ) {

					// If we are not over the hook limit, award points
					if ( ! $this->over_hook_limit( 'giving', 'giving_compliment', $bp_compliments_object->sender_id ) ) {
						$this->core->add_creds(
							'giving_compliment',
							$bp_compliments_object->sender_id,
							$this->prefs['giving']['creds'],
							$this->prefs['giving']['log'],
							$bp_compliments_object->receiver_id,
							array( 'ref_type' => 'user' ),
							$this->mycred_type
						);
					}

				}

				// We then finish with the person reciving it
				if ( $this->prefs['receiving']['creds'] != 0 && ! $this->core->exclude_user( $bp_compliments_object->receiver_id ) ) {

					// If we are not over the hook limit, award points
					if ( ! $this->over_hook_limit( 'receiving', 'receiving_compliment', $bp_compliments_object->receiver_id ) ) {
						$this->core->add_creds(
							'receiving_compliment',
							$bp_compliments_object->receiver_id,
							$this->prefs['receiving']['creds'],
							$this->prefs['receiving']['log'],
							$bp_compliments_object->sender_id,
							array( 'ref_type' => 'user' ),
							$this->mycred_type
						);
					}

				}
			}

			/**
			 * Preferences
			 * If the hook has settings, it has to be added in using this class method.
			 * @since 1.0
			 * @version 1.0
			 */
			public function preferences() {

				$prefs = $this->prefs;

				$select_parm = array(
					'div' => array(
						'class' => array(),
					),
					'input' => array(
						'class' => array(),
						'type' => array(),
						'name' => array(),
						'id' => array(),
						'size' => array(),
						'value' => array()
					),
					'select' => array(
						'name'  => array(),
						'class' => array(),
						'id' => array(),
					),
					'option' => array(
						'value' => array()
					),
				);

				?>
<div class="hook-instance">
	<label class="subheader"><?php esc_html_e( 'Giving a Compliment', 'mycred-toolkit' ); ?></label>
	<div class="row">
		<div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr( $this->field_id( array( 'giving' => 'creds' ) ) ); ?>"><?php echo esc_html( $this->core->plural() ); ?></label>
				<input class="form-control" type="text" name="<?php echo esc_attr( $this->field_name( array( 'giving' => 'creds' ) ) ); ?>" id="<?php echo esc_attr( $this->field_id( array( 'giving' => 'creds' ) ) ); ?>" value="<?php echo esc_attr( $this->core->number( $prefs['giving']['creds'] ) ); ?>" size="8" />

			</div>
		</div>
		<div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr( $this->field_id( array( 'giving' => 'limit' ) ) ); ?>"><?php esc_html_e( 'Limit', 'mycred-toolkit' ); ?></label>
				<?php echo wp_kses( $this->hook_limit_setting( $this->field_name( array( 'giving' => 'limit' ) ), $this->field_id( array( 'giving' => 'limit' ) ), $prefs['giving']['limit'] ), $select_parm ); ?>
			</div>
		</div>
		<div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
			<div class="form-group">
				<label class="subheader"><?php esc_html_e( 'Log template', 'mycred-toolkit' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $this->field_name( array( 'giving' => 'log' ) ) ); ?>" id="<?php echo esc_attr( $this->field_id( array( 'giving' => 'log' ) ) ); ?>" value="<?php echo esc_attr( $prefs['giving']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo wp_kses_post( $this->available_template_tags( array( 'general', 'user' ) ) ); ?></span>
			</div>
		</div>
	</div>
	<label class="subheader"><?php esc_html_e( 'Receiving a Compliment', 'mycred-toolkit' ); ?></label>
	<div class="row">
		<div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr( $this->field_id( array( 'receiving' => 'creds' ) ) ); ?>"><?php echo esc_html( $this->core->plural() ); ?></label>
				<input class="form-control" type="text" name="<?php echo esc_attr( $this->field_name( array( 'receiving' => 'creds' ) ) ); ?>" id="<?php echo esc_attr( $this->field_id( array( 'receiving' => 'creds' ) ) ); ?>" value="<?php echo esc_attr( $this->core->number( $prefs['receiving']['creds'] ) ); ?>" size="8" />
			</div>
		</div>
		<div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr( $this->field_id( array( 'receiving' => 'limit' ) ) ); ?>"><?php esc_html_e( 'Limit', 'mycred-toolkit' ); ?></label>
				<?php echo wp_kses( $this->hook_limit_setting( $this->field_name( array( 'receiving' => 'limit' ) ), $this->field_id( array( 'receiving' => 'limit' ) ), $prefs['receiving']['limit'] ), $select_parm ); ?>
			</div>
		</div>
		<div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
			<div class="form-group">
				<label class="subheader"><?php esc_html_e( 'Log template', 'mycred-toolkit' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $this->field_name( array( 'receiving' => 'log' ) ) ); ?>" id="<?php echo esc_attr( $this->field_id( array( 'receiving' => 'log' ) ) ); ?>" value="<?php echo esc_attr( $prefs['receiving']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo wp_kses_post( $this->available_template_tags( array( 'general', 'user' ) ) ); ?></span>
			</div>
		</div>
	</div>
</div>
<?php
			}

			/**
			 * Sanitise Preferences
			 * While myCRED does some basic sanitization of the data you submit in the settings,
			 * we do need to handle our hook limits since 1.6. If your settings contain a checkbox (or multiple)
			 * then you should also use this method to handle the submission making sure the checkbox values are
			 * taken care of.
			 * @since 1.0
			 * @version 1.0
			 */
			function sanitise_preferences( $data ) {

				if ( isset( $data['giving']['limit'] ) && isset( $data['giving']['limit_by'] ) ) {
					$limit = sanitize_text_field( $data['giving']['limit'] );
					if ( $limit == '' ) {
$limit = 0;
					}
					$data['giving']['limit'] = $limit . '/' . $data['giving']['limit_by'];
					unset( $data['giving']['limit_by'] );
				}

				if ( isset( $data['receiving']['limit'] ) && isset( $data['receiving']['limit_by'] ) ) {
					$limit = sanitize_text_field( $data['receiving']['limit'] );
					if ( $limit == '' ) {
$limit = 0;
					}
					$data['receiving']['limit'] = $limit . '/' . $data['receiving']['limit_by'];
					unset( $data['receiving']['limit_by'] );
				}

				return $data;
			}
		}
	}
endif;
