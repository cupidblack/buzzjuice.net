<?php
return array(


  array(
    'name' => 'displaymode',
    'type' => 'select',
    'category' => 'main',
    'select_type' => 'opener-listbuttons ',
    'title' => esc_html__('Display mode', 'dzsvg'),
    'extra_classes' => ' opener-listbuttons-2-cols ',
    'sidenote' => esc_html__("select the type of media"),
    'choices' => array(
      array(
        'label' => esc_html__("Normal", 'dzsvg'),
        'value' => 'normal',
      ),
      array(
        'label' => esc_html__("Pro", 'dzsvg'),
        'value' => 'wall',
      ),
      array(
        'label' => esc_html__("Boxy", 'dzsvg'),
        'value' => 'rotator3d',
      ),
      array(
        'label' => esc_html__("Boxy rounded", 'dzsvg'),
        'value' => 'videowall',
      ),
    ),
    'choices_html' => array(
      '<span class="option-con"><img src="https://i.imgur.com/3iRmYlc.jpg"/><span class="option-label">' . esc_html__("Gallery", 'dzsvg') . '</span></span>',
      '<span class="option-con"><img src="https://i.imgur.com/YhYVMd9.jpg"/><span class="option-label">' . esc_html__("Wall", 'dzsvg') . '</span></span>',
      '<span class="option-con"><img src="https://i.imgur.com/wQrkSkv.jpg"/><span class="option-label">' . esc_html__("Rotator 3d", 'dzsvg') . '</span></span>',
      '<span class="option-con"><img src="https://i.imgur.com/1jThnc7.jpg"/><span class="option-label">' . esc_html__("Video wall", 'dzsvg') . '</span></span>',
    ),


  ),

  array(
    'name' => 'skin_html5vg',
    'type' => 'select',
    'category' => 'main',
    'select_type' => '',
    'title' => esc_html__('Gallery skin', 'dzsvg'),
    'extra_classes' => ' ',
    'sidenote' => esc_html__("select the type of media"),
    'choices' => array(
      array(
        'label' => esc_html__("Default", 'dzsvg'),
        'value' => 'skin-default',
      ),
      array(
        'label' => esc_html__("Pro", 'dzsvg'),
        'value' => 'skin-pro',
      ),
      array(
        'label' => esc_html__("Boxy", 'dzsvg'),
        'value' => 'skin-boxy',
      ),
      array(
        'label' => esc_html__("Boxy rounded", 'dzsvg'),
        'value' => 'skin-boxy skin-boxy--rounded',
      ),
      array(
        'label' => esc_html__("Aurora", 'dzsvg'),
        'value' => 'skin-aurora',
      ),
      array(
        'label' => esc_html__("Navigation transparent", 'dzsvg'),
        'value' => 'skin-navtransparent',
      ),
      array(
        'label' => esc_html__("Custom", 'dzsvg'),
        'value' => 'skin-custom',
      ),
    ),
    'dependency' => array(
      array(
        'element' => 'term_meta[displaymode]',
        'value' => array('normal'),
      ),

    ),

  ),


  array(
    'name' => 'vpconfig',
    'title' => esc_html__('Player configuration', 'dzsvg'),
    'description' => esc_html__('choose the player configuration', 'dzsvg') . ' ( ' . esc_html__('modify them from', 'dzsvg') . ' <strong>' . esc_html__('Video gallery', 'dzsvg') . '</strong> ' . esc_html__('> Player Configs', 'dzsvg'). ' )',
    'type' => 'select',
    'category' => 'main',
    'options' => array(),
  ),
  array(
    'name' => 'nav_type',
    'title' => esc_html__('Navigation style', 'dzsvg'),
    'description' => esc_html__('Choose a navigation style for the normal display mode.', 'dzsvg'),
    'type' => 'select',
    'category' => 'main',
    'options' => array(
      array(
        'label' => esc_html__("Thumbnails", 'dzsvg'),
        'value' => 'thumbs',
      ),
      array(
        'label' => esc_html__("Thumbs and arrows", 'dzsvg'),
        'value' => 'thumbsandarrows',
      ),
      array(
        'label' => esc_html__("Scrollbar", 'dzsvg'),
        'value' => 'scroller',
      ),
      array(
        'label' => esc_html__("Outer menu", 'dzsvg'),
        'value' => 'outer',
      ),
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'none',
      ),
    ),
  ),
  array(
    'name' => 'menuposition',
    'title' => esc_html__('Menu position', 'dzsvg'),
    'description' => esc_html__('Choose a navigation style for the normal display mode.', 'dzsvg'),
    'type' => 'select',
    'category' => 'main',
    'options' => array(
      array(
        'label' => esc_html__("Right", 'dzsvg'),
        'value' => 'right',
      ),
      array(
        'label' => esc_html__("Bottom", 'dzsvg'),
        'value' => 'bottom',
      ),
      array(
        'label' => esc_html__("Left", 'dzsvg'),
        'value' => 'left',
      ),
      array(
        'label' => esc_html__("Top", 'dzsvg'),
        'value' => 'top',
      ),
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'none',
      ),
    ),
  ),
  array(
    'name' => 'settings_mode_showall_show_number',
    'title' => esc_html__('Mode Showall Number', 'dzsvg'),
    'description' => esc_html__('display the number', 'dzsvg'),
    'type' => 'select',
    'category' => 'main',
    'options' => array(
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
    'dependency' => array(
      array(
        'element' => 'term_meta[mode]',
        'value' => array('mode-showall'),
      ),

    ),
  ),


  array(
    'name' => 'bgcolor',
    'title' => esc_html__('Background Color', 'dzsvg'),
    'category' => 'main',
    'description' => esc_html__('choose background color', 'dzsvg'),
    'type' => 'color',
  ),

  array(
    'name' => 'randomize',
    'title' => esc_html__('Randomize / shuffle elements', 'dzsvg'),
    'description' => esc_html__('Shuffle elements', 'dzsvg'),

    'type' => 'select',
    'category' => 'misc',
    'options' => array(
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
  ),
  array(
    'name' => 'order',
    'title' => esc_html__('Order', 'dzsvg'),
    'description' => esc_html__('order items', 'dzsvg'),

    'type' => 'select',
    'category' => 'misc',
    'options' => array(
      array(
        'label' => esc_html__("Ascending", 'dzsvg'),
        'value' => 'ascending',
      ),
      array(
        'label' => esc_html__("Descending", 'dzsvg'),
        'value' => 'descending',
      ),
    ),
  ),


  array(
    'name' => 'menu_facebook_share',
    'title' => esc_html__('Facebook share in menu', 'dzsvg'),
    'description' => esc_html__('enable a facebook share button in the menu ', 'dzsvg'),

    'type' => 'select',
    'category' => 'misc',
    'options' => array(
      array(
        'label' => esc_html__("Auto", 'dzsvg'),
        'value' => 'auto',
      ),
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
  ),
  array(
    'name' => 'menu_like_button',
    'title' => esc_html__('Like button in menu', 'dzsvg'),
    'description' => esc_html__('enable a like button in the menu ', 'dzsvg'),

    'type' => 'select',
    'category' => 'misc',
    'options' => array(
      array(
        'label' => esc_html__("Auto", 'dzsvg'),
        'value' => 'auto',
      ),
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
  ),


  array(
    'name' => 'playorder',
    'title' => esc_html__('Play order', 'dzsvg'),
    'description' => esc_html__('set to reverse for example to play the latest episode in a series first ... or for RTL configurations', 'dzsvg'),

    'type' => 'select',
    'category' => 'misc',
    'options' => array(
      array(
        'label' => esc_html__("Normal", 'dzsvg'),
        'value' => 'normal',
      ),
      array(
        'label' => esc_html__("Reverse", 'dzsvg'),
        'value' => 'reverse',
      ),
    ),
  ),


  array(
    'name' => 'init_on',
    'title' => esc_html__('Initialize on', 'dzsvg'),
    'description' => esc_html__('you can initialize video gallery and her items, when the scroll reaches the gallery', 'dzsvg'),

    'type' => 'select',
    'category' => 'misc',
    'options' => array(
      array(
        'label' => esc_html__("Init", 'dzsvg'),
        'value' => 'init',
      ),
      array(
        'label' => esc_html__("Scroll", 'dzsvg'),
        'value' => 'scroll',
      ),
    ),
  ),


  array(
    'name' => 'ids_point_to_source',
    'title' => esc_html__('Ids point to source', 'dzsvg'),
    'description' => esc_html__('the id of the video players can point to the source file used ( for lightbox ) ', 'dzsvg'),

    'type' => 'select',
    'category' => 'misc',
    'options' => array(
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
  ),


  array(
    'name' => 'autoplay',
    'title' => esc_html__('Autoplay', 'dzsvg'),


    'type' => 'select',
    'category' => 'autoplay',


    'options' => array(
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
  ),


  array(
    'name' => 'autoplayWithVideoMuted',
    'title' => esc_html__('Mute when autoplay', 'dzsvg'),
    'description' => esc_html__('note that only some desktop browsers allow autoplay with sound, note that setting to autoplay with sound will not work on mobile due to mobile policies', DZSVG_ID),

    'type' => 'select',
    'default' => 'auto',
    'category' => 'autoplay',
    'options' => array(
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
    'dependency' => array(
      array(
        'element' => 'term_meta[autoplay]',
        'value' => array('on'),
      ),

    ),
  ),




  // -- category DESCRIPTION ----
  array(
    'name' => 'enableunderneathdescription',
    'title' => esc_html__('Enable Underneath Description', 'dzsvg'),
    'description' => esc_html__('add a title and description holder underneath the gallery', 'dzsvg'),
    'tooltip' => array(
      'image_url' => 'https://i.imgur.com/Tw16Wef.jpg',
      'description' => esc_html__('add a title and description holder underneath the gallery', 'dzsvg'),
    ),

    'type' => 'select',
    'category' => 'description',
    'options' => array(
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
  ),


  array(
    'name' => 'sharebutton',
    'title' => esc_html__('Social share button', 'dzsvg'),

    'type' => 'select',
    'category' => 'social',
    'options' => array(
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
  ),


  array(
    'name' => 'facebooklink',
    'title' => esc_html__('Facebook link', 'dzsvg'),


    'type' => 'text',
    'category' => 'social',

  ),

  array(
    'name' => 'twitterlink',
    'title' => esc_html__('Twitter link', 'dzsvg'),


    'type' => 'text',
    'category' => 'social',

  ),

  array(
    'name' => 'googlepluslink',
    'title' => esc_html__('Google plus link', 'dzsvg'),


    'type' => 'text',
    'category' => 'social',

  ),

  array(
    'name' => 'social_extracode',
    'title' => esc_html__('Extra Social HTML', 'dzsvg'),
    'description' => esc_html__('you can have here some extra social icons', 'dzsvg'),


    'type' => 'text',
    'category' => 'social',

  ),


  array(
    'name' => 'logo',
    'title' => esc_html__('Logo'),
    'category' => 'social',
    'type' => 'media-upload',
  ),


  array(
    'name' => 'logoLink',
    'title' => esc_html__('Logo link'),
    'category' => 'social',
    'type' => 'text',
  ),


  array(
    'name' => 'html5designmiw',
    'title' => esc_html__('Design menu item width'),
    'category' => 'menu',
    'type' => 'text',
    'default' => 'default',
  ),

  array(
    'name' => 'html5designmih',
    'title' => esc_html__('Design menu item height'),
    'category' => 'menu',
    'type' => 'text',
    'default' => 'default',
  ),
  array(
    'name' => 'navigation_navigationSpace',
    'title' => esc_html__('Navigation space', DZSVG_ID),
    'category' => 'menu',
    'type' => 'text',
    'default' => '0',
  ),
  array(
    'name' => 'thumb_extraclass',
    'title' => esc_html__('Thumbnail Extra Classes'),
    'category' => 'menu',
    'type' => 'text',
    'default' => '',
  ),


  // -- inverse
  array(
    'name' => 'disable_menu_description',
    'title' => esc_html__('menu description', 'dzsvg'),

    'type' => 'select',
    'category' => 'menu',
    'options' => array(
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
  ),
  array(
    'name' => 'nav_type_auto_scroll',
    'title' => esc_html__('Lock scroll', 'dzsvg'),
    'description' => esc_html__('for navigation type thumbs or scrollbar - LOCK SCROLL to current item', 'dzsvg'),

    'type' => 'select',
    'category' => 'menu',
    'options' => array(
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
  ),




  array(
    'name' => 'menu_description_format',
    'title' => esc_html__('Menu item format'),
    'description' => esc_html__('you can use something like {{number}}{{menuimage}}{{menutitle}}{{menudesc}} to display - menu item number , menu image, title and description or leave blank for default mode', 'dzsvg'),
    'category' => 'menu',
    'type' => 'text',
    'default' => '',
  ),


  array(
    'name' => 'embedbutton',
    'title' => esc_html__('Embed Button', 'dzsvg'),

    'type' => 'select',
    'category' => 'social',
    'options' => array(
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
  ),


  array(
    'name' => 'max_width',
    'title' => esc_html__('Max width'),
    'category' => 'dimensions',
    'type' => 'text',
    'default' => '',
  ),


  array(
    'name' => 'force_width',
    'title' => esc_html__('Force width'),
    'description' => esc_html__('recommended - leave default', 'dzsvg'),
    'category' => 'dimensions',
    'type' => 'text',
    'default' => '100%',
  ),
  array(
    'name' => 'force_height',
    'title' => esc_html__('Force height'),
    'description' => esc_html__('the gallery height, if Resize Video Proportionally is selected, this will not take effect', 'dzsvg'),
    'category' => 'dimensions',
    'type' => 'text',
    'default' => '',
  ),
  array(
    'name' => 'forcevideoheight',
    'title' => esc_html__('Force video height'),
    'description' => esc_html__('you can change the height of the video player here - will only have effect if you disable RESIZE VIDEOS PROPORTIONALLY in general settings', 'dzsvg'),
    'category' => 'dimensions_videoArea',
    'type' => 'text',
    'default' => '',
  ),


  array(
    'name' => 'extra_styling',
    'title' => esc_html__('Extra Styling', 'dzsvg'),
    'description' => esc_html__('you can apply custom css here - if you input ', 'dzsvg') . ' <strong>{{gallery}}</strong> ' . esc_html__('it will get replaced with the gallery css identifier', 'dzsvg'),
    'category' => 'appearance',
    'type' => 'textarea',
    'default' => '',
  ),


  array(
    'name' => 'disable_title',
    'title' => esc_html__('Menu title', 'dzsvg'),

    'type' => 'select',
    'category' => 'appearance',
    'options' => array(
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
  ),


  array(
    'name' => 'disable_video_title',
    'title' => esc_html__('Video Title', 'dzsvg'),

    'type' => 'select',
    'category' => 'appearance',
    'options' => array(
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
  ),


  array(
    'name' => 'transition',
    'title' => esc_html__('Transition', 'dzsvg'),
    'description' => esc_html__('set the transition of the gallery between menu items', 'dzsvg'),

    'type' => 'select',
    'category' => 'appearance',
    'options' => array(
      array(
        'label' => esc_html__("Fade", 'dzsvg'),
        'value' => 'fade',
      ),
      array(
        'label' => esc_html__("Slide in", 'dzsvg'),
        'value' => 'slidein',
      ),
    ),
  ),


  array(
    'name' => 'laptopskin',
    'title' => esc_html__('Laptop skin', 'dzsvg'),

    'type' => 'select',
    'category' => 'appearance',
    'options' => array(
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
  ),


  array(
    'name' => 'rtl',
    'title' => esc_html__('Right to left', 'dzsvg'),

    'type' => 'select',
    'category' => 'appearance',
    'options' => array(
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
  ),


  array(
    'name' => 'coverImage',
    'title' => esc_html__('Cover Image'),
    'category' => 'appearance',
    'type' => 'media-upload',
  ),


  array(
    'name' => 'shadow',
    'title' => esc_html__('Shadow', 'dzsvg'),

    'type' => 'select',
    'category' => 'appearance',
    'options' => array(
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
  ),

  array(
    'name' => 'extra_classes',
    'title' => esc_html__('Extra classes'),
    'category' => 'misc',
    'type' => 'text',
    'default' => '',
  ),


  array(
    'name' => 'maxlen_desc',
    'title' => esc_html__('Maximum Description length', 'dzsvg'),
    'description' => esc_html__('youtube video descriptions will be retrieved through YouTube Data API. You can choose here the number of characters to retrieve from it. ', 'dzsvg'),
    'category' => 'description',
    'type' => 'text',
    'default' => '150',
  ),


  array(
    'name' => 'readmore_markup',
    'title' => esc_html__('Read more markup'),
    'category' => 'description',
    'type' => 'text',
    'default' => ' <a href="{{postlink}}">read more</a>',
  ),


  array(
    'name' => 'enable_search_field',
    'title' => esc_html__('Search field', 'dzsvg'),
    'description' => esc_html__('enable a search field inside the gallery', 'dzsvg'),

    'type' => 'select',
    'category' => 'main',
    'options' => array(
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
  ),


  array(
    'name' => 'search_field_location',
    'title' => esc_html__('Search field location', 'dzsvg'),
    'description' => esc_html__('location', 'dzsvg'),

    'type' => 'select',
    'category' => 'main',
    'options' => array(
      array(
        'label' => esc_html__("Outside gallery", 'dzsvg'),
        'value' => 'outside',
      ),
      array(
        'label' => esc_html__("Top of menu", 'dzsvg'),
        'value' => 'inside',
      ),
    ),
    'dependency' => array(
      array(
        'element' => 'term_meta[enable_search_field]',
        'value' => array('on'),
      ),
    ),
  ),


  array(
    'name' => 'striptags',
    'title' => esc_html__('Strip HTML Tags', 'dzsvg'),
    'description' => esc_html__('video descriptions will be retrieved as html rich content. you can choose to strip the html tags to leave just simple text', 'dzsvg'),

    'type' => 'select',
    'category' => 'description',
    'options' => array(
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
  ),


  array(
    'name' => 'desc_different_settings_for_aside',
    'title' => esc_html__('Aside Navigation has Different Settings?', 'dzsvg'),
    'description' => esc_html__('different settings for aside navigation', 'dzsvg'),

    'type' => 'select',
    'category' => 'description',
    'options' => array(
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
  ),


  array(
    'name' => 'desc_aside_maxlen_desc',
    'title' => esc_html__('Max Description length'),
    'description' => esc_html__('youtube video descriptions will be retrieved through YouTube Data API. You can choose here the number of characters to retrieve from it. ', 'dzsvg'),
    'category' => 'description',
    'type' => 'text',
    'default' => '150',
    'dependency' => array(
      array(
        'element' => 'term_meta[desc_different_settings_for_aside]',
        'value' => array('on'),
      ),

    ),
  ),


  array(
    'name' => 'desc_aside_striptags',
    'title' => esc_html__('Strip HTML Tags', 'dzsvg'),
    'description' => esc_html__('video descriptions will be retrieved as html rich content. you can choose to strip the html tags to leave just simple text', 'dzsvg'),

    'type' => 'select',
    'category' => 'description',
    'options' => array(
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
    ),
    'dependency' => array(
      array(
        'element' => 'term_meta[desc_different_settings_for_aside]',
        'value' => array('on'),
      ),

    ),
  ),


  array(
    'name' => 'enable_secondcon',
    'title' => esc_html__('Second con', 'dzsvg'),
    'tooltip' => array(
      'image_url' => ClassDzsvgHelpers::assets_getUrlForHelperImage('dzsvg-custom-second-con.jpg'),
      'description' => '<em>' . esc_html__('enable linking to a slider with titles and descriptions as seen in the demos. to insert the container in your page use this shortcode ', DZSVG_ID) . '</em>' . ' <code>[dzsvg_secondcon id="{{currgalleryslug}}" extraclasses=""]</code>',
    ),
    'description' => esc_html__('enable linking to a slider with titles and descriptions as seen in the demos. to insert the container in your page use this shortcode 
', DZSVG_ID) . ' <div class="pre">[dzsvg_secondcon id="{{currgalleryslug}}" extraclasses=""]</div>',

    'type' => 'select',
    'category' => 'outer',
    'options' => array(
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
  ),


  array(
    'name' => 'enable_outernav',
    'title' => esc_html__('Second navigation', 'dzsvg'),
    'description' => esc_html__('enable linking to a outside navigation [dzsvg_outernav id="{{currgalleryslug}}" skin="oasis" extraclasses="" layout="layout-one-third" thumbs_per_page="9" ]', 'dzsvg'),

    'type' => 'select',
    'category' => 'outer',
    'options' => array(
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
  ),


  array(
    'name' => 'enable_outernav_video_author',
    'title' => esc_html__('Outer Navigation, Show Video Author', 'dzsvg'),
    'description' => esc_html__('show the video author for YouTube channels and playlists', 'dzsvg'),

    'type' => 'select',
    'category' => 'outer',
    'options' => array(
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
  ),


  array(
    'name' => 'enable_outernav_video_date',
    'title' => esc_html__('Outer Navigation, Show Video Date', 'dzsvg'),
    'description' => esc_html__('published date', 'dzsvg'),

    'type' => 'select',
    'category' => 'outer',
    'options' => array(
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
  ),


  array(
    'name' => 'settings_enable_linking',
    'title' => esc_html__('Linking', 'dzsvg'),
    'description' => esc_html__('enable the possibility for the gallery to change the current link depending on the video played, this makes it easy to go to a current video based only on link
', 'dzsvg'),

    'type' => 'select',
    'category' => 'main',
    'options' => array(
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
  ),

  array(
    'name' => 'autoplay_ad',
    'title' => esc_html__('Autoplay Ad', 'dzsvg'),
    'description' => esc_html__('autoplay the ad before a video or not - note that if the video autoplay then the ad will autoplay too before', 'dzsvg'),

    'type' => 'select',
    'category' => 'autoplay',
    'options' => array(
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
    ),
  ),


  array(
    'name' => 'set_responsive_ratio_to_detect',
    'title' => esc_html__('Resize Video Proportionally', 'dzsvg'),
    'description' => esc_html__('Settings this to "on" will make an attempt to remove the black bars plus resizing the video proportionally for mobiles.', 'dzsvg'),

    'type' => 'select',
    'category' => 'dimensions_videoArea',
    'options' => array(
      array(
        'label' => esc_html__("Resize proportionally", 'dzsvg'),
        'value' => 'on',
      ),
      array(
        'label' => esc_html__("Fixed height", 'dzsvg'),
        'value' => 'off',
      ),
    ),
  ),


  array(
    'name' => 'mode_normal_video_mode',
    'title' => esc_html__('Video player mode', 'dzsvg'),
    'description' => esc_html__('(beta)', 'dzsvg') . ' ' . esc_html__('setting this to on will enable autoplay next video on mobile - plus it will only use one player for the playlist', 'dzsvg'),

    'type' => 'select',
    'category' => 'misc',
    'options' => array(
      array(
        'label' => esc_html__("Multiple players", 'dzsvg'),
        'value' => '',
      ),
      array(
        'label' => esc_html__("One player", 'dzsvg'),
        'value' => 'one',
      ),
    ),
  ),


  array(
    'name' => 'mode_wall_layout',
    'title' => esc_html__('Wall item dimensions', DZSVG_ID),
    'description' => esc_html__('the layout for the wall mode. using none will use the Design Menu Item Width and Design Menu Item Height for the item dimensions', 'dzsvg'),
    'category' => 'main',
    'type' => 'select',
    'default' => '',
    'options' => array(
      array(
        'label' => esc_html__("Default", 'dzsvg'),
        'value' => 'default',
      ),
      array(
        'label' => esc_html__("None", 'dzsvg'),
        'value' => 'none',
      ),
      array(
        'label' => sprintf(esc_html__("%s Column", 'dzsvg'), '1'),
        'value' => 'dzs-layout--1-cols',
      ),
      array(
        'label' => sprintf(esc_html__("%s Columns", 'dzsvg'), '2'),
        'value' => 'dzs-layout--2-cols',
      ),
      array(
        'label' => sprintf(esc_html__("%s Columns", 'dzsvg'), '3'),
        'value' => 'dzs-layout--3-cols',
      ),
      array(
        'label' => sprintf(esc_html__("%s Columns", 'dzsvg'), '4'),
        'value' => 'dzs-layout--4-cols',
      ),
    ),


    'dependency' => array(
      array(
        'element' => 'term_meta[displaymode]',
        'value' => array('wall', 'videowall', 'outer'),
        'relation' => 'OR'
      ),

    ),
  ),


  array(
    'name' => 'nav_type_outer_grid',
    'title' => esc_html__('Number of items per row'),
    'description' => esc_html__('Number of items per row in the menu', 'dzsvg'),
    'category' => 'main',
    'type' => 'select',
    'default' => '',
    'options' => array(
      array(
        'label' => esc_html__("Default", 'dzsvg'),
        'value' => 'default',
      ),
      array(
        'label' => esc_html__("None", 'dzsvg'),
        'value' => 'none',
      ),
      array(
        'label' => sprintf(esc_html__("%s Column", 'dzsvg'), '1'),
        'value' => 'dzs-layout--1-cols',
      ),
      array(
        'label' => sprintf(esc_html__("%s Columns", 'dzsvg'), '2'),
        'value' => 'dzs-layout--2-cols',
      ),
      array(
        'label' => sprintf(esc_html__("%s Columns", 'dzsvg'), '3'),
        'value' => 'dzs-layout--3-cols',
      ),
      array(
        'label' => sprintf(esc_html__("%s Columns", 'dzsvg'), '4'),
        'value' => 'dzs-layout--4-cols',
      ),
    ),


    'dependency' => array(
      array(
        'element' => 'term_meta[nav_type]',
        'value' => array('outer'),
        'relation' => 'OR'
      ),

    ),
  ),


  array(
    'name' => 'cueFirstVideo',
    'title' => esc_html__('Cue First media', 'dzsvg'),


    'type' => 'select',
    'category' => 'autoplay',
    'options' => array(
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
    ),
  ),
  array(
    'name' => 'autoplay_next',
    'title' => esc_html__('Autoplay next', 'dzsvg'),


    'type' => 'select',
    'category' => 'autoplay',
    'options' => array(
      array(
        'label' => esc_html__("Enabled", 'dzsvg'),
        'value' => 'on',
      ),
      array(
        'label' => esc_html__("Disabled", 'dzsvg'),
        'value' => 'off',
      ),
    ),
  ),


);
