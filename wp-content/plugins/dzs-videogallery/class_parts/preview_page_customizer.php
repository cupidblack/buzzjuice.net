<?php
if ( ! defined( 'ABSPATH' ) ) // Or some other WordPress constant
	exit;

wp_enqueue_script('preseter', DZSVG_URL . 'assets/preseter/preseter.js');
wp_enqueue_style('preseter', DZSVG_URL . 'assets/preseter/preseter.css');
echo '<div class="preseter"><div class="the-icon"></div>
<div class="the-content"><h3>Quick Config</h3>
<form method="GET">
<div class="setting">
<div class="alabel">Menu Position:</div>
<div class="select-wrapper"><span>right</span><select name="opt3" class="textinput short"><option>right</option><option>down</option><option>up</option><option>left</option><option>none</option></select></div>
</div>
<div class="setting">
<div class="alabel">Autoplay:</div>
<div class="select-wrapper"><span>on</span><select name="opt4" class="textinput short"><option value="on">' . esc_html__('on', 'dzsvg') . '</option><option value="off">' . esc_html__('off', 'dzsvg') . '</option></select></div>
</div>
<div class="setting type_all">
    <div class="setting-label">' . esc_html__('Feed From', 'dzsvg') . '</div>
    <div class="select-wrapper"><span>normal</span><select class="textinput styleme" name="feedfrom">
        <option>ytuserchannel</option>
        <option>ytkeywords</option>
        <option>ytplaylist</option>
        <option>vmuserchannel</option>
        <option>vmchannel</option>
    </select></div>
</div>
<div class="setting">
    <div class="alabel">Target Feed User</div>
    <div class="sidenote">Or playlist ID if you have selected playlist in the dropdown</div>
    <input type="text" name="opt6" value="digitalzoomstudio"/>
</div>
<div class="setting">
    <input type="submit" class="button-primary" name="submiter" value="Submit"/>
</div>
</form>
</div><!--end the-content-->
</div>';
if (isset($_GET['opt3'])) {
	$its['settings']['nav_type'] = 'none';
	$its['settings']['menuposition'] = sanitize_text_field($_GET['opt3']);
	$its['settings']['autoplay'] = sanitize_text_field($_GET['opt4']);
	$its['settings']['feedfrom'] = sanitize_text_field($_GET['feedfrom']);
	$opt6 = sanitize_text_field($_GET['opt6']);
	$its['settings']['youtubefeed_user'] = $opt6;
	$its['settings']['ytkeywords_source'] = $opt6;
	$its['settings']['ytplaylist_source'] = $opt6;
	$its['settings']['vimeofeed_user'] = $opt6;
	$its['settings']['vimeofeed_channel'] = $opt6;
}
