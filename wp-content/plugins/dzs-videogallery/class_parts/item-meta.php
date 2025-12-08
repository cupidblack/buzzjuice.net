<?php

if (!defined('ABSPATH')) // Or some other WordPress constant
  exit;


global $post, $wp_version;

// -- no id insert
$struct_uploader = '<div class="dzs-wordpress-uploader ">
<a href="#" class="button-secondary">' . esc_html__('Upload', 'dzsvp') . '</a>
</div>';
?>
<?php
$lab_nonce = 'dzsap_meta_nonce';
echo '<input type="hidden" name="' . $lab_nonce . '" value="' . wp_create_nonce($lab_nonce) . '"/>';
?>




<?php

foreach ($dzsvg->options_item_meta as $lab => $oim2) {


  $oim = array_merge(array(
    'category' => '',
    'extraattr' => '',
    'default' => '',
  ), $oim2);

  $option_name = $oim['name'];
  if ($option_name == 'the_post_title' || $option_name == 'the_post_content') {
    continue;
  }

  if (!isset($oim['choices']) && isset($oim['options'])) {
    $oim['choices'] = $oim['options'];
  }

  ?>
  <div class="setting <?php


  if ($oim['type'] == 'attach') {
    ?>setting-upload<?php
  }

  ?>">
    <h5 class="setting-label"><?php echo $oim['title']; ?></h5>


    <?php

    if ($oim['type'] == 'attach') {
      ?><span class="uploader-preview"></span><?php
    }

    ?>

    <?php

    $val = $oim['default'];
    if (isset($post->ID) && is_int(intval($post->ID))) {


      if (isset($oim['default']) && $oim['default']) {
        $aux = get_post_meta($post->ID, $option_name);
        if (get_post_meta($post->ID, $option_name)) {

          if (isset($aux[0])) {
            $val = $aux[0];
          }
        }
      } else {

        $val = get_post_meta($post->ID, $option_name, true);
      }
    }


    $class = 'setting-field medium';

    if ($oim['type'] == 'attach') {
      $class .= ' uploader-target';
    }


    if (isset($oim['input_extra_classes']) && $oim['input_extra_classes']) {
      $class .= $oim['input_extra_classes'];
    }

    $argsInput = array(
      'class' => $class,
      'seekval' => $val,
    );
    if ($oim['type'] == 'attach') {
      echo DZSHelpers::generate_input_text($option_name, $argsInput);
    }
    if ($oim['type'] == 'text') {
      echo DZSHelpers::generate_input_text($option_name, $argsInput);
    }
    if ($oim['type'] == 'textarea') {
      if (isset($oim['extraattr'])) {
        $argsInput['extraattr'] = $oim['extraattr'];
      }
      echo DZSHelpers::generate_input_textarea($option_name, $argsInput);
    }
    if ($oim['type'] == 'select') {
      $class = 'dzs-style-me skin-beige';
      if (isset($oim['select_type']) && $oim['select_type']) {
        $class .= ' ' . $oim['select_type'];
      }

      $argsInput['class'] = $class;
      if (isset($oim['choices'])) {
        $argsInput['options'] = $oim['choices'];
      }

      echo DZSHelpers::generate_select($option_name, $argsInput);

      if (isset($oim['select_type']) && $oim['select_type'] == 'opener-listbuttons') {
        echo '<ul class="dzs-style-me-feeder">';
        foreach ($oim['choices_html'] as $oim_html) {
          echo '<li>';
          echo $oim_html;
          echo '</li>';
        }

        echo '</ul>';
      }


    }

    if ($oim['type'] == 'attach') {
      echo $struct_uploader;
    }

    if (isset($oim['extra_html_after_input']) && $oim['extra_html_after_input']) {
      echo $oim['extra_html_after_input'];
    }
    if (isset($oim['sidenote']) && $oim['sidenote']) {
      echo '<div class="sidenote">' . $oim['sidenote'] . '</div>';
    }

    ?>

  </div>

  <?php


}
?>


