<?php
/**
 * Toggle Field Template
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$default_value = isset( $field['Default'] ) ? $field['Default'] : '';
$is_checked    = (bool) cev_pro()->function->cev_pro_admin_settings( $field_id, $default_value );
$checked_attr  = $is_checked ? 'checked' : '';
?>
<div class="accordion-toggle">
	<input 
		class="tgl tgl-flat-cev" 
		id="<?php echo esc_attr( $field_id ); ?>" 
		name="<?php echo esc_attr( $field_id ); ?>" 
		type="checkbox" 
		value="1" 
		<?php echo esc_attr( $checked_attr ); ?>
	/>
	<label class="tgl-btn tgl-panel-label" for="<?php echo esc_attr( $field_id ); ?>"></label>
</div>
<label class="settings_label">
	<?php echo esc_html( $field['title'] ); ?>
</label>

