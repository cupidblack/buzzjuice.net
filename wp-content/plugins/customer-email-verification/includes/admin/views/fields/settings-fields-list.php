<?php
/**
 * Settings Fields List Template
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<ul class="settings_ul">
	<?php
	foreach ( (array) $fields as $field_id => $field ) {
		// Skip rendering if field is not set to show.
		if ( empty( $field['show'] ) ) {
			continue;
		}

		// Determine if field should be initially visible.
		$li_style = '';
		if ( isset( $field['initially_visible'] ) && 'no' === $field['initially_visible'] ) {
			$li_style = ' style="display:none"';
		}

		// Get field class.
		$class = isset( $field['class'] ) ? esc_attr( $field['class'] ) : '';

		// Determine if field should be disabled.
		$disabled = isset( $disabled_states[ $field_id ] ) ? $disabled_states[ $field_id ] : '';
		?>
		<li class="<?php echo esc_attr( $class ); ?>"<?php echo esc_attr( $li_style ); ?>>
			<?php
			// Render label for fields that require it.
			if ( ! in_array( $field['type'], $field_types_without_label, true ) ) {
				$settings_instance->render_field_label( $field, $disabled );
			}

			// Render field based on type.
			$settings_instance->render_field_by_type( $field_id, $field, $disabled );
			?>
		</li>
		<?php
	}
	?>
</ul>
