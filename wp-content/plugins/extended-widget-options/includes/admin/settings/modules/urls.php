<?php

/**
 * URLs Settings Module
 * Settings > Widget Options :: Link Widget
 *
 * @copyright   Copyright (c) 2017, Jeffrey Carandang
 * @since       4.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Create Card Module for Custom URLs Options
 *
 * @since 4.4
 * @global $widget_options
 * @return void
 */

/*
 * Note: Please add a class "no-settings" in the <li> card if the card has no additional configuration, if there are configuration please remove the class
 */

if (!function_exists('widgetopts_settings_urls')) :
	function widgetopts_settings_urls()
	{
		global $widget_options;
		$validLicense = widgetopts_global_check_license();

		//avoid issue after update
		if (!isset($widget_options['urls'])) {
			$widget_options['urls'] = '';
		}
?>
		<li class="widgetopts-module-card widgetopts-module-card-no-settings no-settings <?php echo (!$validLicense) ? 'widgetopts-module-type-pro' : '' ?> <?php echo ($widget_options['urls'] == 'activate') ? 'widgetopts-module-type-enabled' : 'widgetopts-module-type-disabled'; ?>" id="widgetopts-module-card-urls" data-module-id="urls">
			<div class="widgetopts-module-card-content">
				<h2><?php _e('URL & Wildcards Restrictions', 'widget-options'); ?></h2>
				<div class="widgetopts-pro-label"><span class="dashicons dashicons-<?php echo ($validLicense) ? 'un' : '' ?>lock"></span></div>
				<p class="widgetopts-module-desc">
					<?php _e('Show or hide widgets by URL and/or use <code>*</code> to create a URL wildcard restrictions.', 'widget-options'); ?>
				</p>

				<div class="widgetopts-module-actions hide-if-no-js">
					<?php if ($widget_options['urls'] == 'activate') { ?>
						<button class="button button-secondary widgetopts-toggle-settings"><?php _e('Learn More', 'widget-options'); ?></button>
						<button class="button button-secondary widgetopts-toggle-activation"><?php _e('Disable', 'widget-options'); ?></button>
					<?php } else { ?>
						<button class="button button-secondary widgetopts-toggle-settings"><?php _e('Learn More', 'widget-options'); ?></button>
						<button class="button button-primary widgetopts-toggle-activation"><?php _e('Enable', 'widget-options'); ?></button>
					<?php } ?>

				</div>

			</div>

			<?php widgetopts_modal_start($widget_options['urls']); ?>
			<span class="dashicons widgetopts-dashicons widgetopts-no-top dashicons-admin-links"></span>
			<h3 class="widgetopts-modal-header"><?php _e('URL & Wildcards Restrictions', 'widget-options'); ?></h3>
			<p>
				<?php _e('This feature will give you option to target specific URL to show or hide any widgets. You can use <code>*</code> as wildcard url, for example <code>sample-page/*</code> to target all subpages of "sample-page". This will give you a brand new level of managing your widget visibility!', 'widget-options'); ?>
			</p>
			<p class="widgetopts-settings-section">
				<?php _e('No additional settings available.', 'widget-options'); ?>
			</p>
			<?php widgetopts_modal_end($widget_options['urls']); ?>

		</li>
<?php
	}
// add_action('widgetopts_module_cards', 'widgetopts_settings_urls', 25);
endif;
?>