<?php
/**
 * Textarea Field Template
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$default_value = isset( $field['Default'] ) ? $field['Default'] : '';
$current_value = cev_pro()->function->cev_pro_admin_settings( $field_id, $default_value );
$placeholder   = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
?>
<fieldset>
	<textarea 
		placeholder="<?php echo esc_attr( $placeholder ); ?>" 
		class="input-text regular-input" 
		name="<?php echo esc_attr( $field_id ); ?>" 
		id="<?php echo esc_attr( $field_id ); ?>"
	><?php echo esc_textarea( $current_value ); ?></textarea>
</fieldset>

