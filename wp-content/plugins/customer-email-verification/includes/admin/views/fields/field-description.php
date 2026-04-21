<?php
/**
 * Field Description Template
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<p class="section_desc <?php echo esc_attr( $disabled ); ?>" id="<?php echo esc_attr( $field_id ); ?>">
	<?php echo esc_html( $field['title'] ); ?>
</p>

