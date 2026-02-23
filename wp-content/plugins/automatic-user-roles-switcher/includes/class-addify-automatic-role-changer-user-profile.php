<?php

defined('ABSPATH') || exit;

class Addify_Automatic_Role_Changer_User_Profile {

	
	public function __construct() {
		
		add_action( 'edit_user_profile', array( $this, 'arc_extra_user_profile_fields' ) );
		
		add_action( 'edit_user_profile_update', array( $this, 'update_user_profile_fields' ) );

		add_action( 'wp_loaded', array( $this, 'update_user_role' ), 100 );
	}

	public function af_urs_unique_array( $arrays ) {
		
		$uniqueArrays = array();
		
		$serializedArrays = array();
		
		foreach ($arrays as $innerArray) {
		
			$serialized = serialize($innerArray);
			
			if (!isset($serializedArrays[ $serialized ])) {
		
				$serializedArrays[ $serialized ] = true;
		
				$uniqueArrays[] = $innerArray;
			}
		}
		
		return $uniqueArrays;
	}

	public function arc_extra_user_profile_fields() {
		
		global $post, $wp_roles;

		if ( isset( $_GET['user_id'] ) ) {

			$customer_id = intval( $_GET['user_id'] );

		} elseif ( IS_PROFILE_PAGE ) {

			$customer_id = wp_get_current_user()->ID;

		} else {

			return;
		}

		?><h3><?php echo esc_html_e('User Role Change history', 'addify_arc' ); ?></h3>
		<?php

		af_urs_display_log($customer_id);
		?>
		<?php
		
		$user_roles = (array) $wp_roles->roles;

		$roles =  $wp_roles->get_names();
		
		$user = get_user_by( 'id', $customer_id );
		
		$new_adu_role = $user->roles;

		?>

		<table class="form-table">
		
			<tbody>
		
				<h2><?php esc_html_e( 'Roles', 'members' ); ?></h2>
		
				<tr style="font-size:5px !important;">
		
					<th><?php esc_html_e( 'User Roles', 'members' ); ?></th>

					<td>
		
						<div class="wp_back_color">
		
							<ul>
		
								<?php foreach ( $roles as $key => $role ) : ?>
		
									<li>
		
										<label>
		
											<input type="checkbox" name="new_addtional_user_role[]" id="new_addtional_user_role" value="<?php echo esc_attr($key ); ?>"
												<?php if (in_array( $key, (array) $new_adu_role )) : ?>
													checked
												<?php endif ?>
											><?php echo esc_attr( $role ); ?>

										
										</label>
										
										<br>
									
									</li>

								<?php endforeach; ?>
							
							</ul>
							
						</div>
						
					</td>
					
				</tr>
			
			</tbody>
			
		</table>
		
		<?php
		wp_nonce_field( 'af-arc-update-user-nonce-action', 'af-arc-update-user-nonce' );
	}

	/**
	 * Callback function for handling user role changes.  Note that we needed to execute this function.
	 *
	 * @param  int    $user_id
	 */
	public function update_user_profile_fields( $user_id ) {

		if ( ! current_user_can('list_users') ) {
			wp_die('You are not allowed to access users!');
		}

		$nonce = isset( $_POST['af-arc-update-user-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['af-arc-update-user-nonce'] ) ) : 0;

		if ( ! wp_verify_nonce( $nonce, 'af-arc-update-user-nonce-action' ) ) {

			wp_die( 'Sorry, your nonce did not verify.' );
		
		}

		$new_user_roles = isset( $_POST['new_addtional_user_role'] ) ? sanitize_meta( '', wp_unslash($_POST['new_addtional_user_role']), '') : array();

		if ( empty( $new_user_roles ) ) {
			
			return;
		}

		update_user_meta( $user_id, 'new_addtional_user_role', $new_user_roles );
	}

	public function update_user_role() {
		
		// if ( ! current_user_can('list_users') ) {
			
		// 
		//  // wp_die('You are not allowed to access users!');
		// }


		if ( isset($_GET['user_id'])) {

			$user_id = sanitize_text_field( wp_unslash($_GET['user_id'] ) );

		}

		if ( empty( $_GET['user_id'] ) || empty( $_GET['updated'] ) ) {
			return;
		}       

		$new_user_roles = get_user_meta( $user_id, 'new_addtional_user_role', true);

		if ( empty( $new_user_roles ) ) {
			return;
		}

		$user_object = get_user_by('id', $user_id);

		$user_roles = $user_object->roles;


		foreach ( $user_roles as $user_role ) {

			if ( !in_array( $user_role, $new_user_roles ) ) {

				$user_object->remove_role( $user_role );

			}
		}       

		foreach ( $new_user_roles as $add_user_role ) {

			if ( !in_array( $add_user_role, $user_roles ) ) {

				$user_object->add_role( $add_user_role );

			}
		}
	}
}

new Addify_Automatic_Role_Changer_User_Profile();