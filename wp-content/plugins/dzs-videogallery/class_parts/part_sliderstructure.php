<?php
if ( ! defined( 'ABSPATH' ) ) // Or some other WordPress constant
  exit;

// -- figure out slider structure for
$uploadbtnstring = '<button class="button-secondary action upload_file">'.esc_html__("Upload",'dzsvg').'</button>';
$uploadbtnstring_video = '<button class="button-secondary action upload_file only-video upload-type-video">'.esc_html__("Upload",'dzsvg').'</button>';



$this->itemstructure = '<div class="item-con">
            <div class="item-delete">x</div>
            <div class="item-duplicate"></div>
        <div class="item-preview" style="">
        </div>
        <div class="item-settings-con">
        <div class="setting type_all">
            <h4 class="non-underline"><span class="underline">' . esc_html__('Type', 'dzsvg') . '*</span>&nbsp;&nbsp;&nbsp;<span class="sidenote">' . esc_html__('select one from below', 'dzsvg') . '</span></h4> 
            
            <div class="main-feed-chooser select-hidden-metastyle select-hidden-foritemtype">
                <select class="textinput item-type" data-label="type" name="0-0-type">
            <option>youtube</option>
            <option>video</option>
            <option>vimeo</option>
            <option>audio</option>
            <option>image</option>
            <option>link</option>
            <option>rtmp</option>
            <option>dash</option>
            <option>facebook</option>
            <option>inline</option>
                </select>
                <div class="option-con clearfix">
                    <div class="an-option dzstooltip-con">
                    <div class="an-title">
                    ' . esc_html__('YouTube', 'dzsvg') . '
                    </div>
                    <div class="an-desc dzstooltip skin-black arrow-bottom align-left">
                    ';

$safeHtml = sprintf(__('Input in the %sSource%s field below the youtube video ID. You can find the id contained in the link to 
                    the video - https://www.youtube.com/watch?v=<strong>ZdETx2j6bdQ</strong> ( for example )', 'dzsvg'),'<strong>','</strong>');

$this->itemstructure.=wp_kses_post($safeHtml);

$this->itemstructure.=  '
                    </div>
                    </div>
                    
                    <div class="an-option  dzstooltip-con">
                    <div class="an-title">
                    ' . esc_html__('Self-hosted Video', 'dzsvg') . '
                    </div>
                    <div class="an-desc dzstooltip skin-black arrow-bottom align-left">
                    ';

$safeHtml = sprintf(__('Stream videos your own hosted videos. You just have to include two formats of the video you are streaming. In the %sSource%s
                    field you need to include the path to your mp4 formatted video. And in the OGG field there should be the ogg / ogv path, this is not mandatory, 
                    but recommended.', 'dzsvg'),'<strong>','</strong>');

$this->itemstructure.= wp_kses_post($safeHtml);

$this->itemstructure.= ' <a href="' . DZSVG_URL . 'readme/index.html#handbrake" target="_blank" class="">Documentation here</a>.
                    </div>
                    </div>
                    
                    <div class="an-option  dzstooltip-con">
                    <div class="an-title">
                    ' . esc_html__('Vimeo Video', 'dzsvg') . '
                    </div>
                    <div class="an-desc dzstooltip skin-black arrow-bottom align-left">
                    ' . sprintf(__('Insert in the %sSource%s field the ID of the Vimeo video you want to stream. You can identify the ID easy from the link of the video,
                     for example, here see the bolded part', 'dzsvg'),'<strong>','</strong>') . ' - https://vimeo.com/<strong>55698309</strong>
                    </div>
                    </div>
                    
                    <div class="an-option  dzstooltip-con">
                    <div class="an-title">
                    ' . esc_html__('Self-hosted Audio File', 'dzsvg') . '
                    </div>
                    <div class="an-desc dzstooltip skin-black arrow-bottom align-left">
                    ' . esc_html__('You need a MP3 format of your audio file and an OGG format. You put their paths in the Source and Html5 Ogg Format fields', 'dzsvg') . '
                    </div>
                    </div>
                    
                    <div class="an-option  dzstooltip-con">
                    <div class="an-title">
                    ' . esc_html__('Self-hosted Image File', 'dzsvg') . '
                    </div>
                    <div class="an-desc dzstooltip skin-black arrow-bottom align-left">
                    ' . sprintf(esc_html__('Just put in the %sSource%s field the path to your image.', 'dzsvg'),'<strong>','</strong>') . '
                    </div>
                    </div>
                    
                    <div class="an-option  dzstooltip-con">
                    <div class="an-title">
                    ' . esc_html__('A link', 'dzsvg') . '
                    </div>
                    <div class="an-desc dzstooltip skin-black arrow-bottom align-left">
                    ' . esc_html__('Link where the visitor should go when clicking the menu item.', 'dzsvg') . '
                    </div>
                    </div>
                    
                    <div class="an-option  dzstooltip-con">
                    <div class="an-title">
                    ' . esc_html__('RTMP File', 'dzsvg') . '
                    </div>
                    <div class="an-desc dzstooltip skin-black arrow-bottom align-left">
                    ' . sprintf(__('For advanced users - if you have a rtmp server - input the server in the %sStream Server%s from the left and input here in the <strong>Source</strong> the location of the file on the server..', 'dzsvg'),'<strong>','</strong>') . '
                    </div>
                    </div>

                    <div class="an-option  dzstooltip-con">
                    <div class="an-title">
                    ' . esc_html__('Dash Mpeg Stream', 'dzsvg') . '
                    </div>
                    <div class="an-desc dzstooltip skin-black arrow-bottom align-left">
                    ' . sprintf(__('Input the link to the manifest file in the %sSource%s field. To use dash, ofcourse, you need some kind of streaming server like Wowza Streaming Server ', 'dzsvg'),'<strong>','</strong>') . '
                    </div>
                    </div>

                    <div class="an-option  dzstooltip-con">
                    <div class="an-title">
                    ' . esc_html__('Facebook video', 'dzsvg') . '
                    </div>
                    <div class="an-desc dzstooltip skin-black arrow-bottom align-left">
                    ' . sprintf(__('input the id of a facebook video', 'dzsvg'),'<strong>','</strong>') . '
                    </div>
                    </div>
                    
                    <div class="an-option  dzstooltip-con">
                    <div class="an-title">
                    ' . esc_html__('Inline Content', 'dzsvg') . '
                    </div>
                    <div class="an-desc dzstooltip skin-black arrow-bottom align-left">
                    ' . sprintf(__('Insert in the %sSource%s field custom content ( ie. embed from a custom site like dailymotion).', 'dzsvg'),'<strong>','</strong>') . '
                    </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Source', 'dzsvg') . '*
                <div class="info-con">
                <div class="info-icon"></div>
                <div class="sidenote">' . sprintf(__('Below you will enter your video address. If it is a video from YouTube or Vimeo you just need to enter 
                the id of the video in the "Video:" field. The ID is the bolded part https://www.youtube.com/watch?v=%sj_w4Bi0sq_w%s. 
                If it is a local video you just need to write its location there or upload it through the Upload button ( .mp4 / .flv format ).', 'dzsvg'),'<strong>','</strong>') . '
                    </div>
                </div>
            </div>
<textarea class="textinput main-source type_all upload-type-video" data-label="source" name="0-0-source" style="width:320px; height:29px;">Hv7Jxi_wMq4</textarea>' . $uploadbtnstring_video . '
        </div>
        
        
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Manage Ads', 'dzsvg') . '</div>
            <input type="text" class="textinput upload-prev upload-type-video big-field" name="0-0-adarray" value=""/><a class=" button-secondary quick-edit-adarray" href="#" style="cursor:pointer;">'.__("Edit Ads").'</a>
            <div class="sidenote">' . esc_html__('input here optional ads at custom times', 'dzsvg') . '</div>
        </div>


        <div class="setting type_link">
            <div class="setting-label">' . esc_html__('Link Target', 'dzsvg') . '</div>
            <select class="textinput mainsetting styleme" name="0-settings-link_target">
                <option value="_self">' . esc_html__('Open Same Window', 'dzsvg') . '</option>
                <option value="_blank">' . esc_html__('Open New Window', 'dzsvg') . '</option>
            </select>
        </div>
        
        
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Loop', 'dzsvg') . '</div>
            <select class="textinput styleme type_all" name="0-0-loop">
            <option>off</option>
            <option>on</option>
            </select>
                <div class="sidenote">' . esc_html__('play the video again when in reaches the end', 'dzsvg') . '</div>
        </div>
';


if(defined('DZSVG_360_ITEM_EXTRA1')){
  $this->itemstructure.=DZSVG_360_ITEM_EXTRA1;
}


$this->itemstructure.='
        
<div class="dzstoggle toggle1" rel="">
<div class="toggle-title" style="">' . esc_html__('Link Settings', 'dzsvg') . '</div>
<div class="toggle-content">
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Link', 'dzsvg') . '</div>
            <input type="text" class="textinput upload-prev upload-type-video big-field" name="0-0-link" value=""/><div class="sidenote">'  . sprintf(__('If %sEnable Link Button%s is enabled in the Video Player Configurations, then you can place a link here to appear in the video player buttons'),'<strong>','</strong>') . '</div>
       
        </div>
        
        
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Link Label', 'dzsvg') . '</div>
            <input type="text" class="textinput upload-prev upload-type-video big-field" name="0-0-link_label" value=""/><div class="sidenote">'  . sprintf(__('the link text')) . '</div>
       
        </div>
        
        
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Link to Product', 'dzsvg') . '</div>
            <input type="text" class="textinput upload-prev upload-type-video big-field" name="0-0-mediaid" value=""/><div class="sidenote">'  . sprintf(__('you can input here a woocommerce product id in order for the  ')) . '</div>
       
        </div>
        
        
        <div class="setting type_normal">
            <div class="setting-label">HTML5 OGG ' . esc_html__('Format', 'dzsvg') . '</div>
            <input type="text" class="textinput upload-prev upload-type-video big-field" name="0-0-html5sourceogg" value=""/>' . $uploadbtnstring . '
            <div class="sidenote">' . esc_html__('Optional ogg / ogv file', 'dzsvg') . ' / ' . esc_html__('Only for the Video or Audio type', 'dzsvg') . '</div>
        </div>
        
        </div>
        </div>
        
<div class="dzstoggle toggle1" rel="">
<div class="toggle-title" style="">' . esc_html__('Appearance Settings', 'dzsvg') . '</div>
<div class="toggle-content">
        <div class="setting type_all  ">
            <div class="setting-label">' . esc_html__('Thumbnail', 'dzsvg') . '</div>
            <input type="text" class="textinput main-thumb" name="0-0-thethumb"/>' . $uploadbtnstring . ' 
                <button class="refresh-main-thumb button-secondary">' . esc_html__('Refresh Thumbnail', 'dzsvg') . '</button>
                <div class="sidenote">' . esc_html__('Refresh the thumbnail if its a vimeo or youtube video', 'dzsvg') . '</div>
        </div>
        
        
        <div class="setting type_normal">
            <div class="setting-label">' . esc_html__('Manage Qualities', 'dzsvg') . '</div>
            <input type="text" class="textinput upload-prev upload-type-video big-field" name="0-0-qualities" value=""/><a class=" button-secondary quick-edit-qualityarray" href="#" style="cursor:pointer;">'.__("Edit Qualities").'</a>
            <div class="sidenote">' . esc_html__('input here optional qualities', 'dzsvg') . '</div>
        </div>

        
        <div class="dzs-row">
        <div class="dzs-col-md-6">
            <div class="setting type_all ">
                <div class="setting-label">' . esc_html__('Menu Title', 'dzsvg') . '</div>
                <input type="text" class="textinput" name="0-0-title"/>
            </div>
            
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Play From', 'dzsvg') . '</div>
            <input class="textinput upload-prev" name="0-0-playfrom" value=""/>
            <div class="sidenote">' . esc_html__('you can input a number ( seconds ) for the initial play status. or just input "last" for the video to come of where it has last been left', 'dzsvg') . '</div>
        </div>
        </div>
        <div class="dzs-col-md-6">
            <div class="setting type_all ">
                <div class="setting-label">' . esc_html__('Video Description', 'dzsvg') . ':</div>
                <textarea class="textinput" name="0-0-description"></textarea>
            </div>
            <div class="setting type_all  ">
                <div class="setting-label">' . esc_html__('Menu Description', 'dzsvg') . '</div>
                <textarea class="textinput" name="0-0-menuDescription"></textarea>
                    <div class="sidenote">' . esc_html__('This description will appear in the menu', 'dzsvg') . '</div>
            </div>
        </div>
        </div>
        <div class="clear"></div>

            <div class="setting type_all ">
                <div class="setting-label">' . esc_html__('Total Duration', 'dzsvg') . '</div>
                <input type="text" class="textinput" name="0-0-total_duration"/>
            </div>
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Preview Image', 'dzsvg') . '</div>
            <input class="textinput upload-prev" name="0-0-audioimage" value=""/>' . $uploadbtnstring . '
            <div class="sidenote">' . esc_html__('will be used as the background image for audio type too', 'dzsvg') . '</div>
        </div>
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Tags', 'dzsvg') . '</div>
            <input class="textinput tageditor-prev" name="0-0-tags" value=""/><button class="button-secondary btn-tageditor">Tag Editor</button>
            <div class="sidenote">' . esc_html__('use the tag editor to generate tags at given times of the video', 'dzsvg') . '</div>
        </div>
        

        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Subtitle File', 'dzsvg') . '</div>
            <input class="textinput upload-prev" name="0-0-subtitle_file" value=""/>' . $uploadbtnstring . '
            <div class="sidenote">' . esc_html__('you can upload a srt file for optional captioning on the video - it is recommeded  you rename the .srt file to .html format if you want to use the wordpress uploader ( security issues ) ', 'dzsvg') . '</div>
        </div>
        <div class="setting type_all">
            <div class="setting-label">' . esc_html__('Responsive Ratio', 'dzsvg') . '</div>
            <input class="textinput upload-prev" name="0-0-responsive_ratio" value=""/>
            <div class="sidenote">' . esc_html__('set a responsive ratio height/ratio 0.5 means that the player height will resize to 0.5 of the gallery width / or just set it to "detect" and it will autocalculate the ratios if it is a self hosted mp4', 'dzsvg') . '</div>
        </div>
</div>
</div>
        
</div><!--end item-settings-con-->
</div>';
