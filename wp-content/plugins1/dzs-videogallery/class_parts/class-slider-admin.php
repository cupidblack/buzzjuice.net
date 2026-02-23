<?php

class SlidersAdminHelpers {

  public static function sliders_admin_generate_item($po) {

    global $dzsvg;

    $fout = '';
    $thumb = '';
    $thumb_from_meta = '';
    // -- we need real location, not insert-id
    $struct_uploader = '<div class="dzs-wordpress-uploader ">
    <a href="#" class="button-secondary">' . esc_html__('Upload', 'dzsvp') . '</a>
</div>';

    if ($po && is_int($po->ID)) {

      $thumb = ClassDzsvgHelpers::get_post_thumb_src($po->ID);


      $thumb_from_meta = get_post_meta($po->ID, 'dzsvg_meta_thumb', true);
    }

    if ($thumb_from_meta) {

      $thumb = $thumb_from_meta;
    }

    $thumb_url = '';
    if ($thumb) {
      $thumb_url = ClassDzsvgHelpers::sanitize_idToSource($thumb);

    }


    $fout .= '<div class="slider-item dzstooltip-con for-click';

    if ($po->ID == 'placeholder') {
      $fout .= ' slider-item--placeholder';
    }

    $fout .= '" data-id="' . $po->ID . '">';

    $fout .= '<div class="divimage" style="background-image:url(' . $thumb_url . ');"></div>';
    $fout .= '<div class="slider-item--title" >' . $po->post_title . '</div>';

    $fout .= '<div class="delete-btn item-control-btn"><i class="fa fa-times-circle-o"></i></div>
        <div class="clone-item-btn item-control-btn"><i class="fa fa-clone"></i></div>
        <div class="dzstooltip tooltip--sliders-admin  talign-center arrow-top style-rounded color-dark-light  dims-set transition-scaleup  ">
        <div class="dzstooltip--inner">
            <div class="dzstooltip--selector-top"></div>

            <div class="dzstooltip--content">';


    $fout .= '<div class="dzs-tabs dzs-tabs-meta-item  skin-default " data-options=\'{ "design_tabsposition" : "top"
,"design_transition": "fade"
,"design_tabswidth": "default"
,"toggle_breakpoint" : "200"
,"settings_appendWholeContent": "true"
,"toggle_type": "accordion"
}
\' style=\'padding: 0;\'>

                <div class="dzs-tab-tobe">
                    <div class="tab-menu ">' . esc_html__("General", 'dzsvg') . '
    </div>
    <div class="tab-content tab-content-item-meta-cat-main">
' . SlidersAdminHelpers::sliders_admin_generate_item_meta_cat('main', $po) . '
    </div>
    </div>
    ';


    foreach ($dzsvg->item_meta_categories_lng as $lab => $val) {


      ob_start();
      ?>

      <div class="dzs-tab-tobe">
      <div class="tab-menu ">
        <?php
        echo($val);
        ?>
      </div>
      <div class="tab-content tab-content-cat-<?php echo $lab; ?>">


        <?php
        echo SlidersAdminHelpers::sliders_admin_generate_item_meta_cat($lab, $po);
        ?>


      </div>
      </div><?php

      $fout .= ob_get_clean();


    }

    $fout .= '</div>';//-- end .dzstooltip--inner


    $fout .= '</div>';// -- end tabs
    $fout .= '
                    </div>';
    $fout .= '
                    </div>';
    $fout .= '
                    </div>';

    return $fout;
  }


  public static function sliders_admin_generate_item_meta_cat($cat, $po) {
    // -- generate the item in meta category ( for sliders_admin / sliders admin )

    global $dzsvg;

    $fout = '';
    // -- we need real location, not insert-id
    $struct_uploader = '<div class="dzs-wordpress-uploader ">
    <a href="#" class="button-secondary">' . esc_html__('Upload', 'dzsvp') . '</a>
</div>';

    foreach ($dzsvg->options_item_meta as $lab => $oim) {


      $oim = array_merge(array(
        'category' => '',
        'extraattr' => '',
        'default' => '',
      ), $oim);

      if ($oim['category'] == $cat) {

      } else {
        if ($cat == 'main') {
          if ($oim['category'] == '') {
          } else {
            continue;
          }
        } else {
          continue;
        }
      }


      if (isset($oim['only_for']) && is_array($oim['only_for'])) {
        $sw_break = true;
        foreach ($oim['only_for'] as $val) {
          if ($val === 'sliders_admin') {
            $sw_break = false;
          }
        }
        if ($sw_break) {
          continue;
        }
      }


      if ($oim['name'] == 'dzsvg_meta_config') {

        if (count($oim['choices']) === 0) {
          $oim['choices'] = $dzsvg->video_player_configs;
        }
      }
      if ($oim['name'] == 'dzsvg_meta_item_type') {

        if ($dzsvg->mainoptions['facebook_app_id']) {
          array_push($oim['choices'], array(
            'label' => esc_html__("Facebook"),
            'value' => 'facebook',
          ));
          array_push($oim['choices_html'], '<span class="option-con"><img src="' . DZSVG_URL . 'admin/img/type_facebook.png"/><span class="option-label">' . esc_html__("Facebook") . '</span></span>');
        }
      }


      $fout .= '<div class="setting settings-for-sliders-admin-item ';
      $option_name = $oim['name'];

      if (isset($oim['setting_extra_classes'])) {
        $fout .= ' ' . $oim['setting_extra_classes'];
      }
      if ($oim['type'] == 'attach') {
        $fout .= ' setting-upload';
      }

      $fout .= '">';
      $fout .= '<h5 class="setting-label">' . $oim['title'] . '</h5>';


      $fout .= '<div class="input-con">';

      if ($oim['type'] == 'attach') {
        $fout .= '<span class="uploader-preview"></span>';
      }


      $val = $oim['default'];

      if (is_int($po->ID)) {


        if ($oim['default']) {

          $aux = get_post_meta($po->ID, $option_name);
          if (get_post_meta($po->ID, $option_name)) {

            if (isset($aux[0])) {
              $val = $aux[0];
            }
          }
        } else {

          $val = get_post_meta($po->ID, $option_name, true);
        }
      }

      if ($option_name == 'the_post_title') {
        $val = $po->post_title;
      }
      if ($option_name == 'the_post_content') {
        $val = $po->post_content;
      }

      $class = 'setting-field medium';

      if ($oim['type'] == 'attach') {
        $class .= ' uploader-target';
      }


      if ($oim['type'] == 'attach') {


        if (isset($oim['upload_type']) && $oim['upload_type']) {
          $class .= ' upload-type-' . $oim['upload_type'];
        }


        if (isset($oim['dom_type']) && $oim['dom_type'] == 'textarea') {

          $fout .= DZSHelpers::generate_input_textarea($option_name, array(
            'class' => $class,
            'seekval' => $val,
            'extraattr' => ' rows="1"',
          ));
        } else {

          $fout .= DZSHelpers::generate_input_text($option_name, array(
            'class' => $class,
            'seekval' => $val,
          ));
        }
      }


      if ($oim['type'] == 'text') {
        $fout .= DZSHelpers::generate_input_text($option_name, array(
          'class' => $class,
          'seekval' => ($val),
        ));
      }

      if ($oim['type'] == 'textarea') {
        $fout .= DZSHelpers::generate_input_textarea($option_name, array(
          'class' => $class,
          'seekval' => $val,
          'extraattr' => $oim['extraattr'],
        ));
      }
      if ($oim['type'] == 'select') {
        $class = 'dzs-style-me skin-beige setting-field';

        if (isset($oim['select_type']) && $oim['select_type']) {
          $class .= ' ' . $oim['select_type'];
        }

        if (!(isset($oim['choices']))) {
          if (isset($oim['options'])) {
            $oim['choices'] = $oim['options'];
          }
        }


        if ($oim['name'] == 'dzsvg_meta_config') {

        }
        $fout .= DZSHelpers::generate_select($option_name, array(
          'class' => $class,
          'seekval' => $val,
          'options' => $oim['choices'],
        ));


        if (isset($oim['select_type']) && $oim['select_type'] == 'opener-listbuttons') {

          $fout .= '<ul class="dzs-style-me-feeder">';

          foreach ($oim['choices_html'] as $oim_html) {

            $fout .= '<li>';
            $fout .= $oim_html;
            $fout .= '</li>';
          }

          $fout .= '</ul>';
        }


      }

      if ($oim['type'] == 'attach') {
        $fout .= $struct_uploader;
      }


      if (isset($oim['extra_html_after_input']) && $oim['extra_html_after_input']) {
        $fout .= $oim['extra_html_after_input'];
      }

      $fout .= '</div>'; // -- end input con


      if (isset($oim['sidenote']) && $oim['sidenote']) {
        $fout .= '<div class="sidenote">' . $oim['sidenote'] . '</div>';
      }

      $fout .= '
                    </div>';


    }


    return $fout;
  }


}
