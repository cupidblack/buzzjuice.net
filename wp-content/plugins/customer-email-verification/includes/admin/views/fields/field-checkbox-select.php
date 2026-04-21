<?php
/**
 * Checkbox Select Field Template
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$default_value = isset( $field['Default'] ) ? $field['Default'] : 1;
$is_checked    = (bool) cev_pro()->function->cev_pro_admin_settings( $field_id, $default_value );
$checked_attr  = $is_checked ? 'checked' : '';
?>
<label class="<?php echo esc_attr( $disabled ); ?>" for="<?php echo esc_attr( $field_id ); ?>">
	<input 
		type="checkbox" 
		id="<?php echo esc_attr( $field_id ); ?>" 
		name="<?php echo esc_attr( $field_id ); ?>" 
		value="1" 
		<?php echo esc_attr( $checked_attr ); ?> 
		<?php echo esc_attr( $disabled ); ?>
	/>
	<span class="label">
		<?php echo esc_html( $field['title'] ); ?>
		<?php if ( ! empty( $field['select'] ) ) : ?>
			<select 
				name="<?php echo esc_attr( $field['select']['id'] ); ?>" 
				id="<?php echo esc_attr( $field['select']['id'] ); ?>" 
				style="width: auto;" 
				<?php echo esc_attr( $disabled ); ?>
			>
				<?php
				$select_id = $field['select']['id'];
				$selected_value = cev_pro()->function->cev_pro_admin_settings( $select_id, '' );

				foreach ( $field['select']['options'] as $option_key => $option_value ) {
					$selected = ( (string) $selected_value === (string) $option_key ) ? 'selected' : '';
					?>
					<option value="<?php echo esc_attr( $option_key ); ?>" <?php echo esc_attr( $selected ); ?>>
						<?php echo esc_html( $option_value ); ?>
					</option>
					<?php
				}
				?>
			</select>
		<?php endif; ?>
	</span>
</label>

