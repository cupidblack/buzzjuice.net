<?php

function dzsvg_shortcode_sliders_display($pargs, $content = '') {

  global $dzsvg;

  $margs = array(
    'display' => 'default', // -- default or round-white-bg
    'style' => 'default', // -- default or round-white-bg
    'per_row' => '6', // -- default or round-white-bg
    'cat' => 'parent_tags', // -- default or round-white-bg
    'classes' => '', // -- default or round-white-bg
    'extra_style' => '', // -- default or round-white-bg
    'scroller_type' => '', // -- default or round-white-bg
    'page_url' => '', // -- go to filter a page or keep current page
    'image_height' => '80%', // -- go to filter a page or keep current page
  );


  if (is_array($pargs)) {
    $margs = array_merge($margs, $pargs);
  }


  $fout = '';
  $fout .= '<div class="dzsvg-sliders-display tags-display-' . $margs['display'] . ' style-' . $margs['style'] . ' ' . $margs['classes'] . '" style="' . $margs['extra_style'] . '">';


  $taxonomy = 'dzsvg_sliders';


  $main_tag_name = 'dzsvg_sliders';

  if ($margs['page_url'] == '') {
    $margs['page_url'] = dzs_curr_url();
  }
  if ($margs['cat'] == 'parent_tags') {


    $terms = get_terms($taxonomy, array(
      'hide_empty' => false,
      'parent' => 0,
    ));


  } else {


    $term_id = ClassDzsvgHelpers::sanitize_termSlugToId($margs['cat'], $taxonomy);


    $terms = get_terms($taxonomy, array(
      'hide_empty' => false,
      'parent' => $term_id,
    ));

  }


  // -- start display


  if ($margs['display'] == 'default') {

    $fout .= '<div class="dzs-row">';
  }
  if ($margs['display'] == 'select') {

    $fout .= '<select class=" dzs-style-me skin-beige dzsvg-change-playlist">';
    $fout .= '<option value="">' . esc_html__("select playlist..", DZSVG_ID) . '</option>';

    wp_enqueue_style('dzssel', DZSVG_URL . 'libs/dzsselector/dzsselector.css');
    wp_enqueue_script('dzssel', DZSVG_URL . 'libs/dzsselector/dzsselector.js');
  }
  if ($margs['display'] == 'scroller') {


    $fout .= '<div class="contentscroller scroller-type-' . $margs['scroller_type'] . ' auto-init bullets-none animate-height " data-margin="30" style="; height: auto;" data-options=\'{
    "settings_direction": "horizontal"
    ,"settings_onlyone": "off"
    ,"settings_autoHeight": "on"
    ,"per_row": "' . $margs['per_row'] . '"
    ,"outer_thumbs": "#cs2"
}\'>

                    <div class="arrowsCon arrow-skin-bare" style="text-align: right;">
                        <div class="arrow-left">
                            <div class="arrow-con">
                                <i class="the-icon fa fa-long-arrow-left"></i>
                            </div>
                        </div>
                        <div class="arrow-right">

                            <div class="arrow-con">
                                <i class="the-icon fa fa-long-arrow-right"></i>
                            </div>
                        </div>
                    </div>
                    <div class="items">';
  }


  // -- display items

  if ($margs['display'] == 'select') {

    foreach ($terms as $term) {

      $link = add_query_arg(array(
        'dzsvg_gallery_slug' => $term->slug,
      ), $margs['page_url']);


      $fout .= '<option value="' . $term->slug . '"';


      if (isset($_GET['dzsvg_gallery_slug'])) {
        if ($_GET['dzsvg_gallery_slug'] == $term->slug) {
          $fout .= ' selected';
        }
      }


      $fout .= '>' . $term->name . '</option>';
    }
  }
  if ($margs['display'] == 'scroller') {
    foreach ($terms as $term) {


      $link = add_query_arg(array(
        'dzsvg_gallery_slug' => $term->slug,
      ), $margs['page_url']);


      $t_id = $term->term_id;
      $term_meta = get_option("taxonomy_$t_id");

      $parent_term_icon = '';
      if (isset($term_meta['icon'])) {
        $parent_term_icon = $term_meta['icon'];
      }

      $label = $term->name;


      if ($margs['display'] == 'default') {

        $fout .= '<a href="' . $link . '" class="dzs-col-md-4 tag-display';


        if (isset($_GET['dzsvg_gallery_slug']) && $_GET['dzsvg_gallery_slug'] == $term->slug) {


          $fout .= ' active';

        }

        $fout .= '">';
        $fout .= '<div class=" tag-display--inner">';


        if ($parent_term_icon) {

          $fout .= '<i class=" the-icon-con">';
          $fout .= '<i class=" the-icon fa fa-' . $parent_term_icon . '" style="';

          if (isset($term_meta['color'])) {
            $fout .= 'color: ' . $term_meta['color'] . '';
          }

          $fout .= '">';
          $fout .= '</i>';
          $fout .= '</i>';
        }

        if ($parent_term_icon) {
          $fout .= '<span class=" the-label" >';

          $fout .= $label;
          $fout .= '</span>';
        }


        $fout .= '</div>';
        $fout .= '</a>';
      }
      if ($margs['display'] == 'scroller') {


        // -- scroller display

        $fout .= '<a href="' . $link . '" class="csc-item ';




        $image_src = '';
        if (isset($term_meta['coverImage'])) {

          $image_src = ClassDzsvgHelpers::sanitize_idToSource($term_meta['coverImage']);
        }


        if (isset($_GET['tag_' . $main_tag_name]) && $_GET['tag_' . $main_tag_name] == $term->slug) {


          $fout .= ' active';

        }

        $fout .= '" style="text-align: center">
';
        $fout .= '<div class="csc-item--inner--inner">';

        if ($margs['scroller_type'] == 'image') {

          $fout .= '<div class="the-img-con">';

          if (isset($term_meta['coverImage'])) {
            $fout .= '<div class="the-img divimage" style="padding-top: ' . $margs['image_height'] . '; background-image:url(' . $image_src . ')">';
            $fout .= '</div>';

          }
          $fout .= '</div>';
          $fout .= '<h6 class="the-label-title">' . $label . '</h6>';
        } else {

          $fout .= '<div class="the-bg" style=""></div>';


          $fout .= '<div class="flex-abs-container">';

          if ($parent_term_icon) {


            $fout .= '<i class=" the-icon-con">';
            $fout .= '<i class=" the-icon fa fa-' . $parent_term_icon . '" style="';

            if (isset($term_meta['color'])) {
//				        $fout.='color: '.$term_meta['color'].'';
            }

            $fout .= '">';
            $fout .= '</i>';
            $fout .= '</i>';
          }


          $fout .= '<h6 class="the-label-title">' . $label . '</h6>';
          $fout .= '
</div>';
        }

        $fout .= '
</div>';
        $fout .= '
</a>';
      }
    }
  }


  if ($margs['display'] == 'default') {
    $fout .= '</div>';
  }

  if ($margs['display'] == 'select') {
    $fout .= '</select>';
  }


  if ($margs['display'] == 'scroller') {
    $fout .= '</div> </div>';
    wp_enqueue_script('contentscroller', DZSVG_URL . 'libs/contentscroller/contentscroller.js');
    wp_enqueue_style('contentscroller', DZSVG_URL . 'libs/contentscroller/contentscroller.css');
  }

  $fout .= '</div>';


  ClassDzsvgHelpers::enqueueDzsVgShowcase();


  wp_enqueue_style('fontawesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');

  return $fout;


}

