<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MyCred_Addons_Rest_API' ) ) {

	class MyCred_Addons_Rest_API {

		public function __construct() {

			add_action( 'rest_api_init' , array( $this, 'rest_api_mycred_addons' ) );
			add_action( 'mycred_after_plugin_loaded' , array( $this, 'mycred_addons_includes' ) );
		}


		public function rest_api_mycred_addons() {

			register_rest_route('mycred-toolkit/v1', '/enable-addon', array(
				'methods'               => 'POST',
				'callback'              => array( $this, 'mycred_enable_toolkit_addon' ),
				'permission_callback'   => array( $this, 'verify_nonce_and_permissions' )
			));

			register_rest_route('mycred-toolkit/v1', '/check-addons-files', array(
				'methods'               => 'POST',
				'callback'              => array( $this, 'mycred_pro_addon_file_check' ),
				'permission_callback'   => array( $this, 'verify_nonce_and_permissions' )
			));

			register_rest_route('mycred-toolkit/v1', '/get-addons', array(
				'methods' => 'GET',
				'callback' => array( $this, 'get_addons_callback' ),
				'permission_callback'   => array( $this, 'verify_nonce_and_permissions' )
			));
		}

		public function verify_nonce_and_permissions( $request ) {
			$nonce = $request->get_header('X-WP-Nonce'); 

			if ( !$nonce ) {
				return new WP_Error( 
					'rest_missing_nonce', 
					__('You do not have permission to access this resource. Please contact the administrator if you believe this is an error.', 'mycred-toolkit'), 
					array( 
						'status' => 403, 
						'message' => 'Nonce is missing.' 
					) 
				);
			}

			return true;
		}

		public function mycred_pro_addon_file_check( $request ) {

			$nonce = $request->get_header('X-WP-Nonce');
			if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				return new WP_Error( 'rest_forbidden', __('Invalid nonce.', 'mycred-toolkit'), array( 'status' => 403 ) );
			}

			$proAddons = $request->get_json_params();
			$response = array();

			foreach ( $proAddons as $proAddon ) {
				foreach ( $proAddon as $addon ) {
					$addOnSlug = $addon['slug'];
					$addOnPath = MYCRED_TOOLKIT_ROOT_DIR . 'includes/addons/' . $addOnSlug . '/' . $addOnSlug . '.php';

					if ( file_exists( $addOnPath ) ) {
						$response[] = array(
							'slug' => $addOnSlug,
							'status' => 'unlocked',
							'message' => 'Addon unlocked successfully'
						);
					} else {
						$response[] = array(
							'slug' => $addOnSlug,
							'status' => 'locked',
							'message' => 'Addon not found or locked'
						);
					}
				}
			}

			return rest_ensure_response( $response );
		}
		
		public function get_addons_callback( $request ) {
			if ( function_exists( 'mycred_get_active_addon_slugs' ) ) {
				return rest_ensure_response( mycred_get_active_addon_slugs() );
			}

			$enabled_addons = get_option( 'mycred_enabled_addons', array() );
			$response       = array();

			foreach ( $enabled_addons as $key => $addon ) {
				$response[ $key ] = $addon;
			}

			return rest_ensure_response( $response );
		}

		public function mycred_enable_toolkit_addon( $request ) {
			$nonce = $request->get_header('X-WP-Nonce');
			if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				return new WP_Error( 'rest_forbidden', __('Invalid nonce.', 'mycred-toolkit'), array( 'status' => 403 ) );
			}
			$params = $request->get_json_params();
			$addOnSlug = isset($params['addOnSlug']) ? sanitize_text_field($params['addOnSlug']) : '';
			$addOnTitle = isset($params['addOnTitle']) ? sanitize_text_field($params['addOnTitle']) : '';
			$dependency = isset($params['dependency']) ? sanitize_text_field($params['dependency']) : '';
			$dependencyName = isset($params['dependencyName']) ? sanitize_text_field($params['dependencyName']) : '';


			if ($dependency && !is_plugin_active($dependency)) {
				return rest_ensure_response(array(
					'status' => 'error',
					'message' => sprintf('Add-on "%s" requires the "%s" plugin to be active. Please activate the required plugin first.', $addOnTitle, $dependencyName),
					'enabled_addon' => $addOnSlug,
					'toggle' => false
				));
			}

			$addOnPath = MYCRED_TOOLKIT_ROOT_DIR . 'includes/addons/' . $addOnSlug . '/' . $addOnSlug . '.php';

			if ( file_exists( $addOnPath ) ) {

				$active_plugins = get_option( 'active_plugins', array() );
				foreach ( $active_plugins as $plugin ) {
					if ( strpos( $plugin, $addOnSlug ) !== false ) {

						deactivate_plugins( $plugin );

						if ( function_exists( 'mycred_set_addon_active' ) ) {
							$result = mycred_set_addon_active( $addOnSlug, true );
						} else {
							$enabled_addons = get_option( 'mycred_enabled_addons', array() );
							if ( ! in_array( $addOnSlug, $enabled_addons, true ) ) {
								$enabled_addons[] = $addOnSlug;
								update_option( 'mycred_enabled_addons', $enabled_addons );
							}
							$result = array( 'toggle' => true );
						}

						return rest_ensure_response( array(
							'status'        => 'success',
							'message'       => sprintf( 'Add-on "%s" has been enabled after deactivating the conflicting plugin.', $addOnTitle ),
							'enabled_addon' => $addOnSlug,
							'toggle'        => ! empty( $result['toggle'] ),
						) );
					}
				}

				if ( function_exists( 'mycred_set_addon_active' ) ) {
					$prefs          = get_option( 'mycred_pref_addons', array( 'active' => array() ) );
					$was_active     = is_array( $prefs['active'] ) && in_array( $addOnSlug, $prefs['active'], true );
					$result         = mycred_set_addon_active( $addOnSlug );
					$toggle         = ! empty( $result['toggle'] );

					if ( ! $was_active && $toggle ) {
						$this->register_mycred_toolkit_activation( $addOnSlug );
					}

					$message = $toggle
						? sprintf( 'Add-on "%s" has been enabled successfully.', $addOnTitle )
						: sprintf( 'Add-on "%s" has been disabled successfully.', $addOnTitle );

					return rest_ensure_response( array(
						'status'        => 'success',
						'message'       => $message,
						'enabled_addon' => $addOnSlug,
						'toggle'        => $toggle,
					) );
				}

				$enabled_addons = get_option( 'mycred_enabled_addons', array() );

				if ( in_array( $addOnSlug, $enabled_addons, true ) ) {
					$enabled_addons = array_diff( $enabled_addons, array( $addOnSlug ) );
					$message        = sprintf( 'Add-on "%s" has been disabled successfully.', $addOnTitle );
					$toggle         = false;
					$this->register_mycred_toolkit_activation( $addOnSlug );
				} else {
					$enabled_addons[] = $addOnSlug;
					$message          = sprintf( 'Add-on "%s" has been enabled successfully.', $addOnTitle );
					$toggle           = true;
				}

				update_option( 'mycred_enabled_addons', $enabled_addons );

				return rest_ensure_response( array(
					'status'        => 'success',
					'message'       => $message,
					'enabled_addon' => $addOnSlug,
					'toggle'        => $toggle,
				) );
			} else {
				return rest_ensure_response(array(
					'status' => 'error',
					'message' => sprintf('Add-on "%s" could not be enabled/disabled. File does not exist.', $addOnTitle),
					'enabled_addon' => $addOnSlug,
					'toggle' => false
				));
			}
		}

		public function register_mycred_toolkit_activation( $addOnSlug ) {
			$addOnPath = MYCRED_TOOLKIT_ROOT_DIR . 'includes/addons/' . $addOnSlug . '/activate.php';
			
			if ( file_exists($addOnPath) ) {
				include_once $addOnPath;
			}
		}

		public function mycred_addons_includes() {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			if ( ! class_exists( 'myCRED_Core' ) ) {
				return;
			}

			if ( function_exists( 'mycred_maybe_finalize_addons_schema' ) ) {
				mycred_maybe_finalize_addons_schema();
			}

			$enabled_addons = function_exists( 'mycred_get_active_addon_slugs' )
				? mycred_get_active_addon_slugs()
				: get_option( 'mycred_enabled_addons', array() );

			foreach ( $enabled_addons as $addOnSlug ) {
				$addOnPath = MYCRED_TOOLKIT_ROOT_DIR . 'includes/addons/' . $addOnSlug . '/' . $addOnSlug . '.php';
				if ( file_exists( $addOnPath ) ) {
					include_once $addOnPath;
				}
			}
		}
	}

	new MyCred_Addons_Rest_API();
}
