<?php

/** @param DZSVideoGallery $this   */

if (!defined('ABSPATH')) // Or some other WordPress constant
  exit;

$arr_off_on = array(
  array(
    'label' => esc_html__("Off"),
    'value' => 'off',
  ),
  array(
    'label' => esc_html__("On"),
    'value' => 'on',
  ),
);

$arr_on_off = array(
  array(
    'label' => esc_html__("On"),
    'value' => 'on',
  ),
  array(
    'label' => esc_html__("Off"),
    'value' => 'off',
  ),
);
$arr_default_detect = array(
  array(
    'label' => esc_html__("Default"),
    'value' => 'default',
  ),
  array(
    'label' => esc_html__("Detect"),
    'value' => 'detect',
  ),
);

$types = array(
  array(
    'label' => esc_html__("Video"),
    'value' => 'normal',
  ),
  array(
    'value' => 'youtube',
    'label' => esc_html__("YouTube"),
  ),
  array(
    'value' => 'vimeo',
    'label' => esc_html__("Vimeo"),
  ),
  array(
    'value' => 'image',
    'label' => esc_html__("Image"),
  ),
);


$args = array(


  'source' => array(
    'type' => 'upload',
    'library_type' => 'video',
    'dom_type' => 'textarea',
    'class' => '',
    'title' => esc_html__("Source"),
    'sidenote' => esc_html__("The source, input a mp4 or a youtube link or a youtube id or a vimeo link or a vimeo id"),

    'context' => 'content',
    'default' => esc_html__('The link to a mp4', 'dzsvg'),
  ),
  'config' => array(
    'type' => 'select',
    'title' => esc_html__("Video Player Configuration"),
    'sidenote' => esc_html__("the video player configuration, could be edited in Video Gallery > Player Configurations.", DZSVG_ID),

    'context' => 'content',
    'options' => $this->video_player_configs,
    'default' => 'default',
  ),
  'cover' => array(
    'type' => 'image',
    'title' => esc_html__("Cover"),
    'sidenote' => esc_html__("cover image to show before video play"),

    'context' => 'content',
    'default' => '',
  ),
  'autoplay' => array(
    'type' => 'select',
    'title' => esc_html__("Autoplay"),
    'sidenote' => esc_html__("autoplay the videos"),

    'context' => 'content',
    'options' => $arr_off_on,
    'default' => 'off',
  ),
  'cue' => array(
    'type' => 'select',
    'title' => esc_html__("Preload Video"),
    'sidenote' => esc_html__("preload the video"),

    'context' => 'content',
    'options' => $arr_off_on,
    'default' => 'off',
  ),
  'loop' => array(
    'type' => 'select',
    'title' => esc_html__("Loop"),
    'sidenote' => esc_html__("loop the video on end"),

    'context' => 'content',
    'options' => $arr_off_on,
    'default' => 'off',
  ),
  'init_on' => array(
    'type' => 'select',
    'title' => esc_html__("Init on"),
    'sidenote' => esc_html__("choose when to initialize the player"),

    'context' => 'content',
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
  'type' => array(
    'type' => 'select',
    'title' => esc_html__("Type"),
    'sidenote' => esc_html__("media type"),

    'context' => 'content',
    'options' => $types,
    'default' => 'normal',
  ),
  'qualities' => array(
    'type' => 'quality_selecter',
    'title' => esc_html__("Quality manager"),
    'sidenote' => esc_html__("input here optional qualities"),

    'context' => 'content',
    'options' => $types,
    'default' => 'normal',
  ),


  'link' => array(
    'type' => 'text',
    'title' => esc_html__("Link"),
    'sidenote' => esc_html__("If link button is enabled in the player configurations, then you can set a link here"),

    'context' => 'content',
    'default' => '',
  ),
  'link_label' => array(
    'type' => 'text',
    'title' => esc_html__("Link Label"),
    'sidenote' => esc_html__("If link button is enabled in the player configurations, then you can set a link here"),

    'context' => 'content',
    'default' => '',
  ),
  'logo' => array(
    'type' => 'image',
    'title' => esc_html__("Logo"),
    'sidenote' => esc_html__("logo"),

    'context' => 'content',
    'default' => '',
  ),
  'extra_classes_player' => array(
    'type' => 'text',
    'title' => esc_html__("Extra Classes to the Player"),
    'sidenote' => esc_html__("enter a extra css class for the player for example, entering \"with-bottom-shadow\" will create a shadow underneath the player"),

    'context' => 'content',
    'default' => '',
  ),
  'height' => array(
    'type' => 'text',
    'title' => esc_html__("Height"),
    'sidenote' => esc_html__("Force a height in pixels"),

    'context' => 'content',
    'default' => '',
  ),
  'responsive_ratio' => array(
    'type' => 'select',
    'title' => esc_html__("Resize Proportionally"),
    'sidenote' => esc_html__("Try to remove black bars of the video by resizing height proportional to width"),

    'context' => 'content',
    'default' => '',
    'options' => $arr_default_detect,
  ),
  'title' => array(
    'type' => 'text',
    'title' => esc_html__("Title"),
    'sidenote' => esc_html__("title to appear on the left top"),

    'context' => 'content',
    'default' => 'default',
  ),
  'description' => array(
    'type' => 'text',
    'title' => esc_html__("Description"),
    'sidenote' => esc_html__("description to appear if the info button is enabled in video player configurations"),

    'context' => 'content',
    'default' => '',
  ),
  'mediaid' => array(
    'type' => 'text',
    'title' => esc_html__("Link to Product"),
    'sidenote' => esc_html__("link to a media element ID or woocommerce product ID"),

    'context' => 'content',
    'default' => '',
  ),


  'adarray' => array(
    'name' => 'adarray',
    'type' => 'text',
    'category' => 'misc',
    'title' => esc_html__("Manage Ads"),
    'sidenote' => sprintf(__("construct an ad sequence ")),

    'extra_html_after_input' => '<a class=" button-secondary quick-edit-adarray" href="#" style="cursor:pointer;">' . esc_html__("Edit Ads") . '</a>',
  ),

);


if (defined('DZSVG_360_PLAYER_EXTRA1')) {
  $args[DZSVG_360_PLAYER_EXTRA1_LABEL] = json_decode(DZSVG_360_PLAYER_EXTRA1, true);
}

$this->options_array_player = $args;
