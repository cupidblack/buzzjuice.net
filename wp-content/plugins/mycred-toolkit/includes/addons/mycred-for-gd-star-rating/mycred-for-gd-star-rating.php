<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'myCRED_Toolkit_GD_Star_Rating' ) ) :
	final class myCRED_Toolkit_GD_Star_Rating {

		

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

			$this->slug        = 'mycred-gd-star-rating';
			$this->plugin      = plugin_basename( __FILE__ );
			$this->domain      = 'mycred-toolkit';
			$this->plugin_name = 'myCRED for GD Star Rating';

			$this->define_constants();

			add_filter( 'mycred_setup_hooks', array( $this, 'register_hook' ) );
			add_action( 'mycred_init', array( $this, 'load_textdomain' ) );
			add_action( 'mycred_all_references', array( $this, 'add_badge_support' ) );
			add_action( 'mycred_load_hooks', 'mycred_load_gd_star_rating_hook' );
		}

		/**
		 * Define Constants
		 * @since 1.0
		 * @version 1.0
		 */
		public function define_constants() {

			$this->define( 'MYCRED_GD_STAR_SLUG', $this->slug );
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
		 * @version 1.0.1
		 */
		public function register_hook( $installed ) {

			if ( ! function_exists( 'gdrts' ) ) {
return $installed;
			}

			$installed['gdstars'] = array(
				'title'       => __( 'GD Star Rating', 'mycred-toolkit' ),
				'description' => __( 'Awards %_plural% for users rate items using the GD Star Rating plugin.', 'mycred-toolkit' ),
				'callback'    => array( 'myCRED_Hook_GD_Star_Rating' )
			);

			return $installed;
		}

		/**
		 * Add Badge Support
		 * @since 1.0
		 * @version 1.0
		 */
		public function add_badge_support( $references ) {

			if ( ! function_exists( 'gdrts' ) ) {
return $references;
			}

			$references['star_rating'] = __( 'Rating Content (GD Star Rating)', 'mycred-toolkit' );
			$references['star_rated']  = __( 'Receiving a Rating (GD Star Rating)', 'mycred-toolkit' );

			return $references;
		}
	}
endif;

function mycred_gd_star_rating_plugin() {
	return myCRED_Toolkit_GD_Star_Rating::instance();
}
mycred_gd_star_rating_plugin();

/**
 * GD Star Rating Hook
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_load_gd_star_rating_hook' ) ) :
	function mycred_load_gd_star_rating_hook() {

		if ( class_exists( 'myCRED_Hook_GD_Star_Rating' ) || ! function_exists( 'gdrts' ) ) {
return;
		}

		class myCRED_Hook_GD_Star_Rating extends myCRED_Hook {

			/**
			 * Construct
			 */
			function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

				parent::__construct( array(
					'id'       => 'gdstars',
					'defaults' => array(
						'star_rating' => array(
							'creds'       => 1,
							'log'         => '%plural% for rating',
							'limit'       => '0/x'
						),
						'star_rated'  => array(
							'creds'       => 1,
							'log'         => '%plural% for rated content',
							'limit'       => '0/x'
						)
					)
				), $hook_prefs, $type );
			}

			/**
			 * Run
			 * @since 1.0
			 * @version 1.0
			 */
			public function run() {

				add_action( 'gdrts_db_vote_logged', array( $this, 'new_rating' ), 10, 3 );
			}

			/**
			 * Vote
			 * @since 1.0
			 * @version 1.0.1
			 */
			public function new_rating( $log_id, $data, $meta ) {

				if ( ! is_user_logged_in() ) {
return;
				}

				extract( $data );

				$run  = true;
				$item = get_post( $item_id );
				if ( ! isset( $item->post_author ) || $user_id == $item->post_author ) {
					$run = false;
				}

				// Reward the rating
				if ( $run && $this->prefs['star_rating']['creds'] != 0 ) {
					if ( ! $this->over_hook_limit( 'star_rating', 'star_rating', $user_id ) ) {
						$this->core->add_creds(
							'star_rating',
							$user_id,
							$this->prefs['star_rating']['creds'],
							$this->prefs['star_rating']['log'],
							$item_id,
							array(
								'ref_type' => 'post',
								'rating' => $meta['vote']
							),
							$this->mycred_type
						);
					}

				}

				// Reward getting rated
				if ( $run && $this->prefs['star_rated']['creds'] != 0 ) {
					if ( ! $this->over_hook_limit( 'star_rated', 'star_rated', $user_id ) ) {
						$this->core->add_creds(
							'star_rated',
							$item->post_author,
							$this->prefs['star_rated']['creds'],
							$this->prefs['star_rated']['log'],
							$item_id,
							array(
								'ref_type' => 'post',
								'rating' => $meta['vote']
							),
							$this->mycred_type
						);
					}
				}
			}

			/**
			 * Preferences
			 * @since 1.0
			 * @version 1.0.1
			 */
			public function preferences() {
				$prefs = $this->prefs;
				$select_parm_rating = array(
					'ol' => array(),
					'li' => array(),
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
				$select_parm_receiving = array(
					'ol' => array(),
					'li' => array(),
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
	<label class="subheader" for="<?php echo esc_attr( $this->field_id( array( 'star_rating' => 'creds' ) ) ); ?>"><?php esc_html_e( 'Rating', 'mycred-toolkit' ); ?></label>
	<div class="row">
		<div class="col-lg-2 col-md-2 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr( $this->field_id( array( 'star_rating' => 'creds' ) ) ); ?>"><?php echo esc_html( $this->core->plural() ); ?></label>
				<input type="text" name="<?php echo esc_attr( $this->field_name( array( 'star_rating' => 'creds' ) ) ); ?>" id="<?php echo esc_attr( $this->field_id( array( 'star_rating' => 'creds' ) ) ); ?>" value="<?php echo esc_attr( $this->core->number( $prefs['star_rating']['creds'] ) ); ?>" size="8" class="form-control" />
				<span class="description"><?php esc_html_e( 'Authors rating their own content will NOT receive points!', 'mycred-toolkit' ); ?></span>
			</div>
		</div>
				<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
					<div class="form-group">
						<label class="subheader"><?php esc_html_e( 'Limit', 'mycred-toolkit' ); ?></label>
							<?php 
							echo wp_kses(
								$this->hook_limit_setting( $this->field_name( array( 'star_rating' => 'limit' ) ), $this->field_id( array( 'star_rating' => 'limit' ) ), $prefs['star_rating']['limit'] ),
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
		<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
			<div class="form-group">
				<label class="subheader" for="<?php echo esc_attr( $this->field_id( array( 'star_rating' => 'log' ) ) ); ?>"><?php esc_html_e( 'Log Template', 'mycred-toolkit' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $this->field_name( array( 'star_rating' => 'log' ) ) ); ?>" id="<?php echo esc_attr( $this->field_id( array( 'star_rating' => 'log' ) ) ); ?>" value="<?php echo esc_attr( $prefs['star_rating']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo wp_kses_post( $this->available_template_tags( array( 'general', 'post' ) ) ); ?></span>
			</div>
		</div>
	</div>
	<label class="subheader" for="<?php echo esc_attr( $this->field_id( array( 'star_rated' => 'creds' ) ) ); ?>"><?php esc_html_e( 'Receiving a Rating', 'mycred-toolkit' ); ?></label>
	<div class="row">
		<div class="col-lg-2 col-md-2 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr( $this->field_id( array( 'star_rated' => 'creds' ) ) ); ?>"><?php echo esc_html( $this->core->plural() ); ?></label>
				<input type="text" name="<?php echo esc_attr( $this->field_name( array( 'star_rated' => 'creds' ) ) ); ?>" id="<?php echo esc_attr( $this->field_id( array( 'star_rated' => 'creds' ) ) ); ?>" value="<?php echo esc_attr( $this->core->number( $prefs['star_rated']['creds'] ) ); ?>" size="8" class="form-control" />
				<span class="description"><?php esc_html_e( 'Authors rating their own content will NOT receive points!', 'mycred-toolkit' ); ?></span>
			</div>
		</div>
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label class="subheader"><?php esc_html_e( 'Limit', 'mycred-toolkit' ); ?></label>
					<?php 
					echo wp_kses( $this->hook_limit_setting( $this->field_name( array( 'star_rated' => 'limit' ) ), $this->field_id( array( 'star_rated' => 'limit' ) ), $prefs['star_rated']['limit'] ),
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
		<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
			<div class="form-group">
				<label class="subheader" for="<?php echo esc_attr( $this->field_id( array( 'star_rated' => 'log' ) ) ); ?>"><?php esc_html_e( 'Log Template', 'mycred-toolkit' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $this->field_name( array( 'star_rated' => 'log' ) ) ); ?>" id="<?php echo esc_attr( $this->field_id( array( 'star_rated' => 'log' ) ) ); ?>" value="<?php echo esc_attr( $prefs['star_rated']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo wp_kses_post( $this->available_template_tags( array( 'general', 'post' ) ) ); ?></span>
			</div>
		</div>
	</div>
</div>
<?php
			}

			/**
			 * Sanitise Preferences
			 * @since 1.0.1
			 * @version 1.0
			 */
			function sanitise_preferences( $data ) {

				if ( isset( $data['star_rating']['limit'] ) && isset( $data['star_rating']['limit_by'] ) ) {
					$limit = sanitize_text_field( $data['star_rating']['limit'] );
					if ( $limit == '' ) {
$limit = 0;
					}
					$data['star_rating']['limit'] = $limit . '/' . $data['star_rating']['limit_by'];
					unset( $data['star_rating']['limit_by'] );
				}

				if ( isset( $data['star_rated']['limit'] ) && isset( $data['star_rated']['limit_by'] ) ) {
					$limit = sanitize_text_field( $data['star_rated']['limit'] );
					if ( $limit == '' ) {
$limit = 0;
					}
					$data['star_rated']['limit'] = $limit . '/' . $data['star_rated']['limit_by'];
					unset( $data['star_rated']['limit_by'] );
				}

				return $data;
			}
		}
	}
endif;
