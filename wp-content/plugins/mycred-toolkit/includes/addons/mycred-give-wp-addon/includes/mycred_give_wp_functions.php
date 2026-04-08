<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
 * Check Page
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'is_mycred_hook_page' ) ) :
	function is_mycred_hook_page( $page ) {
		return ( strpos( $page, 'mycred' ) !== false && strpos( $page, 'hook' ) !== false );
	}
endif;

if ( ! function_exists( 'myCred_GWP_field_name' ) ) :
	function myCred_GWP_field_name( $type, $attr ) {

		$hook_prefs_key = 'mycred_pref_hooks';

		if ( $type != MYCRED_DEFAULT_TYPE_KEY ) {
			$hook_prefs_key = 'mycred_pref_hooks_' . $type;
		}

		return "{$hook_prefs_key}[hook_prefs][mycred_give_wp_multiple][{$attr}][]";
	}
endif;

if ( ! function_exists( 'myCred_GWP_Hook_Setting' ) ) :
	function myCred_GWP_Hook_Setting( $data, $obj ) {
		foreach ( $data as $hook ) {
			
			$form_data = gwp_content_posts();
			$gwp_form_options = '<option value="999999">ALL</option>';
			if ( ! empty( $form_data ) ) {
				foreach ( $form_data as $forms) {
					$gwp_form_options .= '<option value="' . $forms['form_id'] . '" ' . selected( $hook['gwp_form_id'], $forms['form_id'], false ) . ' >' . $forms['form_title'] . '</option>';
				}
			}
			
			
			?>
		<div class="hook-instance">
			<div class="row">
				<h3 class="description">Points for make donation from specific form</h3>
				<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
					<div class="form-group">
						<label><?php echo $obj->core->plural(); ?></label>
						<input type="text" name="<?php echo myCred_GWP_field_name( $obj->mycred_type, 'creds' ); ?>" value="<?php echo $obj->core->number( $hook['creds'] ); ?>" class="form-control mycred-gwp-creds" />
					</div>
				</div>
				<div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
					<div class="form-group">
						<label for="<?php echo $obj->field_id( 'limit' ); ?>"><?php esc_html_e( 'Limit', 'mycred-toolkit' ); ?></label>
						<?php 
						$limit_name = myCred_GWP_field_name($obj->mycred_type, 'limit');
						echo $obj->hook_limit_setting( $limit_name, $obj->field_id( 'limit' ), esc_attr($hook['limit'] ) );
						?>
					</div>
				</div>
				<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
					<div class="form-group">
						<label><?php esc_html_e( 'Log Template', 'mycred-toolkit' ); ?></label>
						<input type="text" name="<?php echo myCred_GWP_field_name( $obj->mycred_type, 'log' ); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $hook['log'] ); ?>" class="form-control mycred-gwp-log" />
						<span class="description"><?php echo $obj->available_template_tags( array( 'general' ) ); ?></span>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
					<div class="form-group">
						<label><?php esc_html_e( 'Enter minimum amount', 'mycred-toolkit' ); ?></label>
						<input type="text" name="<?php echo myCred_GWP_field_name( $obj->mycred_type, 'gwp_minimum_amount' ); ?>" placeholder="<?php esc_html_e( '0', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $hook['gwp_minimum_amount'] ); ?>" class="form-control mycred-gwp-minimum-amount" />
						<span class="description">Enter '0', if you don't want to set minimum amount</span>
					</div>
				</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-8">
					<div class="form-group">
						<label><?php esc_html_e( 'Select GiveWP form', 'mycred-toolkit' ); ?></label>
						<select class="form-control mycred-gwp-form-id" name="<?php echo myCred_GWP_field_name( $obj->mycred_type, 'gwp_form_id' ); ?>">
							<?php echo wp_kses_post( $gwp_form_options ) ; ?>
						</select>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
					<div class="form-group specific-hook-actions textright">
						<button class="button button-small mycred-add-gwp-specific-hook" type="button">Add More</button>
						<button class="button button-small mycred-gwp-remove-specific-hook" type="button">Remove</button>
					</div>
				</div>
			</div>
		</div>
	<?php
		}
	}
endif;
if ( ! function_exists( 'gwp_content_posts' ) ) :
	function gwp_content_posts() {
			/**
			* Select Give Wp Forms
			**/
			$args = array(
				'post_type'   => 'give_forms',
				'post_status'   => 'publish',
			);
			$list_of_meeting_post_ids = get_posts( $args );
			$form_data = array();
			if (!empty($list_of_meeting_post_ids)) {
				foreach ($list_of_meeting_post_ids as $id) {
					$post_id = $id->ID; 
					$post_title = $id->post_title;  
					$form_data[] = array(
						'form_id'=>$post_id,
						'form_title'=>$post_title
					);
				}
			}
		return $form_data;
	}
endif;
if ( ! function_exists( 'myCred_GWP_Arrange_Data' ) ) :
	function myCred_GWP_Arrange_Data( $data ) {
		$hook_data = array();
		foreach ( $data['gwp_form_id'] as $key => $value ) {
			$hook_data[ $key ]['creds']      = $data['creds'][ $key ];
			$hook_data[ $key ]['limit'] = $data['limit'][ $key ];
			$hook_data[ $key ]['log'] = $data['log'][ $key ];
			$hook_data[ $key ]['gwp_minimum_amount'] = $data['gwp_minimum_amount'][ $key ];
			$hook_data[ $key ]['gwp_form_id'] = $value;
		}
		return $hook_data;
	}
endif;

/**
* GiveWP Badge Functions 
**/
if ( ! function_exists( 'gwp_badge_requirement' ) ) :
	function gwp_badge_requirement( $query, $requirement_id, $requirement, $having, $user_id ) {
		global $wpdb, $mycred_log_table;
		if ($requirement['reference'] == 'mycred_give_wp_multiple' && ! empty( $requirement['specific'] ) && $requirement['specific'] != 'Any') { 
			$query = $wpdb->get_var( $wpdb->prepare( "SELECT {$having} FROM {$mycred_log_table} WHERE ctype = %s AND ref = %s OR ref = %s AND ref_id = %d AND user_id = %d;", $requirement['type'], $requirement['reference'], 'mycred_give_wp_multiple', $requirement['specific'], $user_id ) );
		}
		return $query;
	}
endif;

if ( ! function_exists( 'gwp_badge_template' ) ) :
	function gwp_badge_template( $data, $requirement_id, $requirement, $badge, $level ) {
		if ( $requirement['reference'] == 'mycred_give_wp_multiple' && ! empty( $requirement['specific'] ) ) { 
			
			$form_data = gwp_content_posts();
			$gwp_form_options = '<option value="999999">ALL</option>';
			foreach ( $form_data as $forms ) {
				$gwp_form_options .= '<option value="' . $forms['form_id'] . '"' . selected( $requirement['specific'], $forms['form_id'], false ) . '>' . $forms['form_title'] . '</option>';
			}
			$data = '<div class="form-group"><select name="mycred_badge[levels][' . $level . '][requires][' . $requirement_id . '][specific]" class="form-control specific" data-row="' . $requirement_id . '" >' . $gwp_form_options . '</select></div>';

		}
		return $data;
	}
endif;

if ( ! function_exists( 'gwp_admin_header' ) ) :
	function gwp_admin_header() {
		$screen = get_current_screen();
		
		if ( defined('MYCRED_BADGE_KEY') && $screen->id == MYCRED_BADGE_KEY) :
			?>
		<script type="text/javascript">
		<?php
			
			$form_data = gwp_content_posts();
			$gwp_form_options = '<option value="999999">ALL</option>';
			foreach ( $form_data as $forms ) {
				$gwp_form_options .= '<option value="' . $forms['form_id'] . '">' . $forms['form_title'] . '</option>';
			}
			
			$data = '<div class="form-group"><select name="{{element_name}}" class="form-control" data-row="{{reqlevel}}" >' . $gwp_form_options . '</select></div>';
			echo "var mycred_badge_mycred_give_wp_multiple = '" . wp_kses_post( $data ) . "';";
			?>
		</script>
		<?php
		endif;
	}
endif;

