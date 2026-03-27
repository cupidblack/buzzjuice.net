<?php

/**
 * Widget Area Options Settings Module
 * Settings > Widget Options :: Widget Area Options
 *
 * @copyright   Copyright (c) 2017, Jeffrey Carandang
 * @since       4.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Create Card Module for Widget Area Options
 *
 * @since 4.2
 * @global $widget_options
 * @return void
 */
if (!function_exists('widgetopts_settings_custom_sidebar')) :
	function widgetopts_settings_custom_sidebar()
	{
		global $widget_options;
		$validLicense = widgetopts_global_check_license();
		//avoid issue after update
		if (!isset($widget_options['custom_sidebar'])) {
			$widget_options['custom_sidebar'] = '';
		}

		$custom_sidebar = (isset($widget_options['settings']['custom_sidebar'])) ? $widget_options['settings']['custom_sidebar'] : array(); ?>
		<li class="widgetopts-module-card <?php echo (!$validLicense) ? 'widgetopts-module-type-pro' : '' ?> <?php echo ($widget_options['custom_sidebar'] == 'activate') ? 'widgetopts-module-type-enabled' : 'widgetopts-module-type-disabled'; ?> " id="widgetopts-module-card-custom_sidebar" data-module-id="custom_sidebar">
			<div class="widgetopts-module-card-content">
				<h2><?php _e('Custom Widget Area', 'widget-options'); ?></h2>
				<div class="widgetopts-pro-label"><span class="dashicons dashicons-<?php echo ($validLicense) ? 'un' : '' ?>lock"></span></div>
				<p class="widgetopts-module-desc">
					<?php _e('Allows to create a dedicated back-end container for your widgets.', 'widget-options'); ?>
				</p>

				<div class="widgetopts-module-actions hide-if-no-js">
					<?php if ($widget_options['custom_sidebar'] == 'activate') { ?>
						<button class="button button-secondary widgetopts-toggle-settings"><?php _e('Configure Settings', 'widget-options'); ?></button>
						<button class="button button-secondary widgetopts-toggle-activation"><?php _e('Disable', 'widget-options'); ?></button>
					<?php } else { ?>
						<button class="button button-secondary widgetopts-toggle-settings"><?php _e('Learn More', 'widget-options'); ?></button>
						<button class="button button-primary widgetopts-toggle-activation"><?php _e('Enable', 'widget-options'); ?></button>
					<?php } ?>

				</div>
			</div>

			<?php widgetopts_modal_start($widget_options['custom_sidebar']); ?>
			<span class="dashicons widgetopts-dashicons widgetopts-no-top dashicons-art"></span>
			<h3 class="widgetopts-modal-header"><?php _e('Custom Widget Area', 'widget-options'); ?></h3>
			<p>
				<?php _e('Create widget containers for shortcode use only, eliminating the need for front-end widget placement and visibility conditions. This feature simplifies widget management and provide better control over widget placement and display.', 'widget-options'); ?>
			</p>

			<table class="form-table widgetopts-settings-section">
				<tr class="widgetopts-custom-sidebar-list-container" style="display: <?= (is_array($custom_sidebar) && is_countable($custom_sidebar) && count($custom_sidebar) > 0 ? "table-row" : "none") ?>">
					<th scope="row">
						<label for="widgetopts-custom-sidebar-name"><?php _e('Custom Widget Area Names', 'widget-options'); ?></label>
					</th>
					<td class="widgetopts-custom-sidebar-list-container-td">
						<?php if (is_array($custom_sidebar) && is_iterable($custom_sidebar)) { ?>
							<?php foreach ($custom_sidebar as $key => $cs) { ?>
								<?php if (!isset($cs['id']) || empty($cs['id'])) continue;
								?>
								<p class="widgetops-cs-label" data-key="<?= $key ?>">
									<input type="checkbox" id="widgetopts-custom-sidebar-status-<?= $key ?>" name="custom_sidebar[<?= $key ?>][status]" value="1" <?= (isset($cs['status']) && $cs['status'] == 1)  ? 'checked' : '' ?> />
									<span style="margin-bottom: 16px; display: inline-block;"><?= $cs['name'] ?? '' ?></span>
									<input type="hidden" id="widgetopts-custom-sidebar-name-<?= $key ?>" name="custom_sidebar[<?= $key ?>][name]" value="<?= $cs['name'] ?? '' ?>" />
									<input type="hidden" id="widgetopts-custom-sidebar-id-<?= $key ?>" name="custom_sidebar[<?= $key ?>][id]" value="<?= $cs['id'] ?? '' ?>" />
									<input type="hidden" id="widgetopts-custom-sidebar-description-<?= $key ?>" name="custom_sidebar[<?= $key ?>][description]" value="<?= $cs['description'] ?? '' ?>" />
									<span style="float: right;"><a class="widgetopts-edit-custom-sidebar" style="padding: 5px; cursor: pointer; margin-right: 5px;"><span class="dashicons dashicons-edit"></span></a><a class="widgetopts-delete-custom-sidebar" style="padding: 5px; cursor: pointer; color: #dc3545;"><span class="dashicons dashicons-trash"></span></a><a class="widgetopts-toggle-custom-sidebar-info" style="padding: 5px; cursor: pointer;"><span class="dashicons dashicons-arrow-down-alt2"></span></a></span>
									<span class="widgetopts-cs-toggle-info" style="display:none;min-height: 100px; width: 100%;">
										<span style="display:block;min-height: 100px;border: 1px solid #dcdcde;padding: 10px; margin-bottom: 16px; "><?= $cs['description'] ?? '' ?></span>
										<span style="display:block; margin-bottom: 16px; " class="widgetopts-shortcode-example"><code>[do_sidebar name="<?= $cs['name'] ?? '' ?>"]</code></span>
									</span>
								</p>
								<hr />
							<?php } ?>
						<?php } ?>
					</td>
				</tr>
				<tr>
					<td colspan="2" style="padding-left: 0;">
						<button id="widgetopts-add-new-custom-sidebar" class="button button-primary widgetopts-add-new-custom-sidebar" type="button"><?php _e('Add Custom Widget Area', 'widget-options'); ?></button>
					</td>
				</tr>
				<tr class="widgetopts-custom-sidebar-field-container" style="display: none;">
					<th scope="row">
						<label for="widgetopts-custom-sidebar-name"><?php _e('Widget Area Name', 'widget-options'); ?></label>
					</th>
					<td>
						<input class="widgetoptstempfield" data-id="" style="width: 100%;" type="text" id="widgetopts-custom-sidebar-name-temp" />
						<input class="widgetoptstempfield" data-id="" type="hidden" id="widgetopts-custom-sidebar-id-temp" />
					</td>
				</tr>
				<tr class="widgetopts-custom-sidebar-field-container" style="display: none;">
					<th scope="row">
						<label for="widgetopts-custom-sidebar-name"><?php _e('Widget Area Description', 'widget-options'); ?></label>
					</th>
					<td>
						<textarea class="widgetoptstempfield" data-id="" id="widgetopts-custom-sidebar-description-temp" name="custom_sidebar[][description]" style="width: 100%;"></textarea><br />

						<button type="button" class="button button-secondary" id="widgetopts-temp-cancel-add-to-list" data-purpose="cancel" style="float: right; margin-top: 16px;">Cancel</button>
						<button type="button" class="button button-primary" id="widgetopts-temp-add-to-list" data-purpose="add" style="float: right; margin-right: 10px; margin-top: 16px;">Add</button>
					</td>
				</tr>
			</table>
			<?php widgetopts_modal_end($widget_options['custom_sidebar']); ?>

		</li>
<?php
	}
	add_action('widgetopts_module_cards', 'widgetopts_settings_custom_sidebar', 63);
endif;
?>