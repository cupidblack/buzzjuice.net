<?php

/**
 * Extended Taxonomy Terms Settings Module
 * Settings > Widget Options :: Extended Taxonomy Terms
 *
 * @copyright   Copyright (c) 2016, Jeffrey Carandang
 * @since       4.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Create Card Module for Extended Taxonomy Terms Options
 *
 * @since 4.0
 * @global $widget_options
 * @return void
 */

function widgetopts_settings_taxonomies()
{
	global $widget_options;
	$tax_opts 	= (isset($widget_options['settings']['taxonomies'])) ? $widget_options['settings']['taxonomies'] : array();
	$validLicense = widgetopts_global_check_license();

	//avoid erro when free version also activated
	if (!isset($widget_options['taxonomies'])) {
		$widget_options['taxonomies'] = '';
	}
?>
	<li class="widgetopts-module-card <?php echo (!$validLicense) ? 'widgetopts-module-type-pro' : '' ?> <?php echo ($widget_options['taxonomies'] == 'activate') ? 'widgetopts-module-type-enabled' : 'widgetopts-module-type-disabled'; ?>" id="widgetopts-module-card-taxonomies" data-module-id="taxonomies">
		<div class="widgetopts-module-card-content">
			<h2><?php _e('Extended Taxonomy Terms', 'widget-options'); ?></h2>
			<div class="widgetopts-pro-label"><span class="dashicons dashicons-<?php echo ($validLicense) ? 'un' : '' ?>lock"></span></div>
			<p class="widgetopts-module-desc">
				<?php _e('Extend each widget visibility for custom post types taxonomies and terms.', 'widget-options'); ?>
			</p>

			<div class="widgetopts-module-actions hide-if-no-js">
				<?php if ($widget_options['taxonomies'] == 'activate') { ?>
					<button class="button button-secondary widgetopts-toggle-settings"><?php _e('Configure Settings', 'widget-options'); ?></button>
					<button class="button button-secondary widgetopts-toggle-activation"><?php _e('Disable', 'widget-options'); ?></button>
				<?php } else { ?>
					<button class="button button-secondary widgetopts-toggle-settings"><?php _e('Learn More', 'widget-options'); ?></button>
					<button class="button button-primary widgetopts-toggle-activation"><?php _e('Enable', 'widget-options'); ?></button>
				<?php } ?>

			</div>
		</div>

		<?php widgetopts_modal_start($widget_options['taxonomies']); ?>
		<span class="dashicons widgetopts-dashicons dashicons-visibility"></span>
		<h3 class="widgetopts-modal-header"><?php _e('Extended Taxonomy Terms Support', 'widget-options'); ?></h3>
		<p>
			<?php _e('Show or hide each WordPress widget per post type, taxonomy and/or term! With extended functionality, whenever each term is selected; each post, page or custom post type assigned will inherit the visibility options.', 'widget-options'); ?>
		</p>
		<p class="widgetopts-settings-section">
			<?php
			$args = array(
				'public'   => true

			);
			$output = 'objects'; // or objects
			$operator = 'and'; // 'and' or 'or'
			$taxonomies = get_taxonomies($args, $output, $operator);
			unset($taxonomies['post_format']);
			// print_r( $this->taxonomy_settings );
			if (!empty($taxonomies)) {
				foreach ($taxonomies as $tax) { ?>
		<p>
			<input type="checkbox" name="taxonomies[<?php echo $tax->name; ?>]" id="widgetopts-tax-<?php echo $tax->name; ?>" value="1" <?php echo widgetopts_is_checked($tax_opts, $tax->name); ?> />
			<label for="widgetopts-tax-<?php echo $tax->name; ?>"><?php echo $tax->label; ?></label>
			<?php
					if (isset($tax->object_type) && isset($tax->object_type[0])) {
						echo ' <small>- ' . $tax->object_type[0] . '</small>';
					}
			?>
		</p>
<?php
				}
			} ?>
</p>
<?php widgetopts_modal_end($widget_options['taxonomies']); ?>

	</li>
<?php
}
// add_action('widgetopts_module_cards', 'widgetopts_settings_taxonomies', 140);
?>