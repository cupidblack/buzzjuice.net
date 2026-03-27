<?php
/**
 * Move Widgets Module
 * Settings > Widget Options :: Move Widget
 *
 * @copyright   Copyright (c) 2017, Jeffrey Carandang
 * @since       4.3
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Create Card Module for Move Widget Feature
 *
 * @since 4.3
 * @global $widget_options
 * @return void
 */
 
 /*
 * Note: Please add a class "no-settings" in the <li> card if the card has no additional configuration, if there are configuration please remove the class
 */

if( !function_exists( 'widgetopts_settings_clone' ) ):
    function widgetopts_settings_clone(){
        global $widget_options;
        $validLicense = widgetopts_global_check_license();

        //avoid issue after update
        if( !isset( $widget_options['clone'] ) ){
            $widget_options['clone'] = '';
        }
        ?>
        <li class="widgetopts-module-card widgetopts-module-card-no-settings no-settings <?php echo (!$validLicense) ? 'widgetopts-module-type-pro' : '' ?> <?php echo ( $widget_options['clone'] == 'activate' ) ? 'widgetopts-module-type-enabled' : 'widgetopts-module-type-disabled'; ?>" id="widgetopts-module-card-clone" data-module-id="clone">
    		<div class="widgetopts-module-card-content">
    			<h2><?php _e( 'Clone Widget', 'widget-options' );?></h2>
                <div class="widgetopts-pro-label"><span class="dashicons dashicons-<?php echo ($validLicense) ? 'un' : '' ?>lock"></span></div>
    			<p class="widgetopts-module-desc">
    				<?php _e( 'Clone any widgets easily and assign them to your selected sidebar widget areas.', 'widget-options' );?>
    			</p>

    			<div class="widgetopts-module-actions hide-if-no-js">
                    <?php if( $widget_options['clone'] == 'activate' ){ ?>
    					<button class="button button-secondary widgetopts-toggle-settings"><?php _e( 'Learn More', 'widget-options' );?></button>
    					<button class="button button-secondary widgetopts-toggle-activation"><?php _e( 'Disable', 'widget-options' );?></button>
    				<?php }else{ ?>
    					<button class="button button-secondary widgetopts-toggle-settings"><?php _e( 'Learn More', 'widget-options' );?></button>
    					<button class="button button-primary widgetopts-toggle-activation"><?php _e( 'Enable', 'widget-options' );?></button>
    				<?php } ?>

    			</div>

    		</div>

    		<?php widgetopts_modal_start( $widget_options['clone'] ); ?>
    			<span class="dashicons widgetopts-dashicons widgetopts-no-top dashicons-admin-page"></span>
    			<h3 class="widgetopts-modal-header"><?php _e( 'Clone Widget', 'widget-options' );?></h3>
    			<p>
    				<?php _e( 'Clone Widget feature helps you manage your widgets more by adding <strong>Clone</strong> button that will enable you to easily duplicates any widget and assign them to any sidebar widget areas of your choosing.', 'widget-options' );?>
    			</p>
    			<p class="widgetopts-settings-section">
    				<?php _e( 'No additional settings available.', 'widget-options' );?>
    			</p>
    		<?php widgetopts_modal_end( $widget_options['clone'] ); ?>

    	</li>
        <?php
    }
    add_action( 'widgetopts_module_cards', 'widgetopts_settings_clone', 62 );
endif;
?>
