<?php

/**
 * Support Sidebar Metabox
 * Settings > Widget Options
 *
 * @copyright   Copyright (c) 2016, Jeffrey Carandang
 * @since       4.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Create Metabox for Support
 *
 * @since 4.0
 * @return void
 */

function widgetopts_settings_support_box()
{ ?>
	<div id="widgetopts-sidebar-widget-support" class="postbox widgetopts-sidebar-widget">
		<h3 class="hndle ui-sortable-handle"><span><?php _e('Need Help on Managing Widgets?', 'widget-options'); ?></span></h3>
		<div class="inside">
			<p>
				<?php _e('In need of any assistance or having issue using Widget Options Extended Plugin? We are very happy to help you out, just click the button below and we will answer your concerns professionally. Thanks!', 'widget-options'); ?>
			</p>
			<p>
				<a class="button-secondary" href="https://widget-options.com/contact/" target="_blank"><?php _e('Open Support Ticket', 'widget-options'); ?></a>
			</p>
		</div>
	</div>
<?php
}
add_action('widgetopts_module_sidebar', 'widgetopts_settings_support_box', 21);

/**
 * Create Metabox for recommendation
 *
 * @since 5.1.0
 * @return void
 */

function widgetopts_settings_reco_box()
{
	$link_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" class="linkIcon"><path d="M18.2 17c0 .7-.6 1.2-1.2 1.2H7c-.7 0-1.2-.6-1.2-1.2V7c0-.7.6-1.2 1.2-1.2h3.2V4.2H7C5.5 4.2 4.2 5.5 4.2 7v10c0 1.5 1.2 2.8 2.8 2.8h10c1.5 0 2.8-1.2 2.8-2.8v-3.6h-1.5V17zM14.9 3v1.5h3.7l-6.4 6.4 1.1 1.1 6.4-6.4v3.7h1.5V3h-6.3z"></path></svg>';
?>
	<div id="widgetopts-sidebar-widget-reco" class="postbox widgetopts-sidebar-widget">
		<h3 class="hndle ui-sortable-handle"><span><?php _e('Recommended Plugins', 'widget-options'); ?></span></h3>
		<div class="inside">
			<p>
				<?php _e('Check out some of our other plugins + a few extra that we love using!', 'widget-options'); ?>
			</p>
			<?php $login_wp_url = "https://loginwp.com/ref/11/"; ?>
			<p>
				<?php echo sprintf(
					esc_html__('%sLoginWP%s #1 WordPress User Redirection Plugin. Redirect users to different URLs after they log in, log out and register based on different', 'widget-options'),
					'<a class="widgetopts-link" href="' . $login_wp_url . '" target="_blank" style="margin-bottom: 5px;">',
					$link_icon . '</a>'
				); ?>
			</p>

			<?php $wpdiscussion_url = "https://wpdiscussionboard.com/ref/6/"; ?>
			<p>
				<?php echo sprintf(
					esc_html__('%sWP Discussion Board%s An easy way to add a discussion board, message board or simple forum to your WordPress site with user profiles, boards, topics, notifications & more!', 'widget-options'),
					'<a class="widgetopts-link" href="' . $wpdiscussion_url . '" target="_blank" style="margin-bottom: 5px;">',
					$link_icon . '</a>'
				); ?>
			</p>

			<?php $fusewp_url = "https://fusewp.com/?sscid=81k7_pja98"; ?>
			<p>
				<?php echo sprintf(
					esc_html__('%sFuseWP%s Automatically Sync WordPress Users, customers, and members in ecommerce and membership plugins with your CRM and Email Marketing software.', 'widget-options'),
					'<a class="widgetopts-link" href="' . $fusewp_url . '" target="_blank" style="margin-bottom: 5px;">',
					$link_icon . '</a>'
				); ?>
			</p>

		</div>
	</div>
<?php
}
add_action('widgetopts_module_sidebar', 'widgetopts_settings_reco_box', 22);

/**
 * Create Metabox for LTD Banner
 *
 * @since 5.1.7
 * @return void
 */

function widgetopts_settings_ltd()
{ ?>
	<div id="widgetopts-sidebar-widget-support" class="postbox widgetopts-sidebar-widget">
		<div class="inside" style="padding: 0px;margin: 0;line-height: 0;"><a href="https://widget-options.com/pricing/" target="_blank" class="ltd-link"><img src="<?php echo WIDGETOPTS_PLUGIN_URL.'assets/images/wo-lifetime-sub.png'; ?>" style="max-width: 100%;"></a></div>
	</div>
	<style>.ltd-link:focus{box-shadow:none;outline:none;}</style>
<?php
}
add_action('widgetopts_module_sidebar', 'widgetopts_settings_ltd', 15);

/**
 * Create Metabox for Changelog
 *
 * @since 5.1.0
 * @return void
 */

function widgetopts_settings_changelog_box()
{ ?>
	<div id="widgetopts-sidebar-widget-support" class="postbox widgetopts-sidebar-widget" style="border-color: #ffb310; border-width: 2px;">
		<h3 class="hndle ui-sortable-handle"><span><?php _e('Changelog', 'widget-options'); ?></span></h3>
		<div class="inside">
			<p>
				<?php _e('Widget Options Extended is now compatible with block widget', 'widget-options'); ?>
			</p>

			<p>
				<a class="button-secondary" href="https://widget-options.com/changelog/" target="_blank"><?php _e('More Changes', 'widget-options'); ?></a>
			</p>
		</div>
	</div>
<?php
}
add_action('widgetopts_module_sidebar', 'widgetopts_settings_changelog_box', 20);
?>