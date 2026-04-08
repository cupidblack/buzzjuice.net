<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'myCRED_Toolkit_buddyboss_Core' ) ) :
	final class myCRED_Toolkit_buddyboss_Core {

		
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

			if ( is_plugin_active('mycred/mycred.php') ) {

				$this->includes();
				add_action( 'wp_enqueue_scripts', array( $this, 'load_assets' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_assets' ) );
				add_filter( 'mycred_setup_hooks', array( $this, 'register_hooks' ), 10, 2 );
				add_action( 'mycred_load_hooks', array( $this, 'load_hooks' ) );
				add_filter( 'mycred_all_references', array( $this, 'register_refrences' ) );
				add_filter('mycred_bp_change_text', array( $this, 'mycred_bp_change_text' ), 1000, 1);
				
			} 
		}

		public function mycred_bp_change_text( $title ) {

			if ( ! empty( buddypress()->buddyboss ) ) {
				$title = 'BuddyBoss';
			} 
			
			return $title;
		}


		/**
		 * Define Constants
		 * @since 1.1.1
		 * @version 1.0
		 */
		private function define_constants() {

			$this->define( 'MYCRED_buddyboss', __FILE__ );
			$this->define( 'MYCRED_buddyboss_ROOT_DIR', plugin_dir_path( MYCRED_buddyboss ) );
			$this->define( 'MYCRED_buddyboss_ASSETS_DIR_URL', plugin_dir_url( MYCRED_buddyboss ) . 'assets/' );
			$this->define( 'MYCRED_buddyboss_INCLUDES_DIR', MYCRED_buddyboss_ROOT_DIR . 'includes/' );
		}

		/**
		 * Include Plugin Files
		 * @since 1.1.1
		 * @version 1.0
		 */
		public function includes() {

			$this->file( MYCRED_buddyboss_INCLUDES_DIR . 'mycred-buddyboss-functions.php' );
		}

		/**
		 * Include Hook Files
		 * @since 1.1.1
		 * @version 1.0
		 */
		public function load_hooks() {

			$this->file( MYCRED_buddyboss_INCLUDES_DIR . 'mycred-buddyboss-events-hook.php' );
			$this->file( MYCRED_buddyboss_INCLUDES_DIR . 'mycred-buddyboss-profile-hook.php' );
			$this->file( MYCRED_buddyboss_INCLUDES_DIR . 'mycred-buddyboss-friendship-hook.php' );
			$this->file( MYCRED_buddyboss_INCLUDES_DIR . 'mycred-buddyboss-group-hook.php' );
			$this->file( MYCRED_buddyboss_INCLUDES_DIR . 'mycred-buddyboss-message-hook.php' );
			$this->file( MYCRED_buddyboss_INCLUDES_DIR . 'mycred-buddyboss-forum-hook.php' );
			$this->file( MYCRED_buddyboss_INCLUDES_DIR . 'mycred-buddyboss-activity-stream-hook.php' );
			$this->file( MYCRED_buddyboss_INCLUDES_DIR . 'mycred-buddyboss-email-invites-hook.php' );
			$this->file( MYCRED_buddyboss_INCLUDES_DIR . 'mycred-buddyboss-blockusers.php' );
		}

		public function load_assets() {}

		public function load_admin_assets( $hook ) {    
			
				wp_enqueue_script( 
					'mycred_buddyboss_admin_script', 
					MYCRED_buddyboss_ASSETS_DIR_URL . 'js/script.js', 
					array( 'jquery' ), 
					'1.0' 
				);
				wp_enqueue_style( 
					'mycred_buddyboss_admin_style', 
					MYCRED_buddyboss_ASSETS_DIR_URL . 'css/style.css', 
					array(), 
					'1.0' 
				);
		}


		public function register_hooks( $installed ) {

			$installed['completing_buddyboss_follow_events'] = array(
				'title'       => __('BuddyBoss: Follow Events', 'mycred-toolkit'),
				'description' => __('Adds a myCRED hook for tracking points scored in buddyboss follow events.', 'mycred-toolkit'),
				'documentation' => 'https://codex.mycred.me/chapter-iii/freebies/mycred-buddyboss-integration/follow-events/',
				'callback'    => array( 'myCRED_buddyboss_Follow_Events_Hook' )
			);
			$installed['completing_buddyboss_activity_stream_events'] = array(
				'title'       => __('BuddyBoss: Activity Stream Events', 'mycred-toolkit'),
				'description' => __('Adds a myCRED hook for tracking points scored in buddyboss follow events.', 'mycred-toolkit'),
				'documentation' => 'https://codex.mycred.me/chapter-iii/freebies/mycred-buddyboss-integration/',
				'callback'    => array( 'myCRED_buddyboss_Activity_Stream_Events_Hook' )
			);
			$installed['completing_buddyboss_email_invites_events'] = array(
				'title'       => __('BuddyBoss: Email Invite Events', 'mycred-toolkit'),
				'description' => __('Adds a myCRED hook for tracking points scored in buddyboss follow events.', 'mycred-toolkit'),
				'documentation' => 'https://codex.mycred.me/chapter-iii/freebies/mycred-buddyboss-integration/',
				'callback'    => array( 'myCRED_buddyboss_Email_Invites_Events_Hook' )
			);
			$installed['completing_buddyboss_profile_events'] = array(
				'title'       => __('BuddyBoss: Profile Events', 'mycred-toolkit'),
				'description' => __('Adds a myCRED hook for tracking points scored in buddyboss profile events.', 'mycred-toolkit'),
				'documentation' => 'https://codex.mycred.me/chapter-iii/freebies/mycred-buddyboss-integration/profile-events/',
				'callback'    => array( 'myCRED_buddyboss_Profile_Events_Hook' )
			);
			$installed['completing_buddyboss_friendship_events'] = array(
				'title'       => __('BuddyBoss: Friendship Events', 'mycred-toolkit'),
				'description' => __('Adds a myCRED hook for tracking points scored in buddyboss friendship events.', 'mycred-toolkit'),
				'documentation' => 'https://codex.mycred.me/chapter-iii/freebies/mycred-buddyboss-integration/friendship-events/',
				'callback'    => array( 'myCRED_buddyboss_Friendship_Events_Hook' )
			);
			$installed['completing_buddyboss_group_events'] = array(
				'title'       => __('BuddyBoss: Group Events', 'mycred-toolkit'),
				'description' => __('Adds a myCRED hook for tracking points scored in buddyboss group events.', 'mycred-toolkit'),
				'documentation' => 'https://codex.mycred.me/chapter-iii/freebies/mycred-buddyboss-integration/group-events/',
				'callback'    => array( 'myCRED_buddyboss_Group_Events_Hook' )
			);
			$installed['completing_buddyboss_message_events'] = array(
				'title'       => __('BuddyBoss: Message Events', 'mycred-toolkit'),
				'description' => __('Adds a myCRED hook for tracking points scored in message events.', 'mycred-toolkit'),
				'documentation' => 'https://codex.mycred.me/chapter-iii/freebies/mycred-buddyboss-integration/message-events/',
				'callback'    => array( 'myCRED_buddyboss_Message_Events_Hook' )
			);
			$installed['completing_buddyboss_forum_events'] = array(
				'title'       => __('BuddyBoss: Forum Events', 'mycred-toolkit'),
				'description' => __('Adds a myCRED hook for tracking points scored in forum events.', 'mycred-toolkit'),
				'documentation' => 'https://codex.mycred.me/chapter-iii/freebies/mycred-buddyboss-integration/forum-events/',
				'callback'    => array( 'myCRED_buddyboss_Forum_Events_Hook' )
			);


			return $installed;
		}

		public function register_refrences( $list ) {

			$list['completing_buddyboss_content_general']  = __('Completing buddyboss Content General', 'mycred-toolkit');
			$list['completing_buddyboss_content_specific'] = __('Completing buddyboss Content Specific', 'mycred-toolkit');
			$list['new_group_forum_topic'] = __('New group Forum Topic', 'mycred-toolkit');
			$list['fave_specific_topic_activity'] = __('Forum Favorite Specific Topic Activity', 'mycred-toolkit');
			$list['fave_topic_specific_forum_activity'] = __('Favorite Topic Specific Forum Activity', 'mycred-toolkit');
			$list['new_forum'] = __('New Forum', 'mycred-toolkit');
			$list['specific_forum'] = __('Specific Forum', 'mycred-toolkit');
			$list['fave_activity'] = __('Forum Favorite Activity', 'mycred-toolkit');
			$list['author_fave_activity'] = __('Forum Author Favorite Activity', 'mycred-toolkit');
			$list['delete_activity'] = __('Forum Delete Activity', 'mycred-toolkit');
			$list['reply_activity'] = __('Forum Reply Activity', 'mycred-toolkit');
			$list['reply_specific_topic'] = __('Forum Reply Specific Topic', 'mycred-toolkit');
			$list['forum_delete_activity'] = __('Forum Delete Activity', 'mycred-toolkit');
			$list['delete_reply_activity'] = __('Forum Delete Reply Activity', 'mycred-toolkit');
			$list['add_follow'] = __('Follow Events', 'mycred-toolkit');
			$list['new_follower'] = __('Events New Follower', 'mycred-toolkit');
			$list['get_followers'] = __('Events Get Followers', 'mycred-toolkit');
			$list['stop_follow'] = __('Stop Following Events', 'mycred-toolkit');
			$list['lose_follower'] = __('User loses Follower on Events', 'mycred-toolkit');
			$list['follow_update'] = __('Events Follow Update', 'mycred-toolkit');
			$list['deleted_profile_update'] = __('Deleted Profile Update on Event', 'mycred-toolkit');
			$list['upload_avatar'] = __('Upload Avatar on Event', 'mycred-toolkit');
			$list['upload_cover'] = __('Upload Cover Image on Event', 'mycred-toolkit');
			$list['new_friendship'] = __('New Friendship', 'mycred-toolkit');
			$list['remove_friendship'] = __('Remove Friendship', 'mycred-toolkit');
			$list['request_friendship'] = __('Request Friendship', 'mycred-toolkit');
			$list['ended_friendship'] = __('When user ends friendship', 'mycred-toolkit');
			$list['new_comment'] = __('New Comment', 'mycred-toolkit');
			$list['comment_deletion'] = __('Delete Comment', 'mycred-toolkit');
			$list['unfave_activity'] = __('Remove Favorite', 'mycred-toolkit');
			$list['new_message'] = __('When a user sends new message', 'mycred-toolkit');
			$list['sending_gift'] = __('Sending Gift', 'mycred-toolkit');
			$list['creation_of_new_group'] = __('Create New Group', 'mycred-toolkit');
			$list['promotions_activity'] = __('Group Promotions', 'mycred-toolkit');
			$list['joining_group'] = __('Join Group', 'mycred-toolkit');
			$list['accepted_private_group'] = __('User Gets Accepted on Private Group', 'mycred-toolkit');
			$list['leaving_group'] = __('Leave Group', 'mycred-toolkit');
			$list['user_group_invitation'] = __('User Group Invitation', 'mycred-toolkit');
			$list['publish_activity_feed_message'] = __('Publish Group Activity Stream Message', 'mycred-toolkit');
			$list['delete_activity_feed_message'] = __('Remove Group Activity Stream Message', 'mycred-toolkit');
			$list['update_avatar'] = __('Update Avatar Image', 'mycred-toolkit');
			$list['account_activation_activity'] = __('Account Activation', 'mycred-toolkit');
			$list['specific_profile_type_activity'] = __('User Assigned Specific Profile Type', 'mycred-toolkit');  
			$list['update_cover'] = __('Update profile Cover', 'mycred-toolkit');
			$list['user_profile_update'] = __('Update Profile', 'mycred-toolkit');
			$list['minimum_percent_profile'] = __('When a User Completes Minimum Percent Profile', 'mycred-toolkit');
			$list['deleted_profile_update'] = __('When a User removes profile update', 'mycred-toolkit');
			$list['user_publish_activity_post'] = __('When a user publishes an activity post', 'mycred-toolkit');
			$list['user_remove_activity_post'] = __('When a user removes an activity post', 'mycred-toolkit');
			$list['user_reply_activity_post'] = __('When a user replies to an activity post', 'mycred-toolkit');
			$list['user_like_activity_post'] = __('When a user likes an activity post', 'mycred-toolkit');
			$list['user_unlike_activity_stream_item'] = __('When a user unlikes an activity stream item', 'mycred-toolkit');
			$list['user_get_unlike_activity_stream_item'] = __('When a user get an unlike on an activity stream item', 'mycred-toolkit');
			$list['user_get_like_activity_post'] = __('When a user gets a like on an activity post', 'mycred-toolkit');
			$list['user_send_email_invite'] = __('When a user sends an email invitation', 'mycred-toolkit');
			$list['user_register_from_email_invite'] = __('When an invited user from email invitation gets registered', 'mycred-toolkit');
			$list['user_email_invitation_account_activated'] = __('When an account from email invitation gets activated', 'mycred-toolkit');
			$list['user_email_invited_user_account_activated'] = __('When an invited user account gets activated', 'mycred-toolkit');
			$list['user_get_email_inviter_registered'] = __('When a user registers from email invitation', 'mycred-toolkit');

			return $list;
		}
	}

endif;
function myCRED_Toolkit_buddyboss_Core() {
	return myCRED_Toolkit_buddyboss_Core::instance();
}
myCRED_Toolkit_buddyboss_Core();
