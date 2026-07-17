<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class myCRED_buddy_blockusers_Settings extends myCRED_Module {

	protected static $_instance = null;
	
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct( $type = MYCRED_DEFAULT_TYPE_KEY ) {

		parent::__construct( 
			'myCred_BP_BLOCKUSERS', 
			array( 'module_name' => 'general' ), 
			$type 
		); 
		
		add_action( 'admin_enqueue_scripts', array( $this, 'load_buddyboss_dependency_scripts' ), 1000 );
		add_action( 'wp_enqueue_scripts', array( $this, 'buddyboss_front_scripts' ) );
		add_action( 'mycred_bp_before_settings', array( $this, 'mycred_buddyboss_user_settings' ), 10 );
		add_filter( 'mycred_bp_sanitize_settings', array( $this, 'sanitize_extra_settings' ), 10, 3 );
		add_filter( 'mycred_bp_profile_header', array( $this, 'bp_profile_header' ) );
		add_filter( 'bp_member_members_list_item', array( $this, 'members_list_item' ) );
		add_action( 'bp_before_member_header_meta', array( $this, 'show_achievements_profile_header' ), 10 );
		add_filter( 'bp_get_activity_avatar', array( $this, 'after_activity_author_avatar' ) );   
		add_filter( 'bbp_get_reply_author_avatar', array( $this, 'after_forum_activity_author_avatar' ), 10, 2 );  
		add_action( 'bbp_theme_after_topic_author_details', array( $this, 'after_topic_author_avatar' ) );
		add_filter( 'mycred_add', array( $this, 'mycred_add' ), 1, 3 );
		  
		//Use the below commented filter if you want to show achievements in the activity header  
		// add_filter( 'bp_insert_activity_meta',       array( $this, 'after_activity_author_details' ), 999, 2 ); 
	}

	public function bp_profile_header( $output ) {
		return '';
	}


	public function members_list_item() {

		if ( empty( $this->core->bboss['avatar'] ) ) {
return;
		}

		$user_id     = bp_get_member_user_id();
		$member_type = bp_get_member_type( $user_id, true );

		if ( ! empty( $this->core->bboss['excluded_profiles'] ) && in_array( $member_type, $this->core->bboss['excluded_profiles'] ) ) {
return;
		}

		$this->dispaly_achievements_as_tags( 
			$user_id, 
			$this->core->bboss['avatar'], 
			'mycred-bboss-avatar' 
		);
	}

	public function show_achievements_profile_header() {

		if ( empty( $this->core->bboss['profile'] ) ) {
return;
		}

		$user_id     = bp_displayed_user_id();
		$member_type = bp_get_member_type( $user_id, true );

		if ( ! empty( $this->core->bboss['excluded_profiles'] ) && in_array( $member_type, $this->core->bboss['excluded_profiles'] ) ) {
return;
		}

		$this->dispaly_achievements_as_tags( 
			$user_id, 
			$this->core->bboss['profile'], 
			'mycred-bboss-profile-header' 
		);
	}

	public function after_activity_author_avatar( $avatar ) {

		if ( empty( $this->core->bboss['activity'] ) ) {
return $avatar;
		}

		global $activities_template;

		$current_activity_item = isset( $activities_template->activity->current_comment ) ? $activities_template->activity->current_comment : $activities_template->activity;

		$author_id = $current_activity_item->user_id;

		if ( bbp_is_reply_anonymous( $author_id ) ) {
return $avatar;
		}

		$member_type = bp_get_member_type( $author_id, true );

		if ( ! empty( $this->core->bboss['excluded_profiles'] ) && in_array( $member_type, $this->core->bboss['excluded_profiles'] ) ) {
return $avatar;
		}

		ob_start();

		$this->dispaly_achievements_as_tags( 
			$author_id, 
			$this->core->bboss['activity'], 
			'mycred-bboss-activity' 
		); 

		$html = ob_get_clean();

		return $avatar . $html;
	}

	public function after_forum_activity_author_avatar( $avatar, $reply_id ) {

		if ( empty( $this->core->bboss['forum'] ) ) {
return $avatar;
		}

		$author_id = bbp_get_reply_author_id( $reply_id );

		if ( bbp_is_reply_anonymous( $author_id ) ) {
return $avatar;
		}

		$member_type = bp_get_member_type( $author_id, true );

		if ( ! empty( $this->core->bboss['excluded_profiles'] ) && in_array( $member_type, $this->core->bboss['excluded_profiles'] ) ) {
return $avatar;
		}

		ob_start();

		$this->dispaly_achievements_as_tags( 
			$author_id, 
			$this->core->bboss['forum'], 
			'mycred-bboss-forum' 
		); 

		$html = ob_get_clean();

		return $avatar . $html;
	}

	public function after_topic_author_avatar() {

		if ( empty( $this->core->bboss['forum'] ) ) {
return;
		}

		$author_id = bbp_get_reply_author_id();

		$member_type = bp_get_member_type( $author_id, true );

		if ( ! empty( $this->core->bboss['excluded_profiles'] ) && in_array( $member_type, $this->core->bboss['excluded_profiles'] ) ) {
return;
		}

		$this->dispaly_achievements_as_tags( 
			$author_id,
			$this->core->bboss['forum'], 
			'mycred-bboss-forum' 
		); 
	}

	public function dispaly_achievements_as_tags( $user_id, $settings, $wrapper_class = '' ) {
		
		?>
		<div class="<?php echo esc_attr( $wrapper_class ); ?>">
			<?php if ( ! empty( $settings['types'] ) ) : ?>
			<div class="mycred-bboss-tags points">
				<?php
				foreach ( $settings['types'] as $type ) :

					$mycred = mycred( $type );

					if ( $mycred->exclude_user( $user_id ) ) {
continue;
					}

					$balance = $mycred->get_users_balance( $user_id, $type );

					?>
					<div class="mycred-bboss-tag">
						<?php if ( ! empty( $mycred->image_url ) ) : ?>
						<img src="<?php echo esc_url( $mycred->image_url ); ?>" alt="<?php echo esc_html( $mycred->plural() ); ?>" width="25" title="<?php echo esc_html( $mycred->plural() ); ?>">
						<?php endif; ?>
						<span><?php echo esc_html( $mycred->format_creds( $balance ) ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
			<?php
			if ( class_exists( 'myCRED_Badge' ) && ! empty( $settings['badges'] ) ) :

				$badges = $this->get_users_sorted_badges( $user_id, $settings['badges'] );

				if ( ! empty( $badges ) ) :
					?>
				<div class="mycred-bboss-tags badges">
					<?php
					foreach ( $badges as $badge ) :

						$users_level = $badge->get_users_current_level( $user_id );
						$badge_image = $badge->get_image_url( $users_level );

						if ( empty( $badge_image ) ) {
							$badge_image = $badge->main_image_url;
						}

						?>
						<div class="mycred-bboss-tag">
							<?php if ( ! empty( $badge_image ) ) : ?>
							<img src="<?php echo esc_url( $badge_image ); ?>" alt="<?php echo esc_html( $badge->title ); ?>" width="25" title="<?php echo esc_html( $badge->title ); ?>">
							<?php endif; ?>
							<span><?php echo esc_html( $badge->title ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
			<?php endif; ?>
			<?php if ( class_exists( 'myCRED_Rank' ) && ! empty( $settings['ranks'] ) ) : ?>
			<div class="mycred-bboss-tags ranks">
				<?php
				foreach ( $settings['ranks'] as $type ) :

					$rank = mycred_get_users_rank( $user_id, $type );

					if ( empty( $rank ) ) {
continue;
					}

					?>
					<div class="mycred-bboss-tag">
						<?php if ( ! empty( $rank->logo_url ) ) : ?>
						<img src="<?php echo esc_url( $rank->logo_url ); ?>" alt="<?php echo esc_html( $rank->title ); ?>" width="25" title="<?php echo esc_html( $rank->title ); ?>">
						<?php endif; ?>
						<span><?php echo esc_html( $rank->title ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	public function get_users_sorted_badges( $user_id, $selected_badges ) {

		$badges = array();

		if ( ! empty( $selected_badges ) ) {
			foreach ( $selected_badges as $badge_id ) {
				
				$badge = mycred_get_badge( $badge_id );

				if ( ! empty( $badge->post_id ) && $badge->user_has_badge( $user_id ) ) {

					$badges[] = $badge;

				}

			}
		}

		return $badges; 
	}

	public function buddyboss_front_scripts() {

		wp_enqueue_style( 
			'mycred_buddyboss_front_style', 
			plugin_dir_url( __DIR__ ) . 'assets/css/mycred-buddyboss-front.css', 
			'', 
			'1.0' 
		);
	}

	public function sanitize_extra_settings( $new_data, $data, $core ) {

		$new_data['bboss']['excluded_profiles'] = mycred_sanitize_array( $data['bboss']['excluded_profiles'] );
		$new_data['bboss']['profile']           = mycred_sanitize_array( $data['bboss']['profile'] );
		$new_data['bboss']['forum']             = mycred_sanitize_array( $data['bboss']['forum'] );
		$new_data['bboss']['activity']          = mycred_sanitize_array( $data['bboss']['activity'] );
		$new_data['bboss']['avatar']            = mycred_sanitize_array( $data['bboss']['avatar'] );

		$new_data['buddypress']['balance_location'] = '';

		return $new_data;
	}


	public function load_buddyboss_dependency_scripts() {

		wp_enqueue_script( 
			'mycred-bp-blockusers-script', 
			plugin_dir_url( __DIR__ ) . 'assets/js/block-users.js', 
			array( 'jquery', 'jquery-ui-sortable' ), 
			1.0, 
			true 
		);      
		
		wp_enqueue_script( 
			'mycred-selectize-script', 
			plugin_dir_url( __DIR__ ) . 'assets/js/selectize.min.js', 
			array( 'jquery' ), 
			1.0, 
			true 
		);
		
		wp_enqueue_style( 
			'mycred_buddyboss_admin_style', 
			plugin_dir_url( __DIR__ ) . 'assets/css/mycred-buddyboss-admin.css', 
			'', 
			1.0 
		);

		wp_enqueue_style( 
			'mycred_buddyboss_selectize_style', 
			plugin_dir_url( __DIR__ ) . 'assets/css/selectize.default.css', 
			'', 
			1.0 
		);
	}
	
	public function mycred_buddyboss_user_settings( $object ) {

		if ( ! function_exists( 'buddypress' ) || empty( buddypress()->buddyboss ) ) {
			return;
		}

		$profile_types = bp_get_member_types( array(), 'objects' );
		$point_types   = mycred_get_types( true );
		$badges        = $this->get_all_badges();

		?>

		<div class="mycred-buddyboss-settings">
			<?php if ( ! empty( $profile_types ) ) : ?>
			<div class="row">
				<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
					<h3><?php esc_html_e( 'Block Profiles', 'mycred-toolkit' ); ?></h3>
					<div class="form-group">
						<label for="<?php echo esc_attr( $this->field_id( array( 'bboss' => 'excluded_profiles' ) ) ); ?>"><?php esc_html_e( 'Blocked Profile Types', 'mycred-toolkit' ); ?></label>
						<?php 

						$profile_types_options = array();

						foreach ( $profile_types as $profile_type => $profile_type_obj ) {
							$profile_types_options[ $profile_type ] = $profile_type_obj->labels['singular_name'];
						}

						$bb_excluded_profiles = array();

						if ( ! empty( $object->core->core['bboss']['excluded_profiles'] ) ) {
							$bb_excluded_profiles = $object->core->core['bboss']['excluded_profiles'];
						}

						mycred_create_select_field( 
							$profile_types_options,
							$bb_excluded_profiles,
							array(
								'name'     => $this->field_name( array( 'bboss' => 'excluded_profiles' ) ) . '[]',
								'id'       => $this->field_id( array( 'bboss' => 'excluded_profiles' ) ),
								'class'    => 'mycred-select2 mbb-excluded-profiles',
								'multiple' => 'multiple'
							)
						);
						?>
					</div>
				</div>
			</div>
			<hr class="mb-4">
			<?php endif; ?>
			<div class="row">
				<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
					<h3><?php esc_html_e( 'Show in Members Listing', 'mycred-toolkit' ); ?></h3>
					<div class="form-group">
						 <label for="<?php echo esc_attr( $this->field_id( array( 'bboss', 'avatar', 'types' ) ) ); ?>"><?php esc_html_e( 'Point Types', 'mycred-toolkit' ); ?></label>
						<?php 

						$avatar_ctypes = array();

						if ( ! empty( $object->core->core['bboss']['avatar']['types'] ) ) {
							$avatar_ctypes = $object->core->core['bboss']['avatar']['types'];
						}

						mycred_create_select_field( 
							array_replace( array_flip( $avatar_ctypes ), $point_types ), //Reorder array
							$avatar_ctypes, 
							array(
								'id'       => $this->field_id( array( 'bboss', 'avatar', 'types' ) ),
								'name'     => $this->field_name( array( 'bboss', 'avatar', 'types' ) ) . '[]',
								'multiple' => 'multiple',
								'class'    => 'mycred-selectize'
							) 
						);

						?>
					 </div>
					<?php if ( class_exists('myCRED_Badge') ) : ?>
					 <div class="form-group">
						 <label for="<?php echo esc_attr( $this->field_id( array( 'bboss', 'avatar', 'badges' ) ) ); ?>"><?php esc_html_e( 'Badges', 'mycred-toolkit' ); ?></label>
						<?php 

						$profile_badges = array();

						if ( ! empty( $object->core->core['bboss']['avatar']['badges'] ) ) {
							$profile_badges = $object->core->core['bboss']['avatar']['badges'];
						}

						mycred_create_select_field( 
							array_replace( array_flip( $profile_badges ), $badges ), //Reorder array
							$profile_badges, 
							array(
								'id'       => $this->field_id( array( 'bboss', 'avatar', 'badges' ) ),
								'name'     => $this->field_name( array( 'bboss', 'avatar', 'badges' ) ) . '[]',
								'multiple' => 'multiple',
								'class'    => 'mycred-selectize'
							) 
						);

						?>
					</div>
					<?php endif; ?>
					<?php if ( class_exists('myCRED_Rank') ) : ?>
					<div class="form-group">
						 <label for="<?php echo esc_attr( $this->field_id( array( 'bboss', 'avatar', 'ranks' ) ) ); ?>"><?php esc_html_e( 'Ranks', 'mycred-toolkit' ); ?></label>
						<?php 

						$profile_ranks = array();

						if ( ! empty( $object->core->core['bboss']['avatar']['ranks'] ) ) {
							$profile_ranks = $object->core->core['bboss']['avatar']['ranks'];
						}

						mycred_create_select_field( 
							array_replace( array_flip( $profile_ranks ), $point_types ), //Reorder array
							$profile_ranks, 
							array(
								'id'       => $this->field_id( array( 'bboss', 'avatar', 'ranks' ) ),
								'name'     => $this->field_name( array( 'bboss', 'avatar', 'ranks' ) ) . '[]',
								'multiple' => 'multiple',
								'class'    => 'mycred-selectize'
							) 
						);

						?>
					 </div>
					<?php endif; ?>
				</div>
				<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
					<h3><?php esc_html_e( 'Show in Profile Header', 'mycred-toolkit' ); ?></h3>
					<div class="form-group">
						 <label for="<?php echo esc_attr( $this->field_id( array( 'bboss', 'profile', 'types' ) ) ); ?>"><?php esc_html_e( 'Point Types', 'mycred-toolkit' ); ?></label>
						<?php 

						$profile_ctypes = array();

						if ( ! empty( $object->core->core['bboss']['profile']['types'] ) ) {
							$profile_ctypes = $object->core->core['bboss']['profile']['types'];
						}

						mycred_create_select_field( 
							array_replace( array_flip( $profile_ctypes ), $point_types ), //Reorder array
							$profile_ctypes, 
							array(
								'id'       => $this->field_id( array( 'bboss', 'profile', 'types' ) ),
								'name'     => $this->field_name( array( 'bboss', 'profile', 'types' ) ) . '[]',
								'multiple' => 'multiple',
								'class'    => 'mycred-selectize'
							) 
						);

						?>
					 </div>
					<?php if ( class_exists('myCRED_Badge') ) : ?>
					 <div class="form-group">
						 <label for="<?php echo esc_attr( $this->field_id( array( 'bboss', 'profile', 'badges' ) ) ); ?>"><?php esc_html_e( 'Badges', 'mycred-toolkit' ); ?></label>
						<?php 

						$profile_badges = array();

						if ( ! empty( $object->core->core['bboss']['profile']['badges'] ) ) {
							$profile_badges = $object->core->core['bboss']['profile']['badges'];
						}

						mycred_create_select_field( 
							array_replace( array_flip( $profile_badges ), $badges ), //Reorder array
							$profile_badges, 
							array(
								'id'       => $this->field_id( array( 'bboss', 'profile', 'badges' ) ),
								'name'     => $this->field_name( array( 'bboss', 'profile', 'badges' ) ) . '[]',
								'multiple' => 'multiple',
								'class'    => 'mycred-selectize'
							) 
						);

						?>
					</div>
					<?php endif; ?>
					<?php if ( class_exists('myCRED_Rank') ) : ?>
					<div class="form-group">
						 <label for="<?php echo esc_attr( $this->field_id( array( 'bboss', 'profile', 'ranks' ) ) ); ?>"><?php esc_html_e( 'Ranks', 'mycred-toolkit' ); ?></label>
						<?php 

						$profile_ranks = array();

						if ( ! empty( $object->core->core['bboss']['profile']['ranks'] ) ) {
							$profile_ranks = $object->core->core['bboss']['profile']['ranks'];
						}

						mycred_create_select_field( 
							array_replace( array_flip( $profile_ranks ), $point_types ), //Reorder array
							$profile_ranks, 
							array(
								'id'       => $this->field_id( array( 'bboss', 'profile', 'ranks' ) ),
								'name'     => $this->field_name( array( 'bboss', 'profile', 'ranks' ) ) . '[]',
								'multiple' => 'multiple',
								'class'    => 'mycred-selectize'
							) 
						);

						?>
					 </div>
					<?php endif; ?>
				</div>
				<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
					<h3><?php esc_html_e( 'Show in Activity', 'mycred-toolkit' ); ?></h3>
					<div class="form-group">
						 <label for="<?php echo esc_attr( $this->field_id( array( 'bboss', 'activity', 'types' ) ) ); ?>"><?php esc_html_e( 'Point Types', 'mycred-toolkit' ); ?></label>
						<?php 

						$activity_ctypes = array();

						if ( ! empty( $object->core->core['bboss']['activity']['types'] ) ) {
							$activity_ctypes = $object->core->core['bboss']['activity']['types'];
						}

						mycred_create_select_field( 
							array_replace( array_flip( $activity_ctypes ), $point_types ), //Reorder array
							$activity_ctypes, 
							array(
								'id'       => $this->field_id( array( 'bboss', 'activity', 'types' ) ),
								'name'     => $this->field_name( array( 'bboss', 'activity', 'types' ) ) . '[]',
								'multiple' => 'multiple',
								'class'    => 'mycred-selectize'
							) 
						);

						?>
					 </div>
					<?php if ( class_exists('myCRED_Badge') ) : ?>
					 <div class="form-group">
						 <label for="<?php echo esc_attr( $this->field_id( array( 'bboss', 'activity', 'badges' ) ) ); ?>"><?php esc_html_e( 'Badges', 'mycred-toolkit' ); ?></label>
						<?php 

						$profile_badges = array();

						if ( ! empty( $object->core->core['bboss']['activity']['badges'] ) ) {
							$profile_badges = $object->core->core['bboss']['activity']['badges'];
						}

						mycred_create_select_field( 
							array_replace( array_flip( $profile_badges ), $badges ), //Reorder array
							$profile_badges, 
							array(
								'id'       => $this->field_id( array( 'bboss', 'activity', 'badges' ) ),
								'name'     => $this->field_name( array( 'bboss', 'activity', 'badges' ) ) . '[]',
								'multiple' => 'multiple',
								'class'    => 'mycred-selectize'
							) 
						);

						?>
					</div>
					<?php endif; ?>
					<?php if ( class_exists('myCRED_Rank') ) : ?>
					<div class="form-group">
						 <label for="<?php echo esc_attr( $this->field_id( array( 'bboss', 'activity', 'ranks' ) ) ); ?>"><?php esc_html_e( 'Ranks', 'mycred-toolkit' ); ?></label>
						<?php 

						$profile_ranks = array();

						if ( ! empty( $object->core->core['bboss']['activity']['ranks'] ) ) {
							$profile_ranks = $object->core->core['bboss']['activity']['ranks'];
						}

						mycred_create_select_field( 
							array_replace( array_flip( $profile_ranks ), $point_types ), //Reorder array
							$profile_ranks, 
							array(
								'id'       => $this->field_id( array( 'bboss', 'activity', 'ranks' ) ),
								'name'     => $this->field_name( array( 'bboss', 'activity', 'ranks' ) ) . '[]',
								'multiple' => 'multiple',
								'class'    => 'mycred-selectize'
							) 
						);

						?>
					 </div>
					<?php endif; ?>
				</div>
				<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
					<h3><?php esc_html_e( 'Show in Forums', 'mycred-toolkit' ); ?></h3>
					<div class="form-group">
						 <label for="<?php echo esc_attr( $this->field_id( array( 'bboss', 'forum', 'types' ) ) ); ?>"><?php esc_html_e( 'Point Types', 'mycred-toolkit' ); ?></label>
						<?php 

						$forum_ctypes = array();

						if ( ! empty( $object->core->core['bboss']['forum']['types'] ) ) {
							$forum_ctypes = $object->core->core['bboss']['forum']['types'];
						}

						mycred_create_select_field( 
							array_replace( array_flip( $forum_ctypes ), $point_types ), //Reorder array
							$forum_ctypes, 
							array(
								'id'       => $this->field_id( array( 'bboss', 'forum', 'types' ) ),
								'name'     => $this->field_name( array( 'bboss', 'forum', 'types' ) ) . '[]',
								'multiple' => 'multiple',
								'class'    => 'mycred-selectize'
							) 
						);

						?>
					 </div>
					<?php if ( class_exists('myCRED_Badge') ) : ?>
					 <div class="form-group">
						 <label for="<?php echo esc_attr( $this->field_id( array( 'bboss', 'forum', 'badges' ) ) ); ?>"><?php esc_html_e( 'Badges', 'mycred-toolkit' ); ?></label>
						<?php 

						$profile_badges = array();

						if ( ! empty( $object->core->core['bboss']['forum']['badges'] ) ) {
							$profile_badges = $object->core->core['bboss']['forum']['badges'];
						}

						mycred_create_select_field( 
							array_replace( array_flip( $profile_badges ), $badges ), //Reorder array
							$profile_badges, 
							array(
								'id'       => $this->field_id( array( 'bboss', 'forum', 'badges' ) ),
								'name'     => $this->field_name( array( 'bboss', 'forum', 'badges' ) ) . '[]',
								'multiple' => 'multiple',
								'class'    => 'mycred-selectize'
							) 
						);

						?>
					</div>
					<?php endif; ?>
					<?php if ( class_exists('myCRED_Rank') ) : ?>
					<div class="form-group">
						 <label for="<?php echo esc_attr( $this->field_id( array( 'bboss', 'forum', 'ranks' ) ) ); ?>"><?php esc_html_e( 'Ranks', 'mycred-toolkit' ); ?></label>
						<?php 

						$profile_ranks = array();

						if ( ! empty( $object->core->core['bboss']['forum']['ranks'] ) ) {
							$profile_ranks = $object->core->core['bboss']['forum']['ranks'];
						}

						mycred_create_select_field( 
							array_replace( array_flip( $profile_ranks ), $point_types ), //Reorder array
							$profile_ranks, 
							array(
								'id'       => $this->field_id( array( 'bboss', 'forum', 'ranks' ) ),
								'name'     => $this->field_name( array( 'bboss', 'forum', 'ranks' ) ) . '[]',
								'multiple' => 'multiple',
								'class'    => 'mycred-selectize'
							) 
						);

						?>
					 </div>
					<?php endif; ?>
				</div>
			</div>
			<hr class="mb-4">
			<h3><?php esc_html_e( 'Points History', 'mycred-toolkit' ); ?></h3>
		</div>
		<?php
	}

	public function get_all_badges() {

		$badges = array();

		if ( class_exists('myCRED_Badge') ) {
			
			$args = array(
				'post_type'   => MYCRED_BADGE_KEY,
				'post_status' => 'publish',
				'numberposts' => -1
			);

			$posts = get_posts( $args );

			foreach ( $posts as $post ) {
				$badges[ $post->ID ] = $post->post_title;
			}

		}
		
		return $badges;
	}

	public function mycred_add( $reply, $request, $mycred ) {
			
		if ( $reply ) {
			
			$member_type = bp_get_member_type( $request['user_id'], true );

			if ( ! empty( $this->core->bboss['excluded_profiles'] ) && in_array( $member_type, $this->core->bboss['excluded_profiles'] ) ) {
return false;
			}

		}

		return $reply;
	}
}

function myCRED_buddy_blockusers_Settings() {
	return myCRED_buddy_blockusers_Settings::instance();
}
myCRED_buddy_blockusers_Settings();