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

if ( ! function_exists( 'myCred_ZA_field_name' ) ) :
	function myCred_ZA_field_name( $type, $attr ) {

		$hook_prefs_key = 'mycred_pref_hooks';

		if ( $type != MYCRED_DEFAULT_TYPE_KEY ) {
			$hook_prefs_key = 'mycred_pref_hooks_' . $type;
		}

		return "{$hook_prefs_key}[hook_prefs][mycred_zoom][{$attr}][]";
	}
endif;

if ( ! function_exists( 'myCred_ZA_Hook_Setting' ) ) :
	function myCred_ZA_Hook_Setting( $data, $obj ) {

		foreach ( $data as $hook ) {

			
			$args = array(
				'post_type'   => 'zoom-meetings'
			);
			$list_of_meeting_post_ids = get_posts( $args );
			$meeting_data = array();
			if (!empty($list_of_meeting_post_ids)) {
				foreach ($list_of_meeting_post_ids as $id) {
					$post_id = $id->ID; 
					$post_title = $id->post_title;  
					$meeting_data[] = array(
						'meeting_id'=>$post_id,
						'meeting_title'=>$post_title
					);
				}
			} 
			
			$meeting_options = '<option value="999999">ALL</option>';
			if ( ! empty( $meeting_data ) ) {
				foreach ( $meeting_data as $meetings) {
					$meeting_options .= '<option value="' . $meetings['meeting_id'] . '" ' . selected( $hook['zoom_id'], $meetings['meeting_id'], false ) . ' >' . $meetings['meeting_title'] . '</option>';
				}
			}
			$myCred_Type_Options = '';
			$types = mycred_get_types();
			if (!empty($types)) {
				foreach ( $types as $type => $label ) {
					$myCred_Type_Options .= '<option value="' . $type . '">' . $label . '</option>';
				}
			}
			
			?>
		<div class="hook-instance">
			
			<div class="row">
				<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
					<div class="form-group">
						<label><?php echo esc_html($obj->core->plural()); ?></label>
						<input type="text" name="<?php echo esc_attr(myCred_ZA_field_name( $obj->mycred_type, 'creds' )); ?>" value="<?php echo esc_attr($obj->core->number( $hook['creds'] )); ?>" class="form-control mycred-za-creds" />
					</div>
				</div>
				<div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
					<div class="form-group">
						<label for="<?php echo esc_attr($obj->field_id( 'limit' )); ?>"><?php esc_html_e( 'Limit', 'mycred-toolkit' ); ?></label>
						<?php 
						$limit_name = myCred_ZA_field_name($obj->mycred_type, 'limit');
						echo wp_kses($obj->hook_limit_setting( $limit_name, $obj->field_id( 'limit' ), $hook['limit']),
						array(
							'div' => array(
								'class' => array(),
							),
							'input' => array(
								'class' => array(),
								'type' => array(),
								'name' => array(),
								'id' => array(),
								'size' => array(),
								'value' => array()
							),
							'select' => array(
								'name'  => array(),
								'class' => array(),
								'id' => array(),
							),
							'option' => array(
								'value' => array()
							),
						)
						)
						
						;
						?>
					</div>
				</div>
				<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
					<div class="form-group">
						<label><?php esc_html_e( 'Log Template', 'mycred-toolkit' ); ?></label>
						<input type="text" name="<?php echo esc_attr(myCred_ZA_field_name( $obj->mycred_type, 'log' )); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $hook['log'] ); ?>" class="form-control mycred-za-log" />
						<span class="description"><?php echo esc_html($obj->available_template_tags( array( 'general' ) )); ?></span>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
					<div class="form-group">
						<label><?php esc_html_e( 'Select Zoom Meeting', 'mycred-toolkit' ); ?></label>
						<select class="form-control mycred-za-meeting-id" name="<?php echo esc_attr(myCred_ZA_field_name( $obj->mycred_type, 'zoom_id' )); ?>">
							<?php echo esc_html($meeting_options); ?>
						</select>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
					<div class="form-group specific-hook-actions textright">
						<button class="button button-small mycred-add-za-specific-hook" type="button">Add More</button>
						<button class="button button-small mycred-za-remove-specific-hook" type="button">Remove</button>
					</div>
				</div>
			</div>
		</div>
	<?php
		}
	}
endif;



if ( ! function_exists( 'myCred_ZA_Arrange_Data' ) ) :
	function myCred_ZA_Arrange_Data( $data ) {
		$hook_data = array();
		foreach ( $data['zoom_id'] as $key => $value ) {
			$hook_data[ $key ]['creds']      = $data['creds'][ $key ];
			$hook_data[ $key ]['limit'] = $data['limit'][ $key ];
			$hook_data[ $key ]['log'] = $data['log'][ $key ];
			$hook_data[ $key ]['zoom_id'] = $value;
		}
		return $hook_data;
	}
endif;


