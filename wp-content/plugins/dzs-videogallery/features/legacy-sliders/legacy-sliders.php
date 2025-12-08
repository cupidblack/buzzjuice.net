<?php

function dzsvg_generateHtmlLegacySlider($vpconfigsstr) {
	global $dzsvg;


	$uploadbtnstring = '<button class="button-secondary action upload_file">' . esc_html__("Upload", 'dzsvg') . '</button>';
	$uploadbtnstring_video = '<button class="button-secondary action upload_file only-video upload-type-video">' . esc_html__("Upload", 'dzsvg') . '</button>';
	$fout = '<div class="slider-con" style="display:none;">
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Select Feed Mode', 'dzsvg') . '</div>
                <div class="main-feed-chooser select-hidden-metastyle">
                <select class="textinput mainsetting" name="0-settings-feedfrom">
                    <option value="normal">' . esc_html__('Normal', 'dzsvg') . '</option>
                    <option value="ytuserchannel">' . esc_html__('Youtube User Channel', 'dzsvg') . '</option>
                    <option value="ytplaylist">' . esc_html__('YouTube Playlist', 'dzsvg') . '</option>
                    <option value="ytkeywords">' . esc_html__('YouTube Keywords', 'dzsvg') . '</option>
                    <option value="vmuserchannel">' . esc_html__('Vimeo User Channel', 'dzsvg') . '</option>
                    <option value="vmchannel">' . esc_html__('Vimeo Channel', 'dzsvg') . '</option>
                    <option value="vmalbum">' . esc_html__('Vimeo Album', 'dzsvg') . '</option>
                    <option value="facebook">' . esc_html__('Facebook feed', 'dzsvg') . '</option>
                </select>
                <div class="option-con clearfix">
                    <div class="an-option">
                    <div class="an-title">
                    ' . esc_html__('Normal', 'dzsvg') . '
                    </div>
                    <div class="an-desc">
                    ' . esc_html__('Feed from custom items you set below.', 'dzsvg') . '
                    </div>
                    </div>
                    
                    <div class="an-option">
                    <div class="an-title">
                    ' . esc_html__('Youtube User Channel', 'dzsvg') . '
                    </div>
                    <div class="an-desc">
                    ' . esc_html__(' Feed videos from your YouTube User Channel.', 'dzsvg') . '
                   
                    </div>
                    </div>
                    
                    <div class="an-option">
                    <div class="an-title">
                    ' . esc_html__('YouTube Playlist', 'dzsvg') . '
                    </div>
                    <div class="an-desc">
                    ' . esc_html__('Feed videos from the YouTube Playlist you create on their site. Just input the Playlist ID below.', 'dzsvg') . '
                    
                    </div>
                    </div>
                    
                    <div class="an-option">
                    <div class="an-title">
                    ' . esc_html__('YouTube Keywords', 'dzsvg') . '
                    </div>
                    <div class="an-desc">
                    ' . sprintf(__('Feed videos by searching for keywords ie. %sfunny cat%s', 'dzsvg'), '<strong>', '</strong>') . '
                    </div>
                    </div>
                    
                    <div class="an-option">
                    <div class="an-title">
                    ' . esc_html__('Vimeo User Channel', 'dzsvg') . '
                    </div>
                    <div class="an-desc">
                    ' . esc_html__('Feed videos from your Vimeo User channel.', 'dzsvg') . '
                    </div>
                    </div>
                    
                    <div class="an-option">
                    <div class="an-title">
                    ' . esc_html__('Vimeo Channel', 'dzsvg') . '
                    </div>
                    <div class="an-desc">
                    ' . esc_html__('Feed videos from a Vimeo Channel.', 'dzsvg') . '
                    </div>
                    </div>
                    
                    <div class="an-option">
                    <div class="an-title">
                    ' . esc_html__('Vimeo Album', 'dzsvg') . '
                    </div>
                    <div class="an-desc">
                    ' . esc_html__('Feed videos from a Vimeo Album.', 'dzsvg') . '
                    </div>
                    </div>
                    
                    <div class="an-option">
                    <div class="an-title">
                    ' . esc_html__('Facebook feed', 'dzsvg') . '
                    </div>
                    <div class="an-desc">
                    ' . esc_html__('input a facebook link', 'dzsvg') . '
                    </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="settings-con">
        <h4>' . esc_html__('General Options', 'dzsvg') . '</h4>
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('ID', 'dzsvg') . '</div>
            <input type="text" class="textinput mainsetting main-id" name="0-settings-id" value="default"/>
            <div class="sidenote">' . esc_html__('Choose an unique id. Do not use spaces, do not use special characters.', 'dzsvg') . '</div>
        </div>
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Force Height', 'dzsvg') . '</div>
            <input type="text" class="textinput mainsetting" name="0-settings-height" value=""/>
        </div>
        <div class="setting styleme">
            <div class="setting-label">' . esc_html__('Display Mode', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme has-extra-desc" name="0-settings-displaymode">
                <option>normal</option>
                <option>wall</option>
                <option>rotator</option>
                <option>rotator3d</option>
                <option>slider</option>
                <option>videowall</option>    
            </select>
            
            <div class="extra-desc">
            
            
            <div class="bigoption select-option ">
            <div class="option-con">
            <div class="divimage" data-src="https://i.imgur.com/3iRmYlc.jpg"></div>
            <span class="option-label">' . esc_html__("Default", DZSVG_ID) . '</span>
            </div>
            </div>
            
            <div class="bigoption select-option ">
            <div class="option-con">
            <div class="divimage" data-src="https://i.imgur.com/YhYVMd9.jpg"></div>
            <span class="option-label">' . esc_html__("Wall") . '</span>
            </div>
            </div>
            
            <div class="bigoption"></div>
            
            <div class="bigoption select-option ">
            <div class="option-con">
            <div class="divimage" data-src="https://i.imgur.com/wQrkSkv.jpg"></div>
            <span class="option-label">' . esc_html__("Rotator 3D") . '</span>
            </div>
            </div>
            
            
            <div class="bigoption"></div>
            
            <div class="bigoption select-option ">
            <div class="option-con">
            <div class="divimage" data-src="https://i.imgur.com/1jThnc7.jpg"></div>
            <span class="option-label">' . esc_html__("Video Wall") . '</span>
            </div>
            </div>
            
            
            
            </div>
            
            
            
        </div>
        <div class="setting styleme">
            <div class="setting-label">' . esc_html__('Video Gallery Skin', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-skin_html5vg">
                <option value="skin-default">' . esc_html__("Default", 'dzsvg') . ' </option>
                <option value="skin-pro">' . esc_html__("Skin", 'dzsvg') . ' Pro </option>
                <option value="skin-boxy">' . esc_html__("Skin", 'dzsvg') . ' Boxy </option>
                <option value="skin-boxy skin-boxy--rounded">' . esc_html__("Skin", 'dzsvg') . ' Boxy Rounded</option>
                <option value="skin-aurora">' . esc_html__("Skin", 'dzsvg') . ' Aurora</option>
                <option value="skin-navtransparent">' . esc_html__("Skin", 'dzsvg') . ' NavTransparent</option>
                <option value="skin-custom">' . esc_html__("Custom Skin", 'dzsvg') . '</option>
            </select>
            <div class="sidenote">' . esc_html__('Skin Custom can be modified via Designer Center.', 'dzsvg') . '</div>
        </div>
        
        
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Video Player Configuration', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-vpconfig">
                <option value="default">' . esc_html__('default', 'dzsvg') . '</option>
                ' . $vpconfigsstr . '
            </select>
            <div class="sidenote" style="">' . esc_html__('setup these inside the <strong>Video Player Configs</strong> admin', 'dzsvg') . ' <a id="quick-edit" class="quick-edit-vp" href="' . admin_url('admin.php?page=' . DZSVG_PAGENAME_VPCONFIGS . '&currslider=0&from=shortcodegenerator') . '" class="sidenote" style="cursor:pointer;">' . esc_html__("Quick Edit ") . '</a></div>
        </div>
        
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Navigation Style', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme dzs-dependency-field" name="0-settings-nav_type">
                <option>thumbs</option>
                <option>thumbsandarrows</option>
                <option>scroller</option>
                <option>outer</option>
                <option>none</option>
            </select>
            <div class="sidenote">' . esc_html__('Choose a navigation style for the normal display mode.', 'dzsvg') . '</div>
        </div>';


	$dependency = array(

		array(
			'lab' => '0-settings-nav_type',
			'val' => array('outer'),
		),
	);


	$dependency = json_encode($dependency);
	$fout .= '<div class="setting type_all"  data-dependency=\'' . $dependency . '\'">
            <div class="setting-label">' . esc_html__('Max. Height For Navigation', 'dzsvg') . '</div>
            <input type="text" class="textinput mainsetting" name="0-settings-nav_type_outer_max_height" value=""/>
            <div class="sidenote" style="">' . esc_html__('input a maximum height for the outer navigation ( only for bottom and top menu positions ) - if the content is larger, then a scrollbar will appear', 'dzsvg') . '</div>
        </div>
        
        
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Menu Position', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-menuposition">
                <option>right</option>
                <option>bottom</option>
                <option>left</option>
                <option>top</option>
                <option>none</option>
            </select>
        </div>
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Autoplay', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-autoplay">
                <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
                <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
            </select>
        </div>
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Autoplay Next', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-autoplaynext">
                <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
                <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
            </select>
            <div class="sidenote">' . esc_html__('autoplay next track when selecting in the menu or when the current video has ended', 'dzsvg') . '</div>
        </div>
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Cue First Video', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-cueFirstVideo">
                <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
                <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
            </select>
            <div class="sidenote">' . esc_html__('Choose if the video should load at start or it should activate on click ( if a <strong>Cover Image</strong> is set ).', 'dzsvg') . '</div>
            
        </div>
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Randomize / Shuffle Elements', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-randomize">
                <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
                <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
            </select>
        </div>
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Order', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-order">
                <option value="ASC">' . esc_html__('ascending', 'dzsvg') . '</option>
                <option value="DESC">' . esc_html__('descending', 'dzsvg') . '</option>
            </select>
        </div>
        
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Transition', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-transition">
                <option value="fade">' . esc_html__('Fade', 'dzsvg') . '</option>
                <option value="slidein">' . esc_html__('Slide In', 'dzsvg') . '</option>
            </select>
            <div class="sidenote" style="">' . esc_html__('set the transition of the gallery  ( when it loads ) ', 'dzsvg') . '</div>
        </div>

        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Enable Underneath Description', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-enableunderneathdescription">
                <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
                <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
            </select>
            <div class="sidenote" style="">' . esc_html__('add a title and description holder underneath the gallery', 'dzsvg') . '</div>
        </div>

        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Enable Search Field', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme  dzs-dependency-field" name="0-settings-enable_search_field">
                <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
                <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
            </select>
            <div class="sidenote" style="">' . esc_html__('enable a search field inside the gallery', 'dzsvg') . '</div>
        </div>';


	$dependency = array(
		array(
			'lab' => '0-settings-enable_search_field',
			'val' => array('on'),
		),
	);


	$dependency = json_encode($dependency);
	$fout .= '<div class="setting type_all"  data-dependency=\'' . $dependency . '\'">
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
                <option value="on">' . esc_html__('on', 'dzsvg') . '</option>
                <option value="off">' . esc_html__('off', 'dzsvg') . '</option>
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
            <div class="sidenote" style="">' . esc_html__('these also control the width and height for wall items', 'dzsvg') . '</div>
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
            <div class="setting-label">' . esc_html__('Enable Easing on Menu', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-design_navigationuseeasing">
                <option>off</option>
                <option>on</option>
            </select>
                <div class="sidenote" style="">' . esc_html__('for navigation type <strong>thumbs</strong> - use a easing on mouse tracking ', 'dzsvg') . '</div>
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
            <div class="sidenote" style="">' . sprintf(__('you can use something like %s{{number}}{{menuimage}}{{menutitle}}{{menudesc}}%s to display - menu item number , menu image, title and description or leave blank for default mode', 'dzsvg'), '<strong>', '</strong>') . '</div>
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
	$fout .= '<div class="setting">
                                    <h4 class="setting-label">' . esc_html__('Aside Navigation has Different Settings?', 'dzsvg') . '</h4>
                                    <div class="dzscheckbox skin-nova">
                                        ' . DZSHelpers::generate_input_checkbox($lab, array('id' => $lab, 'class' => 'mainsetting', 'val' => 'on', 'seekval' => '')) . '
                                        <label for="' . $lab . '"></label>
                                    </div>
                                    <div class="sidenote">' . esc_html__('allow creating new accounts') . '</div>
                                </div>';


	$fout .= '



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


	if ($dzsvg->mainoptions['enable_developer_options'] == 'on') {

		$fout .= '


        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Javascript on Playlist End', 'dzsvg') . '</div>
            
            ' . DZSHelpers::generate_input_textarea('0-settings-action_playlist_end', array(
				'class' => 'textinput mainsetting',
			)) . '
                <div class="sidenote" style="">' . esc_html__('write a javascript function that happens on playlist end ', 'dzsvg') . '</div>

        </div>
    
';
	}


	$fout .= '
        
        
        </div><!--end settings con-->
        <div class="modes-con">
        
        <div class="setting mode_ytuserchannel">
            <div class="setting_label">' . esc_html__('YouTube User', 'dzsvg') . '</div>
            <input type="text" class="short textinput mainsetting" name="0-settings-youtubefeed_user" value=""/>
        </div>
	<div class="setting mode_ytplaylist">
            <div class="setting_label">' . esc_html__('YouTube Playlist', 'dzsvg') . '
                <div class="info-con">
                <div class="info-icon"></div>
                <div class="sidenote">' . esc_html__('You need to set the playlist ID there not the playlist Name. For example for this playlist http:' . '/' . '' . '/' . 'www.youtube.com/my_playlists?p=PL08BACDB761A0C52A the id is 08BACDB761A0C52A. Remember that if you have the characters PL at the beggining of the ID they should not be included here.', 'dzsvg') . '</div>
                </div>
</div>
                
                <input type="text" class="short textinput mainsetting" name="0-settings-ytplaylist_source" value=""/>
        </div>
	<div class="setting mode_ytkeywords">
            <div class="setting_label">' . esc_html__('YouTube Keywords', 'dzsvg') . '
                <div class="info-con">
                <div class="info-icon"></div>
                <div class="sidenote">' . '' . '</div>
                </div>
                </div>

                <input type="text" class="short textinput mainsetting" name="0-settings-ytkeywords_source" value=""/>
        </div>
        <div class="setting type_all mode_ytuserchannel mode_ytplaylist mode_ytkeywords">
            <div class="setting-label">' . esc_html__('YouTube Max Videos', 'dzsvg') . '</div>
            <input type="text" class="textinput mainsetting" name="0-settings-youtubefeed_maxvideos" value="50"/>
            <div class="sidenote">' . esc_html__('input a limit of videos here ( can be a maximum of 50 ) if you have more then 50 videos in your stream, just input "<strong>all</strong>" in this field ( without quotes ) ', 'dzsvg') . '</div>
        </div>';


	if (ini_get('allow_url_fopen') || function_exists('curl_version')) {
	} else {

		$fout .= '<div class="setting type_all mode_ytuserchannel mode_ytplaylist mode_ytkeywords">
            <div class="setting-label warning">' . esc_html__('warning - curl nor allow_furl_open enabled on your server ..  / ask your server to enable any of these', 'dzsvg') . '</div>
        </div>';
	}


	$fout .= '<div class="setting type_all mode_vmuserchannel">
            <div class="setting_label">' . esc_html__('Vimeo User ID', 'dzsvg') . '</div>
            <input type="text" class="textinput mainsetting" name="0-settings-vimeofeed_user" value=""/>
            <div class="sidenote">' . sprintf(__('be sure this to be your user id . For example mine is user5137664 even if my name is digitalzoomstudio  %s  you get that by checking your profile link.', 'dzsvg'), '- https://vimeo.com/user5137664 -') . '</div>
        </div>
        
        <div class="setting type_all mode_vmchannel">
            <div class="setting_label">' . esc_html__('Vimeo Channel ID', 'dzsvg') . '</div>
            <input type="text" class="textinput mainsetting" name="0-settings-vimeofeed_channel" value=""/>
            <div class="sidenote">' . esc_html__('be sure all videos are allowed to be embedded . Channel example for  – https://vimeo.com/channels/636900 - is <strong>636900</strong>.', 'dzsvg') . '</div>
        </div>
        
        <div class="setting type_all mode_vmalbum">
            <div class="setting_label">' . esc_html__('Vimeo Album ID', 'dzsvg') . '</div>
            <input type="text" class="textinput mainsetting" name="0-settings-vimeofeed_vmalbum" value=""/>
            <div class="sidenote">' . esc_html__('be sure all videos are allowed to be embedded . Channel example for  – https://vimeo.com/album/2633720 - is <strong>2633720</strong>.', 'dzsvg') . '</div>
        </div>


        <div class="setting type_all mode_vmuserchannel mode_vmchannel mode_vmalbum">
            <div class="setting-label">' . esc_html__('Vimeo Max Videos', 'dzsvg') . '</div>
            <input type="text" class="textinput mainsetting" name="0-settings-vimeo_maxvideos" value="25"/>
            <div class="sidenote">' . esc_html__('input a limit of videos here - note that if you have not set a Vimeo API oAuth login <a href="admin.php?page=' . DZSVG_PAGENAME_MAINOPTIONS . '">here</a> /  the limit will be 20 videos with no api setup', 'dzsvg') . '</div>
        </div>
        
        <div class="setting type_all mode_facebook">
            <div class="setting_label">' . esc_html__('Facebook url', 'dzsvg') . '</div>
            <input type="text" class="textinput mainsetting" name="0-settings-facebook_url" value=""/>
            <div class="sidenote">' . esc_html__('input full facebook page url', 'dzsvg') . '</div>
        </div>


        <div class="setting type_all mode_facebook">
            <div class="setting-label">' . esc_html__('Facebook Max Videos', 'dzsvg') . '</div>
            <input type="text" class="textinput mainsetting" name="0-settings-vimeo_maxvideos" value="25"/>
            <div class="sidenote">' . esc_html__('input a limit of videos here - note that if you have not set a Facebook API oAuth login ', 'dzsvg') . '</div>
        </div>


        <div class="setting type_all mode_vmuserchannel mode_vmchannel mode_vmalbum">
            <div class="setting-label">' . esc_html__('Vimeo Sort Mode', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-vimeo_sort">
                <option value="default">' . esc_html__("Default", 'dzsvg') . '</option>
                <option value="manual">' . esc_html__('Manual', 'dzsvg') . '</option>
                <option value="date">' . esc_html__('By Date', 'dzsvg') . '</option>
                <option value="alphabetic">' . esc_html__('Alphabetic', 'dzsvg') . '</option>
                <option value="plays">' . esc_html__('Number plays', 'dzsvg') . '</option>
                </select>
            <div class="sidenote">' . esc_html__('Default means as served by vimeo by default / Manual means as sorted in album settings', 'dzsvg') . '</div>
        </div>
        
</div>
        <div class="master-items-con mode_normal">
        <div class="items-con "></div>
        <a href="#" class="add-item"></a>
        </div><!--end master-items-con-->
        <div class="clear"></div>
        </div>';

	return $fout;

}
