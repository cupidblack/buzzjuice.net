<?php
/**
 * Dropdown Field Template
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$default_value = isset( $field['Default'] ) ? $field['Default'] : '';
$current_value  = cev_pro()->function->cev_pro_admin_settings( $field_id, $default_value );
?>
<fieldset>
	<select 
		class="select select2" 
		id="<?php echo esc_attr( $field_id ); ?>" 
		name="<?php echo esc_attr( $field_id ); ?>" 
		<?php echo esc_attr( $disabled ); ?>
	>
		<?php
		foreach ( $field['options'] as $option_key => $option_value ) {
			$selected = ( (string) $current_value === (string) $option_key ) ? 'selected' : '';
			?>
			<option value="<?php echo esc_attr( $option_key ); ?>" <?php echo esc_attr( $selected ); ?>>
				<?php echo esc_html( $option_value ); ?>
			</option>
			<?php
		}
		?>
	</select>
</fieldset>

