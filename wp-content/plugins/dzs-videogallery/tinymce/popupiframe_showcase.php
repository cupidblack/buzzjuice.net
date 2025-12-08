<?php


function dzsvg_shortcode_showcase_builder() {

  global $dzsvg;

  $url_admin = get_admin_url();


  $taxonomy_main = DZSVG_POST_NAME__CATEGORY;


  $categories = get_terms($taxonomy_main, 'orderby=count&hide_empty=0');





  $cats_checkboxes = '';
  $cats_options = '<option value="none">' . esc_html__("None") . '</option>';

  if (count($categories) > 0) {
    foreach ($categories as $cat) {

      $cats_checkboxes .= '<label for="cat' . $cat->term_id . '"><input type="checkbox" name="cat_checkbox[]" id="cat' . $cat->term_id . '" value="' . $cat->term_id . '"><span class="the-label"><span class="the-text"> ' . $cat->name . '</span></span></label> ';

      $cats_options .= '<option value="' . $cat->term_id . '">' . $cat->name . '</option>';
    }
  }

  ?>
  <div class="sc-con sc-con-for-showcase-builder">

  <script>
    <?php

    $terms = get_terms($taxonomy_main, 'orderby=count&hide_empty=0');
    ?>
    window.dzsvg_showcase_options = {
      'sampledata_installed': <?php if (get_option('dzsvg_demo_data') == '') {
        echo 'false';
      } else {
        echo 'true';
      }; ?>
      ,
      'sampledata_cats': ["<?php $demo_data = (get_option('dzsvg_demo_data')); $i = 0; if (isset($demo_data['cats']) && is_array($demo_data['cats'])) {
        foreach ($demo_data['cats'] as $cat) {
          if ($i > 0) {
            echo "\",\"";
          }
          echo $cat;
          ++$i;
        }
      }; ?>"]
      ,
      'categoryportfolio_terms': "<?php $i = 0; foreach ($terms as $term) {
        if ($i > 0) {
          echo ',';
        }
        echo $term->term_id;
        ++$i;
      }; echo ';'; $i = 0; foreach ($terms as $term) {
        if ($i > 0) {
          echo ',';
        }
        echo $term->name;
        ++$i;
      }; ?>"
    };
  </script>
  <div class="sc-menu">


    <div class="main-type-container">


      <div class="setting  mode-any">
        <h3><?php echo esc_html__("Type"); ?></h3>
        <?php


        $lab = "type";


        $arr_opts = array(
          'video_items',
          'youtube',
          'vimeo',
          'video_gallery',
          'facebook',
        );


        echo DZSHelpers::generate_select($lab, array(
          'options' => $arr_opts,
          'class' => 'dzs-style-me opener-listbuttons dzs-dependency-field',
          'seekval' => '',
        ));

        ?>
        <ul class="dzs-style-me-feeder">
          <li><span class="option-con"><img src="<?php echo DZSVG_URL; ?>tinymce/img/type1.png"/><span
                class="option-label"><?php echo esc_html__("Video Items"); ?></span></span></li>
          <li><span class="option-con"><img src="<?php echo DZSVG_URL; ?>tinymce/img/type2.png"/><span
                class="option-label"><?php echo esc_html__("YouTube Feed"); ?></span></span></li>
          <li><span class="option-con"><img src="<?php echo DZSVG_URL; ?>tinymce/img/type3.png"/><span
                class="option-label"><?php echo esc_html__("Vimeo Feed"); ?></span></span></li>
          <li><span class="option-con"><img
                src="<?php echo DZSVG_URL; ?>tinymce/img/type_video_gallery.png"/><span
                class="option-label"><?php echo esc_html__("Video Gallery"); ?></span></span></li>
          <li><span class="option-con"><img src="<?php echo DZSVG_URL; ?>tinymce/img/type_facebook.png"/><span
                class="option-label"><?php echo esc_html__("Facebook"); ?></span></span></li>
        </ul>
        <div class="sidenote"><?php echo esc_html__("This is where the showcase items will come from... "); ?></div>
      </div>
<?php
      // -- for future we can do a logical set like "(" .. ")" .. "AND" .. "OR"
      $dependency = array(

        array(
          'lab' => 'type',
          'val' => array('video_gallery'),
        ),
      );


      ?>


      <div class="setting type-any" data-dependency='<?php echo json_encode($dependency); ?>'>
        <h3><?php echo esc_html__("Select a Gallery to Insert", DZSVG_ID); ?></h3>
        <select class="styleme dzs-dependency-field" name="dzsvg_selectid">
          <?php



          echo DZSVideoGalleryHelper::get_string_galleries_to_select_options();

          ?>
        </select>
      </div>


      <?php


      $lab = 'cat';
      echo DZSHelpers::generate_input_text($lab, array(
        'class' => '  dzs-dependency-field',
        'seekval' => '',
        'input_type' => 'hidden',
      ));
      ?>


      <?php if ($cats_checkboxes) { ?>
        <div class="setting type-video_items ">
          <h3><?php echo esc_html__("Category"); ?></h3>
          <?php echo '<div class="dzs-checkbox-selector skin-nova">';


          echo $cats_checkboxes;

          echo '</div>';
          ?>
        </div>
      <?php } ?>


      <div class="setting type-youtube ">
        <h3><?php echo esc_html__("Link"); ?></h3>
        <input class="regular-text  dzs-dependency-field" name="youtube_link" value=""/>
        <div
          class="sidenote"><?php printf(esc_html__('ie. %1$s - for a user channel feed') . '<br>', '<strong>https://www.youtube.com/user/digitalzoomstudio</strong>');
          printf(__('ie. %1$s - for a playlist feed') . '<br>', '<strong>https://www.youtube.com/playlist?list=PLBsCKuJJu1pbD4ONNTHgNsVebK4ughuch</strong>');
          printf(__('ie. %1$s - for a search feed') . '<br>', '<strong>https://www.youtube.com/results?search_query=cat+funny</strong>'); ?></div>
      </div>

      <div class="setting type-youtube ">
        <h3><?php echo esc_html__("Max. Videos"); ?></h3>
        <input class="regular-text" name="max_videos" value=""/>
      </div>


      <div class="setting type-facebook ">
        <h3><?php echo esc_html__("Link"); ?></h3>
        <input class="regular-text  dzs-dependency-field" name="facebook_link" value=""/>
        <div
          class="sidenote"><?php printf(esc_html__('ie. %1$s - for a page public videos') . '<br>', '<strong>https://facebook.com/digitalzoomstudio</strong>');;; ?></div>
      </div>

      <div class="setting type-vimeo ">
        <h3><?php echo esc_html__("Link"); ?></h3>
        <input class="regular-text  dzs-dependency-field" name="vimeo_link" value=""/>
        <div
          class="sidenote"><?php printf(esc_html__('ie. %1$s - for a user channel feed') . '<br>', '<strong>https://vimeo.com/user5137664</strong>');
          printf(esc_html__('ie. %1$s - for a channel feed') . '<br>', '<strong>https://vimeo.com/channels/636900</strong>');
          printf(esc_html__('ie. %1$s - for a album feed') . '<br>', '<strong>https://vimeo.com/album/2633720</strong>'); ?></div>
      </div>


      <div class="setting  type-video_items">
        <h3><?php echo esc_html__("Order By"); ?></h3>
        <?php


        $lab = "orderby";


        $arr_opts = array(
          array(
            'value' => 'none',
            'label' => esc_html__("Default"),
          ),
          array(
            'value' => 'date',
            'label' => esc_html__("Date"),
          ),
          array(
            'value' => 'views',
            'label' => esc_html__("Views"),
          ),
          array(
            'value' => 'similar',
            'label' => esc_html__("Similar"),
          ),
        );


        echo DZSHelpers::generate_select($lab, array(
          'options' => $arr_opts,
          'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
          'seekval' => '',
        ));
        ?>
      </div>

      <div class="setting  type-video_items">
        <h3><?php echo esc_html__("Order"); ?></h3>
        <?php


        $lab = "order";


        $arr_opts = array(
          array(
            'value' => 'DESC',
            'label' => esc_html__("Descending"),
          ),
          array(
            'value' => 'ASC',
            'label' => esc_html__("Ascending"),
          ),
        );


        echo DZSHelpers::generate_select($lab, array(
          'options' => $arr_opts,
          'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
          'seekval' => '',
        ));
        ?>
      </div>


      <!-- end type-container-->
    </div>
    <div class="setting  mode-any">
      <h3><?php echo esc_html__("Mode"); ?></h3>
      <?php


      $lab = "mode";


      $arr_opts = array(
        'ullist',
        'list',
        'list-2',
        'featured',
        'scroller',
        'scrollmenu',
        'zfolio',
        'gallery_view',
      );


      echo DZSHelpers::generate_select($lab, array(
        'options' => $arr_opts,
        'class' => 'dzs-style-me opener-listbuttons  dzs-dependency-field',
        'seekval' => '',
      ));

      ?>
      <ul class="dzs-style-me-feeder">
        <li><span class="option-con"><img src="<?php echo DZSVG_URL; ?>assets/svg/style_ullist.svg"/><span
              class="option-label"><?php echo esc_html__("UL LIST"); ?></span></span></li>
        <li><span class="option-con"><img src="<?php echo DZSVG_URL; ?>assets/svg/style_list.svg"/><span
              class="option-label"><?php echo esc_html__("LIST"); ?></span></span></li>
        <li><span class="option-con"><img src="<?php echo DZSVG_URL; ?>assets/svg/style_list-2.svg"/><span
              class="option-label"><?php echo esc_html__("LIST"); ?> 2</span></span></li>
        <li><span class="option-con"><img src="<?php echo DZSVG_URL; ?>assets/svg/style_featured.svg"/><span
              class="option-label"><?php echo esc_html__("FEATURED"); ?></span></span></li>
        <li><span class="option-con"><img src="<?php echo DZSVG_URL; ?>assets/svg/style_scroller.svg"/><span
              class="option-label"><?php echo esc_html__("SCROLLER"); ?></span></span></li>
        <li><span class="option-con"><img src="<?php echo DZSVG_URL; ?>assets/svg/scrollmenu.svg"/><span
              class="option-label"><?php echo esc_html__("SCROLL MENU"); ?></span></span></li>
        <li><span class="option-con"><img src="<?php echo DZSVG_URL; ?>assets/svg/style_zfolio.svg"/><span
              class="option-label"><?php echo esc_html__("ZFOLIO"); ?></span></span></li>
        <li><span class="option-con"><img src="<?php echo DZSVG_URL; ?>assets/svg/style_gallery_view.svg"/><span
              class="option-label"><?php echo esc_html__("GALLERY VIEW"); ?></span></span></li>
      </ul>
    </div>

    <div class="setting  mode-scrollmenu">
      <h4><?php echo esc_html__("Scroll Menu Height"); ?></h4>
      <input class="regular-text" name="mode_scrollmenu_height" value="300"/>


    </div>


    <div class="setting  mode-zfolio">
      <h3><?php echo esc_html__("Skin"); ?></h3>
      <?php


      $lab = "mode_zfolio_skin";


      $arr_opts = array(
        array(
          'value' => 'skin-forwall',
          'label' => esc_html__("Skin Forwall", 'dzsvg'),
        ),
        array(
          'value' => 'skin-alba',
          'label' => esc_html__("Skin Alba", 'dzsvg'),
        ),
        array(
          'value' => 'skin-overlay',
          'label' => esc_html__("Skin Overlay", 'dzsvg'),
        ),
      );


      echo DZSHelpers::generate_select($lab, array(
        'options' => $arr_opts,
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'seekval' => '',
      ));
      ?>
    </div>

    <div class="setting  mode-zfolio">
      <h3><?php echo esc_html__("Gap Size"); ?></h3>
      <?php


      $lab = "mode_zfolio_gap";


      $arr_opts = array(
        array(
          'value' => '30px',
          'label' => esc_html__("30px"),
        ),
        array(
          'value' => '1px',
          'label' => esc_html__("1px"),
        ),
      );


      echo DZSHelpers::generate_select($lab, array(
        'options' => $arr_opts,
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'seekval' => '',
      ));
      ?>
    </div>

    <div class="setting  mode-zfolio">
      <h3><?php echo esc_html__("Layout"); ?></h3>
      <?php


      $lab = "mode_zfolio_layout";


      $arr_opts = array(
        array(
          'value' => '3columns',
          'label' => sprintf(esc_html__("%s Columns "), '3'),
        ),
        array(
          'value' => '5columns',
          'label' => esc_html__("5 Columns"),
        ),
      );


      echo DZSHelpers::generate_select($lab, array(
        'options' => $arr_opts,
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'seekval' => '',
      ));
      ?>
    </div>

    <div class="setting  mode-zfolio">
      <h3><?php echo esc_html__("Title links to..."); ?></h3>
      <?php


      $lab = "mode_zfolio_title_links_to";


      $arr_opts = array(
        array(
          'value' => '',
          'label' => esc_html__("Nothing"),
        ),

        array(
          'value' => 'direct_link_a',
          'label' => esc_html__("Direct Link"),
        ),
        array(
          'value' => 'zoombox',
          'label' => esc_html__("Lightbox open"),
        ),
      );


      echo DZSHelpers::generate_select($lab, array(
        'options' => $arr_opts,
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'seekval' => '',
      ));
      ?>
    </div>

    <div class="setting  mode-gallery_view">
      <h3><?php echo esc_html__("Navigation Type", 'dzsvg'); ?></h3>
      <?php


      $lab = "mode_gallery_view_nav_type";


      $arr_opts = array(
        array(
          'value' => 'thumbs',
          'label' => esc_html__("Normal"),
        ),
        array(
          'value' => 'thumbsandarrows',
          'label' => esc_html__("Thumbnails and Arrows"),
        ),
        array(
          'value' => 'scroller',
          'label' => esc_html__("Scrollbar"),
        ),
      );


      echo DZSHelpers::generate_select($lab, array(
        'options' => $arr_opts,
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'seekval' => '',
      ));
      ?>
    </div>

    <div class="setting  mode-gallery_view">
      <h3><?php echo esc_html__("Gallery Skin", 'dzsvg'); ?></h3>
      <?php


      $lab = 'mode_gallery_view_gallery_skin';
      $arr_opts = array(
        array(
          'value' => 'skin_default',
          'label' => esc_html__("Default", 'dzsvg'),
        ),
        array(
          'value' => 'skin_navtransparent',
          'label' => esc_html__("Navigation Transparent"),
        ),
        array(
          'value' => 'skin_pro',
          'label' => esc_html__("Skin Pro"),
        ),
        array(
          'value' => 'skin_boxy',
          'label' => esc_html__("Skin Boxy"),
        ),
        array(
          'value' => 'skin_custom',
          'label' => esc_html__("Skin Custom"),
        ),
      );


      echo DZSHelpers::generate_select($lab, array(
        'options' => $arr_opts,
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'seekval' => '',
      ));
      ?>

      <div class="sidenote"><?php echo esc_html__('Skin Custom can be modified via Designer Center.', 'dzsvg'); ?></div>
    </div>

    <div class="setting  mode-gallery_view">
      <h3><?php echo esc_html__("Gallery Skin"); ?></h3>
      <?php

      $lab = 'mode_gallery_view_set_responsive_ratio_to_detect';

      $arr_off_on = array(
        array(
          'value' => 'off',
          'label' => esc_html__("Off"),
        ),
        array(
          'value' => 'on',
          'label' => esc_html__("On"),
        ),
      );


      echo DZSHelpers::generate_select($lab, array(
        'options' => $arr_off_on,
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'seekval' => '',
      ));
      ?>

      <div
        class="sidenote"><?php echo esc_html__('The player can adjust to keep aspect ratio / no black bars', 'dzsvg'); ?></div>
    </div>

    <div class="setting  mode-gallery_view">
      <h3><?php echo esc_html__("Autoplay"); ?></h3>
      <?php

      $lab = 'mode_gallery_view_autoplay';


      echo DZSHelpers::generate_select($lab, array(
        'options' => $arr_off_on,
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'seekval' => '',
      ));
      ?>

      <div class="sidenote"><?php echo esc_html__('auto play the first video', 'dzsvg'); ?></div>
    </div>

    <div class="setting  mode-gallery_view">
      <h3><?php echo esc_html__("Autoplay Next Video"); ?></h3>
      <?php

      $lab = 'mode_gallery_view_autoplaynext';


      echo DZSHelpers::generate_select($lab, array(
        'options' => $arr_off_on,
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'seekval' => '',
      ));
      ?>

      <div class="sidenote"><?php echo esc_html__('auto play the first video', 'dzsvg'); ?></div>
    </div>

    <div class="setting  mode-gallery_view">
      <h3><?php echo esc_html__("Autoload First Video"); ?></h3>
      <?php

      $lab = 'mode_gallery_view_cueFirstVideo';


      echo DZSHelpers::generate_select($lab, array(
        'options' => $arr_off_on,
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'seekval' => '',
      ));
      ?>

      <div class="sidenote"><?php echo esc_html__('auto load the first video', 'dzsvg'); ?></div>
    </div>

    <div class="setting  mode-gallery_view">
      <h3><?php echo esc_html__("Enable Analytics"); ?></h3>
      <?php

      $lab = 'mode_gallery_view_analytics_enable';


      echo DZSHelpers::generate_select($lab, array(
        'options' => $arr_off_on,
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'seekval' => '',
      ));
      ?>

      <div class="sidenote"><?php echo esc_html__('', 'dzsvg'); ?></div>
    </div>

    <div class="setting  mode-gallery_view">
      <h3><?php echo esc_html__("Force Width"); ?></h3>
      <?php

      $lab = 'mode_gallery_view_width';


      echo DZSHelpers::generate_input_text($lab, array(
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'seekval' => '',
      ));
      ?>

      <div class="sidenote"><?php echo esc_html__('input a width - ie. "900" ( pixels ) or "100%" ', 'dzsvg'); ?></div>
    </div>

    <div class="setting  mode-gallery_view">
      <h3><?php echo esc_html__("Force Height"); ?></h3>
      <?php

      $lab = 'mode_gallery_view_height';


      echo DZSHelpers::generate_input_text($lab, array(
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'seekval' => '',
      ));
      ?>

      <div
        class="sidenote"><?php echo esc_html__('input a height - ie. "900" ( pixels ) or "100%" - this will get overwritten if responsive ratio is set ', 'dzsvg'); ?></div>
    </div>


    <div class="setting  mode-gallery_view">
      <h3><?php echo esc_html__("Navigation Type"); ?></h3>
      <?php

      $lab = 'mode_gallery_view_nav_type';

      $arr_positions = array(
        array(
          'value' => 'thumbs',
          'label' => esc_html__("Thumbnails"),
        ),
        array(
          'value' => 'thumbsandarrows',
          'label' => esc_html__("Thumbnails and Arrows"),
        ),
        array(
          'value' => 'scroller',
          'label' => esc_html__("Scroller"),
        ),
        array(
          'value' => 'outer',
          'label' => esc_html__("Top"),
        ),
        array(
          'value' => 'none',
          'label' => esc_html__("None"),
        ),
      );


      echo DZSHelpers::generate_select($lab, array(
        'options' => $arr_positions,
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'seekval' => '',
      ));
      ?>

      <div class="sidenote"><?php echo esc_html__('choose the navigation between items type', 'dzsvg'); ?></div>
    </div>


    <div class="setting  mode-gallery_view">
      <h3><?php echo esc_html__("Menu Item Width"); ?></h3>
      <?php

      $lab = 'mode_gallery_view_html5designmiw';


      echo DZSHelpers::generate_input_text($lab, array(
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'val' => '275',
      ));
      ?>

      <div
        class="sidenote"><?php echo esc_html__('input a width - ie. "200" ( pixels ) or "100%" - this will get overwritten if responsive ratio is set ', 'dzsvg'); ?></div>
    </div>

    <div class="setting  mode-gallery_view">
      <h3><?php echo esc_html__("Menu Item Height"); ?></h3>
      <?php

      $lab = 'mode_gallery_view_html5designmih';


      echo DZSHelpers::generate_input_text($lab, array(
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'val' => '100',
      ));
      ?>

      <div
        class="sidenote"><?php echo esc_html__('input a height - ie. "200" ( pixels ) or "100%" - this will get overwritten if responsive ratio is set ', 'dzsvg'); ?></div>
    </div>

    <div class="setting  mode-gallery_view">
      <h3><?php echo esc_html__("Navigation Space"); ?></h3>
      <?php

      $lab = 'mode_gallery_view_nav_space';


      echo DZSHelpers::generate_input_text($lab, array(
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'val' => '0',
      ));
      ?>

      <div class="sidenote"><?php echo esc_html__('navigation space between video and navigation', 'dzsvg'); ?></div>
    </div>


    <div class="setting  mode-gallery_view">
      <h3><?php echo esc_html__("Menu Position"); ?></h3>
      <?php

      $lab = 'mode_gallery_view_menuposition';

      $arr_positions = array(
        array(
          'value' => 'right',
          'label' => esc_html__("Right"),
        ),
        array(
          'value' => 'bottom',
          'label' => esc_html__("Bottom"),
        ),
        array(
          'value' => 'left',
          'label' => esc_html__("Left"),
        ),
        array(
          'value' => 'top',
          'label' => esc_html__("Top"),
        ),
        array(
          'value' => 'none',
          'label' => esc_html__("None"),
        ),
      );


      echo DZSHelpers::generate_select($lab, array(
        'options' => $arr_positions,
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'seekval' => '',
      ));
      ?>

      <div
        class="sidenote"><?php echo esc_html__('Only available for the thumbnails / thumbnails and arrows / scroller navigation type', 'dzsvg'); ?></div>
    </div>


    <div class="setting  mode-gallery_view">
      <h3><?php echo esc_html__("Play order"); ?></h3>
      <?php

      $lab = 'mode_gallery_view_playorder';

      $arr_positions = array(
        array(
          'value' => 'normal',
          'label' => esc_html__("Normal"),
        ),
        array(
          'value' => 'reverse',
          'label' => esc_html__("Reverse"),
        ),
      );


      echo DZSHelpers::generate_select($lab, array(
        'options' => $arr_positions,
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'seekval' => '',
      ));
      ?>

      <div class="sidenote"><?php echo esc_html__('', 'dzsvg'); ?></div>
    </div>


    <div class="setting  mode-gallery_view">
      <h3><?php echo esc_html__("Disable Video Title"); ?></h3>
      <?php

      $lab = 'mode_gallery_view_disable_video_title';


      echo DZSHelpers::generate_select($lab, array(
        'options' => $arr_off_on,
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'seekval' => '',
      ));
      ?>

      <div class="sidenote"><?php echo esc_html__('hide the video title', 'dzsvg'); ?></div>
    </div>


    <div class="setting  mode-gallery_view">
      <h3><?php echo esc_html__("Enable Easing on Navigation Thumbnails"); ?></h3>
      <?php

      $lab = 'mode_gallery_view_design_navigationuseeasing';


      echo DZSHelpers::generate_select($lab, array(
        'options' => $arr_off_on,
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'seekval' => '',
      ));
      ?>

      <div class="sidenote"><?php echo esc_html__('', 'dzsvg'); ?></div>
    </div>


    <div class="setting  mode-gallery_view">
      <h3><?php echo esc_html__("Enable Search Field"); ?></h3>
      <?php

      $lab = 'mode_gallery_view_enable_search_field';


      echo DZSHelpers::generate_select($lab, array(
        'options' => $arr_off_on,
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'seekval' => '',
      ));
      ?>

      <div class="sidenote"><?php echo esc_html__('', 'dzsvg'); ?></div>
    </div>


    <div class="setting  mode-gallery_view">
      <h3><?php echo esc_html__("Enable Linking"); ?></h3>
      <?php

      $lab = 'mode_gallery_view_settings_enable_linking';


      echo DZSHelpers::generate_select($lab, array(
        'options' => $arr_off_on,
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'seekval' => '',
      ));
      ?>

      <div
        class="sidenote"><?php echo esc_html__('enable so that each video has it\'s own link and can be shared.', 'dzsvg'); ?></div>
    </div>


    <div class="setting  mode-gallery_view">
      <h3><?php echo esc_html__("Autoplay Advertisment"); ?></h3>
      <?php

      $lab = 'mode_gallery_view_autoplay_ad';


      echo DZSHelpers::generate_select($lab, array(
        'options' => $arr_off_on,
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'seekval' => '',
      ));
      ?>

      <div class="sidenote"><?php echo esc_html__('autoplay adverts', 'dzsvg'); ?></div>
    </div>


    <div class="setting  mode-gallery_view">
      <h3><?php echo esc_html__("Enable Embed Button"); ?></h3>
      <?php

      $lab = 'mode_gallery_view_embedbutton';


      echo DZSHelpers::generate_select($lab, array(
        'options' => $arr_off_on,
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'seekval' => '',
      ));
      ?>

      <div class="sidenote"><?php echo esc_html__('', 'dzsvg'); ?></div>
    </div>


    <div class="setting  mode-gallery_view">
      <h3><?php echo esc_html__("Logo"); ?></h3>
      <?php

      $lab = 'mode_gallery_view_logo';


      echo DZSHelpers::generate_input_text($lab, array(
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'seekval' => '',
      ));
      ?>

      <div class="sidenote"><?php echo esc_html__('', 'dzsvg'); ?></div>
    </div>


    <div class="setting  mode-gallery_view">
      <h3><?php echo esc_html__("Logo Link"); ?></h3>
      <?php

      $lab = 'mode_gallery_view_logoLink';


      echo DZSHelpers::generate_input_text($lab, array(
        'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
        'seekval' => '',
      ));
      ?>

      <div class="sidenote"><?php echo esc_html__('', 'dzsvg'); ?></div>
    </div>


    <div class="setting  mode-zfolio">
      <h3><?php echo esc_html__("Enable Special Layout"); ?></h3>
      <?php


      $lab = "mode_zfolio_enable_special_layout";


      ?>
      <div class="dzscheckbox skin-nova"><?php
        echo DZSHelpers::generate_input_checkbox($lab, array(
          'id' => $lab,
          'val' => 'on',
          'class' => ' dzs-dependency-field',));
        ?>
        <label for="<?php echo $lab; ?>"></label>
      </div>
    </div>


    <div class="setting  mode-zfolio">
      <h3><?php echo esc_html__("Show Filters"); ?></h3>
      <?php


      $lab = "mode_zfolio_show_filters";


      ?>
      <div class="dzscheckbox skin-nova"><?php
        echo DZSHelpers::generate_input_checkbox($lab, array(
          'id' => $lab,
          'val' => 'on',
          'class' => ' dzs-dependency-field',));
        ?>
        <label for="<?php echo $lab; ?>"></label>
      </div>
    </div>


    <?php

    $dependency = array(

      array(
        'lab' => 'mode_zfolio_show_filters',
        'val' => array('on'),
      ),
    );


    ?>


    <div class="setting type-any" data-dependency='<?php echo json_encode($dependency); ?>'>

      <h3><?php echo esc_html__("Categories are links"); ?></h3>
      <?php


      $lab = "mode_zfolio_categories_are_links";


      ?>
      <div class="dzscheckbox skin-nova"><?php
        echo DZSHelpers::generate_input_checkbox($lab, array(
          'id' => $lab,
          'val' => 'on',
          'class' => ' dzs-dependency-field',));
        ?>
        <label for="<?php echo $lab; ?>"></label>
      </div>


    </div>


    <div class="setting type-any" data-dependency='<?php echo json_encode($dependency); ?>'>

      <h3><?php echo esc_html__("Categories are ajax links"); ?></h3>
      <?php


      $lab = "mode_zfolio_categories_are_links_ajax";


      ?>
      <div class="dzscheckbox skin-nova"><?php
        echo DZSHelpers::generate_input_checkbox($lab, array(
          'id' => $lab,
          'val' => 'on',
          'class' => ' dzs-dependency-field',));
        ?>
        <label for="<?php echo $lab; ?>"></label>


      </div>

      <div
        class="sidenote"><?php echo esc_html__("Enable this for instant ajax functionality  when switching categories - and updating link"); ?></div>


    </div>


    <div class="setting type-any" data-dependency='<?php echo json_encode($dependency); ?>'>

      <h3><?php echo esc_html__("Default Category"); ?></h3>
      <?php


      $lab = "mode_zfolio_default_cat";


      ?><select name="<?php echo $lab; ?>" class="dzs-style-me skin-beige"><?php echo $cats_options; ?></select>


    </div>

    <div class="setting  mode-list">
      <h3><?php echo esc_html__("Enable View Count"); ?></h3>
      <?php


      $lab = "mode_list_enable_view_count";


      ?>
      <div class="dzscheckbox skin-nova"><?php
        echo DZSHelpers::generate_input_checkbox($lab, array(
          'id' => $lab,
          'val' => 'on',
          'class' => ' dzs-dependency-field ',));
        ?>
        <label for="<?php echo $lab; ?>"></label>
      </div>
    </div>


    <br>


    <link href='https://fonts.googleapis.com/css?family=Open+Sans:700' rel='stylesheet' type='text/css'>
    <style id="dzstabs_accordio_styling"></style>
    <div id="dzstabs_accordio" class="dzs-tabs auto-init skin-melbourne tab-menu-content-con---no-padding"
         data-options="{ 'design_tabsposition' : 'top'
,design_transition: 'fade'
,design_tabswidth: 'default'
,toggle_breakpoint : '300'
,refresh_tab_height: '2000'
,settings_appendWholeContent: true
,design_tabswidth: 'fullwidth'
,toggle_type: 'accordion'}">

      <div class="dzs-tab-tobe">
        <div class="tab-menu "><?php echo esc_html__("Linking Settings", 'dzsvg'); ?></div>
        <div class="tab-content">

          <div class="sidenote"
               style="font-size:14px;"><?php echo esc_html__('Choose what clicking on the video item does', 'dzsvg'); ?></div>

          <div class="linking_type-con">
            <div class="setting  linking_type-all">
              <h3><?php echo esc_html__("Link Type"); ?></h3>
              <?php


              $lab = "linking_type";


              $arr_opts = array(
                array(
                  'value' => 'default',
                  'label' => esc_html__("Default"),
                ),
                array(
                  'value' => 'zoombox',
                  'label' => esc_html__("Zoombox"),
                ),
                array(
                  'value' => 'direct_link',
                  'label' => esc_html__("Direct Link to item page"),
                ),
                array(
                  'value' => 'vg_change',
                  'label' => esc_html__("Change Video Player"),
                ),
              );


              echo DZSHelpers::generate_select($lab, array(
                'options' => $arr_opts,
                'class' => 'dzs-style-me skin-beige  dzs-dependency-field',
                'seekval' => '',
              ));
              ?>
              <div class="sidenote"
                   style=";"><?php echo esc_html__('<strong>Default</strong> - means that the item click action will depend on the mode you chose and choose its default mode.  <br><strong>Zoombox</strong> - open the video in a lightbox. <br><strong>Direct Link</strong> - clicking will get the user to the video page.  <br><strong>Change Video Player</strong> - clicking will change a player current video.  ', 'dzsvg'); ?></div>
            </div>


            <div class="setting  linking_type-vg_change">
              <h3><?php echo esc_html__("ID of Target Gallery"); ?></h3>
              <input name="gallery_target" value="default"/>

              <div class="sidenote" style=";"><?php echo esc_html__('', 'dzsvg'); ?></div>
            </div>


          </div>

          <br>
          <br>


        </div>
      </div>

      <div class="dzs-tab-tobe">
        <div class="tab-menu "><?php echo esc_html__("Video Player Settings"); ?></div>
        <div class="tab-content">

          <?php


          $vpconfigsstr = '';
          foreach ($dzsvg->mainvpconfigs as $vpconfig) {
            //print_r($vpconfig);
            $vpconfigsstr .= '<option value="' . $vpconfig['settings']['id'] . '">' . $vpconfig['settings']['id'] . '</option>';
          }

          ?>

          <div class="sidenote"
               style="font-size:14px;"><?php echo esc_html__('Choose what clicking on the video item does', 'dzsvg'); ?></div>

          <div class="setting mode-any">
            <h3 class="setting-label"><?php echo esc_html__('Video Player Configuration', 'dzsvg'); ?></h3>
            <select class=" dzs-style-me skin-beige  dzs-dependency-field" name="vpconfig">
              <option value="default"><?php echo esc_html__('default', 'dzsvg'); ?></option>
              <?php echo $vpconfigsstr; ?>
            </select>
            <div class="sidenote"
                 style=""><?php echo esc_html__('setup these inside the <strong>Video Player Configs</strong> admin', 'dzsvg'); ?></div>
          </div>


          <br>
          <br>


        </div>
      </div>


      <div class="dzs-tab-tobe">
        <div class="tab-menu "><?php echo esc_html__("Description Settings"); ?></div>
        <div class="tab-content">

          <div class="sidenote"
               style="font-size:14px;"><?php echo esc_html__('Use these settings to control how many characters get shown from the video content.', 'dzsvg'); ?></div>

          <div class="setting  mode-any">
            <h3><?php echo esc_html__("Number of Characters"); ?></h3>
            <input name="desc_count" value="default"/>

            <div class="sidenote"
                 style=";"><?php echo esc_html__('Leave this to <strong>default</strong> in order for the number of characters to get best displayed based on the Mode.. ', 'dzsvg'); ?></div>
          </div>

          <br>
          <br>


        </div>
      </div>

      <div class="dzs-tab-tobe ">
        <div class="tab-menu ">
          <?php echo esc_html__("Pagination Settings"); ?>
        </div>
        <div class="tab-content">
          <div class="sidenote"
               style="font-size:14px;"><?php echo esc_html__('Useful if you have many videos and you want to separate them somehow.', 'dzsvg'); ?></div>

          <div class="setting  mode-any">
            <h3><?php echo esc_html__("Select Number of Items per Page"); ?></h3>
            <input name="count" value="5"/>


          </div>
          <br>
          <br>
        </div>
      </div>


      <div class="dzs-tab-tobe">
        <div class="tab-menu ">
          <span style="display: inline-block; vertical-align: middle;" class="tab-menu-title--label"><?php echo esc_html__("Sample Data", DZSVG_ID); ?></span>
          <?php
          $lab_notice = 'dzsvg_notice_sample_items_dismissed';
          if (get_option($lab_notice) == ''){

          ?>
          <span style="display: inline-block; vertical-align: middle;" class="dzstooltip-con dzsvg-notice dzsvg-notice--preview" data-lab="<?php echo $lab_notice; ?>">
            <span class="tooltip-indicator"><span  class="tooltip-info-indicator"><span class="tooltip-info-indicator--i">i</span></span></span>
            <span  class="dzstooltip  talign-center arrow-bottom style-rounded color-dark-light  dims-set transition-slidedown "  style="width: 280px;">  <span class="dzstooltip--inner">   <?php echo esc_html__("You can import examples and sample data from here."); ?>
                        <span class="dzstooltip--close"><span class="label--x-button">x</span></span>  </span> </span>
                        </span>

            <?php
            }

            ?>
        </div>
        <div class="tab-content">

          <div class="sidenote"
               style="font-size:14px;"><?php echo esc_html__('Import any of these examples with one click. ', 'dzsvg'); ?>
            <form class="no-style import-sample-items <?php

            if (get_option('dzsvg_demo_data')) {
              echo ' active-showing';
            }

            ?>" method="post">
              <button name="action" value="dzsvg_import_sample_data"><?php echo("Import sample items"); ?></button>
              <button class="only-when-active" name="action"
                      value="dzsvg_import_sample_data"><?php echo("Remove sample items"); ?></button>
            </form>
          </div>

          <div class="dzs-container">
            <div class="one-fourth ">
              <div class="feat-sample-con  import-sample import-showcase-sample-1">

                <img class="feat-sample " src="https://c3.staticflickr.com/8/7381/28034570402_7c4cd15dbe.jpg"/>
                <h4><?php echo esc_html__("9GAG.TV example"); ?></h4>
              </div>
            </div>
            <div class="one-fourth ">
              <div class="feat-sample-con  import-sample import-showcase-sample-2">

                <img class="feat-sample " src="https://i.imgur.com/iO1P255.png"/>
                <h4><?php echo esc_html__("Vimeo User Channel Wall"); ?></h4>
              </div>
            </div>
            <div class="one-fourth ">
              <div class="feat-sample-con  import-sample import-showcase-sample-3">

                <img class="feat-sample " src="https://i.imgur.com/Ma5b5Ox.png"/>
                <h4><?php echo esc_html__("Wall with Filters"); ?></h4>
              </div>
            </div>

          </div>


        </div>
      </div>


    </div>
    <div class="clear"></div>
    <br/>
    <br/>
    <div class="bottom-right-buttons">

      <button id="" class="button-secondary insert-sample"><?php echo esc_html__("Sample Galleries"); ?></button>
      <button id="insert_tests" class="button-primary insert-tests"><?php echo esc_html__("Insert Gallery"); ?></button>
    </div>
    <div class="shortcode-output"></div>
  </div>
  <div class="feedbacker"><i class="fa fa-circle-o-notch fa-spin"></i><?php echo esc_html__(" Loading... "); ?></div>
  </div><?php
}
