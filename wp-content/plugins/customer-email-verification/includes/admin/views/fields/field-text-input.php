<?php
/**
 * Text Input Field Template
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
	<input 
		class="input-text regular-input" 
		type="text" 
		name="<?php echo esc_attr( $field_id ); ?>" 
		id="<?php echo esc_attr( $field_id ); ?>" 
		value="<?php echo esc_attr( $current_value ); ?>" 
		placeholder="<?php echo esc_attr( $placeholder ); ?>"
	/>
</fieldset>

