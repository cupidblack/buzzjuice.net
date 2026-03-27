<?php
/**
 * Disable Widgets Settings Module
 * Settings > Widget Options :: Disable Widgets
 *
 * @copyright   Copyright (c) 2016, Jeffrey Carandang
 * @since       4.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Create Card Module for Disable Widgets Options
 *
 * @since 4.0
 * @global $widget_options
 * @return void
 */

function widgetopts_settings_disable_widgets(){
    global $widget_options;
	$disable_widgets 	= ( isset( $widget_options['settings']['disable_widgets'] ) ) ? $widget_options['settings']['disable_widgets'] : array();
	$widgets 			= array();
	$validLicense = widgetopts_global_check_license();

	if ( ! empty( $GLOBALS['wp_widget_factory'] ) ) {
		$widgets = $GLOBALS['wp_widget_factory']->widgets;
	}

	$available_widgets = apply_filters( 'widgetopts_widget_disable_default_sidebar_widgets', $widgets );?>
	<li class="widgetopts-module-card <?php echo (!$validLicense) ? 'widgetopts-module-type-pro' : '' ?> <?php echo ( $widget_options['disable_widgets'] == 'activate' ) ? 'widgetopts-module-type-enabled' : 'widgetopts-module-type-disabled'; ?>" id="widgetopts-module-card-disable_widgets" data-module-id="disable_widgets">
		<div class="widgetopts-module-card-content">
			<h2><?php _e( 'Disable Widgets', 'widget-options' );?></h2>
			<div class="widgetopts-pro-label"><span class="dashicons dashicons-<?php echo ($validLicense) ? 'un' : '' ?>lock"></span></div>
			<p class="widgetopts-module-desc">
				<?php _e( 'Disable several widgets that you won\'t be using to lessen widget dashboard space.', 'widget-options' );?>
			</p>

			<div class="widgetopts-module-actions hide-if-no-js">
				<?php if( $widget_options['disable_widgets'] == 'activate' ){ ?>
					<button class="button button-secondary widgetopts-toggle-settings"><?php _e( 'Configure Settings', 'widget-options' );?></button>
					<button class="button button-secondary widgetopts-toggle-activation"><?php _e( 'Disable', 'widget-options' );?></button>
				<?php }else{ ?>
					<button class="button button-secondary widgetopts-toggle-settings"><?php _e( 'Learn More', 'widget-options' );?></button>
					<button class="button button-primary widgetopts-toggle-activation"><?php _e( 'Enable', 'widget-options' );?></button>
				<?php } ?>

			</div>
		</div>

		<?php widgetopts_modal_start( $widget_options['disable_widgets'] ); ?>
			<span class="dashicons widgetopts-dashicons widgetopts-no-top dashicons-welcome-widgets-menus"></span>
			<h3 class="widgetopts-modal-header"><?php _e( 'Disable Widgets', 'widget-options' );?></h3>
			<p>
				<?php _e( 'Disable any widgets that you don\'t want to be available on admin widget dashboard. Simply check the widgets that you want to disable.', 'widget-options' );?>
			</p>
			<?php if ( ! $available_widgets ) {
				printf(
					'<p class="widgetopts-settings-section">%s</p>',
					__( 'Oops, we could not retrieve the sidebar widgets! Maybe there is another plugin already managing them?', 'widget-options' )
				);
			} ?>

			<table class="form-table widgetopts-settings-section">
				<?php
				foreach ( $available_widgets as $id => $widget ) { ?>
					<tr>
						<th scope="row">
							<label for="widgetopts-disable_widgets-<?php echo esc_attr( $id ); ?>"><?php _e(  esc_html( $widget->name ) , 'widget-options' );?></label>
						</th>
						<td>
							<input type="checkbox" id="widgetopts-disable_widgets-<?php echo esc_attr( $id ); ?>" name="disable_widgets[<?php echo esc_attr( $id ); ?>]" <?php echo widgetopts_is_checked( $disable_widgets, esc_attr( $id ) ) ?> value="1" />
							<label for="widgetopts-disable_widgets-<?php echo esc_attr( $id ); ?>"><?php _e(  'Disable' , 'widget-options' );?> <?php _e( esc_html( $widget->name ) , 'widget-options' );?></label>
							<p class="description">
								<?php printf( __( 'Remove %1$s on admin widget dashboard page.', 'widget-options' ),'<code>'. esc_html( $id ) .'</code>' );?>
							</p>
						</td>
					</tr>
				<?php } ?>
			</table>
		<?php widgetopts_modal_end( $widget_options['disable_widgets'] ); ?>

	</li>
    <?php
}
add_action( 'widgetopts_module_cards', 'widgetopts_settings_disable_widgets', 150 );
?>
