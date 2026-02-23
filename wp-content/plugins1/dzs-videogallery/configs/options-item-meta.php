<?php

if (!defined('ABSPATH')) // Or some other WordPress constant
  exit;

/**
 *
 */



$foutArr = array();

$options = array(


  array(
    'name' => 'the_post_title',
    'type' => 'text',
    'title' => esc_html__("Title"),
    'only_for' => array('sliders_admin'),
    'sidenote' => esc_html__("the title of the video", DZSVG_ID),
  ),


  array(
    'name' => 'dzsvg_meta_item_type',
    'type' => 'select',
    'select_type' => 'opener-listbuttons',
    'title' => esc_html__("Type"),
    'sidenote' => esc_html__("select the type of media"),
    'setting_extra_classes' => ' setting-for-item-type rounded-highlight',
    'choices' => array(
      array(
        'label' => esc_html__("Detect"),
        'value' => 'detect',
      ),
      array(
        'label' => esc_html__("Self Hosted"),
        'value' => 'video',
      ),
      array(
        'label' => esc_html__("YouTube"),
        'value' => 'youtube',
      ),
      array(
        'label' => esc_html__("Vimeo"),
        'value' => 'vimeo',
      ),
      array(
        'label' => esc_html__("Inline"),
        'value' => 'inline',
      ),
    ),
    'choices_html' => array(
      '<span class="option-con"><img src="' . DZSVG_URL . 'admin/img/illustration_videoType_detect.png"/><span class="option-label">' . esc_html__("Detect automatically", 'dzsvg') . '</span></span>',
      '<span class="option-con"><img src="' . DZSVG_URL . 'admin/img/illustration_videoType_video.png"/><span class="option-label">' . esc_html__("Self Hosted", 'dzsvg') . '</span></span>',
      '<span class="option-con"><img src="' . DZSVG_URL . 'admin/img/illustration_videoType_youtube.png"/><span class="option-label">' . esc_html__("YouTube", 'dzsvg') . '</span></span>',
      '<span class="option-con"><img src="' . DZSVG_URL . 'admin/img/illustration_videoType_vimeo.png"/><span class="option-label">' . esc_html__("Vimeo", 'dzsvg') . '</span></span>',
      '<span class="option-con"><img src="' . DZSVG_URL . 'admin/img/illustration_videoType_inline.png"/><span class="option-label">' . esc_html__("Inline", 'dzsvg') . '</span></span>',
    ),


  ),


  array(
    'name' => 'dzsvg_meta_featured_media',
    'type' => 'attach',
    'title' => esc_html__("Video", 'dzsvg'),
    'dom_type' => 'textarea',
    'input_extra_classes' => ' main-source',
    'sidenote' => esc_html__("input a self hosted video or youtube link or vimeo link", 'dzsvg'),
    'setting_extra_classes' => ' setting-for-source',
  ),


  // -- start exclusive gutenberg options
  array(
    'name' => 'dzsvg_meta_config',
    'type' => 'select',
    'category' => '',
    'title' => esc_html__("Video Player Configuration", 'dzsvg'),
    'sidenote' => sprintf(__("the video player configuration, can be edited in %s > Player Configurations"), '<strong>Video Gallery</strong>'),
    'choices' => $dzsvg->video_player_configs,
    'default' => 'default',
    'only_for' => array('gutenberg'),
  ),


  array(
    'name' => 'dzsvg_meta_autoplay',
    'type' => 'select',
    'category' => 'autoplay',
    'select_type' => '',
    'title' => esc_html__("Autoplay", 'dzsvg'),
    'sidenote' => esc_html__("autoplay video - not all browsers support this"),
    'setting_extra_classes' => '',
    'only_for' => array('gutenberg'),
    'choices' => array(
      array(
        'label' => esc_html__("No"),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Yes"),
        'value' => 'on',
      ),
    ),
  ),
  array(
    'name' => 'autoplayWithVideoMuted',
    'type' => 'select',
    'category' => 'autoplay',
    'select_type' => '',
    'title' => esc_html__("Autoplay Muted", DZSVG_ID),
    'sidenote' => esc_html__("Automatically decide - will try to autoplay with sound, if not possible, it will autoplay muted.", DZSVG_ID),
    'setting_extra_classes' => '',
    'only_for' => array('gutenberg'),
    'choices' => array(
      array(
        'label' => esc_html__("Automatically decide", DZSVG_ID),
        'value' => 'auto',
      ),
      array(
        'label' => esc_html__("Always autoplay with sound", DZSVG_ID),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Always autoplay muted", DZSVG_ID),
        'value' => 'always',
      ),
    ),
  ),
  array(
    'name' => 'dzsvg_meta_cue',
    'type' => 'select',
    'category' => 'autoplay',
    'select_type' => '',
    'title' => esc_html__("Preload Video"),
    'sidenote' => esc_html__("preload the video - if set to YES, it will listed to the option set in player configuration"),
    'setting_extra_classes' => '',
    'only_for' => array('gutenberg'),
    'choices' => array(
      array(
        'label' => esc_html__("Yes"),
        'value' => 'on',
      ),
      array(
        'label' => esc_html__("No"),
        'value' => 'off',
      ),
    ),
  ),
  array(
    'name' => 'init_on',
    'type' => 'select',
    'title' => esc_html__("Init on"),
    'sidenote' => esc_html__("choose when to initialize the player"),

    'context' => 'content',
    'only_for' => array('gutenberg'),
    'options' => array(
      array(
        'label' => esc_html__("Page load", 'dzsvg'),
        'value' => '',
      ),
      array(
        'label' => esc_html__("Scroll to video", 'dzsvg'),
        'value' => 'scroll',
      ),
    ),
    'default' => '',
  ),


  array(
    'name' => 'link',
    'type' => 'text',
    'title' => esc_html__("Link"),
    'sidenote' => esc_html__("If link button is enabled in the player configurations, then you can set a link here"),

    'context' => 'content',
    'only_for' => array('gutenberg'),
    'default' => '',
  ),
  array(
    'name' => 'link_label',
    'type' => 'text',
    'title' => esc_html__("Link Label"),
    'sidenote' => esc_html__("If link button is enabled in the player configurations, then you can set a link here"),

    'context' => 'content',
    'only_for' => array('gutenberg'),
    'default' => '',
  ),
  array(
    'name' => 'width',
    'type' => 'text',
    'title' => esc_html__("Force a Width", 'dzsvg'),
    'sidenote' => esc_html__("Force a width in pixels"),

    'context' => 'content',
    'only_for' => array('gutenberg'),
    'default' => '',
  ),
  array(
    'name' => 'height',
    'type' => 'text',
    'title' => esc_html__("Force a Height", 'dzsvg'),
    'sidenote' => esc_html__("Force a height in pixels"),

    'context' => 'content',
    'only_for' => array('gutenberg'),
    'default' => '',
  ),
  array(
    'name' => 'responsive_ratio',
    'type' => 'select',
    'title' => esc_html__("Resize Proportionally", 'dzsvg'),
    'sidenote' => esc_html__("Try to remove black bars of the video by resizing height proportional to width"),

    'context' => 'content',
    'default' => '',
    'only_for' => array('gutenberg'),
    'options' => array(
      array(
        'label' => esc_html__("Default"),
        'value' => 'default',
      ),
      array(
        'label' => esc_html__("Detect"),
        'value' => 'detect',
      ),
    ),
  ),
  array(
    'name' => 'mediaid',
    'type' => 'text',
    'title' => esc_html__("Link to Product"),
    'sidenote' => esc_html__("link to a media element ID or woocommerce product ID"),

    'only_for' => array('gutenberg'),
    'context' => 'content',
    'default' => '',
  ),


  array(
    'name' => 'adarray',
    'type' => 'text',
    'category' => 'misc',
    'title' => esc_html__("Manage Ads"),
    'sidenote' => sprintf(__("construct an ad sequence ")),
    'only_for' => array('gutenberg', 'sliders_admin'),

    'setting_extra_classes' => ' bundle-input-with-extra-html',
    'extra_html_after_input' => '<a class=" button-secondary quick-edit-adarray" href="#" style="cursor:pointer;">' . esc_html__("Edit Ads") . '</a>',
  ),


  // -- end exclusive gutenberg options


  array(
    'name' => 'dzsvg_meta_thumb',
    'type' => 'attach',
    'input_extra_classes' => ' main-thumb',
    'only_for' => array('sliders_admin'),
    'title' => esc_html__("Thumbnail"),
    'sidenote' => esc_html__("This will replace the default wordpress thumbnail"),
    'extra_html_after_input' => '<button style="display: inline-block; vertical-align: middle;" class="refresh-main-thumb button-secondary">Auto Generate</button>',
  ),


  array(
    'name' => 'the_post_content',
    'type' => 'textarea',
    'title' => esc_html__("Description"),
    'extraattr' => ' rows="2"',
    'sidenote' => esc_html__("the video description"),
  ),

  array(
    'name' => 'dzsvg_meta_menu_description',
    'type' => 'textarea',
    'title' => esc_html__("Menu Description"),
    'extraattr' => ' rows="2"',
    'sidenote' => esc_html__("the menu description"),
    'only_for' => array('sliders_admin'),
    'default' => 'as_description',
  ),

  array(
    'name' => 'dzsvg_meta_extra_classes_player',
    'type' => 'text',
    'category' => 'extra_html',
    'title' => esc_html__("Extra Classes"),
    'sidenote' => esc_html__("extra html classes applied to the player"),
  ),

  array(
    'name' => 'dzsvg_meta_play_from',
    'type' => 'text',
    'category' => 'misc',
    'title' => esc_html__("Play from"),
    'sidenote' => esc_html__("choose a number of seconds from which the track to play from ( for example if set \"70\" then the track will start to play from 1 minute and 10 seconds ) or input \"last\" for the track to play at the last position where it was.", 'dzsap'),
  ),


  array(
    'name' => 'dzsvg_meta_loop',
    'type' => 'select',
    'category' => 'misc',
    'select_type' => '',
    'title' => esc_html__("Loop"),
    'sidenote' => esc_html__("loop the video when it ends"),
    'setting_extra_classes' => '',
    'choices' => array(
      array(
        'label' => esc_html__("Disable"),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Enable"),
        'value' => 'on',
      ),
    ),


  ),

  array(
    'name' => 'dzsvg_meta_is_360',
    'type' => 'select',
    'category' => 'misc',
    'select_type' => '',
    'title' => esc_html__("is 360 ? "),
    'sidenote' => esc_html__("is 360 video ? "),
    'setting_extra_classes' => '',
    'choices' => array(
      array(
        'label' => esc_html__("No"),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Yes"),
        'value' => 'on',
      ),
    ),
  ),


  array(
    'name' => 'dzsvg_meta_subtitle',
    'type' => 'attach',
    'category' => 'misc',
    'title' => esc_html__("Subtitle"),
    'sidenote' => esc_html__("a optional subtitle file"),
    'extra_html_after_input' => '',
  ),

  array(
    'name' => 'logo',
    'attach_type' => 'image',
    'title' => esc_html__("Logo"),
    'sidenote' => esc_html__("logo"),
    'type' => 'attach',
    'category' => 'misc',
    'only_for' => array('gutenberg'),

    'context' => 'content',
    'default' => '',
  ),

  array(
    'name' => 'cover',
    'type' => 'attach',
    'title' => esc_html__("Cover"),
    'attach_type' => 'image',
    'category' => 'misc',
    'sidenote' => esc_html__("cover image to show before video play"),

    'context' => 'content',
    'default' => '',
  ),


  array(
    'name' => 'dzsvg_meta_overwrite_responsive_ratio',
    'type' => 'text',
    'category' => 'misc',
    'title' => esc_html__("Overwrite Responsive ratio"),
    'sidenote' => esc_html__("(optional) set a responsive ratio height/ratio 0.5 means that the player height will resize to 0.5 of the gallery width / or just set it to \"detect\" and it will autocalculate the ratios if it is a self hosted mp4", 'dzsvg'),
  ),

);


$options_item_meta_sanitized = array_merge(array(), $options);
foreach ($options_item_meta_sanitized as $lab => $val) {

  if (isset($val['name'])) {

    if (strpos($val['name'], 'dzsvg_meta_item_') !== false) {
      $newname = str_replace('dzsvg_meta_item_', '', $val['name']);
      $options_item_meta_sanitized[$lab]['name'] = $newname;
    } else {

      if (strpos($val['name'], 'dzsvg_meta_') !== false) {
        $newname = str_replace('dzsvg_meta_', '', $val['name']);
        $options_item_meta_sanitized[$lab]['name'] = $newname;
      }
    }
  }

}

return array(
  'unsanitized'=>$options,
  'sanitized'=>$options_item_meta_sanitized,
);
