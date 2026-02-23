<?php
defined('ABSPATH') || exit;

function af_urs_unique_array( $arrays ) {
		
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
function af_urs_display_log( $user_ids ) {

	$af_role_change_history = (array) get_user_meta( $user_ids, 'af_arc_data', true );
	$af_role_change_history = af_urs_unique_array($af_role_change_history);

	// $remove_scroll = 'white-space: nowrap;';
	// if(is_admin()){
	//  $remove_scroll = '';
	// }
	?>
	<div style="padding:10px;overflow-x: auto;overflow-y: hidden;white-space: nowrap;" class="af_sr_log_tab_table">
		<table class=" widefat table-view-list af_arc_log_table">

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

						$arc_end_start = isset( $af_role_his['arc_end_start'] ) && ! empty( $af_role_his['arc_end_start'] ) ? $af_role_his['arc_end_start'] : 'Not Set';

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
							$reason_to_change = '';
							
							if (isset($af_role_his['reason_to_change']) && is_array($af_role_his['reason_to_change'])) {

								foreach ($af_role_his['reason_to_change'] as $message_key) {

									switch ( $message_key ) {

										case 'purchase_product':
											$reason_to_change .= 'Purchase Specific Product - ';
											break;

										case 'number_products':
											$reason_to_change .= 'Purchase Specific Number of Product - ';
											break;

										case 'price_range':
											$reason_to_change .= 'Order Subtotal within Range - ';
											break;

										case 'total_spend':
											$reason_to_change .= 'Customer Total Spend - ';
											break;

										case 'email_domain_v':
											$reason_to_change .= 'Email Domain - ';
											break;

										case 'product_cat_tag':
											$reason_to_change .= 'Specific Category- Tag Purchase - ';
											break;

										case 'sub_prod':
											$reason_to_change .= 'Specific Subscription Purchase - ';
											break;

										case 'memberships':
											$reason_to_change .= 'Specific Membership Purchase - ';
											break;
									}
								}

							} else {
								
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
						}

						$date_changed = isset( $af_role_his['date_changed'] ) ? gmdate('d-m-Y', strtotime( $af_role_his['date_changed'] ) ) : '';

						$switch_for_total_days = isset( $af_role_his['switch_for_total_days'] ) ? $af_role_his['switch_for_total_days'] : 'Not Set';
				
						?>
				
						<tr style="font-size: 11px;">
				
							<td style="text-align: center; border: 1px solid">
								<?php echo esc_attr( $switch_from_role ); ?>
							</td>

							<td style="text-align: center; border: 1px solid">
								<?php echo esc_attr( $switch_to_role ); ?>
							</td>
				
							<td style="text-align: center; border: 1px solid">
								<?php echo esc_attr( rtrim( $reason_to_change, ' -' ) ); ?>
							</td>

							<td style="text-align: center; border: 1px solid">
								<?php echo esc_attr( $date_changed ); ?>
							</td>

							<td style="text-align: center; border: 1px solid">
								<?php

								// if (( 'sub_prod' != $af_role_his['reason_to_change'] ) && ( 'memberships' != $af_role_his['reason_to_change'] ) ) {
								// } 
								echo esc_attr( $switch_for_total_days );
								?>
							</td>

							<td style="text-align: center; border: 1px solid">
								<?php echo esc_attr( $arc_date_start ); ?>
							</td>

							<td style="text-align: center; border: 1px solid">
								<?php 
								// if ('sub_prod' != $af_role_his['reason_to_change'] && 'memberships' != $af_role_his['reason_to_change'] ) {
								// }
								echo esc_attr( $arc_end_start ); 
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
	</div>
		<?php
}