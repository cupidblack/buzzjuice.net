<?php
if ( ! defined( 'BP_CHARGES_VERSION' ) ) exit;

/**
 * Charge for Ranks
 * @since 1.0
 * @version 1.1
 */
if ( ! class_exists( 'myCRED_BP_Activity_Ranks_Badges' ) ) :
	class myCRED_BP_Activity_Ranks_Badges extends myCRED_BP_Charge {

		
		function __construct( $charge_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			global $mycred_bp_charge;

			$this->core_point_types = mycred_get_types();
            $this->point_type_value = array();
            $this->point_type_key = array();
            foreach ($this->core_point_types as $point_type => $point_type_label) {
            	$this->point_type_key[$point_type] = $point_type;
            	$this->point_type_value[$point_type_label] = $point_type_label;

            }

			parent::__construct( array(
				'id'       => 'activity_ranks_and_badges',
				'defaults' => array(
					'ctype'             => MYCRED_DEFAULT_TYPE_KEY,
					'userranks'        => 0,
					'userbadges'        => 0,
					'badgelevelimage'   => 0
					
				)
			), $charge_prefs, $type );

		}


		/**
		 * Run
		 * @since 1.0
		 * @version 1.0.1
		 */


		public function run() {
		

			add_action('bp_activity_entry_meta',array( $this, 'display_ranks_badges' ));
			add_action( 'admin_enqueue_scripts', array( $this, 'bpcharges_enqueue_scripts' ),20 );

			

		}

		public function bpcharges_enqueue_scripts() {
			

			wp_enqueue_script( 'bp_charges_admin_script', plugin_dir_url( __FILE__ ) . '../assets/js/script.js', array(), '1.0' );
		}

        
		/**
		 * display_ranks_badges
		 * Function for displaying user badges and ranks when the user slects ranks or batches from settings page
		 * @since 1.0
		 * @version 1.0.1
		 */
		 
		public function display_ranks_badges() {


						global $activities_template,$mycred,$level_image,$post;
			
			
						$all_badges = mycred_get_badge_ids();
						$profile_user_id = bp_get_activity_user_id();
						$users_badges = mycred_get_users_badges( $profile_user_id, true );
						$earned       = array();
						$has_rank = mycred_get_my_rank($this->prefs['ctype']);
						$users_rank = mycred_get_users_rank( $profile_user_id, $this->prefs['ctype'] );

						

                        // Badge Display Starts

						if($this->prefs['userbadges']== '1' && $this->prefs['badgelevelimage']== '1' && class_exists( 'myCRED_Badge' )) {
 
				
								
								 foreach ( $all_badges as $badge_id ) {

						 	 	 $badge_id     = absint( $badge_id );
                                 $has_earned        = mycred_get_badge( $badge_id );
                                 $badge       = mycred_get_badge( $badge_id );

                                  $users_badges = mycred_get_users_badges($profile_user_id );
                                  $mycred = mycred();

                                  if ( array_key_exists( $badge_id, $users_badges ) ) {
                                        $earned       = 1;
                                        $earned_level = $users_badges[ $badge_id ];
                                        $badge_image  = $badge->get_image( $earned_level );

                                        
                                    }


                                  if($has_earned && $has_earned->point_types[0] == $this->prefs['ctype']) {

                                  	
                                  	$badge_title = $has_earned->title;
	            					$badge_img = $has_earned->main_image;
	            					$level_image = $has_earned->level_image;
	            					$show_img = $has_earned->user_has_badge( $profile_user_id );


	            				
	            					if($show_img) {

	            					echo '<div class="user-badges">';

	            					echo '<span class="user-badge-heading"><b>Badge: </b> </span>'  ;

	            					echo   '<span>' . $badge_title . ' '.'</span>';
	            					echo '<span>' . $badge_image . '</span>';

	            					echo '</div>';

	            					}



                                  }

						$uservalue = mycred_get_option($option_id);
						
						}


						}
						
						 elseif($this->prefs['userbadges']== '1' && class_exists( 'myCRED_Badge' )) {


						 	 foreach ( $all_badges as $badge_id ) {

						 	 	$badge_id     = absint( $badge_id );
                                $has_earned        = mycred_get_badge( $badge_id );
                                
                                 $badge       = mycred_get_badge( $badge_id );


                                  $users_badges = mycred_get_users_badges($profile_user_id );
                                  $mycred = mycred();

                                

                                  if($has_earned && $has_earned->point_types[0] == $this->prefs['ctype']) {

                                  	
                                  	$badge_title = $has_earned->title;
	            					$badge_img = $has_earned->main_image;
	            					$show_img = $has_earned->user_has_badge( $profile_user_id );

	            					
	            					if($show_img) {

	            					echo '<div class="user-badges">';

	            					echo '<span class="user-badge-heading"><b>Badge: </b> </span>'  ;

	            					echo   '<span>' . $badge_title . '</span>';
	            					echo '<span>' . $badge_img . '</span>';

	            					echo '</div>';

	            					}



                                  }

						$uservalue = mycred_get_option($option_id);
						
						}


						}

						

						// Badge Display Ends

						// Rank Display Starts

						if($this->prefs['userranks']== '1' && class_exists( 'myCRED_Ranks_Module' )) {

			
								// Get rank object
								$rank = mycred_get_users_rank( $profile_user_id );
								
								$account_object = mycred_get_account( $profile_user_id );

								$ranks = mycred_get_ranks('publish', -1, 'DESC', $this->prefs['ctype']);

								if($users_rank) {
							

										echo '<div class="user-ranks">';

										echo '<span class="user-rank-heading"><b>Rank: </b> </span>'  ;
										echo '<span>' .$users_rank->title . '</span>';


											echo '<span>' . $users_rank->get_image( 'logo' ) . '</span>';
			
										echo '</div>';

									

							}


							}

							// Rank Display Ends

						}

		
		public function preferences() {
            
			$prefs = $this->prefs;
			$types = mycred_get_types();
?>

<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
        <div class="form-group">
            <label class="subheader"><?php _e( 'Point Type', 'bp_charge' ); ?></label>
           <?php if ( count( $types ) == 1 ) echo $this->core->plural(); ?>
           <?php echo mycred_types_select_from_dropdown( $this->field_name( 'ctype' ), $this->field_id( 'ctype' ), $prefs['ctype'], false, ' class="form-control"' ); ?>
        </div>
        <div class="form-group">
            <label for="<?php echo $this->field_id( 'userranks' ); ?>">
                <input type="checkbox" name="<?php echo $this->field_name( 'userranks' ); ?>" id="<?php echo $this->field_id( 'userranks' );  ?>"<?php checked( $prefs['userranks'], 1 ); ?> value="1" class="checkme" /> <?php _e( 'Enable Ranks', 'bp_charge' ); ?>
            </label>
        </div>
        <div class="form-group">
            <label for="<?php echo $this->field_id( 'userbadges' ); ?>">
                <input type="checkbox" name="<?php echo $this->field_name( 'userbadges' ); ?>" id="<?php echo $this->field_id( 'userbadges' ); ?>"<?php checked( $prefs['userbadges'], 1 ); ?> value="1" /> <?php _e( 'Enable Badges', 'bp_charge' ); ?>
            </label>
        </div>
        <div class="form-group">
            <div class="userselectedoption" <?php echo checked( $prefs['userbadges'], 1 ) ? "style='display: block;'" : "style='display: none;'" ?>>
                <label for="<?php echo $this->field_id( 'badgelevelimage' ); ?>">
                    <input type="checkbox" name="<?php echo $this->field_name( 'badgelevelimage' ); ?>" id="<?php echo $this->field_id( 'badgelevelimage' ); ?>"<?php checked( $prefs['badgelevelimage'], 1 ); ?> value="1" /><?php _e( 'Show Badge Level Image', 'bp_charge' ); ?>
                </label>
            </div>
        </div>
    </div>
</div>
<?php
		}

		
		/**
		 * Sanitise Preferences
		 * @since 1.1.1
		 * @version 1.0
		 */

		public function sanitise_preferences( $data ) {

			$new_data = array();

			$new_data['ctype']            = sanitize_key( $data['ctype'] );
			if ( $new_data['ctype'] == '' ) $new_data['ctype'] = MYCRED_DEFAULT_TYPE_KEY;

			
			$new_data['userranks']          = ( ( isset( $data['userranks'] ) ) ? sanitize_key( $data['userranks'] ) : '' );
			$new_data['userbadges']          = ( ( isset( $data['userbadges'] ) ) ? sanitize_key( $data['userbadges'] ) : '' );
			$new_data['badgelevelimage']          = ( ( isset( $data['badgelevelimage'] ) ) ? sanitize_key( $data['badgelevelimage'] ) : '' );

			
			if ( $new_data['ctype'] != $this->core->cred_id ) {
				$mycred = mycred( $new_data['ctype'] );
			}
			else {
				$mycred = $this->core;
			}

		
			return $data;

		}

	}
endif;

?>


 
