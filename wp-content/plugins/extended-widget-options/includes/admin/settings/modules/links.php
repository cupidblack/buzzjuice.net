<?php
/**
 * Links Settings Module
 * Settings > Widget Options :: Link Widget
 *
 * @copyright   Copyright (c) 2016, Jeffrey Carandang
 * @since       4.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Create Card Module for Custom Links Options
 *
 * @since 4.0
 * @global $widget_options
 * @return void
 */
 
 /*
 * Note: Please add a class "no-settings" in the <li> card if the card has no additional configuration, if there are configuration please remove the class
 */

function widgetopts_settings_links(){
    global $widget_options;
	$validLicense = widgetopts_global_check_license();  
?>
    <li class="widgetopts-module-card widgetopts-module-card-no-settings no-settings <?php echo (!$validLicense) ? 'widgetopts-module-type-pro' : '' ?> <?php echo ( $widget_options['links'] == 'activate' ) ? 'widgetopts-module-type-enabled' : 'widgetopts-module-type-disabled'; ?>" id="widgetopts-module-card-links" data-module-id="links">
		<div class="widgetopts-module-card-content">
			<h2><?php _e( 'Link Widget', 'widget-options' );?></h2>
			<div class="widgetopts-pro-label"><span class="dashicons dashicons-<?php echo ($validLicense) ? 'un' : '' ?>lock"></span></div>
			<p class="widgetopts-module-desc">
				<?php _e( 'Add custom links to any widgets to redirect users on click action.', 'widget-options' );?>
			</p>

			<div class="widgetopts-module-actions hide-if-no-js">
                <?php if( $widget_options['links'] == 'activate' ){ ?>
					<button class="button button-secondary widgetopts-toggle-settings"><?php _e( 'Learn More', 'widget-options' );?></button>
					<button class="button button-secondary widgetopts-toggle-activation"><?php _e( 'Disable', 'widget-options' );?></button>
				<?php }else{ ?>
					<button class="button button-secondary widgetopts-toggle-settings"><?php _e( 'Learn More', 'widget-options' );?></button>
					<button class="button button-primary widgetopts-toggle-activation"><?php _e( 'Enable', 'widget-options' );?></button>
				<?php } ?>

			</div>

		</div>

		<?php widgetopts_modal_start( $widget_options['links'] ); ?>
			<span class="dashicons widgetopts-dashicons widgetopts-no-top dashicons-admin-links"></span>
			<h3 class="widgetopts-modal-header"><?php _e( 'Link Widget', 'widget-options' );?></h3>
			<p>
				<?php _e( 'Link Widget feature will allow you to add custom link on each widget. Whenever the user clicks the widget they will be redirected to the links you\'ve added, you can add hover color changes state using the Custom Styling feature for perfect integration.', 'widget-options' );?>
			</p>
			<p class="widgetopts-settings-section">
				<?php _e( 'No additional settings available.', 'widget-options' );?>
			</p>
		<?php widgetopts_modal_end( $widget_options['links'] ); ?>

	</li>
    <?php
}
add_action( 'widgetopts_module_cards', 'widgetopts_settings_links', 70 );
?>
