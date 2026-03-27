<?php

// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * myCRED_License class
 * Used for the addons license and update.
 * @since 2.3
 * @version 1.0
 */
if ( ! class_exists( 'myCRED_License' ) ) :
	#[AllowDynamicProperties]
	class myCRED_License {

		// Plugin Version
		private $version;

		// Plugin Slug
		private $slug;

		private $base;

		private $license;

		private $license_key_name;

		private $cache_key_name;

		private $api_base_url;

		/**
		 * Construct
		 */
		public function __construct( $data ) { 

			$this->version           = $data['version'];
			$this->slug              = $data['slug'];
			$this->base              = $data['base'];
			$this->filename          = plugin_basename( $this->base );
			$this->license_key_name  = 'mycred_membership_key';
			$this->transient_key     = 'mcl_' . md5( $this->slug );
			$this->api_endpoint      = 'https://license.mycred.me/wp-json/license/get-plugins';

			$this->init();

		}

		public function get_plugin_detail( $force = false ) {

			$plugin_info = get_site_transient( $this->transient_key );

			if ( false === $plugin_info || $force ) {
				
				$plugins_info_remote = $this->get_api_data();

				foreach ( $plugins_info_remote as $plugin ) {
					
					if ( ! empty( $plugin->package ) ) {
						
						$plugin->package = add_query_arg( 
							array( 
						        'license_key' => $this->get_license_key(),
						        'site'        => get_bloginfo( 'url' ),
								'api-key'     => md5( get_bloginfo( 'url' ) ),
								'slug'        => $plugin->slug
						    ), 
							$plugin->package 
						);

					}

					$transient_key = 'mcl_' . md5( $plugin->slug );
					set_site_transient( $transient_key, $plugin, 5 * HOUR_IN_SECONDS );

					if ( $plugin->slug == $this->slug ) 
						$plugin_info = $plugin;
				
				}

			}

			return $plugin_info;

		}

		public function init() {

			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ), 80 );
			add_filter( 'plugins_api', 							 array( $this, 'plugin_api_call' ), 80, 3 );
			add_filter( 'plugin_row_meta', 						 array( $this, 'plugin_view_info' ), 80, 3 );
			add_filter( 'mycred_license_addons',                 array( $this, 'register_addon_for_license' ) );
            add_action( 'admin_menu',        					 array( $this, 'mycred_membership_menu' ) );

		}

		public function register_addon_for_license( $addons ) {
			
			array_push( $addons, $this->slug );

			return $addons;

		}

		public function check_update( $data ) {

			$plugin_info = $this->get_plugin_detail();

			if ( ! empty( $plugin_info ) && ! empty( $plugin_info->new_version ) ) {
				
				if ( version_compare( $this->version, $plugin_info->new_version, '<' ) ) {
	                $data->response[ $this->filename ] = $plugin_info;
	            } else {
	                $data->no_update[ $this->filename ] = $plugin_info;
	            }

			}

	        $data->last_checked = time();
	        $data->checked[ $this->filename ] = $this->version;

			return $data;

		}

		public function plugin_api_call( $result, $action, $args ) {

			if ( empty( $args->slug ) || $args->slug != $this->slug ) 
				return $result;

			$data = $this->get_plugin_detail();

			if ( isset( $data->banners ) )
				$data->banners = (array) $data->banners;

			if ( isset( $data->sections ) ) {
				
				if ( ! empty( $data->sections->description ) )
					$data->sections->description = html_entity_decode( $data->sections->description );
				
				if ( ! empty( $data->sections->change_log ) )
					$data->sections->change_log = html_entity_decode( $data->sections->change_log );
				
				if ( ! empty( $data->sections->installation ) )
					$data->sections->installation = html_entity_decode( $data->sections->installation );

				$data->sections = (array) $data->sections;

			}

			return $data;

		}

		public function plugin_view_info( $plugin_meta, $file, $plugin_data ) {

			if ( $file != plugin_basename( $this->base ) ) return $plugin_meta;

			$plugin_info = $this->get_plugin_detail();

			if ( empty( $plugin_info->package ) && ( empty( $plugin_info->expiry ) || empty( $plugin_info->expiry->expiration_date ) ) ) {
				
				$message = 'License not found.';

				if ( ! empty( $plugin_info->message ) ) {
					$message = $plugin_info->message;
				}
				
				$plugin_meta[] = '<a href="http://mycred.me/about/terms/#product-licenses" style="color:red;" target="_blank"><strong>' . $message . '</strong></a>';

			}
			else {

				$plugin_meta[] = '<strong style="color:green;">Expires in ' . $this->calculate_license_expiry( $plugin_info->expiry->expiration_date ) . '</strong>';

			}


			return $plugin_meta;

		}

		public function get_mycred_addons() {

			return apply_filters( 'mycred_license_addons', array() );

		}

		public function get_license_key() {

			return get_option( $this->license_key_name );

		}

		public function get_api_data() {

			$cache_key    = 'mycred_license_remote_data';
		    $plugins_data = wp_cache_get( $cache_key );
		    
		    if ( false === $plugins_data ) {

				$plugins_data = new stdClass();
		        $license_key  = $this->get_license_key();
				$addons       = $this->get_mycred_addons();
				$request_args = array(
					'body' => array(
						'license_key' => $license_key,
						'site'        => get_bloginfo( 'url' ),
						'api-key'     => md5( get_bloginfo( 'url' ) ),
						'addons'      => $addons
					),
					'timeout' => 12
				);

				// Start checking for an update
				$response = wp_remote_post( $this->api_endpoint, $request_args );

				if ( ! is_wp_error( $response ) ) {

					$response_data = json_decode( $response['body'] );

					if ( ! empty( $response_data->status ) && $response_data->status == 'success' ) {
						
						$plugins_data = $response_data->data;

					}

				}
		  
		    }

	        wp_cache_set( $cache_key, $plugins_data );
			return $plugins_data;
			
		}

		public function calculate_license_expiry( $expire_date ) {

			$interval = date_create('now')->diff( date_create( $expire_date ) );

			$label_y = $interval->y > 1 ? "{$interval->y} years " : ( $interval->y == 1 ? "{$interval->y} year " : '' );
			$label_m = $interval->m > 1 ? "{$interval->m} months " : ( $interval->m == 1 ? "{$interval->m} month " : '' );
			$label_d = $interval->d > 1 ? "{$interval->d} days " : ( $interval->d == 1 ? "{$interval->d} day " : '' );

			return "{$label_y}{$label_m}{$label_d}";

		}

        /**
         * Register membership menu
         */
        public function mycred_membership_menu() {

        	$cache_key = 'mycred_add_main_submenu_license';
		    $has_menu  = wp_cache_get( $cache_key );

		    if ( false === $has_menu ) {

		        if ( ! $this->has_license_menu() ) {
		        	
		        	mycred_add_main_submenu( 
		                'License', 
		                'License', 
		                'manage_options', 
		                'mycred-membership',
		                array( $this, 'mycred_membership_callback' ) 
		            );

		        }
		 
		        wp_cache_set( $cache_key, true );
		        
		    }

        }
		
        /**
         * Membership menu callback
         */
        public function mycred_membership_callback() {

            $user_id = get_current_user_id();
            
            $this->mycred_save_license();

            $membership_key = get_option( 'mycred_membership_key', '' );
            $is_valid_key   = false;

            if ( ! empty( $membership_key ) ) {

                $is_valid_key = $this->mycred_is_valid_license_key( $membership_key );
            
            }

            global $license_msg;
           
            ?>
            <div class="wrap" id="myCRED-wrap">
                <div class="mmc_welcome">
                    <div class="mmc_welcome_content">
                        <?php if ( ! empty( $license_msg ) && $license_msg == 'empty' ) :?>
                            <div class="mycred_license_valid_check error notice is-dismissible">
                                <div class="mycred_valid_message">
                                    <strong>Please Enter a License Key.</strong>
                                </div>
                            </div>
                        <?php elseif ( ! empty( $license_msg ) && $license_msg == 'invalid' ) :?>
                            <div class="mycred_license_valid_check error notice is-dismissible">
                                <div class="mycred_valid_message">
                                    <strong>The License Key is Invalid.</strong>
                                </div>
                            </div>
                        <?php elseif ( ! empty( $license_msg ) && $license_msg == 'valid' ) :?>
                            <div class="mycred_license_valid_check updated notice is-dismissible">
                                <div class="mycred_valid_message">
                                    <strong>The License Key is Valid.</strong>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="mmc_title"><?php esc_html_e( 'Welcome to myCred Premium Club', 'mycred' ); ?></div>
                        <form action="#" method="post">
                        <?php 
                            wp_nonce_field( 'myCredLicense-nonce', 'mycred-license-nonce' );
                            if( $is_valid_key ) {
                                echo '<span class="dashicons dashicons-yes-alt membership-license-activated"></span>';
                            } 
                            else {
                                // if membership is not active in current site and the membership key is entered
                                echo '<span class="dashicons dashicons-dismiss membership-license-inactive"></span>';
                            }      
                        ?>
                            <input type="text" name="mmc_lincense_key" class="mmc_lincense_key" placeholder="<?php esc_attr_e( 'Add Your License key', 'mycred' ); ?>" value="<?php echo esc_attr( $membership_key );?>">
                            <input type="submit" class="mmc_save_license button-primary" style="<?php echo $is_valid_key ? 'background: #5e2ced' : 'background:#b91514'; ?>"value="Save"/>
                        </form>
                        <div class="mmc_license_link">
                            <a href="https://mycred.me/redirect-to-membership/" target="_blank">
                                <?php esc_html_e('Click here to get your License Key','mycred') ?>
                                <span class="dashicons dashicons-editor-help"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }

        /**
         * Saving user membership key
         */
        public function mycred_save_license() {
            
            if ( 
                isset( $_POST['mmc_lincense_key'] ) && 
                isset( $_POST['mycred-license-nonce'] ) && 
                wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mycred-license-nonce'] ) ), 'myCredLicense-nonce' ) 
            ) {

                $license_key = sanitize_text_field( wp_unslash( $_POST['mmc_lincense_key'] ) );

                update_option( 'mycred_membership_key', $license_key );

                global $license_msg;

                if( ! empty( $license_key ) ) {

                    $is_valid    = $this->mycred_is_valid_license_key( $license_key, true );
                    $license_msg = 'invalid'; 

                    if ( $is_valid ) 
                        $license_msg = 'valid'; 

                    $this->removeLicenseTransients();

                }
                else {

                    $license_msg = 'empty';

                }
                
            }
            
        }

        public function removeLicenseTransients() {
            
            $addons      = apply_filters( 'mycred_license_addons', array() );
            $update_data = get_site_transient( 'update_plugins' );

            foreach ( $addons as $addon ) {
                if ( isset( $update_data->response[ $addon . '/' . $addon . '.php' ] ) ) {
                    unset( $update_data->response[ $addon . '/' . $addon . '.php' ] );
                }
                if ( isset( $update_data->no_update[ $addon . '/' . $addon . '.php' ] ) ) {
                    unset( $update_data->no_update[ $addon . '/' . $addon . '.php' ] );
                }
                if ( isset( $update_data->checked[ $addon . '/' . $addon . '.php' ] ) ) {
                    unset( $update_data->checked[ $addon . '/' . $addon . '.php' ] );
                }       
                $transient_key = 'mcl_' . md5( $addon );
                delete_site_transient( $transient_key );
            }
            set_site_transient( 'update_plugins', $update_data );
        }

        public function mycred_is_valid_license_key( $key, $force = false ) {
	            
	        if( empty( $key ) ) return false;

	        $is_valid = get_site_transient( 'mycred_is_valid_license_key' );

	        if ( false === $is_valid || $force ) {

	            $is_valid     = 'no';
	            $api_endpoint = 'https://license.mycred.me/wp-json/license/is-valid-license-key';

	            $request_args = array(
	                'body' => array(
	                    'license_key' => $key,
	                    'site'        => get_bloginfo( 'url' ),
	                    'api-key'     => md5( get_bloginfo( 'url' ) )
	                ),
	                'timeout' => 12
	            );

	            // Start checking for an update
	            $response = wp_remote_post( $api_endpoint, $request_args );

	            if ( ! is_wp_error( $response ) ) {

	                $response_data = json_decode( $response['body'] );

	                if ( 
	                    ! empty( $response_data->status ) && 
	                    $response_data->status == 'success' 
	                ) {
	                    
	                    $is_valid = $response_data->data;

	                }

	            }

	            set_site_transient( 'mycred_is_valid_license_key', $is_valid, DAY_IN_SECONDS * 9999 );
	        }
	        
	        return ( $is_valid == 'yes' );

	    }

	    public function has_license_menu() {
	    	
	    	$has_menu = false;

	    	global $submenu;

	    	if ( ! empty( $submenu['mycred-main'] ) ) {

	    		foreach( $submenu['mycred-main'] as $menu_item ) {

	    			if ( $menu_item[2] == 'mycred-membership' ) {
	    				
	    				$has_menu = true;
	    				break;
	    			
	    			}

	    		}
	    		
	    	}

	    	return $has_menu;

	    }

	}
endif;