<?php
/**
 * Shortcodes Settings Module
 * Settings > Widget Options :: Shortcodes
 *
 * @copyright   Copyright (c) 2016, Jeffrey Carandang
 * @since       4.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Create Card Module for Shortcodes Options
 *
 * @since 4.0
 * @global $widget_options
 * @return void
 */

function widgetopts_settings_shortcodes(){
    global $widget_options;
	$validLicense = widgetopts_global_check_license(); 
?>
	<li class="widgetopts-module-card <?php echo (!$validLicense) ? 'widgetopts-module-type-pro' : '' ?> <?php echo ( $widget_options['shortcodes'] == 'activate' ) ? 'widgetopts-module-type-enabled' : 'widgetopts-module-type-disabled'; ?>" id="widgetopts-module-card-shortcodes" data-module-id="shortcodes">
		<div class="widgetopts-module-card-content">
			<h2><?php _e( 'Shortcodes', 'widget-options' );?></h2>
			<div class="widgetopts-pro-label"><span class="dashicons dashicons-<?php echo ($validLicense) ? 'un' : '' ?>lock"></span></div>
			<p class="widgetopts-module-desc">
				<?php _e( 'Display any sidebars and widgets anywhere using shortcodes.', 'widget-options' );?>
			</p>

			<div class="widgetopts-module-actions hide-if-no-js">
				<?php if( $widget_options['shortcodes'] == 'activate' ){ ?>
					<button class="button button-secondary widgetopts-toggle-settings"><?php _e( 'Configure Settings', 'widget-options' );?></button>
					<button class="button button-secondary widgetopts-toggle-activation"><?php _e( 'Disable', 'widget-options' );?></button>
				<?php }else{ ?>
					<button class="button button-secondary widgetopts-toggle-settings"><?php _e( 'Learn More', 'widget-options' );?></button>
					<button class="button button-primary widgetopts-toggle-activation"><?php _e( 'Enable', 'widget-options' );?></button>
				<?php } ?>

			</div>

		</div>

		<?php widgetopts_modal_start( $widget_options['shortcodes'] ); ?>
			<span class="dashicons widgetopts-dashicons widgetopts-no-top dashicons-editor-code"></span>
			<h3 class="widgetopts-modal-header"><?php _e( 'Shortcodes', 'widget-options' );?></h3>
			<p>
				<?php _e( 'You can also display any sidebars and widgets anywhere by using shortcodes. If you are unsure of the values you can check the shortcode below each widgets when this features is enabled.', 'widget-options' );?>
			</p>
			<div class="widgetopts-settings-section">
				<h4><?php _e( 'For sidebars:', 'widget-options' );?></h4>
				<p>
					<code>[do_sidebar name="Primary" container="div" container_id="div_id" container_class="div_class"]</code>
				</p>
				<p>
					<strong><?php _e( 'Shortcode Parameters', 'widget-options' );?></strong>
				</p>
				<p>
					<strong><?php _e( 'name', 'widget-options' );?></strong> : <?php _e( 'the name or id of the sidebar you want to display.', 'widget-options' );?>
				</p>
				<p>
					<strong><?php _e( 'container', 'widget-options' );?></strong> : <?php _e( '<em>optional</em> content container for all the contents.', 'widget-options' );?>
				</p>
				<p>
					<strong><?php _e( 'container_id', 'widget-options' );?></strong> : <?php _e( '<em>optional</em> container id if you have container value.', 'widget-options' );?>
				</p>
				<p>
					<strong><?php _e( 'container_class', 'widget-options' );?></strong> : <?php _e( '<em>optional</em> container class if you have container value.', 'widget-options' );?>
				</p>

				<h4><?php _e( 'For widgets:', 'widget-options' );?></h4>
				<p>
					<code>[do_widget id="widget-id" container="div" container_id="div_id" container_class="div_class"]</code>
				</p>
				<p>
					<strong><?php _e( 'Shortcode Parameters', 'widget-options' );?></strong>
				</p>
				<p>
					<strong><?php _e( 'id', 'widget-options' );?></strong> : <?php _e( 'the id of the widget you want to display.', 'widget-options' );?>
				</p>
				<p>
					<strong><?php _e( 'container', 'widget-options' );?></strong> : <?php _e( '<em>optional</em> content container for the widget.', 'widget-options' );?>
				</p>
				<p>
					<strong><?php _e( 'container_id', 'widget-options' );?></strong> : <?php _e( '<em>optional</em> container id if you have container value.', 'widget-options' );?>
				</p>
				<p>
					<strong><?php _e( 'container_class', 'widget-options' );?></strong> : <?php _e( '<em>optional</em> container class if you have container value.', 'widget-options' );?>
				</p>
			</div>
		<?php widgetopts_modal_end( $widget_options['shortcodes'] ); ?>

	</li>
    <?php
}
add_action( 'widgetopts_module_cards', 'widgetopts_settings_shortcodes', 170 );
?>
