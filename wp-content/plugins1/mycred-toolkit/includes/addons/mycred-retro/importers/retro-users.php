<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Tool: Content
 * @since 1.0
 * @version 1.0
 */
if ( ! class_exists( 'myCRED_Retro_Users_Tool' ) ) :
	class myCRED_Retro_Users_Tool {

		const tool   = 'mycred_retro_users';

		/**
		 * Register Tool 
		 * @since 1.0
		 * @version 1.0
		 */
		static function register() {

			register_importer(
				self::tool,
				sprintf( 
					// Translators: %s is the mycred label .
					__( '%s Retroactive Registrations', 'mycred-toolkit' ), mycred_label() ),
				__( 'Award or deduct points from your users for being a member on your website.', 'mycred-toolkit' ),
				array( __CLASS__, 'render' )
			);
		}

		/**
		 * Header
		 * @since 1.0
		 * @version 1.0
		 */
		static function header() {

			$screen = get_current_screen();

			$screen->add_help_tab( array(
				'id'       => 'retro-users',
				'title'    => __( 'Introduction', 'mycred-toolkit' ),
				'content'  => '
<h2>Retroactive Content</h2>
<p>This tool allows you give points to users for registering on your website.</p>
<p>To prevent to heavy queries, this tool will process <strong>' . MYCRED_RETRO_MAX . '</strong> users at a time.</p>
<p>If you feel your site can handle more in one session, use the <code>MYCRED_RETRO_MAX</code> constant to change the threshold, by defining it in your wp-config.php file.</p>'
			) );
			$screen->add_help_tab( array(
				'id'       => 'retro-users-eligible',
				'title'    => __( 'Eligible Users', 'mycred-toolkit' ),
				'content'  => '<h2>Not Excluded</h2><p>Only users that are not excluded can be used with this tool.</p>'
			) );

			$screen->add_help_tab( array(
				'id'       => 'retro-users-amount',
				'title'    => __( 'Amount', 'mycred-toolkit' ),
				'content'  => '<h2>Point Amount</h2><p>You can give points to a user by providing a positive number (without a plus sign) or take points form a user by providing a negative number.</p>'
			) );
			$screen->add_help_tab( array(
				'id'       => 'retro-users-log',
				'title'    => __( 'Log Entries', 'mycred-toolkit' ),
				'content'  => '<h2>Log Entries</h2><p>Saving a log entry for each point adjustments will allow you to reward users with badges / ranks and it also prevents users from gaining points twice for the same published content. But adding a log entry for each adjustment is optional. If you do not want to do this, simply make sure the log entry template is empty. If you do not add log entries, the users balance will be updated but there will be no record of how they got those points. These adjustments will not be seen by e.g. our hook limits or the badges add-on.</p><p>Supports <a href="http://codex.mycred.me/category/template-tags/temp-general/" target="_blank">General</a> and <a href="http://codex.mycred.me/category/template-tags/temp-user/" target="_blank">User related</a> template tags.</p>'
			) );

			$screen->set_help_sidebar(
				'<p><strong>' . __( 'For more information:', 'mycred-toolkit' ) . '</strong></p>' .
				'<p><a href="https://mycred.me/" target="_blank">' . __( 'myCRED Website', 'mycred-toolkit' ) . '</a></p>' .
				'<p><a href="http://codex.mycred.me/" target="_blank">' . __( 'Documentation', 'mycred-toolkit' ) . '</a></p>'
			);
		}

		/**
		 * Render Tool 
		 * @since 1.0
		 * @version 1.0
		 */
		static function render() {

			global $wpdb, $mycred;

			$users = count_users();
			$total = $users['total_users'];

			?>
<style type="text/css">
.wrap h1 { margin-bottom: 12px; }
form .form-control, form select { width: 100%; }
form p label { display: block; }
form table.widefat { margin-bottom: 24px; }
#import-action { text-align: right; }
td h5 { margin: 6px 0; line-height: 16px; font-size: 16px; }
#task-status { padding-top: 24px; }
#stop-tool-action { text-align: center; margin-bottom: 24px; }
.loading-indicator { height: 5px; width: 100%; position: relative; overflow: hidden; background-color: white; margin-bottom: 24px; }
.loading-indicator:before { display: block; position: absolute; content: ""; left: -200px; width: 200px; height: 5px; background-color: #c5d93d; animation: loading 2s linear infinite; }
@keyframes loading { from { left: -200px; width: 30%; } 25% { width: 50%; } 50% { width: 50%; } 70% { width: 50%; } 80% { left: 75%; } 95% { left: 100%; } to { left: 100%; } }
h1.task-completed { text-align: center; color: green; }
h1.task-failed { text-align: center; color: red; }
#progress-indicator { width: 100%; line-height: 64px; font-size: 18px; text-align: center; margin-bottom: 24px; }
#progress-indicator .border { border: 1px solid #ddd; padding: 12px; height: 64px }
#progress-indicator .border div { height: 64px; }
#progress-indicator .progress-bars { width: 100%; background-color: white; }
#progress-indicator #progress-bar { background-color: orange; color: white; }
#progress-indicator #progress-end.error { background-color: red !important; color: white; }
#progress-indicator #progress-end.success { background-color: green !important; color: white; }
</style>
<div class="wrap">
	<h1><?php esc_html_e( 'Retroactive Registrations', 'mycred-toolkit' ); ?> <a href="<?php echo esc_url( admin_url( 'import.php' ) ); ?>" class="page-title-action"><?php esc_html_e( 'All Tools', 'mycred-toolkit' ); ?></a></h1>
	<div id="message" class="info notice notice-info"><p><?php esc_html_e( 'Remember to disable this plugin once you are done using it!', 'mycred-toolkit' ); ?></p></div>
<?php

			if ( $total == 0 ) {

				?>
	<div id="message" class="updated notice"><p><?php esc_html_e( 'No users found.', 'mycred-toolkit' ); ?></p></div>
<?php

			} else {

				?>
	<form id="import-upload-form" method="post" action="">
		<table class="wp-list-table widefat fixed striped users">
			<thead>
				<tr>
					<th style="width: 30%;"><?php esc_html_e( 'Role', 'mycred-toolkit' ); ?></th>
					<th style="width: 20%;"><?php esc_html_e( 'Point Type', 'mycred-toolkit' ); ?></th>
					<th style="width: 15%;"><?php esc_html_e( 'Amount', 'mycred-toolkit' ); ?></th>
					<th style="width: 25%;"><?php esc_html_e( 'Log Entry Template', 'mycred-toolkit' ); ?></th>
					<th style="width: 10%;"><?php esc_html_e( 'Users', 'mycred-toolkit' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>
						<select name="mycred_retro[post_type]" id="select-user-type" class="form-control">
							<option value="" data-template="" data-count=""><?php esc_html_e( 'Select Role', 'mycred-toolkit' ); ?></option>
<?php

				$editable_roles  = array_reverse( get_editable_roles() );
				foreach ( $editable_roles as $role => $details ) {

					$name  = translate_user_role( $details['name'] );
					$count = ( array_key_exists( $role, $users['avail_roles'] ) ) ? $users['avail_roles'][ $role ] : 0;

					echo '<option value="' . esc_attr( $role ) . '" data-template="%plural% for becoming a member"' . ( ( $count == 0 ) ? ' disabled="disabled"' : '' ) . ' data-count="' . esc_attr( $count ) . '">' . esc_attr ( $name ) . '</option>';

				}

				?>
						</select>
					</td>
					<td>
						<?php mycred_types_select_from_dropdown( 'mycred_retro[point_type]', 'user-log-type' ); ?>
					</td>
					<td>
						<input type="text" name="mycred_retro[amount]" placeholder="<?php esc_html_e( 'amount', 'mycred-toolkit' ); ?>" id="user-log-amount" class="form-control" value="" />
					</td>
					<td>
						<input type="text" name="mycred_retro[log_template]" id="user-log-template" placeholder="<?php esc_html_e( 'no log entry', 'mycred-toolkit' ); ?>" class="form-control" value="" />
					</td>
					<td id="user-count"><h5><?php echo esc_attr($total); ?></h5></td>
				</tr>
			</tbody>
		</table>
		<p>
			<label for="user-original-date"><input type="checkbox" name="mycred_retro[date]" id="user-original-date" checked="checked" value="1" /> <?php esc_html_e( 'When adding a log entry, use the date the user registered, and not the current date.', 'mycred-toolkit' ); ?></label>
		</p>
		<p id="import-action" style="display: none;">
			<?php submit_button( __( 'Run Task', 'mycred-toolkit' ), 'primary', '', false ); ?>
		</p>
		<div id="task-status" style="display: none;">
			<div id="progress-indicator">
				<div class="border">
					<div id="progress-start" class="progress-bars" style="display: block;">0 %</div>
					<div id="progress-end" class="progress-bars" style="display: none;"></div>
					<div id="progress-bar" class="progress-bars" style="display: none; width: 0% !important;"></div>
				</div>
			</div>
			<div id="stop-tool-action">
				<button type="button" id="cancel-task" class="button button-secondary button-large"><?php esc_html_e( 'Stop Task', 'mycred-toolkit' ); ?></button>
			</div>
			<h3><?php esc_html_e( 'Task Report', 'mycred-toolkit' ); ?></h3>
			<table class="wp-list-table widefat fixed striped users" id="task-progress-table">
				<thead>
					<tr>
						<th style="width: 25%;"><?php esc_html_e( 'Eligible Users', 'mycred-toolkit' ); ?></th>
						<th style="width: 25%;"><?php esc_html_e( 'Completed', 'mycred-toolkit' ); ?></th>
						<th style="width: 25%;"><?php esc_html_e( 'Excluded', 'mycred-toolkit' ); ?></th>
						<th style="width: 25%;"><?php esc_html_e( 'Remaining', 'mycred-toolkit' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td id="tak-report-total">0</td>
						<td id="tak-report-completed">0</td>
						<td id="tak-report-excluded">0</td>
						<td id="tak-report-remaining">0</td>
					</tr>
				</tbody>
			</table>
			<div id="completed-actions"></div>
		</div>
	</form>
<script type="text/javascript">
jQuery(function($){

	var run_all        = false;
	var task_completed = 0;
	var task_size      = 0;
	var run_task       = true;
	var users          = { 'role' : '', 'type' : '', 'amount' : 0, 'log' : '', 'original_date' : true };

	var run_this_task  = function( offset ) {

		if ( run_task === false ) return false;

		$.ajax({
			type : "POST",
			data : {
				action     : '<?php echo esc_attr( self::tool ) ; ?>',
				_token     : '<?php echo esc_attr( wp_create_nonce( self::tool ) ); ?>',
				task       : users,
				offset     : offset
			},
			dataType   : "JSON",
			url        : '<?php echo esc_attr( admin_url( 'admin-ajax.php' ) ); ?>',
			success    : function( response ) {

				if ( response.success === undefined ) {
					alert( 'Lost communication.' );
				}
				else {

					console.log( response.data );
					var processed   = parseInt( response.data.processed );

					task_completed += processed;
					task_size      -= processed;

					var completed_el = parseInt( $( '#tak-report-completed' ).text() );
					var completed    = parseInt( response.data.report.completed );
					$( '#tak-report-completed' ).empty().text( completed_el + completed );

					var excluded_el  = parseInt( $( '#tak-report-excluded' ).text() );
					var excluded     = parseInt( response.data.report.excluded );
					$( '#tak-report-excluded' ).empty().text( excluded_el + excluded );

					var originaltotal = task_size + task_completed;
					var progress      = parseInt( ( task_completed / originaltotal ) * 100 );
					if ( progress > 100 ) progress = 100;

					$( '#progress-indicator #progress-bar' ).css({ 'width' : progress + '%' });

					if ( response.success && ! response.data.finished )
						run_this_task( task_completed );

					else {
					
						if ( response.success && response.data.finished ) {
							$( '#progress-indicator #progress-bar' ).hide();
							$( '#progress-indicator #progress-end' ).addClass( 'success' ).empty().text( 'Task completed' ).show();
							$( '#completed-actions' ).empty().html( response.data.actions );
						}

						else if ( ! response.success ) {
							$( '#progress-indicator #progress-bar' ).hide();
							$( '#progress-indicator #progress-end' ).addClass( 'error' ).empty().text( response.data ).show();
						}

						run_task = false;

						$( '#stop-tool-action' ).hide();

					}

				}

			}
		});

	};

	$(document).ready(function(){

		$( '#select-user-type' ).change(function(){

			var selectedstatus = $(this).find( ':selected' );
			if ( selectedstatus === undefined || selectedstatus.val() == '' ) {

				$( '#import-action' ).hide();
				return false;

			}

			$( '#user-log-template' ).val( selectedstatus.data( 'template' ) );

			$( '#import-action' ).show();

			$( '#user-count h5' ).empty().text( selectedstatus.data( 'count' ) );
			$( '#tak-report-total' ).empty().text( selectedstatus.data( 'count' ) );

			task_size = parseInt( selectedstatus.data( 'count' ) );
			console.log( 'Task size: ' + task_size );

		});

		$( '#import-upload-form' ).on( 'submit', function(e){

			if ( $( '#user-log-amount' ).val() == '' ) {
				alert( 'You must enter an amount.' );
				return false;
			}

			e.preventDefault();

			$( '#import-action' ).hide();
			$( '#cancel-task' ).removeAttr( 'disabled' );
			

			$( '#select-user-type' ).attr( 'disabled', 'disabled' );
			$( '#user-log-type' ).attr( 'disabled', 'disabled' );
			$( '#user-log-amount' ).attr( 'disabled', 'disabled' );
			$( '#user-log-template' ).attr( 'disabled', 'disabled' );
			$( '#user-original-date' ).attr( 'disabled', 'disabled' );

			users.role          = $( '#select-user-type' ).find( ':selected' ).val();
			users.type          = $( '#user-log-type' ).find( ':selected' ).val();
			users.amount        = $( '#user-log-amount' ).val();
			users.log           = $( '#user-log-template' ).val();
			users.original_date = $( '#user-original-date' ).is( ':checked' );

			run_task = true;

			$( '#task-status' ).show();
			$( '#progress-indicator #progress-end' ).removeClass( 'success error' ).hide();
			$( '#progress-indicator #progress-start' ).hide();
			$( '#progress-indicator #progress-bar' ).css( 'width', '0% !important' ).show();

			run_this_task( 0 );

		});

		$( '#cancel-task' ).click(function(){

			run_task = false;

			$(this).attr( 'disabled', 'disabled' ).text( 'Refresh page to start over.' );

			users = { 'role' : '', 'type' : '', 'amount' : 0, 'log' : '', 'original_date' : false };

		});

	});

});
</script>
<?php

			}

			?>
</div>
<?php
		}

		/**
		 * AJAX Handler
		 * @since 1.0
		 * @version 1.0
		 */
		static function ajax_handler() {

			check_ajax_referer( self::tool, '_token' );

			$args = shortcode_atts( array(
				'role'          => 'subscriber',
				'type'          => MYCRED_DEFAULT_TYPE_KEY,
				'amount'        => 0,
				'log'           => '',
				'original_date' => 'true'
			), array_map( 'sanitize_text_field', $_POST['task'] ) );

			$args['role']          = sanitize_key( $args['role'] );
			$args['type']          = sanitize_key( $args['type'] );

			if ( ! mycred_point_type_exists( $args['type'] ) ) {
				wp_send_json_error( __( 'Selected point type does not exist. Please refresh this page and try again.', 'mycred-toolkit' ) );
			}

			$mycred                = mycred( $args['type'] );

			$args['amount']        = $mycred->number( $args['amount'] );

			if ( $args['amount'] == $mycred->zero() ) {
				wp_send_json_error( __( 'Amount can not be zero. Please refresh this page and try again.', 'mycred-toolkit' ) );
			}

			$args['log']           = sanitize_text_field( $args['log'] );

			$now                   = current_time( 'timestamp' );
			$number                = absint( MYCRED_RETRO_MAX );
			$offset                = absint( $_POST['offset'] );

			$format                = '%s';
			if ( $mycred->format['decimals'] > 0 ) {
				$format = '%f';

			} elseif ( $mycred->format['decimals'] == 0 ) {
				$format = '%d';
			}

			global $wpdb;

			if ( defined( 'MYCRED_LOG_TABLE' ) ) {
				$log_table = MYCRED_LOG_TABLE;

			} elseif ( mycred_centralize_log() ) {

					$log_table = $wpdb->base_prefix . 'myCRED_log';
			} else {
$log_table = $wpdb->prefix . 'myCRED_log';

			}

			$report                = array(
				'completed' => 0,
				'excluded' => 0
			);
			$processed             = 0;

			$blog_id = 0;
			if ( ! mycred_centralize_log() ) {
				$blog_id = get_current_blog_id();
			}

			$users_of_role        = $wpdb->get_results( $wpdb->prepare( "
				SELECT DISTINCT users.ID , users.user_registered  
				FROM {$wpdb->users} users 
					LEFT JOIN {$wpdb->usermeta} roles ON ( users.ID = roles.user_id AND roles.meta_key = %s ) 
				WHERE users.user_registered != '' 
					AND roles.meta_value LIKE %s 
				ORDER BY users.ID ASC 
				LIMIT %d,%d;", $wpdb->get_blog_prefix( $blog_id ) . 'capabilities', '%"' . $args['role'] . '"%', $offset, $number ) );

			if ( ! empty( $users_of_role ) ) {
				foreach ( $users_of_role as $user ) {

					$user_id = absint( $user->ID );
					if ( $mycred->exclude_user( $user_id ) ) {

						$report['excluded']++;
						$processed++;

						continue;

					}

					$report['completed']++;

					$mycred->update_users_balance( $user_id, $args['amount'], $args['type'] );

					if ( $args['log'] != '' ) {

						$time = ( $args['original_date'] === 'true' ) ? strtotime( $user->user_registered, $now ) : $now;

						// Insert into DB
						$wpdb->insert(
							$log_table,
							array(
								'ref'     => 'registration',
								'ref_id'  => $user_id,
								'user_id' => $user_id,
								'creds'   => $args['amount'],
								'ctype'   => $args['type'],
								'time'    => $time,
								'entry'   => $args['log'],
								'data'    => serialize( array( 'ref_type' => 'user' ) )
							),
							array( '%s', '%d', '%d', $format, '%s', '%d', '%s', '%s' )
						);

					}

					$processed++;

				}
			}

			$finished = false;
			$actions  = '';
			if ( $processed < $number ) {

				$finished  = true;
				$actions   = array();
				$admin_url = admin_url( 'admin.php' );

				$page      = MYCRED_SLUG;
				if ( $args['type'] != MYCRED_DEFAULT_TYPE_KEY ) {
					$page .= '_' . $args['type'];
				}

				$actions[] = '<a href="' . add_query_arg( array(
					'page' => $page,
					'ref' => 'registration'
				), $admin_url ) . '" class="button button-secondary">' . __( 'View Log Entries', 'mycred-toolkit' ) . '</a>';
				$actions[] = '<a href="' . add_query_arg( array( 'import' => self::tool ), $admin_url ) . '" class="button button-secondary">' . __( 'Reload Tool', 'mycred-toolkit' ) . '</a>';

				$actions = implode( ' ', $actions );

			}

			wp_send_json_success( array(
				'processed' => $processed,
				'finished'  => $finished,
				'actions'   => $actions,
				'report'    => $report,
				'task'      => array_map( 'sanitize_text_field', $_POST['task'] )
			) );
		}
	}
endif;
