<?php
/**
 * Roles Settings Module
 * Settings > Widget Options :: User Roles Restriction
 *
 * @copyright   Copyright (c) 2016, Jeffrey Carandang
 * @since       4.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Create Card Module for User Roles Restriction
 *
 * @since 4.0
 * @global $widget_options
 * @return void
 */
 
 /*
 * Note: Please add a class "no-settings" in the <li> card if the card has no additional configuration, if there are configuration please remove the class
 */

function widgetopts_settings_roles(){
    global $widget_options;
	$validLicense = widgetopts_global_check_license(); 
?>
	<li class="widgetopts-module-card no-settings <?php echo (!$validLicense) ? 'widgetopts-module-type-pro' : '' ?> <?php echo ( $widget_options['roles'] == 'activate' ) ? 'widgetopts-module-type-enabled' : 'widgetopts-module-type-disabled'; ?>" id="widgetopts-module-card-roles" data-module-id="roles">
		<div class="widgetopts-module-card-content">
			<h2><?php _e( 'User Roles Restriction', 'widget-options' );?></h2>
			<div class="widgetopts-pro-label"><span class="dashicons dashicons-<?php echo ($validLicense) ? 'un' : '' ?>lock"></span></div>
			<p class="widgetopts-module-desc">
				<?php _e( 'Restrict each widgets visibility for each user roles at ease via checkboxes.', 'widget-options' );?>
			</p>
			<div class="widgetopts-module-actions hide-if-no-js">
				<?php if( $widget_options['roles'] == 'activate' ){ ?>
					<button class="button button-secondary widgetopts-toggle-settings"><?php _e( 'Learn More', 'widget-options' );?></button>
					<button class="button button-secondary widgetopts-toggle-activation"><?php _e( 'Disable', 'widget-options' );?></button>
				<?php }else{ ?>
					<button class="button button-secondary widgetopts-toggle-settings"><?php _e( 'Learn More', 'widget-options' );?></button>
					<button class="button button-primary widgetopts-toggle-activation"><?php _e( 'Enable', 'widget-options' );?></button>
				<?php } ?>

			</div>
		</div>

		<?php widgetopts_modal_start( $widget_options['roles'] ); ?>
			<span class="dashicons widgetopts-dashicons widgetopts-no-top dashicons-admin-users"></span>
			<h3 class="widgetopts-modal-header"><?php _e( 'User Roles Restriction', 'widget-options' );?></h3>
			<p>
				<?php _e( 'This feature will allow you to manage widget visibility and restrict them per defined user roles on your WordPress site. You can show or hide widgets for members-only page or serve different sidebar contents on guest visitors.', 'widget-options' );?>
			</p>
            <p class="widgetopts-settings-section">
				<?php _e( 'No additional settings available.', 'widget-options' );?>
			</p>
		<?php widgetopts_modal_end( $widget_options['roles'] ); ?>

	</li>
    <?php
}
add_action( 'widgetopts_module_cards', 'widgetopts_settings_roles', 100 );
?>
