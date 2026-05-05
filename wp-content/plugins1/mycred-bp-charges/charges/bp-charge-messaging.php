<?php
if ( ! defined( 'BP_CHARGES_VERSION' ) ) exit;

/**
 * Charge for Messaging
 * @since 1.0
 * @version 1.1
 */
if ( ! class_exists( 'myCRED_BP_Charge_Messaging' ) ) :
	class myCRED_BP_Charge_Messaging extends myCRED_BP_Charge {

		/**
		 * Construct
		 */
		function __construct( $charge_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			global $mycred_bp_charge;

			

			parent::__construct( array(
				'id'       => 'messaging',
				'defaults' => array(
					'ctype'             => MYCRED_DEFAULT_TYPE_KEY,
					'access'            => '',
					'buttons'           => '',
					'new_message'       => 0,
					'new_message_log'   => '',
					'messages_warning'  => __( 'Not enough %plural% to send a new message.', 'bp_charge' ),
					'show_message_cost' => 'New Message: %cred_f%',
					'new_reply'         => 0,
					'new_reply_log'     => '',
					'reply_warning'     => __( 'Not enough %plural% to send a reply.', 'bp_charge' ),
					'show_reply_cost'   => 'New Reply: %cred_f%',
					'redirect_to'       => 0,
					'template'          => 'wp-content/plugins/buddypress-charges/templates/compose.php'
				)
			), $charge_prefs, $type );

		}

		/**
		 * Run
		 * @since 1.0
		 * @version 1.0.1
		 */
		public function run() {

			if ( ! bp_is_active( 'messages' ) ) return;

			if ( isset( $this->prefs['ctype'] ) && $this->prefs['ctype'] != '' )
				$this->core = mycred( $this->prefs['ctype'] );

			add_action( 'mycred_init',           array( $this, 'module_init' ) );

			add_action( 'bp_actions',            array( $this, 'bp_create_new_message' ), 9 );

			add_action( 'bp_actions',            array( $this, 'bp_messages_action_conversation' ), 9 );

			add_action( 'admin_init', array( $this, 'remove_hook' ), 13);

			add_action( 'check_ajax_referer',    array( $this, 'new_reply_via_ajax' ), 1 );

			add_filter( 'mycred_all_references', array( $this, 'add_badge_support' ), 81 );

			add_action( 'mycred_overview_after', array( $this, 'overview' ), 30 );

		}

		function remove_hook() {

			remove_action( 'wp_ajax_messages_send_message', 'bp_nouveau_ajax_messages_send_message');
			remove_action( 'wp_ajax_nopriv_messages_send_message', 'bp_nouveau_ajax_messages_send_message');

			add_action( 'wp_ajax_messages_send_message', array( $this,'bp_ajax_message_charges'),12 );
			add_action( 'wp_ajax_nopriv_messages_send_message', array( $this,'bp_ajax_message_charges'),12 );

			remove_action( 'wp_ajax_messages_send_reply', 'bp_nouveau_ajax_messages_send_reply');
			remove_action( 'wp_ajax_nopriv_messages_send_reply', 'bp_nouveau_ajax_messages_send_reply');

			add_action( 'wp_ajax_messages_send_reply', array( $this,'bp_ajax_messages_send_reply'),12 );
			add_action( 'wp_ajax_nopriv_messages_send_reply', array( $this,'bp_ajax_messages_send_reply'),12 );
		}

		/**function reply will start here */
		function bp_ajax_messages_send_reply( $action ) { 

			$response = array(
				'feedback' => __( 'There was a problem sending your reply. Please try again.', 'buddyboss' ),
				'type'     => 'error',
			);
		
			// Verify nonce
			if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'messages_send_message' ) ) {
				wp_send_json_error( $response );
			}
		
			if ( empty( $_POST['content'] ) || empty( $_POST['thread_id'] ) ) {
				$response['feedback'] = __( 'Please add some content to your message.', 'buddyboss' );
		
				wp_send_json_error( $response );
			}
		
			$thread_id = (int) $_POST['thread_id'];

			if ( 'stop' === $this->charge_new_reply( (int) $thread_id ) ) {
				$response['feedback'] = __( 'You donot have sufficient balance to reply.', 'buddyboss' );
				wp_send_json_error( $response );
				wp_die();
			}
		
			if ( ! bp_current_user_can( 'bp_moderate' ) && ( ! messages_is_valid_thread( $thread_id ) || ! messages_check_thread_access( $thread_id ) ) ) {
				wp_send_json_error( $response );
			}
		
			$new_reply = messages_new_message( array(
				'thread_id'  => $thread_id,
				'subject'    => ! empty( $_POST['subject'] ) ? $_POST['subject'] : false,
				'content'    => $_POST['content'],
				'date_sent'  => $date_sent = bp_core_current_time(),
				'error_type' => 'wp_error',
			) );
		
			if ( is_wp_error( $new_reply ) ) {
				$response['feedback'] = $new_reply->get_error_message();
				wp_send_json_error( $response );
			}
		
			// Send the reply.
			if ( empty( $new_reply ) ) {
				wp_send_json_error( $response );
			}
		
			// Get the message by pretending we're in the message loop.
			global $thread_template, $media_template;
		
			$bp           = buddypress();
			$reset_action = $bp->current_action;
		
			// Override bp_current_action().
			$bp->current_action = 'view';
		
			bp_thread_has_messages( array( 'thread_id' => $thread_id, 'before' => $date_sent ) );
		
			// Set current message to current key.
			$thread_template->current_message = - 1;
		
			// Now manually iterate message like we're in the loop.
			bp_thread_the_message();
		
			// Manually call oEmbed
			// this is needed because we're not at the beginning of the loop.
			bp_messages_embed();
		
			// Output single message template part.
			$reply = array(
				'id'            => bp_get_the_thread_message_id(),
				'content'       => do_shortcode( bp_get_the_thread_message_content() ),
				'sender_id'     => bp_get_the_thread_message_sender_id(),
				'sender_name'   => esc_html( bp_get_the_thread_message_sender_name() ),
				'is_deleted'    => empty( get_userdata( bp_get_the_thread_message_sender_id() ) ) ? 1 : 0,
				'sender_link'   => bp_get_the_thread_message_sender_link(),
				'sender_is_you' => bp_get_the_thread_message_sender_id() === bp_loggedin_user_id(),
				'sender_avatar' => esc_url( bp_core_fetch_avatar( array(
					'item_id' => bp_get_the_thread_message_sender_id(),
					'object'  => 'user',
					'type'    => 'thumb',
					'width'   => 32,
					'height'  => 32,
					'html'    => false,
				) ) ),
				'date'          => bp_get_the_thread_message_date_sent() * 1000,
				'display_date'  => bp_get_the_thread_message_time_since(),
			);
		
			if ( bp_is_active( 'messages', 'star' ) ) {
		
				$star_link = bp_get_the_message_star_action_link( array(
					'message_id' => bp_get_the_thread_message_id(),
					'url_only'   => true,
				) );
		
				$reply['star_link']  = $star_link;
				$reply['is_starred'] = array_search( 'unstar', explode( '/', $star_link ) );
		
			}
		
			if ( bp_is_active( 'media' ) && bp_is_messages_media_support_enabled() ) {
				$media_ids = bp_messages_get_meta( bp_get_the_thread_message_id(), 'bp_media_ids', true );
		
				if ( ! empty( $media_ids ) && bp_has_media( array(
						'include'  => $media_ids,
						'order_by' => 'menu_order',
						'sort'     => 'ASC',
					) ) ) {
					$reply['media'] = array();
					while ( bp_media() ) {
						bp_the_media();
		
						$reply['media'][] = array(
							'id'        => bp_get_media_id(),
							'title'     => bp_get_media_title(),
							'thumbnail' => bp_get_media_attachment_image_thumbnail(),
							'full'      => bp_get_media_attachment_image(),
							'meta'      => $media_template->media->attachment_data->meta,
						);
					}
				}
			}
		
			if ( bp_is_active( 'media' ) && bp_is_messages_gif_support_enabled() ) {
				$gif_data = bp_messages_get_meta( bp_get_the_thread_message_id(), '_gif_data', true );
		
				if ( ! empty( $gif_data ) ) {
					$preview_url  = wp_get_attachment_url( $gif_data['still'] );
					$video_url    = wp_get_attachment_url( $gif_data['mp4'] );
					$reply['gif'] = array(
						'preview_url' => $preview_url,
						'video_url'   => $video_url,
					);
				}
			}
		
			$extra_content = bp_nouveau_messages_catch_hook_content( array(
				'beforeMeta'    => 'bp_before_message_meta',
				'afterMeta'     => 'bp_after_message_meta',
				'beforeContent' => 'bp_before_message_content',
				'afterContent'  => 'bp_after_message_content',
			) );
		
			if ( array_filter( $extra_content ) ) {
				$reply = array_merge( $reply, $extra_content );
			}
		
			// Clean up the loop.
			bp_thread_messages();
		
			// Remove the bp_current_action() override.
			$bp->current_action = $reset_action;
		
			// set a flag
			$reply['is_new'] = true;
		
			wp_send_json_success( array(
				'messages'  => array( $reply ),
				'thread_id' => $thread_id,
				'feedback'  => __( 'Your reply was sent successfully', 'buddyboss' ),
				'type'      => 'success',
			) );
		}

		function bp_ajax_message_charges() {

			global $thread_template, $messages_template;

			/*echo '<pre>testtest';
			print_r($_POST);
			echo '</pre>';
			wp_die('stop');*/

			//mycred_add( 'private_message', $user_id, -5, 'Sending Private Message' );
			$response = array(
				'feedback' => __( 'Your message could not be sent. Please try again.', 'buddypress' ),
				'type'     => 'error',
			);
			// Verify nonce
			if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'messages_send_message' ) ) {
				wp_send_json_error( $response );
			}
			// Validate subject and message content
			/*if ( empty( $_POST['subject'] ) || empty( $_POST['message_content'] ) ) {
				if ( empty( $_POST['subject'] ) ) {
					$response['feedback'] = __( 'Your message was not sent. Please enter a subject line.', 'buddypress' );
				} else {
					$response['feedback'] = __( 'Your message was not sent. Please enter some content.', 'buddypress' );
				}
				wp_send_json_error( $response );
			}*/
			// Validate subject and message content
			if ( empty( $_POST['message_content'] ) ) {
				$response['feedback'] = __( 'Your message was not sent. Please enter some content.', 'buddyboss' );

				wp_send_json_error( $response );
			}
			// Validate recipients
			if ( empty( $_POST['send_to'] ) || ! is_array( $_POST['send_to'] ) ) {
				$response['feedback'] = __( 'Please add at least one recipient.', 'buddyboss' );

				wp_send_json_error( $response );
			}
			// Trim @ from usernames
			/**
			 * Filters the results of trimming of `@` characters from usernames for who is set to receive a message.
			 *
			 * @since 3.0.0
			 *
			 * @param array $value Array of trimmed usernames.
			 * @param array $value Array of un-trimmed usernames submitted.
			 */
			$recipients = apply_filters( 'bp_messages_recipients', array_map( function( $username ) {
				return trim( $username, '@' );
			}, $_POST['send_to'] ) );

			// before sending message check if user have enough points to send
			if ( $this->prefs['ctype'] != MYCRED_DEFAULT_TYPE_KEY )
				$mycred = mycred( $this->prefs['ctype'] );
			else
				$mycred = $this->core;

			$cost    = $mycred->number( $this->prefs['new_message'] * count( $recipients ) );
			//check user balance
			$user_id = bp_loggedin_user_id();
			$balance = mycred_get_users_balance( $user_id, $this->prefs['ctype'] );
			//echo 'cost to send this messasge '. $cost. '<br><br>';
			//echo 'user balance '. $balance;
			//wp_die('wait here');
			if ($balance < $cost ) {
				$response = array(
					'feedback' => __( 'Your donot have sufficient balance to send this message.', 'buddypress' ),
					'type'     => 'error',
				);
				wp_send_json_error( $response );
				wp_die();
			}

			$subject = '';

			if( isset($_POST['subject']) && trim($_POST['subject'])!='' ) {
				$subject = $_POST['subject'];
			} else {
				$subject = wp_trim_words( $_POST['message_content'], messages_get_default_subject_length() );
			}

			// Attempt to send the message.
			$send = messages_new_message( array(
				'recipients' => $recipients,
				'subject'    => $subject,
				'content'    => $_POST['message_content'],
				'error_type' => 'wp_error',
			) );

			// Send the message.
			if ( true === is_int( $send ) ) {
				$response = array();

				$this->charge_messages( $recipients, $send );

				if ( bp_has_message_threads( array( 'include' => $send ) ) ) {

					while ( bp_message_threads() ) {
						bp_message_thread();
						$last_message_id = (int) $messages_template->thread->last_message_id;
		
						$response = array(
							'id'            => bp_get_message_thread_id(),
							'message_id'    => (int) $last_message_id,
							'subject'       => wp_strip_all_tags( bp_get_message_thread_subject() ),
							'excerpt'       => wp_strip_all_tags( bp_get_message_thread_excerpt() ),
							'content'       => do_shortcode( bp_get_message_thread_content() ),
							'unread'        => bp_message_thread_has_unread(),
							'sender_name'   => bp_core_get_user_displayname( $messages_template->thread->last_sender_id ),
							'sender_is_you' => $messages_template->thread->last_sender_id == bp_loggedin_user_id(),
							'sender_link'   => bp_core_get_userlink( $messages_template->thread->last_sender_id, false, true ),
							'sender_avatar' => esc_url( bp_core_fetch_avatar( array(
								'item_id' => $messages_template->thread->last_sender_id,
								'object'  => 'user',
								'type'    => 'thumb',
								'width'   => BP_AVATAR_THUMB_WIDTH,
								'height'  => BP_AVATAR_THUMB_HEIGHT,
								'html'    => false,
							) ) ),
							'count'         => bp_get_message_thread_total_count(),
							'date'          => strtotime( bp_get_message_thread_last_post_date_raw() ) * 1000,
							'display_date'  => bp_nouveau_get_message_date( bp_get_message_thread_last_post_date_raw() ),
							'started_date' => bp_nouveau_get_message_date( $messages_template->thread->first_message_date, get_option('date_format') ),
						);
		
						if ( is_array( $messages_template->thread->recipients ) ) {
							foreach ( $messages_template->thread->recipients as $recipient ) {
								if ( empty( $recipient->is_deleted ) ) {

									$response['recipients'][] = array(
										'avatar'    => esc_url( bp_core_fetch_avatar( array(
											'item_id' => $recipient->user_id,
											'object'  => 'user',
											'type'    => 'thumb',
											'width'   => BP_AVATAR_THUMB_WIDTH,
											'height'  => BP_AVATAR_THUMB_HEIGHT,
											'html'    => false,
										) ) ),
										'user_link'  => bp_core_get_userlink( $recipient->user_id, false, true ),
										'user_name'  => bp_core_get_user_displayname( $recipient->user_id ),
										'is_deleted' => empty( get_userdata( $recipient->user_id ) ) ? 1 : 0,
										'is_you'     => $recipient->user_id === bp_loggedin_user_id(),
									);
								}
							}
						}
		
						if ( bp_is_active( 'messages', 'star' ) ) {
							$star_link = bp_get_the_message_star_action_link( array(
								'thread_id' => bp_get_message_thread_id(),
								'url_only'  => true,
							) );
		
							$response['star_link'] = $star_link;
		
							$star_link_data         = explode( '/', $star_link );
							$response['is_starred'] = array_search( 'unstar', $star_link_data );
		
							// Defaults to last
							$sm_id = $last_message_id;
		
							if ( $response['is_starred'] ) {
								$sm_id = (int) $star_link_data[ $response['is_starred'] + 1 ];
							}
		
							$response['star_nonce'] = wp_create_nonce( 'bp-messages-star-' . $sm_id );
							$response['starred_id'] = $sm_id;
						}
		
						$thread_extra_content = bp_nouveau_messages_catch_hook_content( array(
							'inboxListItem' => 'bp_messages_inbox_list_item',
							'threadOptions' => 'bp_messages_thread_options',
						) );
		
						if ( array_filter( $thread_extra_content ) ) {
							$response = array_merge( $response, $thread_extra_content );
						}
					}
				}
		
				if ( empty( $response ) ) {
					$response = array( 'id' => $send );
				}

				wp_send_json_success( array(
					'feedback' => __( 'Message successfully sent.', 'buddyboss' ),
					'type'     => 'success',
					'thread'   => $response,
				) );

			// Message could not be sent.
			} else {
				$response['feedback'] = $send->get_error_message();
				wp_send_json_error( $response );
			}

			
		}

		/**
		 * Add Badge Support
		 * @since 1.1.2
		 * @version 1.0
		 */
		public function add_badge_support( $references ) {

			$references['new_message'] = __( 'New Message Payment (BP Charges)', 'bp_charge' );
			$references['new_reply']   = __( 'New Reply Payment (BP Charges)', 'bp_charge' );

			return $references;

		}

		/**
		 * Module Init
		 * @since 1.0
		 * @version 1.1.1
		 */
		public function module_init() {

			if ( isset( $this->prefs['new_message'] ) && $this->prefs['new_message'] != 0 ) {

				add_action( 'bp_before_messages_compose_content', array( $this, 'message_warning' ) );
				add_action( 'messages_screen_compose',            array( $this, 'compose_message_screen' ) );

				if ( isset( $this->prefs['show_message_cost'] ) && $this->prefs['show_message_cost'] != '' )
					add_action( 'bp_after_messages_compose_content',   array( $this, 'show_message_cost' ) );

				if ( isset( $this->prefs['buttons'] ) && $this->prefs['buttons'] == 'yes' && ! $this->current_user_can_afford( 'new_message', 1 ) )
					add_filter( 'bp_get_send_message_button_args', '__return_empty_array' );

			}

			if ( isset( $this->prefs['new_reply'] ) && $this->prefs['new_reply'] != 0 ) {

				add_action( 'bp_before_message_thread_reply', array( $this, 'reply_warning' ) );

				if ( isset( $this->prefs['show_reply_cost'] ) && $this->prefs['show_reply_cost'] != '' )
					add_action( 'bp_after_message_thread_reply', array( $this, 'show_reply_cost' ) );

			}

		}

		/**
		 * Show Overview Totals
		 * @since 1.0
		 * @version 1.0
		 */
		public function overview() {

			global $wpdb;

			$mycred       = mycred( $this->prefs['ctype'] );

			$page        = MYCRED_SLUG;
			if ( $this->prefs['ctype'] != MYCRED_DEFAULT_TYPE_KEY )
				$page .= '_' . $this->prefs['ctype'];

			$url          = admin_url( 'admin.php?page=' . $page );

			$messages_url = add_query_arg( array( 'ref' => 'new_message' ), $url );
			$messages     = $wpdb->get_var( $wpdb->prepare( "SELECT SUM( creds ) FROM {$mycred->log_table} WHERE ref = %s AND ctype = %s;", 'new_message', $this->prefs['ctype'] ) );
			$messages     = ( $messages === NULL ) ? 0 : abs( $messages );

			$replies_url  = add_query_arg( array( 'ref' => 'new_reply' ), $url );
			$replies      = $wpdb->get_var( $wpdb->prepare( "SELECT SUM( creds ) FROM {$mycred->log_table} WHERE ref = %s AND ctype = %s;", 'new_reply', $this->prefs['ctype'] ) );
			$replies      = ( $replies === NULL ) ? 0 : abs( $replies );

			$total        = $messages + $replies;

?>
<div class="mycred-type clear first">
	<div class="module-title"><div class="type-icon"><div class="dashicons dashicons-admin-comments"></div></div><?php _e( 'Messages', 'bp_charge' ); ?><a href="<?php echo $url; ?>"><?php echo $mycred->format_creds( $total ); ?></a></div>
	<div class="overview clear">
		<div class="section border" style="width: 50%;">
			<p><strong><?php _e( 'New Messages', 'bp_charge' ); ?>:</strong> <a href="<?php echo esc_url( $messages_url ); ?>"><?php echo $mycred->format_creds( $messages ); ?></a></p>
		</div>
		<div class="section border" style="width: 50%; margin-left: -1px;">
			<p><strong><?php _e( 'New Replies', 'bp_charge' ); ?>:</strong> <a href="<?php echo esc_url( $replies_url ); ?>"><?php echo $mycred->format_creds( $replies ); ?></a></p>
		</div>
	</div>
</div>
<?php

		}

		/**
		 * Current user can afford
		 * @since 1.0
		 * @version 1.1
		 */
		public function current_user_can_afford( $instance = '', $no_of_recipients = 1 ) {

			if ( ! is_user_logged_in() || ! isset( $this->prefs[ $instance ] ) ) return false;

			// Free or Admin
			if ( $this->prefs[ $instance ] == 0 || bp_current_user_can( 'bp_moderate' ) || apply_filters( 'mycred_bp_charge_can_afford', false, $instance, $no_of_recipients, $this ) === true ) return true;

			// Prep
			$user_id = get_current_user_id();

			if ( $this->prefs['ctype'] != MYCRED_DEFAULT_TYPE_KEY )
				$mycred = mycred( $this->prefs['ctype'] );
			else
				$mycred = $this->core;

			// Excluded
			if ( $mycred->exclude_user( $user_id ) ) return false;

			// Get balance
			$balance = $mycred->get_users_balance( $user_id, $this->prefs['ctype'] );

			// Balance check
			if ( $balance < $mycred->number( ( $this->prefs[ $instance ] * $no_of_recipients ) ) )
				return false;

			return true;

		}

		/**
		 * Create New Message
		 * @since 1.1
		 * @version 1.0
		 */
		public function bp_create_new_message() {

			remove_action( 'bp_actions',         'bp_messages_action_create_message' );

			// Bail if not posting to the compose message screen.
			if ( ! bp_is_post_request() || ! bp_is_messages_component() || ! bp_is_current_action( 'compose' ) ) {
				return false;
			}

			// Check the nonce.
			check_admin_referer( 'messages_send_message' );

			// Define local variables.
			$redirect_to = '';
			$feedback    = '';
			$success     = false;

			// Missing subject or content.
			if ( empty( $_POST['subject'] ) || empty( $_POST['content'] ) ) {

				$success  = false;

				if ( empty( $_POST['subject'] ) ) {
					$feedback = __( 'Your message was not sent. Please enter a subject line.', 'bp_charge' );
				} else {
					$feedback = __( 'Your message was not sent. Please enter some content.', 'bp_charge' );
				}

			// Subject and content present.
			} else {

				// Setup the link to the logged-in user's messages.
				$member_messages = trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() );

				// Site-wide notice.
				if ( isset( $_POST['send-notice'] ) ) {

					// Attempt to save the notice and redirect to notices.
					if ( messages_send_notice( $_POST['subject'], $_POST['content'] ) ) {
						$success     = true;
						$feedback    = __( 'Notice successfully created.', 'bp_charge' );
						$redirect_to = trailingslashit( $member_messages . 'notices' );

					// Notice could not be sent.
					} else {
						$success  = false;
						$feedback = __( 'Notice was not created. Please try again.', 'bp_charge' );
					}

				// Private conversation.
				} else {

					// Filter recipients into the format we need - array( 'username/userid', 'username/userid' ).
					$autocomplete_recipients = (array) explode( ',', $_POST['send-to-input']     );
					$typed_recipients        = (array) explode( ' ', $_POST['send_to_usernames'] );
					$recipients              = array_merge( $autocomplete_recipients, $typed_recipients );

					/**
					 * Filters the array of recipients to receive the composed message.
					 *
					 * @since 1.2.10
					 *
					 * @param array $recipients Array of recipients to receive message.
					 */
					$recipients = apply_filters( 'bp_messages_recipients', $recipients );

					// Some recipients are empty, make sure we only count actual recipients
					$charged_recipients = 0;
					foreach ( $recipients as $rec ) {
						if ( $rec == '' ) continue;
						$charged_recipients ++;
					}

					// Send message if the sender can afford it
					if ( $this->current_user_can_afford( 'new_message', $charged_recipients ) ) {

						// Attempt to send the message.
						$send = messages_new_message( array(
							'recipients' => $recipients,
							'subject'    => $_POST['subject'],
							'content'    => $_POST['content'],
							'error_type' => 'wp_error'
						) );

						// Send the message and redirect to it.
						if ( true === is_int( $send ) ) {
							$success     = true;
							$feedback    = __( 'Message successfully sent.', 'bp_charge' );
							$view        = trailingslashit( $member_messages . 'view' );
							$redirect_to = trailingslashit( $view . $send );

						// Message could not be sent.
						} else {
							$success  = false;
							$feedback = $send->get_error_message();
						}

					}
					else {
						$success  = false;
						$feedback = $this->core->template_tags_general( $this->prefs['messages_warning'] );
					}

				}

			}
	
			// Feedback.
			if ( ! empty( $feedback ) ) {

				// Determine message type.
				$type = ( true === $success )
					? 'success'
					: 'error';

				// Add feedback message.
				bp_core_add_message( $feedback, $type );

			}

			// Charge Message
			if ( $success === true ) {

				$this->charge_messages( $charged_recipients, $send );

			}

			// Maybe redirect.
			if ( ! empty( $redirect_to ) ) {
				bp_core_redirect( $redirect_to );
			}

		}

		/**
		 * Redirect to Page
		 * @since 1.0
		 * @version 1.0
		 */
		public function compose_message_screen() {

			//if ( ! isset( $_POST['send'] ) ) return;

			if ( $this->prefs['redirect_to'] != 0 && ! $this->current_user_can_afford( 'new_message' ) )
				bp_core_redirect( get_permalink( $this->prefs['redirect_to'] ) );

		}

		/**
		 * Message Warning
		 * @since 1.0
		 * @version 1.0
		 */
		public function message_warning() {

			if ( ! $this->current_user_can_afford( 'new_message' ) && $this->prefs['messages_warning'] != '' ) {

				$message = $this->core->template_tags_general( $this->prefs['messages_warning'] );
				echo '<div id="message" class="error"><p>' . $message . '</p></div>';

			}

		}

		/**
		 * Show Message Cost
		 * @since 1.0
		 * @version 1.0.1
		 */
		public function show_message_cost() {

			if ( bp_current_user_can( 'bp_moderate' ) || apply_filters( 'mycred_bp_show_charge_message_cost', true, $this ) === false ) return;

			$message = $this->core->template_tags_amount( $this->prefs['show_message_cost'], $this->prefs['new_message'] );
			$message = $this->core->template_tags_general( $message );

			echo '<div class="bp-charge-cost bp-charge-message-cost">' . $message . '</div>';

		}

		/**
		 * Charge Messages
		 * @since 1.0
		 * @version 1.1
		 */
		public function charge_messages( $recipients, $thread_id ) {

			global $mycred_bp_charge;

			if ( ! isset( $this->prefs['new_message'] ) || $this->prefs['new_message'] == 0 ) return;

			if ( bp_current_user_can( 'bp_moderate' ) || apply_filters( 'mycred_bp_charge_new_message', true, $thread_id, $this ) === false ) return;

			$user_id = bp_loggedin_user_id();

			if ( $this->prefs['ctype'] != MYCRED_DEFAULT_TYPE_KEY )
				$mycred = mycred( $this->prefs['ctype'] );
			else
				$mycred = $this->core;

			$cost    = $mycred->number( $this->prefs['new_message'] * count( $recipients ) );

			$mycred->update_users_balance( $user_id, 0 - $cost );

			if ( strlen( $this->prefs['new_message_log'] ) > 0 )
				
				//$new_message_log = str_replace ("%user%", "larry", $this->prefs['new_message_log']);

				//$new_message_log = !empty($new_message_log)? $new_message_log : $this->prefs['new_message_log'];

				$mycred->add_to_log(
					'new_message',
					$user_id,
					0 - $cost,
					$this->prefs['new_message_log'], //$new_message_log, 
					$thread_id,
					$recipients,
					$this->prefs['ctype']
				);

		}

		/**
		 * New Reply
		 * @since 1.1
		 * @version 1.0
		 */
		public function bp_messages_action_conversation() {
			
			remove_action( 'bp_actions',         'messages_action_conversation' );

			// Bail if not viewing a single conversation.
			if ( ! bp_is_messages_component() || ! bp_is_current_action( 'view' ) ) {
				return false;
			}

			// Get the thread ID from the action variable.
			$thread_id = (int) bp_action_variable( 0 );

			if ( ! messages_is_valid_thread( $thread_id ) || ( ! messages_check_thread_access( $thread_id ) && ! bp_current_user_can( 'bp_moderate' ) ) ) {
				return;
			}

			// Check if a new reply has been submitted.
			if ( isset( $_POST['send'] ) ) {

				// Check the nonce.
				check_admin_referer( 'messages_send_message', 'send_message_nonce' );

				if ( $this->current_user_can_afford( 'new_reply' ) ) {

					$new_reply = messages_new_message( array(
						'thread_id' => $thread_id,
						'subject'   => ! empty( $_POST['subject'] ) ? $_POST['subject'] : false,
						'content'   => $_POST['content']
					) );

					// Send the reply.
					if ( ! empty( $new_reply ) ) {

						bp_core_add_message( __( 'Your reply was sent successfully', 'bp_charge' ) );

						$this->charge_new_reply( $thread_id );

					} else {
						bp_core_add_message( __( 'There was a problem sending your reply. Please try again.', 'bp_charge' ), 'error' );
					}

				}
				else {

					bp_core_add_message( $this->core->template_tags_general( $this->prefs['reply_warning'] ), 'error' );

				}

				bp_core_redirect( bp_displayed_user_domain() . bp_get_messages_slug() . '/view/' . $thread_id . '/' );

			}

			/**
			 * Mark message read, but only run on the logged-in user's profile.
			 * If an admin visits a thread, it shouldn't change the read status.
			 */
			if ( bp_is_my_profile() ) {
				messages_mark_thread_read( $thread_id );
			}

			/**
			 * Fires after processing a view request for a single message thread.
			 *
			 * @since 1.7.0
			 */
			do_action( 'messages_action_conversation' );

		}

		/**
		 * New Reply via AJAX
		 * @since 1.1
		 * @version 1.0
		 */
		public function new_reply_via_ajax( $action ) {

			if ( $action != 'messages_send_message' || isset( $_REQUEST['send_message_nonce'] ) ) return;

			if ( $this->current_user_can_afford( 'new_reply' ) ) {

				$this->charge_new_reply( (int) $_REQUEST['thread_id'] );

				$result = messages_new_message( array( 'thread_id' => (int) $_REQUEST['thread_id'], 'content' => $_REQUEST['content'] ) );

				if ( $result ) {

?>
<div class="message-box new-message">
	<div class="message-metadata">
		<?php do_action( 'bp_before_message_meta' ); ?>
		<?php echo bp_loggedin_user_avatar( 'type=thumb&width=30&height=30' ); ?>

		<strong><a href="<?php echo bp_loggedin_user_domain(); ?>"><?php bp_loggedin_user_fullname(); ?></a> <span class="activity"><?php printf( __( 'Sent %s', 'bp_charge' ), bp_core_time_since( bp_core_current_time() ) ); ?></span></strong>

		<?php do_action( 'bp_after_message_meta' ); ?>
	</div>

	<?php do_action( 'bp_before_message_content' ); ?>

	<div class="message-content">
		<?php echo stripslashes( apply_filters( 'bp_get_the_thread_message_content', $_REQUEST['content'] ) ); ?>
	</div>

	<?php do_action( 'bp_after_message_content' ); ?>

	<div class="clear"></div>
</div>
<?php

					// If this is the last reply a user could afford, we need to attach the warning as well
					if ( ! $this->current_user_can_afford( 'new_reply' ) )
						echo "<div id='message' class='error'><p>" . $this->core->template_tags_general( $this->prefs['reply_warning'] ) . '</p></div>';

				} else {
					echo "-1<div id='message' class='error'><p>" . __( 'There was a problem sending that reply. Please try again.', 'bp_charge' ) . '</p></div>';
				}

			} else {
				echo "-1<div id='message' class='error'><p>" . $this->core->template_tags_general( $this->prefs['reply_warning'] ) . '</p></div>';
			}

			exit;

		}

		/**
		 * Reply Warning
		 * @since 1.0
		 * @version 1.1
		 */
		public function reply_warning() {

			if ( ! $this->current_user_can_afford( 'new_reply' ) && $this->prefs['reply_warning'] != '' ) {

				$message = $this->core->template_tags_general( $this->prefs['reply_warning'] );
				echo '<div id="message" class="error"><p>' . $message . '</p></div>';

			}

		}

		/**
		 * Show Reply Cost
		 * @since 1.0
		 * @version 1.1.1
		 */
		public function show_reply_cost() {

			if ( bp_current_user_can( 'bp_moderate' ) || apply_filters( 'mycred_bp_show_charge_reply_cost', true, $this ) === false ) return;

			$message = $this->core->template_tags_amount( $this->prefs['show_reply_cost'], $this->prefs['new_reply'] );
			$message = $this->core->template_tags_general( $message );

			echo '<div class="bp-charge-cost bp-charge-reply-cost">' . $message . '</div>';

		}

		/**
		 * Charge Replies
		 * @since 1.1
		 * @version 1.0
		 */
		public function charge_new_reply( $thread_id ) {

			global $mycred_bp_charge;

			if ( ! isset( $this->prefs['new_reply'] ) || $this->prefs['new_reply'] == 0 ) return;

			if ( bp_current_user_can( 'bp_moderate' ) || apply_filters( 'mycred_bp_charge_new_reply', true, $thread_id, $this ) === false ) return;

			$user_id = bp_loggedin_user_id();

			if ( $this->prefs['ctype'] != MYCRED_DEFAULT_TYPE_KEY )
				$mycred = mycred( $this->prefs['ctype'] );
			else
				$mycred = $this->core;

			$cost = $mycred->number( $this->prefs['new_reply'] );

			$balance = mycred_get_users_balance( $user_id, $this->prefs['ctype'] );
			//echo 'cost to send this messasge '. $cost. '<br><br>';
			//echo 'user balance '. $balance.'<br><br>';
			//wp_die('wait here!');
			if ($balance < $cost ) {
				return 'stop';	
			} else {

				$mycred->update_users_balance( $user_id, 0 - $cost );

				if ( strlen( $this->prefs['new_reply_log'] ) > 0 ) {
					$mycred->add_to_log(
						'new_reply',
						$user_id,
						0 - $cost,
						$this->prefs['new_reply_log'],
						$thread_id,
						'',
						$this->prefs['ctype']
					);
				}
				
			}

		}

		/**
		 * Preference for Charge
		 * @since 1.0
		 * @version 1.1.1
		 */
		public function preferences() {

			$prefs = $this->prefs;
			$types = mycred_get_types();

?>
<h3><?php _e( 'Access', 'bp_charge' ); ?></h3>
<ol>
	<li>
		<span class="description"><?php _e( 'What should happen when a user tries to compose a new message in BuddyPress but can not afford to send a message.', 'bp_charge' ); ?></span>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( 'access-none' ); ?>"><input type="radio" class="toggle-sub-section" name="<?php echo $this->field_name( 'access' ); ?>" id="<?php echo $this->field_id( 'access-none' ); ?>"<?php checked( $prefs['access'], '' ); ?> value="" /> <?php _e( 'Nothing. Let the user compose a new message but show an error when they try and send the message.', 'bp_charge' ); ?></label><br />
		<label for="<?php echo $this->field_id( 'access-redirect' ); ?>"><input type="radio" class="toggle-sub-section" name="<?php echo $this->field_name( 'access' ); ?>" id="<?php echo $this->field_id( 'access-redirect' ); ?>"<?php checked( $prefs['access'], 'redirect' ); ?> value="redirect" /> <?php _e( 'Redirect user to a specific page.', 'bp_charge' ); ?></label>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<span class="description"><?php _e( 'Should the "Private Message" button be hidden if the user can not afford to pay for a new message?', 'bp_charge' ); ?></span>
	</li>
	<li>
		<label for="<?php echo $this->field_id( 'buttons-yes' ); ?>"><input type="radio" class="toggle-sub-section" name="<?php echo $this->field_name( 'buttons' ); ?>" id="<?php echo $this->field_id( 'buttons-yes' ); ?>"<?php checked( $prefs['buttons'], 'yes' ); ?> value="yes" /> <?php _e( 'Yes, hide the button', 'bp_charge' ); ?></label><br />
		<label for="<?php echo $this->field_id( 'buttons-no' ); ?>"><input type="radio" class="toggle-sub-section" name="<?php echo $this->field_name( 'buttons' ); ?>" id="<?php echo $this->field_id( 'buttons-no' ); ?>"<?php checked( $prefs['buttons'], 'no' ); ?> value="no" /> <?php _e( 'No, always show the button.', 'bp_charge' ); ?></label>
	</li>
</ol>
<div id="charge-message-" class="charge-message-access-option" style="display:<?php if ( $prefs['access'] == '' ) echo 'block'; else echo 'none'; ?>;">
</div>
<div id="charge-message-redirect" class="charge-message-access-option" style="display:<?php if ( $prefs['access'] == 'redirect' ) echo 'block'; else echo 'none'; ?>;">
	<label class="subheader"><?php _e( 'Page Redirect', 'bp_charge' ); ?></label>
	<ol>
		<li>
<?php

		wp_dropdown_pages( array(
			'name'              => $this->field_name( 'redirect_to' ),
			'id'                => $this->field_id( 'redirect_to' ),
            'class'             => 'form-control',
			'selected'          => $prefs['redirect_to'],
			'show_option_none'  => __( 'Select page', 'bp_charge' ),
			'option_none_value' => 0
		) );

?>
		</li>
	</ol>
</div>
<label class="subheader"><?php _e( 'Point Type', 'bp_charge' ); ?></label>
<ol>
	<li>

		<?php if ( count( $types ) == 1 ) : ?>

		<?php echo $this->core->plural(); ?>

		<?php endif; ?>

		<?php echo mycred_types_select_from_dropdown( $this->field_name( 'ctype' ), $this->field_id( 'ctype' ), $prefs['ctype'], false, ' class="form-control"' ); ?>

	</li>
</ol>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <h3><?php _e( 'New Message', 'bp_charge' ); ?></h3>
                    <div class="form-group">
                        <label class="subheader"><?php _e( 'Price', 'bp_charge' ); ?></label>
                        <input type="text" name="<?php echo $this->field_name( 'new_message' ); ?>" id="<?php echo $this->field_id( 'new_message' ); ?>" value="<?php echo esc_attr( $prefs['new_message'] ); ?>" class="form-control short" />
                        <span class="description"><?php _e( 'Use zero if this is free.', 'bp_charge' ); ?></span>
                    </div>
                    <div class="form-group">
                        <label><?php _e( 'Log Entry', 'bp_charge' ); ?></label>
                        <input type="text" name="<?php echo $this->field_name( 'new_message_log' ); ?>" id="<?php echo $this->field_id( 'new_message_log' ); ?>" value="<?php echo esc_attr( $prefs['new_message_log'] ); ?>" class="form-control long" />
                    </div>
                    <div class="form-group">
                        <label><?php _e( 'Insufficient Funds Warning', 'bp_charge' ); ?></label>
                        <?php wp_editor( $prefs['messages_warning'], 'mycredbpchargemessagewarningmessage', array( 'textarea_name' => $this->field_name( 'messages_warning' ), 'textarea_rows' => 5 ) ); ?>
                        <span class="description"><?php _e( 'Leave empty if you do not want to show a warning. Used on the Compose page.', 'bp_charge' ); ?></span>
                    </div>
                    <div class="form-group">
                        <label><?php _e( 'Cost Template', 'bp_charge' ); ?></label>
                        <input type="text" name="<?php echo $this->field_name( 'show_message_cost' ); ?>" id="<?php echo $this->field_id( 'show_message_cost' ); ?>" value="<?php echo esc_attr( $prefs['show_message_cost'] ); ?>" class="form-control long" />
                        <span class="description"><?php _e( 'Option to show the cost when a user composes a new message. Leave empty to hide.', 'bp_charge' ); ?></span><br 7>
                        <span class="description"><?php echo $this->core->available_template_tags( array( 'general', 'amount' ) ); ?></span>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <h3><?php _e( 'New Reply', 'bp_charge' ); ?></h3>
                    <div class="form-group">
                        <label><?php _e( 'Price', 'bp_charge' ); ?></label>
                        <input type="text" name="<?php echo $this->field_name( 'new_reply' ); ?>" id="<?php echo $this->field_id( 'new_reply' ); ?>" value="<?php echo esc_attr( $prefs['new_reply'] ); ?>" class="form-control short" />
                        <span class="description"><?php _e( 'Use zero if this is free.', 'bp_charge' ); ?></span>
                    </div>
                    <div class="form-group">
                        <label><?php _e( 'Log Entry', 'bp_charge' ); ?></label>
                        <input type="text" name="<?php echo $this->field_name( 'new_reply_log' ); ?>" id="<?php echo $this->field_id( 'new_reply_log' ); ?>" value="<?php echo esc_attr( $prefs['new_reply_log'] ); ?>" class="form-control long" />
                        <span class="description"><?php _e( 'Leave empty to just charge without a log entry.', 'bp_charge' ); ?></span>
                    </div>
                    <div class="form-group">
                        <label><?php _e( 'Insufficient Funds Warning', 'bp_charge' ); ?></label>
                       <?php wp_editor( $prefs['reply_warning'], 'mycredbpchargemessagewarningreply', array( 'textarea_name' => $this->field_name( 'reply_warning' ), 'textarea_rows' => 5 ) ); ?>
                        <span class="description"><?php _e( 'Leave empty if you do not want to show a warning. Used when viewing a message.', 'bp_charge' ); ?></span>
                    </div>
                    <div class="form-group">
                        <label><?php _e( 'Cost Template', 'bp_charge' ); ?></label>
                        <input type="text" name="<?php echo $this->field_name( 'show_reply_cost' ); ?>" id="<?php echo $this->field_id( 'show_reply_cost' ); ?>" value="<?php echo esc_attr( $prefs['show_reply_cost'] ); ?>" class="form-control long" />
                        <span class="description"><?php _e( 'Option to show the cost when a user composes a new reply. Leave empty to hide.', 'bp_charge' ); ?></span><br 7>
                        <span class="description"><?php echo $this->core->available_template_tags( array( 'general', 'amount' ) ); ?></span>

                    </div>
                </div>
            </div>
<script type="text/javascript">//<![CDATA[
jQuery(function($){

	$( 'input.toggle-sub-section' ).click(function(){
		var box = $(this).val();
		$( 'div.charge-message-access-option' ).hide();
		$( 'div#charge-message-' + box ).show();
	});

});//]]>
</script>
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

			$new_data['access']           = ( ( isset( $data['access'] ) ) ? sanitize_key( $data['access'] ) : '' );
			$new_data['buttons']          = ( ( isset( $data['buttons'] ) ) ? sanitize_key( $data['buttons'] ) : '' );
			$new_data['redirect_to']      = absint( $data['redirect_to'] );

			if ( $new_data['ctype'] != $this->core->cred_id )
				$mycred = mycred( $new_data['ctype'] );
			else
				$mycred = $this->core;

			$new_data['new_message']      = $mycred->number( $data['new_message'] );
			$new_data['new_message_log']  = sanitize_text_field( $data['new_message_log'] );
			$new_data['messages_warning'] = wp_kses_post( $data['messages_warning'] );

			$new_data['new_reply']        = $mycred->number( $data['new_reply'] );
			$new_data['new_reply_log']    = sanitize_text_field( $data['new_reply_log'] );
			$new_data['reply_warning']    = wp_kses_post( $data['reply_warning'] );

			return $data;

		}

	}
endif;

?>