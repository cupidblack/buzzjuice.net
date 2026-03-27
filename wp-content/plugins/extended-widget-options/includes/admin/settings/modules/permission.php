<?php
/**
 * Alignment Settings Module
 * Settings > Widget Options :: Custom Alignment
 *
 * @copyright   Copyright (c) 2016, Jeffrey Carandang
 * @since       4.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Create Card Module for Custom Alignment Options
 *
 * @since 4.0
 * @global $widget_options
 * @return void
 */

function widgetopts_settings_permissions(){
    global $widget_options;
	$permission = ( isset( $widget_options['settings']['permission'] ) ) ? $widget_options['settings']['permission'] : array();
	$roles 		= get_editable_roles();
	$validLicense = widgetopts_global_check_license();

	//prevent missing array key
	if( !isset( $widget_options['permission'] ) ){
		$widget_options['permission'] = '';
	}
	?>
	<li class="widgetopts-module-card <?php echo (!$validLicense) ? 'widgetopts-module-type-pro' : '' ?> <?php echo ( $widget_options['permission'] == 'activate' ) ? 'widgetopts-module-type-enabled' : 'widgetopts-module-type-disabled'; ?>" id="widgetopts-module-card-permission" data-module-id="permission">
		<div class="widgetopts-module-card-content">
			<h2><?php _e( 'Permission', 'widget-options' );?></h2>
			<div class="widgetopts-pro-label"><span class="dashicons dashicons-<?php echo ($validLicense) ? 'un' : '' ?>lock"></span></div>
			<p class="widgetopts-module-desc">
				<?php _e( 'Hide widget options tabs below each widgets to selected user roles.', 'widget-options' );?>
			</p>

			<div class="widgetopts-module-actions hide-if-no-js">
				<?php if( $widget_options['permission'] == 'activate' ){ ?>
					<button class="button button-secondary widgetopts-toggle-settings"><?php _e( 'Configure Settings', 'widget-options' );?></button>
					<button class="button button-secondary widgetopts-toggle-activation"><?php _e( 'Disable', 'widget-options' );?></button>
				<?php }else{ ?>
					<button class="button button-secondary widgetopts-toggle-settings"><?php _e( 'Learn More', 'widget-options' );?></button>
					<button class="button button-primary widgetopts-toggle-activation"><?php _e( 'Enable', 'widget-options' );?></button>
				<?php } ?>

			</div>
		</div>

		<?php widgetopts_modal_start( $widget_options['permission'] ); ?>
			<span class="dashicons widgetopts-dashicons widgetopts-no-top dashicons-admin-generic"></span>
			<h3 class="widgetopts-modal-header"><?php _e( 'Permission', 'widget-options' );?></h3>
			<p>
				<?php _e( 'Prevent certain user roles on editing the widget options. If you select any user roles using this option, the widget options will be hidden even if they have capability to edit the widget values.', 'widget-options' );?>
			</p>
			<table class="form-table widgetopts-settings-section">
				<?php foreach ($roles as $rolename => $role) { ?>
					<tr>
						<th scope="row">
							<label for="widgetopts-permission-<?php echo $rolename;?>"><?php echo $role['name']; ?></label>
						</th>
						<td>
							<input type="checkbox" id="widgetopts-permission-<?php echo $rolename;?>" name="permission[<?php echo $rolename;?>]" <?php echo widgetopts_is_checked( $permission, $rolename ) ?> value="1" />
							<label for="widgetopts-permission-<?php echo $rolename;?>"><?php _e( 'Hide to ' . $role['name'], 'widget-options' );?></label>
							<p class="description">
								<?php _e( 'Don\'t show widget options when user role is ' . $role['name']. '.', 'widget-options' );?>
							</p>
						</td>
					</tr>
				<?php } ?>
			</table>
		<?php widgetopts_modal_end( $widget_options['permission'] ); ?>

	</li>
    <?php
}
add_action( 'widgetopts_module_cards', 'widgetopts_settings_permissions', 160 );
?>
