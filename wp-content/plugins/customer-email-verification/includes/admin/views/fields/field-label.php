<?php
/**
 * Field Label Template
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<label class="settings_label <?php echo esc_attr( $disabled ); ?>">
	<?php echo esc_html( $field['title'] ); ?>
	<?php if ( isset( $field['tooltip'] ) ) : ?>
		<span class="woocommerce-help-tip tipTip" title="<?php echo esc_attr( $field['tooltip'] ); ?>"></span>
	<?php endif; ?>
</label>

