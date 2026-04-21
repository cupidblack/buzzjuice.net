<?php
/**
 * Menu Tabs Template
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Variables should be passed from the calling function.
if ( ! isset( $current_tab ) || ! isset( $tabs ) ) {
	return;
}

// Loop through each tab configuration.
foreach ( (array) $tabs as $tab_id => $tab_data ) {
	// Check if this is a link-type tab.
	if ( isset( $tab_data['type'] ) && 'link' === $tab_data['type'] ) {
		// Render as an anchor tag.
		?>
		<a class="menu_cev_link" href="<?php echo esc_url( $tab_data['link'] ); ?>">
			<?php echo esc_html( $tab_data['title'] ); ?>
		</a>
		<?php
	} else {
		// Render as a radio button tab.
		$is_checked = ( $current_tab === $tab_data['data-tab'] ) ? 'checked' : '';
		?>
		<!-- Radio input for tab selection -->
		<input 
			class="cev_tab_input" 
			id="<?php echo esc_attr( $tab_id ); ?>" 
			name="<?php echo esc_attr( $tab_data['name'] ); ?>" 
			type="radio" 
			data-tab="<?php echo esc_attr( $tab_data['data-tab'] ); ?>" 
			data-label="<?php echo esc_attr( $tab_data['data-label'] ); ?>" 
			<?php echo esc_attr( $is_checked ); ?> 
		/>
		<!-- Label for the tab -->
		<label 
			class="<?php echo esc_attr( $tab_data['class'] ); ?>" 
			for="<?php echo esc_attr( $tab_id ); ?>">
			<?php echo esc_html( $tab_data['title'] ); ?>
		</label>
		<?php
	}
}
