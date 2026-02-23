<?php
if (!defined('ABSPATH')) // Or some other WordPress constant
  exit;

$dzsvg->videoplayerconfig = '<div class="slider-con" style="display:none;">
        
        <div class="settings-con">
        <h4>' . esc_html__('General Options', 'dzsvg') . '</h4>
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Config ID', 'dzsvg') . '</div>
            <input type="text" class="textinput mainsetting main-id" name="0-settings-id" value="default"/>
            <div class="sidenote">' . esc_html__('Choose an unique id.', 'dzsvg') . '</div>
        </div>
        <div class="setting styleme ">
            <div class="setting-label">' . esc_html__('Video Player Skin', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme dzs-dependency-field" name="0-settings-skin_html5vp">
                <option>skin_aurora</option>
                <option>skin_default</option>
                <option>skin_white</option>
                <option>skin_pro</option>
                <option>skin_bigplay</option>
                <option value="skin_noskin">' . esc_html__("No controls") . '</option>
                <option>skin_reborn</option>
                <option>skin_avanti</option>
                <option>skin_custom</option>
                <option>skin_custom_aurora</option>
            </select>
            <div class="sidenote">' . esc_html__('Skin Custom can be modified via Designer Center.', 'dzsvg') . '</div>
        </div>
        <div class="setting styleme">
            <div class="setting-label">' . esc_html__('Use Custom Colors ? ', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-use_custom_colors">
                <option>off</option>
                <option>on</option>
            </select>
            <div class="sidenote">' . sprintf(__('custom colors can be modified - %shere%s', 'dzsvg'), '<a href="' . admin_url("admin.php?page=" . DZSVG_PAGENAME_LEGACY_DESIGNER_CENTER) . '" target="_blank">', '</a>') . '</div>
        </div>
        <hr/>
        <div class="setting styleme">
            <div class="setting-label">' . esc_html__('Video Overlay', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-settings_video_overlay">
                <option>off</option>
                <option>on</option>
            </select>
            <div class="sidenote">' . esc_html__('an overlay over the video that you can press for pause / unpause', 'dzsvg') . '</div>
        </div>
        <div class="setting styleme">
            <div class="setting-label">' . esc_html__('Big Play Button', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme dzs-dependency-field" name="0-settings-settings_big_play_btn">
                <option>off</option>
                <option>on</option>
            </select>
            <div class="sidenote">' . esc_html__('show a big play button centered on video paused', 'dzsvg') . '</div>
        </div>

        
        
        ';


$dependency = array(

  array(
    'lab' => '0-settings-settings_big_play_btn',
    'val' => array('on'),
  ),
);


$dependency = json_encode($dependency);
$dependency = str_replace('"', '{{quot}}', $dependency);


$dzsvg->videoplayerconfig .= '<div class="setting styleme"  >
            <div class="setting-label">' . esc_html__('Disable Mouse Out Behaviour', DZSVG_ID) . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-settings_disable_mouse_out">
                <option value="off">' . esc_html__('Enable', DZSVG_ID) . '</option>
                <option value="on">' . esc_html__('Disable', DZSVG_ID) . '</option>
            </select>
            <div class="sidenote">' . esc_html__('some skins hide the controls on mouse out, you can disable this.', 'dzsvg') . '</div>
        </div>
        <div class="setting styleme"  data-dependency="' . $dependency . '">
            <div class="setting-label">' . esc_html__('Hide controls on paused', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-hide_on_paused">
                <option>off</option>
                <option>on</option>
            </select>
            <div class="sidenote">' . esc_html__('if big play button is enabled, controls can be hidden on paused too', 'dzsvg') . '</div>
        </div>
        <div class="setting ">
            <div class="setting-label">' . esc_html__('Hide controls on mouse out', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-hide_on_mouse_out">
                <option>off</option>
                <option>on</option>
            </select>
            <div class="sidenote">' . esc_html__('only for certain skins ( skin_aurora ) / only hides when video is playing', 'dzsvg') . '</div>
        </div>
        
        <div class="setting ">
            <div class="setting-label">' . esc_html__('Video Description Style on Video', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-video_description_style">
                <option value="none" >' . esc_html__("No show") . '</option>
                <option value="show-description" >' . esc_html__("Show Description") . '</option>
                <option value="gradient">' . esc_html__("Gradient Info on Paused") . '</option>
            </select>
            <div class="sidenote">' . esc_html__('choose how Video Description text shows', 'dzsvg') . '</div>
        </div>
        
        
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Delay on Which to Hide Controls', 'dzsvg') . '</div>
            <input type="text" class="textinput mainsetting" name="0-settings-settings_mouse_out_delay" value="100"/>
            <div class="sidenote">' . esc_html__('number of ms in which to delay the controls hiding', 'dzsvg') . '</div>
        </div>


        <div class="setting styleme">
            <div class="setting-label">' . esc_html__('Use the Custom Skin on iOS', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-settings_ios_usecustomskin">
                <option>on</option>
                <option>off</option>
            </select>
            <div class="sidenote">' . esc_html__('overwrites the default ios ( ipad and iphone ) skin with the skin you chose in the Video Player Configuration', 'dzsvg') . '</div>
        </div>
        <div class="setting styleme">
            <div class="setting-label">' . esc_html__('iOS video plays inline', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-settings_ios_playinline">
                <option>on</option>
                <option>off</option>
            </select>
            <div class="sidenote">' . esc_html__('choose if the video should play inline or fullscreen by default', 'dzsvg') . '</div>
        </div>

        <div class="setting ">
            <div class="setting-label">' . esc_html__('Send Google Analytics Event for Play', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-ga_enable_send_play">
                <option>off</option>
                <option>on</option>
            </select>
            <div class="sidenote">' . esc_html__('send the play event to google analytics to record gallery plays on your site / you need the google analytics wordpress plugin', 'dzsvg') . '</div>
        </div>

        <div class="setting ">
            <div class="setting-label">' . esc_html__('Video End Displays the Last Frame', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-settings_video_end_reset_time">
                <option>on</option>
                <option>off</option>
            </select>
            <div class="sidenote">' . esc_html__('available for the self hosted video type', 'dzsvg') . '</div>
        </div>

        <div class="setting ">
            <div class="setting-label">' . esc_html__('Laptop container', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-laptop_container">
                <option>off</option>
                <option>on</option>
            </select>
            <div class="sidenote">' . esc_html__('enable a laptop container for the video player', 'dzsvg') . '</div>
        </div>
        
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Normal Controls Opacity', 'dzsvg') . '</div>
            <input type="text" class="textinput mainsetting" name="0-settings-html5design_controlsopacityon" value="1"/>
            <div class="sidenote">' . esc_html__('Choose an opacity from 0 to 1', 'dzsvg') . '</div>
        </div>
        
        ';

$lab = 'preload_method';
$name = '0-settings-' . $lab;
$dzsvg->videoplayerconfig .= '

        <div class="setting ">
            <div class="setting-label">' . esc_html__('Preload video', 'dzsvg') . '</div>
            ';

$class = 'textinput mainsetting styleme';

$val = '';

$dzsvg->videoplayerconfig .= DZSHelpers::generate_select($name, array(
  'class' => $class,
  'seekval' => $val,
  'options' => array(
    array(
      'label' => esc_html__('Only metadata ( default )', 'dzsvg'),
      'value' => 'metadata',
    ),
    array(
      'label' => esc_html__('All video', 'dzsvg'),
      'value' => 'auto',
    ),
    array(
      'label' => esc_html__('No preloading', 'dzsvg'),
      'value' => 'none',
    ),
  ),
));

$dzsvg->videoplayerconfig .= '

            <div class="sidenote">' . esc_html__('available for the self hosted video type', 'dzsvg') . '</div>
        </div>';


$lab = 'settings_disableVideoArray';
$name = '0-settings-' . $lab;
$dzsvg->videoplayerconfig .= '

        <div class="setting ">
            <div class="setting-label">' . esc_html__('Pause other videos when playing one', 'dzsvg') . '</div>
            ';

$class = 'textinput mainsetting styleme';

$val = '';

$dzsvg->videoplayerconfig .= DZSHelpers::generate_select($name, array(
  'class' => $class,
  'seekval' => $val,
  'options' => array(
    array(
      'label' => esc_html__('Pause all other', 'dzsvg'),
      'value' => 'off',
    ),
    array(
      'label' => esc_html__('Do not pause', 'dzsvg'),
      'value' => 'on',
    ),

  ),
));

$dzsvg->videoplayerconfig .= '

            <div class="sidenote">' . esc_html__('available for the self hosted video type', 'dzsvg') . '</div>
        </div>';


$dependency = array(

  array(
    'lab' => '0-settings-skin_html5vp',
    'val' => array('skin_default', 'skin_aurora', 'skin_custom_aurora', 'skin_pro', 'skin_custom_pro'),
  ),
);


$dependency = json_encode($dependency);
$dependency = str_replace('"', '{{quot}}', $dependency);

$lab = '0-settings-enable_info_button';
$dzsvg->videoplayerconfig .= '
        
        <div class="setting type_all" data-dependency="' . $dependency . '">
            <div class="setting-label">' . esc_html__('Enable Info Button', 'dzsvg') . '</div>
            <div class="dzscheckbox skin-nova">
                                        ' . DZSHelpers::generate_input_checkbox($lab, array('id' => $lab, 'class' => 'mainsetting', 'val' => 'on', 'seekval' => '')) . '
                                        <label for="' . $lab . '"></label>
                                    </div>            
            <div class="sidenote">' . esc_html__('enable a extra button for video info', 'dzsvg') . '</div>
        </div>';


$lab = '0-settings-enable_link_button';
$dzsvg->videoplayerconfig .= '
        
        <div class="setting type_all" data-dependency="' . $dependency . '">
            <div class="setting-label">' . esc_html__('Enable Link Button', 'dzsvg') . '</div>
            <div class="dzscheckbox skin-nova">
                                        ' . DZSHelpers::generate_input_checkbox($lab, array('id' => $lab, 'class' => 'mainsetting', 'val' => 'on', 'seekval' => '')) . '
                                        <label for="' . $lab . '"></label>
                                    </div>            
            <div class="sidenote">' . esc_html__('enable a extra button for video info', 'dzsvg') . '</div>
        </div>';


$lab = '0-settings-enable_cart_button';
$dzsvg->videoplayerconfig .= '
        
        <div class="setting type_all" data-dependency="' . $dependency . '">
            <div class="setting-label">' . esc_html__('Enable Cart Button', 'dzsvg') . '</div>
            <div class="dzscheckbox skin-nova">
                                        ' . DZSHelpers::generate_input_checkbox($lab, array('id' => $lab, 'class' => 'mainsetting', 'val' => 'on', 'seekval' => '')) . '
                                        <label for="' . $lab . '"></label>
                                    </div>            
            <div class="sidenote">' . esc_html__('if this is linked to a WooCommerce product a Add to Cart button will appear in the player - you can input the product id in the media id of the player', 'dzsvg') . '</div>
        </div>';


$lab = '0-settings-enable_quality_changer_button';
$dzsvg->videoplayerconfig .= '
        
        <div class="setting type_all" data-dependency="' . $dependency . '">
            <div class="setting-label">' . esc_html__('Enable Quality Changer Button', 'dzsvg') . '</div>
            <div class="dzscheckbox skin-nova">
                                        ' . DZSHelpers::generate_input_checkbox($lab, array('id' => $lab, 'class' => 'mainsetting', 'val' => 'on', 'seekval' => '')) . '
                                        <label for="' . $lab . '"></label>
                                    </div>            
            <div class="sidenote">' . esc_html__('if this is an youtube video, the quality changer button will appear if there are multiple quality options', 'dzsvg') . '</div>
        </div>';


$lab = '0-settings-enable_multisharer_button';
$dzsvg->videoplayerconfig .= '
        
        <div class="setting type_all" data-dependency="' . $dependency . '">
            <div class="setting-label">' . esc_html__('Enable Multisharer Button', 'dzsvg') . '</div>
            <div class="dzscheckbox skin-nova">
                                        ' . DZSHelpers::generate_input_checkbox($lab, array('id' => $lab, 'class' => 'mainsetting', 'val' => 'on', 'seekval' => '')) . '
                                        <label for="' . $lab . '"></label>
                                    </div>            
            <div class="sidenote">' . esc_html__('clicking this button will open a lightbox full of share options', 'dzsvg') . '</div>
        </div>';

$lab = '0-settings-enable_mute_icon';
$dzsvg->videoplayerconfig .= '
        
        <div class="setting type_all" data-dependency="' . $dependency . '">
            <div class="setting-label">' . esc_html__('Mute icon', 'dzsvg') . '</div>
            <div class="dzscheckbox skin-nova">
                                        ' . DZSHelpers::generate_input_checkbox($lab, array('id' => $lab, 'class' => 'mainsetting', 'val' => 'on', 'seekval' => '')) . '
                                        <label for="' . $lab . '"></label>
                                    </div>            
            <div class="sidenote">' . esc_html__('show an icon when video is muted', 'dzsvg') . '</div>
        </div>';


$dzsvg->videoplayerconfig .= '
        
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Default Volume', 'dzsvg') . '</div>
            <input type="text" class="textinput mainsetting" name="0-settings-defaultvolume" value=""/>
            <div class="sidenote">' . wp_kses(sprintf(__('Enter a number from 0 to 1. For example for half volume enter %s0.5%s - for the last volume the user used input %slast%s', 'dzsvg'), '<strong>', '</strong>', '<strong>', '</strong>'), (DZSVG_HTML_ALLOWED_TAGS)) . '</div>
        </div>
        
<div class="dzstoggle toggle1" rel="">
<div class="toggle-title" style="">' . esc_html__('YouTube Options', 'dzsvg') . '</div>
<div class="toggle-content">
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('SD Quality', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-youtube_sdquality">
                <option>small</option>
                <option>medium</option>
                <option>default</option>
            </select>
        </div>
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('HD Quality', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-youtube_hdquality">
                <option>hd720</option>
                <option>hd1080</option>
                <option>default</option>
            </select>
        </div>
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Default Quality', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-youtube_defaultquality">
                <option value="hd">' . esc_html__('HD', 'dzsvg') . '</option>
                <option value="sd">' . esc_html__('SD', 'dzsvg') . '</option>
            </select>
        </div>
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Enable Custom Skin', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-yt_customskin">
                <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
                <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
            </select>
            <div class="sidenote">' . esc_html__('Choose if the custom skin you set in the Video Player Skin is how YouTube videos should show ( on )
                 or if the default YouTube skin should show ( off )', 'dzsvg') . '</div>
        </div>
</div>
</div>
        

<div class="dzstoggle toggle1" rel="">
<div class="toggle-title" style="">' . esc_html__('Vimeo Options', 'dzsvg') . '</div>
<div class="toggle-content">
        
                <div class="setting">
                    <div class="label">' . esc_html__('Vimeo Player Title', 'dzsvg') . '</div>
            <input type="text" class="textinput mainsetting" name="0-settings-vimeo_title" value="1"/>
                    <div class="sidenote">' . esc_html__('show the vimeo title in the vimeo default player', 'dzsvg') . '</div>
                </div>
        
                <div class="setting">
                    <div class="label">' . esc_html__('Vimeo Player Byline', 'dzsvg') . '</div>
            <input type="text" class="textinput mainsetting" name="0-settings-vimeo_byline" value="0"/>
                    <div class="sidenote">' . esc_html__('', 'dzsvg') . '</div>
                </div>
                <div class="setting">
                    <div class="label">' . esc_html__('Vimeo Player Portrait', 'dzsvg') . '</div>
            <input type="text" class="textinput mainsetting" name="0-settings-vimeo_portrait" value="1"/>
                    <div class="sidenote">' . esc_html__('show the vimeo author avatar', 'dzsvg') . '</div>
                </div>
                <div class="setting">
                    <div class="label">' . esc_html__('Vimeo Player Badge', 'dzsvg') . '</div>
            <input type="text" class="textinput mainsetting" name="0-settings-vimeo_badge" value="1"/>
                    <div class="sidenote">' . esc_html__('show the vimeo author badge', 'dzsvg') . '</div>
                </div>
                <div class="setting">
                    <div class="label">' . esc_html__('Vimeo Player Color', 'dzsvg') . '</div>
            <input type="text" class="textinput mainsetting" name="0-settings-vimeo_color" value=""/>
                    <div class="sidenote">' . esc_html__('input the color of controls in this format RRGGBB, ie. <strong>ffffff</strong> for white ', 'dzsvg') . '</div>
                </div>
                <div class="setting">
                    <div class="label">' . esc_html__('Vimeo player is chromeless', 'dzsvg') . '</div>
                <select class="textinput mainsetting styleme" name="0-settings-vimeo_is_chromeless">
                    <option value="off">' . esc_html__('no', 'dzsvg') . '</option>
                    <option value="on">' . esc_html__('yes', 'dzsvg') . '</option>
                </select>
                    <div class="sidenote">' . esc_html__('if you have vimeo plus membership you can make vimeo player have your own custom controls', 'dzsvg') . '</div>
                </div>
</div>
</div>
        
        </div><!--end settings con-->
        </div>';
