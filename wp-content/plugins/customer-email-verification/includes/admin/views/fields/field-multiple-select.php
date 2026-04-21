<?php
/**
 * Multiple Select Field Template
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$multi_values = cev_pro()->function->cev_pro_admin_settings( $field_id, array() );
?>
<div class="multiple_select_container">
	<select 
		multiple 
		class="wc-enhanced-select" 
		name="<?php echo esc_attr( $field_id ); ?>[]" 
		id="<?php echo esc_attr( $field_id ); ?>"
	>
		<?php
		foreach ( $field['options'] as $option_key => $option_value ) {
			$is_selected = isset( $multi_values[ $option_key ] ) && 1 === (int) $multi_values[ $option_key ];
			$selected    = $is_selected ? 'selected' : '';
			?>
			<option value="<?php echo esc_attr( $option_key ); ?>" <?php echo esc_attr( $selected ); ?>>
				<?php echo esc_html( $option_value ); ?>
			</option>
			<?php
		}
		?>
	</select>
</div>

