<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MWP_Module class
 * @since 2.0
 * @version 1.0
 */
if ( ! class_exists( 'MWP_Module' ) ) :
	abstract class MWP_Module {

		use MWP_Helper_Trait;

		// Module ID (unique)
		public $module_id;

		// Module name (string)
		public $module_name;

		// Separate Settings tab (bool)
		public $add_tab;

		// Setting Tab Title (string)
		public $title;

		// Icon (string)
		public $icon;

		// Screen ID (string)
		public $tab_pos;

		// Default preferences
		public $default_prefs = array();

		// Preferences
		public $prefs = array();

		// Module template files
		public $templates = array();

		/**
		 * Construct
		 */
		public function __construct( $module_id = '', $args = array() ) {

			// Module ID is required
			if ( empty( $module_id ) ) wp_die( 'MWP_Module() Error. A Module ID is required!' );

			$this->module_id = $module_id;

			// Default arguments
			$defaults = array(
				'module_name' => '',
				'defaults'    => array(),
				'add_tab'     => false,
				'title'       => '',
				'icon'        => 'dashicons-star-filled',
				'tab_pos'     => 10,
				'templates'   => array()
			);
			$args = wp_parse_args( $args, $defaults );

			$this->module_name   = $args['module_name'];
			$this->default_prefs = $args['defaults'];
			$this->add_tab       = $args['add_tab'];
			$this->title         = $args['title'];
			$this->icon          = $args['icon'];
			$this->tab_pos       = $args['tab_pos'];
			
			$this->set_settings();

		}

		/**
		 * Set Settings
		 * @since 2.0
		 * @version 1.0
		 */
		public function set_settings() {

			if ( empty( $this->default_prefs ) ) {
				return;
			}

			$prefs       = array();
			$module_name = $this->module_name;
			$settings    = mycred_get_option( 'mycred_pref_woo' );

			if ( ! empty( $settings ) && ! empty( $settings[ $module_name ] ) ) {

				$prefs = $settings[ $module_name ];

			}

			$this->prefs = wp_parse_args( $prefs, $this->default_prefs );

		}

		/**
		 * Load
		 * @since 2.0
		 * @version 1.0
		 */
		public function load() {

			if ( $this->add_tab ) {
				add_action( 'mycred_after_woocommerce_prefs', array( $this, 'add_settings_tab' ), $this->tab_pos + 1 );
			}

			if ( ! empty( $this->default_prefs ) ) {
				
				add_filter( 'mycred_get_addon_defaults',            array( $this, 'set_defaults' ), 10, 2 );
				add_filter( 'mycred_woocommerce_sanitize_settings', array( $this, 'sanitize_settings' ), 10, 3 );

			}

			add_action( 'mycred_pre_init',      array( $this, 'module_pre_init' ) );
			add_action( 'mycred_init',          array( $this, 'module_init' ) );
			add_action( 'mycred_admin_init',    array( $this, 'module_admin_init' ), $this->tab_pos + 1 );

		}

		/**
		 * Plugins Loaded (pre init)
		 * @since 2.0
		 * @version 1.0
		 */
		public function module_pre_init() { }

		/**
		 * Init
		 * @since 2.0
		 * @version 1.0
		 */
		public function module_init() { }

		/**
		 * Admin Init
		 * @since 2.0
		 * @version 1.0
		 */
		public function module_admin_init() { }
		
		/**
		 * Register Scripts & Styles
		 * @since 2.0
		 * @version 1.0
		 */
		public function add_settings_tab( $core ) {

			?>
			<div class="mycred-ui-accordion">
                <div class="mycred-ui-accordion-header">
                    <h4 class="mycred-ui-accordion-header-title">
                        <span class="dashicons <?php echo esc_attr( $this->icon );?> static mycred-ui-accordion-header-icon"></span>
                        <label><?php echo esc_html( $this->title );?></label>
                    </h4>
                    <div class="mycred-ui-accordion-header-actions hide-if-no-js">
                        <button type="button" aria-expanded="true">
                            <span class="mycred-ui-toggle-indicator" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
                <div class="body mycred-ui-accordion-body" style="display: none;">
                	<?php $this->admin_settings( $core );?>
                </div>
            </div>
			<?php
			
		}
		
		public function admin_settings( $core ) { }

		public function sanitize_settings( $new_data, $data, $core ) {

			return $new_data;

		}

		public function set_defaults( $default_setting, $addon ) {

			if ( $addon == 'woocommerce' ) {

				$default_setting[ $this->module_name ] = $this->default_prefs;
	            
			}

			return $default_setting;

		}

		/**
		 * Update Notice
		 * @since 1.4
		 * @version 1.0
		 */
		public function update_notice( $get = 'settings-updated', $class = 'updated', $message = '' ) {

			if ( empty( $message ) )
				$message = __( 'Settings Updated', 'mycred' );

			if ( isset( $_GET[ $get ] ) )
				echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p></div>';

		}

		/**
		 * Input Field Name Value
		 * @since 0.1
		 * @version 1.0
		 */
		public function field_name( $name = '' ) {

			if ( is_array( $name ) ) {

				$array = array();
				foreach ( $name as $parent => $child ) {

					if ( ! is_numeric( $parent ) )
						$array[] = $parent;

					if ( ! empty( $child ) && ! is_array( $child ) )
						$array[] = $child;

				}
				$name = '[' . implode( '][', $array ) . ']';

			}
			else {

				$name = '[' . $name . ']';

			}

			return 'mycred_pref_woo[' . $this->module_name . ']' . $name;

		}

		/**
		 * Input Field Id Value
		 * @since 0.1
		 * @version 1.0
		 */
		public function field_id( $id = '' ) {

			if ( is_array( $id ) ) {

				$array = array();
				foreach ( $id as $parent => $child ) {

					if ( ! is_numeric( $parent ) )
						$array[] = str_replace( '_', '-', $parent );

					if ( ! empty( $child ) && ! is_array( $child ) )
						$array[] = str_replace( '_', '-', $child );

				}
				$id = implode( '-', $array );

			}
			else {

				$id = str_replace( '_', '-', $id );

			}

			$id = 'mycredprefwoo' . strtolower( $this->module_name ) . $id;
			$id = strtolower( $id );
			$id = str_replace( array( '[', ']' ), '', $id );
			$id = str_replace( array( '_', '-' ), '', $id );

			return $id;

		}

		//Add new menu after given menu in WooCommerce My Account
		protected function add_menu_after( $items, $new_items, $after ) {

			// Search for the item position and +1 since is after the selected item key.
			$position = array_search( $after, array_keys( $items ) ) + 1;

			// Insert the new item.
			$sorted_items  = array_slice( $items, 0, $position, true );
			$sorted_items += $new_items;
			$sorted_items += array_slice( $items, $position, count( $items ) - $position, true );

			return $sorted_items;

		}

	}
endif;
