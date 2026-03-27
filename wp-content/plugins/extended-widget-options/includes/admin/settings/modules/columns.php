<?php
/**
 * Columns Settings Module
 * Settings > Widget Options :: Column Display
 *
 * @copyright   Copyright (c) 2016, Jeffrey Carandang
 * @since       4.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Create Card Module for Column Display Options
 *
 * @since 4.0
 * @global $widget_options
 * @return void
 */
 
 /*
 * Note: Please add a class "no-settings" in the <li> card if the card has no additional configuration, if there are configuration please remove the class
 */

function widgetopts_settings_columns(){
    global $widget_options;
	$validLicense = widgetopts_global_check_license(); 
?>
    <li class="widgetopts-module-card widgetopts-module-card-no-settings no-settings <?php echo (!$validLicense) ? 'widgetopts-module-type-pro' : '' ?> <?php echo ( $widget_options['columns'] == 'activate' ) ? 'widgetopts-module-type-enabled' : 'widgetopts-module-type-disabled'; ?>" id="widgetopts-module-card-columns" data-module-id="columns">
		<div class="widgetopts-module-card-content">
			<h2><?php _e( 'Column Display', 'widget-options' );?></h2>
			<div class="widgetopts-pro-label"><span class="dashicons dashicons-<?php echo ($validLicense) ? 'un' : '' ?>lock"></span></div>
			<p class="widgetopts-module-desc">
				<?php _e( 'Manage your widgets display as columns, set different columns for specific devices.', 'widget-options' );?>
			</p>

			<div class="widgetopts-module-actions hide-if-no-js">
                <?php if( $widget_options['columns'] == 'activate' ){ ?>
					<button class="button button-secondary widgetopts-toggle-settings"><?php _e( 'Learn More', 'widget-options' );?></button>
					<button class="button button-secondary widgetopts-toggle-activation"><?php _e( 'Disable', 'widget-options' );?></button>
				<?php }else{ ?>
					<button class="button button-secondary widgetopts-toggle-settings"><?php _e( 'Learn More', 'widget-options' );?></button>
					<button class="button button-primary widgetopts-toggle-activation"><?php _e( 'Enable', 'widget-options' );?></button>
				<?php } ?>

			</div>

		</div>

		<?php widgetopts_modal_start( $widget_options['columns'] ); ?>
			<span class="dashicons widgetopts-dashicons widgetopts-no-top dashicons-grid-view"></span>
			<h3 class="widgetopts-modal-header"><?php _e( 'Column Display', 'widget-options' );?></h3>
			<p>
				<?php _e( 'Column Display feature allows you to set each widgets display for up to four columns.  Set different columns for each widgets on desktop, tablet and/or mobile devices.', 'widget-options' );?>
			</p>
			<p class="widgetopts-settings-section">
				<?php _e( 'No additional settings available.', 'widget-options' );?>
			</p>
		<?php widgetopts_modal_end( $widget_options['columns'] ); ?>

	</li>
    <?php
}
add_action( 'widgetopts_module_cards', 'widgetopts_settings_columns', 90 );
?>
