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

		$af_role_change_history = (array) get_user_meta( $customer_id, 'af_arc_data', true );

		$af_role_change_history = $this->af_urs_unique_array($af_role_change_history);
		?>

		<h3><?php esc_html_e( 'User Role Change history', 'addify_arc' ); ?></h3>
		
		<table class="form-table">

			<thead>
				
				<tr style="font-size: 14px;">
				
					<th style="text-align: center; border: 1px solid;"> 
						<?php echo esc_html__( 'Switch From', 'addify_arc' ); ?>
					</th>
				
					<th style="text-align: center; border: 1px solid;"> 
						<?php echo esc_html__( 'Switch To', 'addify_arc' ); ?> 
					</th>
				
					<th style="text-align: center; border: 1px solid;"> 
						<?php echo esc_html__( 'Reason to Change', 'addify_arc' ); ?> 
					</th>
				
					<th style="text-align: center; border: 1px solid;"> 
						<?php echo esc_html__( 'Current Role Change Date ', 'addify_arc' ); ?> 
					</th>
				
					<th style="text-align: center; border: 1px solid;"> 
						<?php echo esc_html__( 'Total Days For Role', 'addify_arc' ); ?> 
					</th>
				
					<th style="text-align: center; border: 1px solid;"> 
						<?php echo esc_html__( 'Role Start Date ', 'addify_arc' ); ?> 
					</th>
				
					<th style="text-align: center; border: 1px solid;"> 
						<?php echo esc_html__( 'Role End Date ', 'addify_arc' ); ?> 
					</th>
				
				</tr>

			</thead>
		
			<tbody>
				
				<?php

				if ( ! empty( $af_role_change_history ) ) {
				
					foreach ( $af_role_change_history as $af_role_his ) {

						$switch_from_role = isset( $af_role_his['switch_from_role'] ) ? ucwords( str_replace( '_', ' ', $af_role_his['switch_from_role'] ) ) : '';

						$arc_date_start = isset( $af_role_his['arc_date_start'] ) && ! empty( $af_role_his['arc_date_start'] ) ? gmdate('d-m-Y', strtotime( $af_role_his['arc_date_start'] ) ) : 'Not Set';

						$arc_end_start = '';

						$arc_end_start = isset( $af_role_his['arc_end_start'] ) && ! empty( $af_role_his['arc_end_start'] ) ? gmdate('d-m-Y', strtotime( $af_role_his['arc_end_start'] ) ) : 'Not Set';

						if ( ! isset( $af_role_his['switch_to_role'] )  ) {
							continue;
						}
						if ( isset( $af_role_his['switch_to_role'] ) ) {

							$array = explode(',', $af_role_his['switch_to_role']);

							$array = array_map(function ( $word ) {
								return ucfirst(trim($word));
							}, $array);

							$switch_to_role = implode(' , ', $array);
						}

						if ( isset( $af_role_his['reason_to_change'] ) ) {
								
							$reason_to_change = ucwords( str_replace( '_', ' ', $af_role_his['reason_to_change'] ) );

							switch ( $af_role_his['reason_to_change'] ) {

								case 'purchase_product':
									$reason_to_change = 'Purchase Specific Product';
									break;

								case 'number_products':
									$reason_to_change = 'Purchase Specific Number of Product';
									break;

								case 'price_range':
									$reason_to_change = 'Order Subtotal within Range';
									break;

								case 'total_spend':
									$reason_to_change = 'Customer Total Spend';
									break;

								case 'email_domain_v':
									$reason_to_change = 'Email Domain';
									break;

								case 'product_cat_tag':
									$reason_to_change = 'Specific Category/Tag Purchase';
									break;

								case 'sub_prod':
									$reason_to_change = 'Specific Subscription Purchase';
									break;

								case 'memberships':
									$reason_to_change = 'Specific Membership Purchase';
									break;
							}
						}

						$date_changed = isset( $af_role_his['date_changed'] ) ? gmdate('d-m-Y', strtotime( $af_role_his['date_changed'] ) ) : '';

						$switch_for_total_days = isset( $af_role_his['switch_for_total_days'] ) ? $af_role_his['switch_for_total_days'] : 'Not Set';
				
						?>
				
						<tr style="font-size: 14px;">
				
							<td style="text-align: center; border: 1px solid">
								<?php echo esc_attr( $switch_from_role ); ?>
							</td>

							<td style="text-align: center; border: 1px solid">
								<?php echo esc_attr( $switch_to_role ); ?>
							</td>
				
							<td style="text-align: center; border: 1px solid">
								<?php echo esc_attr( $reason_to_change ); ?>
							</td>

							<td style="text-align: center; border: 1px solid">
								<?php echo esc_attr( $date_changed ); ?>
							</td>

							<td style="text-align: center; border: 1px solid">
								<?php

								if (( 'sub_prod' != $af_role_his['reason_to_change'] ) && ( 'memberships' != $af_role_his['reason_to_change'] ) ) {
									echo esc_attr( $switch_for_total_days );
								} 
								?>
							</td>

							<td style="text-align: center; border: 1px solid">
								<?php echo esc_attr( $arc_date_start ); ?>
							</td>

							<td style="text-align: center; border: 1px solid">
								<?php 
								if ('sub_prod' != $af_role_his['reason_to_change'] && 'memberships' != $af_role_his['reason_to_change'] ) {

									echo esc_attr( $arc_end_start ); 
								}
								?>
							</td>
						
						</tr>
						
						<?php
					
					}
				
				} else {
				
					?>
				
					<tr><td colspan="7" style="text-align: center; font-weight: bold;"><?php esc_html_e( 'No History Available.', 'addify_arc' ); ?></td></tr>
				
					<?php
				
				}
				
				?>
			
			</tbody>
		
		</table>
		
		<?php
		
		$user_roles = (array) $wp_roles->roles;

		$roles =  $wp_roles->get_names();

		$user = get_user_by( 'id', $customer_id );

		$new_adu_role = $user->roles;

		?>

		<table class="form-table">
		
			<tbody>
		
				<h2><?php esc_html_e( 'Roles', 'members' ); ?></h2>
		
				<tr>
		
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

		$new_user_roles = isset( $_POST['new_addtional_user_role'] ) ? sanitize_meta( '', $_POST['new_addtional_user_role'], '') : array();

		if ( empty( $new_user_roles ) ) {
			
			return;
		}

		update_user_meta( $user_id, 'new_addtional_user_role', $new_user_roles );
	}

	public function update_user_role() {

		if ( ! current_user_can('list_users') ) {
			
			wp_die('You are not allowed to access users!');
		}

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