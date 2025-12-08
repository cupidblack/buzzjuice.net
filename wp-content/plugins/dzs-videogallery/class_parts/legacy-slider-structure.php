<?php
$this->sliderstructure.='<div class="setting type_all"  data-dependency=\''.$dependency.'\'">
  <div class="setting-label">' . esc_html__('Search Field Location', 'dzsvg') . '</div>
  <select class="textinput mainsetting styleme" name="0-settings-search_field_location">
    <option value="outside">' . esc_html__('Outside Gallery', 'dzsvg') . '</option>
    <option value="inside">' . esc_html__('Inside Gallery', 'dzsvg') . '</option>
  </select>
  <div class="sidenote" style="">' . esc_html__('search bar location', 'dzsvg') . '</div>
</div>

<div class="setting type_all">
  <div class="setting-label">' . esc_html__('Enable Linking', 'dzsvg') . '</div>
  <select class="textinput mainsetting styleme" name="0-settings-settings_enable_linking">
    <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
    <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
  </select>
  <div class="sidenote" style="">' . esc_html__('enable the possibility for the gallery to change the current link depending on the video played, this makes it easy to go to a current video based only on link', 'dzsvg') . '</div>
</div>


<div class="setting type_all">
  <div class="setting-label">' . esc_html__('Autoplay Ad', 'dzsvg') . '</div>
  <select class="textinput mainsetting styleme" name="0-settings-autoplay_ad">
  </select>
  <div class="sidenote" style="">' . esc_html__('autoplay the ad before a video or not - note that if the video autoplay then the ad will autoplay too before', 'dzsvg') . '</div>
</div>


<div class="setting type_all">
  <div class="setting-label">' . esc_html__('Resize Video Proportionally', 'dzsvg') . '</div>
  <select class="textinput mainsetting styleme" name="0-settings-set_responsive_ratio_to_detect">
    <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
    <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
  </select>
</div>
<div class="sidenote">' . esc_html__('Settings this to "on" will make an attempt to remove the black bars plus resizing the video proportionally for mobiles.', 'dzsvg') . '</div>


<hr/>
<div class="dzstoggle toggle1" rel="">
  <div class="toggle-title" style="">' . esc_html__('Social Options', 'dzsvg') . '</div>
  <div class="toggle-content">

    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Share Button', 'dzsvg') . '</div>
      <select class="textinput mainsetting styleme" name="0-settings-sharebutton">
        <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
        <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
      </select>
    </div>
    <div class="setting type_all">
      <div class="setting_label">' . esc_html__('Facebook Link', 'dzsvg') . '</div>
      <input type="text" class="textinput mainsetting" name="0-settings-facebooklink" value=""/>
      <div class="sidenote" style="">' . esc_html__('input here a full link to your facebook page ie. <strong><a href="https://www.facebook.com/digitalzoomstudio">https://www.facebook.com/digitalzoomstudio</a></strong> or input "<strong>{{share}}</strong>" and the button will share the current playing video', 'dzsvg') . '</div>
    </div>
    <div class="setting type_all">
      <div class="setting_label">' . esc_html__('Twitter Link', 'dzsvg') . '</div>
      <input type="text" class="textinput mainsetting" name="0-settings-twitterlink" value=""/>
    </div>
    <div class="setting type_all">
      <div class="setting_label">' . esc_html__('Google Plus Link', 'dzsvg') . '</div>
      <input type="text" class="textinput mainsetting" name="0-settings-googlepluslink" value=""/>
    </div>
    <div class="setting type_all">
      <div class="setting_label">' . esc_html__('Extra Social HTML', 'dzsvg') . '</div>
      <input type="text" class="textinput mainsetting" name="0-settings-social_extracode" value=""/>
      <div class="sidenote" style="">' . esc_html__('you can have here some extra social icons', 'dzsvg') . '</div>
    </div>
    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Embed Button', 'dzsvg') . '</div>
      <select class="textinput mainsetting styleme" name="0-settings-embedbutton">
        <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
        <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
      </select>
    </div>


    <div class="setting">
      <div class="setting_label">' . esc_html__('Logo', 'dzsvg') . '</div>
      <input type="text" class="textinput mainsetting" name="0-settings-logo" value=""/>' . $uploadbtnstring . '
    </div>
    <div class="setting">
      <div class="setting_label">' . esc_html__('Logo Link', 'dzsvg') . '</div>
      <input type="text" class="textinput mainsetting" name="0-settings-logoLink" value=""/>
    </div>
  </div>
</div>
<div class="dzstoggle toggle1" rel="">
  <div class="toggle-title" style="">' . esc_html__('Menu Options', 'dzsvg') . '</div>
  <div class="toggle-content">

    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Design Menu Item Width', 'dzsvg') . '</div>
      <input type="text" class="textinput mainsetting" name="0-settings-html5designmiw" value="275"/>
      <div class="sidenote" style="">' . esc_html__('these also control the width and height for wall items', 'dzsvg').'</div>
    </div>
    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Design Menu Item Height', 'dzsvg') . '</div>
      <input type="text" class="textinput mainsetting" name="0-settings-html5designmih" value="76"/>
      <div class="sidenote" style="">' . esc_html__('these also control the width and height for wall items ( for auto height leave blank here, on wall mode )', 'dzsvg') . '</div>
    </div>
    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Design Menu Item Space', 'dzsvg') . '</div>
      <input type="text" class="textinput mainsetting" name="0-settings-html5designmis" value="0"/>
    </div>

    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Thumbnail Extra Classes', 'dzsvg') . '</div>
      <input type="text" class="textinput mainsetting" name="0-settings-thumb_extraclass" value=""/>
      <div class="sidenote" style="">' . esc_html__('add a special class to the thumbnail like <strong>thumb-round</strong> for making the thumbnails rounded', 'dzsvg') . '</div>
    </div>


    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Disable Menu Description', 'dzsvg') . '</div>
      <select class="textinput mainsetting styleme" name="0-settings-disable_menu_description">
        <option>off</option>
        <option>on</option>
      </select>
    </div>
    



    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Lock Scroll', 'dzsvg') . '</div>
      <select class="textinput mainsetting styleme" name="0-settings-nav_type_auto_scroll">
        <option>off</option>
        <option>on</option>
      </select>
      <div class="sidenote" style="">' . esc_html__('for navigation type <strong>thumbs</strong> - LOCK SCROLL to current item ', 'dzsvg') . '</div>
    </div>


    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Menu Item Format', 'dzsvg') . '</div>
      <input type="text" class="textinput mainsetting" name="0-settings-menu_description_format" value=""/>
      <div class="sidenote" style="">';


$htmlSafe = sprintf(__('you can use something like %s{{number}}{{menuimage}}{{menutitle}}{{menudesc}}%s to display - menu item number , menu image, title and description or leave blank for default mode', 'dzsvg'),'<strong>','</strong>');

echo wp_kses($htmlSafe, array('strong' => array()));

echo '</div>
    </div>


  </div>
</div>
<div class="dzstoggle toggle1" rel="">
  <div class="toggle-title" style="">' . esc_html__('Design Options', 'dzsvg') . '</div>
  <div class="toggle-content">


    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Max Width', 'dzsvg') . '</div>
      <input type="text" class="textinput mainsetting" name="0-settings-max_width" value=""/>
      <div class="sidenote">' . esc_html__('Limit the max width of the gallery ( in pixels ) and center the gallery ', 'dzsvg') . '</div>
    </div>




    <div class="setting">
      <div class="setting_label">' . esc_html__('Cover Image', 'dzsvg') . '</div>
      <input type="text" class="textinput mainsetting" name="0-settings-coverImage" value=""/>' . $uploadbtnstring . '
      <div class="sidenote">A image that appears while the video is cued / not played</div>
    </div>


    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Navigation Space', 'dzsvg') . '</div>
      <input type="text" class="textinput mainsetting" name="0-settings-nav_space" value="0"/>
      <div class="sidenote" style="">' . esc_html__('space between navigation and video container', 'dzsvg') . '</div>
    </div>
    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Disable Menu Title', 'dzsvg') . '</div>
      <select class="textinput mainsetting styleme" name="0-settings-disable_title">
        <option>off</option>
        <option>on</option>
      </select>
    </div>
    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Disable Video Title', 'dzsvg') . '</div>
      <select class="textinput mainsetting styleme" name="0-settings-disable_video_title">
        <option>off</option>
        <option>on</option>
      </select>
    </div>

    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Laptop Skin', 'dzsvg') . '</div>
      <select class="textinput mainsetting styleme" name="0-settings-laptopskin">
        <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
        <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
      </select>
      <div class="sidenote" style="">' . esc_html__('apply a laptop container to the gallery', 'dzsvg') . '</div>
    </div>
    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Transition', 'dzsvg') . '</div>
      <select class="textinput mainsetting styleme" name="0-settings-html5transition">
        <option>slideup</option>
        <option>fade</option>
      </select>
    </div>

    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Right to Left', 'dzsvg') . '</div>
      <select class="textinput mainsetting styleme" name="0-settings-rtl">
        <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
        <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
      </select>
      <div class="sidenote" style="">' . esc_html__('enable RTL', 'dzsvg') . '</div>
    </div>



    <div class="setting">
      <div class="setting-label">' . esc_html__('Extra Classes', 'dzsvg') . '</div>
      <input type="text" class="textinput mainsetting" name="0-settings-extra_classes" value=""/>
      <div class="sidenote" style="">' . esc_html__('some extra css classes that you can use to stylize this gallery', 'dzsvg') . '</div>
    </div>



    <div class="setting">
      <div class="setting-label">' . esc_html__('Background', 'dzsvg') . '</div>
      <input type="text" class="textinput mainsetting with-colorpicker" name="0-settings-bgcolor" value="#444444"/><div class="picker-con"><div class="the-icon"></div><div class="picker"></div></div>
    </div>

    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Enable Shadow', 'dzsvg') . '</div>
      <select class="textinput mainsetting styleme" name="0-settings-shadow">
        <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
        <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
      </select>
    </div>


    <div class="dzstoggle toggle1" rel="">
      <div class="toggle-title" style="">' . esc_html__('Force Sizes', 'dzsvg') . '</div>
      <div class="toggle-content">

        <div class="setting type_all">
          <div class="setting-label">' . esc_html__('Force Width', 'dzsvg') . '</div>
          <input type="text" class="textinput mainsetting" name="0-settings-width" value="100%"/>
          <div class="sidenote">' . esc_html__('Leave "100%" for responsive mode. ', 'dzsvg') . '</div>
        </div>
        <div class="setting type_all">
          <div class="setting-label">' . esc_html__('Force Video Height', 'dzsvg') . '</div>
          <input type="text" class="textinput mainsetting" name="0-settings-forcevideoheight" value=""/>
          <div class="sidenote">' . esc_html__('Leave this blank if you want the video to autoresize. .', 'dzsvg') . '</div>
        </div>
      </div></div>

    <h5>' . esc_html__('Mode Wall Settings') . '</h5>

    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Layout for Mode Wall', 'dzsvg') . '</div>
      <select class="textinput mainsetting styleme" name="0-settings-mode_wall_layout">
        <option value="none">' . esc_html__('None', 'dzsvg') . '</option>
        <option value="dzs-layout--1-cols">' . esc_html__('1 column', 'dzsvg') . '</option>
        <option value="dzs-layout--2-cols">' . esc_html__('2 columns', 'dzsvg') . '</option>
        <option value="dzs-layout--3-cols">' . esc_html__('3 columns', 'dzsvg') . '</option>
        <option value="dzs-layout--4-cols">' . esc_html__('4 columns', 'dzsvg') . '</option>
      </select>
      <div class="sidenote" style="">' . esc_html__('the layout for the wall mode. using none will use the Design Menu Item Width and Design Menu Item Height for the item dimensions', 'dzsvg') . '</div>
    </div>


    <br>
  </div>
</div>


<div class="dzstoggle toggle1" rel="">
  <div class="toggle-title" style="">' . esc_html__('Description Options', 'dzsvg') . '</div>
  <div class="toggle-content">
    <div class="sidenote" style="font-size:14px;">' . esc_html__('some options regarding YouTube feed mode - playlist / user channel / ', 'dzsvg') . '</div>
    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Max Description Length', 'dzsvg') . '</div>
      <input type="text" class="textinput mainsetting" name="0-settings-maxlen_desc" value="250"/>
      <div class="sidenote" style="">' . esc_html__('youtube video descriptions will be retrieved through YouTube Data API. You can choose here the number of characters to retrieve from it. ', 'dzsvg') . '</div>
    </div>
    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Read More Markup ', 'dzsvg') . '</div>
      <input type="text" class="textinput mainsetting" name="0-settings-readmore_markup" value="<p><a class=ignore-zoombox href={{postlink}}>' . esc_html__('read more') . ' &raquo;</a></p>"/>
      <div class="sidenote" style="">' . '' . '</div>
    </div>
    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Strip HTML Tags', 'dzsvg') . '</div>
      <select class="textinput mainsetting styleme" name="0-settings-striptags">
        <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
        <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
      </select>
      <div class="sidenote" style="">' . esc_html__('video descriptions will be retrieved as html rich content. you can choose to strip the html tags to leave just simple text ', 'dzsvg') . '</div>
    </div>
    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Repair HTML Markup', 'dzsvg') . '</div>
      <select class="textinput mainsetting styleme" name="0-settings-try_to_close_unclosed_tags">
        <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
        <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
      </select>
      <div class="sidenote" style="">' . esc_html__('video descriptions will be retrieved as html rich content, some may be broken after shortage. attempt to repair this by setting this to <strong>on</strong>', 'dzsvg') . '</div>
    </div>';


    $lab = '0-settings-desc_different_settings_for_aside';
    //                                echo DZSHelpers::generate_input_text($lab,array('id' => $lab, 'val' => 'off','input_type'=>'hidden'));
    $this->sliderstructure .= '<div class="setting">
      <h4 class="setting-label">' . esc_html__('Aside Navigation has Different Settings?', 'dzsvg') . '</h4>
      <div class="dzscheckbox skin-nova">
        ' . DZSHelpers::generate_input_checkbox($lab, array('id' => $lab, 'class' => 'mainsetting', 'val' => 'on', 'seekval' => '')) . '
        <label for="' . $lab . '"></label>
      </div>
      <div class="sidenote">' . esc_html__('allow creating new accounts') . '</div>
    </div>';


    $this->sliderstructure .= '



    <div class="setting type_all appear-only-when-is-on-desc_different_settings_for_aside">
      <div class="setting-label">' . esc_html__('Max Description Length', 'dzsvg') . '</div>
      <input type="text" class="textinput mainsetting" name="0-settings-desc_aside_maxlen_desc" value="250"/>
      <div class="sidenote" style="">' . esc_html__('youtube video descriptions will be retrieved through YouTube Data API. You can choose here the number of characters to retrieve from it. ', 'dzsvg') . '</div>
    </div>
    <div class="setting type_all appear-only-when-is-on-desc_different_settings_for_aside">
      <div class="setting-label">' . esc_html__('Strip HTML Tags', 'dzsvg') . '</div>
      <select class="textinput mainsetting styleme" name="0-settings-desc_aside_striptags">
        <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
        <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
      </select>
      <div class="sidenote" style="">' . esc_html__('video descriptions will be retrieved as html rich content. you can choose to strip the html tags to leave just simple text ', 'dzsvg') . '</div>
    </div>
    <div class="setting type_all appear-only-when-is-on-desc_different_settings_for_aside">
      <div class="setting-label">' . esc_html__('Repair HTML Markup', 'dzsvg') . '</div>
      <select class="textinput mainsetting styleme" name="0-settings-desc_aside_try_to_close_unclosed_tags">
        <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
        <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
      </select>
      <div class="sidenote" style="">' . esc_html__('video descriptions will be retrieved as html rich content, some may be broken after shortage. attempt to repair this by setting this to <strong>on</strong>', 'dzsvg') . '</div>
    </div>





  </div>
</div>




<div class="dzstoggle toggle1" rel="">
  <div class="toggle-title" style="">' . esc_html__('Outer Parts', 'dzsvg') . '</div>
  <div class="toggle-content">

    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Second Con', 'dzsvg') . '</div>
      <select class="textinput mainsetting styleme" name="0-settings-enable_secondcon">
        <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
        <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
      </select>
      <div class="sidenote" style="">' . esc_html__('enable linking to a slider with titles and descriptions as seen in the demos. to insert the container in your page use this shortcode [dzsvg_secondcon id="theidofthegallery" extraclasses=""]', 'dzsvg') . '</div>

    </div>

    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Outer Navigation', 'dzsvg') . '</div>
      <select class="textinput mainsetting styleme" name="0-settings-enable_outernav">
        <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
        <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
      </select>
      <div class="sidenote" style="">' . esc_html__('enable linking to a outside navigation [dzsvg_outernav id="theidofthegallery" skin="oasis" extraclasses="" layout="layout-one-third" thumbs_per_page="9" ]', 'dzsvg') . '</div>

    </div>
    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Outer Navigation, Show Video Author', 'dzsvg') . '</div>
      <select class="textinput mainsetting styleme" name="0-settings-enable_outernav_video_author">
        <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
        <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
      </select>
      <div class="sidenote" style="">' . esc_html__('show the video author for YouTube channels and playlists', 'dzsvg') . '</div>

    </div>
    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Outer Navigation, Show Video Date', 'dzsvg') . '</div>
      <select class="textinput mainsetting styleme" name="0-settings-enable_outernav_video_date">
        <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
        <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
      </select>
      <div class="sidenote" style="">' . esc_html__('published date', 'dzsvg') . '</div>

    </div>


  </div>
</div>




<div class="dzstoggle toggle1" rel="">
  <div class="toggle-title" style="">' . esc_html__('Misc Options', 'dzsvg') . '</div>
  <div class="toggle-content">


    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Play Order', 'dzsvg') . '</div>
      <select class="textinput mainsetting styleme" name="0-settings-playorder">
        <option value="ASC">' . esc_html__('normal', 'dzsvg') . '</option>
        <option value="DESC">' . esc_html__('reverse', 'dzsvg') . '</option>
      </select>
      <div class="sidenote" style="">' . esc_html__('set to reverse for example to play the latest episode in a series first ... or for RTL configurations', 'dzsvg') . '</div>
    </div>


    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Initialize On', 'dzsvg') . '</div>
      <select class="textinput mainsetting styleme" name="0-settings-init_on">
        <option value="init">' . esc_html__('Init', 'dzsvg') . '</option>
        <option value="scroll">' . esc_html__('Scroll', 'dzsvg') . '</option>
      </select>
      <div class="sidenote" style="">' . esc_html__('init - at start // scroll - when visible in page view', 'dzsvg') . '</div>
    </div>

    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Ids Point to Source', 'dzsvg') . '</div>
      <select class="textinput mainsetting styleme" name="0-settings-ids_point_to_source">
        <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
        <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
      </select>
      <div class="sidenote" style="">' . esc_html__('the id of the video players can point to the source file used', 'dzsvg') . '</div>

    </div>

    <div class="setting type_all">
      <div class="setting-label">' . esc_html__('Autoplay on Mobiles', 'dzsvg') . '</div>
      <select class="textinput mainsetting styleme" name="0-settings-autoplayWithVideoMuted">
        <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
        <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
      </select>
      <div class="sidenote" style="">' . esc_html__('normally, videos cannot autoplay on mobiles to save bandwidth, but with newest standards videos are allowed to play, but muted - if your video has no sound you can choose this option to autoplay on mobiles', 'dzsvg') . '</div>

    </div>



  </div>
</div>

';
