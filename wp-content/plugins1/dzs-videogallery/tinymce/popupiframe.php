<?php


$dzsvg_example_lib_index = 0;
function dzsvg_generate_example_lib_item($pargs) {
  global $dzsvg_example_lib_index, $dzsvg;

  $margs = array(
    'featured_image' => '',
    'title' => '',
    'demo-slug' => '',
  );


  $margs = array_merge($margs, $pargs);

  if ($dzsvg_example_lib_index % 3 == 0) {
    echo '<div class="dzs-row">';

  }


  ?>
  <div class="dzs-col-md-4">
  <div class="lib-item <?php


  if ($dzsvg->mainoptions['dzsvg_purchase_code_binded'] == 'on') {

  } else {

    echo ' dzstooltip-con';

    echo ' disabled';
  }


  ?>" data-demo="<?php echo $margs['demo-slug']; ?>"><?php


    if ($dzsvg->mainoptions['dzsvg_purchase_code_binded'] == 'on') {

    } else {

      ?>
      <div class=" dzstooltip skin-black arrow-bottom align-left">
      <?php echo esc_html__("You need to activate video gallery with purchase code before importing demos");
      ?>
      </div>
      <?php
    }


    ?>
    <i class="fa  fa-lock lock-icon"></i>
    <div class="loading-overlay">
      <i class="fa fa-spin fa-circle-o-notch loading-icon"></i>
    </div>
    <div class="divimage" style="background-image:url(<?php echo $margs['featured_image']; ?>); "></div>
    <h5><?php echo $margs['title'];; ?></h5>

  </div>

  </div><?php


  if ($dzsvg_example_lib_index % 3 == 2) {

    echo '</div>';
  }


  $dzsvg_example_lib_index++;


}


function dzsvg_shortcode_builder() {

  global $dzsvg;

  $url_admin = get_admin_url();
//<script src="<?php echo site_url(); "></script>
  ?>
  <div class="sc-con">
  <div class="sc-menu">
    <div class="setting type_any">
      <h3><?php echo esc_html__("Select a Gallery to Insert", 'dzsvg'); ?></h3>
      <select class="styleme" name="dzsvg_selectid">
        <?php

        $dzsvg->db_read_mainitems(array('called_from' => 'popupiframe'));
        if ($dzsvg->mainoptions['playlists_mode'] == 'normal') {

          foreach ($dzsvg->mainitems as $mainitem) {
            echo '<option value="' . $mainitem['value'] . '">' . $mainitem['label'] . '</option>';
          }
        } else {

          foreach ($dzsvg->mainitems as $mainitem) {
            echo '<option>' . ($mainitem['settings']['id']) . '</option>';
          }
        }


        ?>
      </select>
      <p>
        <a id="quick-edit"
           href="<?php echo admin_url('admin.php?page=' . DZSVG_PAGENAME_LEGACY_SLIDERS . '&currslider=0&from=shortcodegenerator'); ?>"
           class="sidenote" style="cursor:pointer;" onclick="          var _t = jQuery(this);                  window.open_ultibox(null,{

                                type: 'iframe'
                                ,source: _t.attr('href')
                                ,scaling: 'fill' // -- this is the under description
                                ,suggested_width: '95vw' // -- this is the under description
                                ,suggested_height: '95vh' // -- this is the under description
                                ,item: null // -- we can pass the items from here too

                            });

                            return false;
"><?php echo esc_html__("Quick Edit Gallery"); ?></a></p>
    </div>

    <?php

    if ($dzsvg->mainoptions['playlists_mode'] != 'normal') {

      ?>
      <div class="setting type_any">
        <h3><?php echo esc_html__("Select Database"); ?></h3>
        <select class="styleme" name="dzsvg_selectdb">
          <?php foreach ($dzsvg->dbs as $mainitem) {
            echo '<option>' . ($mainitem) . '</option>';
          }
          ?>
        </select>
      </div>
      <?php
    }
    ?>

    <div class="dzstoggle toggle1" rel="">
      <div class="toggle-title" style=""><?php echo esc_html__("Pagination Settings"); ?></div>
      <div class="toggle-content">
        <div class="sidenote"
             style="font-size:14px;"><?php echo esc_html__('Useful if you have many videos and you want to separate them somehow.', 'dzsvg'); ?></div>

        <div class="setting type_any">
          <h3><?php echo esc_html__("Select a Pagination Method", DZSVG_ID); ?></h3>
          <select class="styleme" name="dzsvg_settings_separation_mode">
            <option>normal</option>
            <option>pages</option>
            <option>scroll</option>
            <option>button</option>
          </select>

        </div>
        <div class="setting type_any">
          <h3><?php echo esc_html__("Select Number of Items per Page"); ?></h3>
          <input name="dzsvg_settings_separation_pages_number" value="5"/>


        </div>
      </div>
    </div>

    <div class="dzstoggle toggle1" rel="">
      <div class="toggle-title" style=""><?php echo esc_html__("Sample Data"); ?></div>
      <div class="toggle-content">
        <div class="sidenote"
             style="font-size:14px;"><?php echo esc_html__('Import any of these examples with one click. ', 'dzsvg'); ?>
          <form class="no-style import-sample-galleries" method="post">
            <button name="action" value="dzsvg_import_galleries"><?php echo("Import sample galeries"); ?></button>
          </form>
        </div>

        <div class="dzs-container">
          <div class="one-fourth ">
            <div class="feat-sample-con  import-sample import-sample-1">

              <img class="feat-sample " src="<?php echo $dzsvg->thepath; ?>sampledata/img/sample_1.jpg"/>
              <h4><?php echo esc_html__("Sample Wall"); ?></h4>
            </div>
          </div>
          <div class="one-fourth ">
            <div class="feat-sample-con  import-sample import-sample-2">

              <img class="feat-sample " src="<?php echo $dzsvg->thepath; ?>sampledata/img/sample_2.jpg"/>
              <h4><?php echo esc_html__("YouTube Channel"); ?></h4>
            </div>
          </div>


          <div class="one-fourth ">
            <div class="feat-sample-con  import-sample import-sample-3">

              <img class="feat-sample " src="<?php echo $dzsvg->thepath; ?>sampledata/img/sample_3.jpg"/>
              <h4><?php echo esc_html__("Ad Before Video"); ?></h4>
            </div>
          </div>
          <div class="one-fourth ">
            <div class="feat-sample-con  import-sample import-sample-4">

              <img class="feat-sample " src="<?php echo $dzsvg->thepath; ?>sampledata/img/sample_4.jpg"/>
              <h4><?php echo esc_html__("Balne Layout"); ?></h4>
            </div>
          </div>
          <div class="one-fourth ">
            <div class="feat-sample-con  import-sample import-sample-5">

              <img class="feat-sample " src="https://i.imgur.com/xZeF8kw.png"/>
              <h4><?php echo esc_html__("Vimeo Channel"); ?></h4>
            </div>
          </div>
        </div>


      </div>
    </div>
    <div class="clear"></div>
    <br/>
    <br/>
    <div class="bottom-right-buttons">

      <button id=""
              class="button-secondary insert-sample-library"><?php echo esc_html__("One Click Install Example"); ?></button>
      <span style="font-size: 11px; opacity: 0.5;"><?php echo esc_html__("OR", 'dzsvg'); ?></span>
      <button id="insert_tests" class="button-primary insert-tests"><?php echo esc_html__("Insert Gallery"); ?></button>
    </div>
    <div class="shortcode-output"></div>
  </div>
  <div class="feedbacker"><i class="fa fa-circle-o-notch fa-spin"></i> <?php echo esc_html__("Loading", 'dzsvg'); ?>...</div>

  <div id="import-sample-lib" class="show-only-in-ultibox"><?php

    echo '<h3>' . esc_html__("Import Demo", 'dzsvg') . '</h3>';


    $args = array(
      'featured_image' => $dzsvg->thepath . 'sampledata/img/sample_1.jpg',
      'title' => 'Sample Wall',
      'demo-slug' => 'sample-wall',
    );

    dzsvg_generate_example_lib_item($args);


    $args = array(
      'featured_image' => $dzsvg->thepath . 'sampledata/img/sample_2.jpg',
      'title' => 'Sample YouTube Channel',
      'demo-slug' => 'sample_youtube_channel',
    );

    dzsvg_generate_example_lib_item($args);


    $args = array(
      'featured_image' => $dzsvg->thepath . 'sampledata/img/sample_3.jpg',
      'title' => 'Ad Before Video',
      'demo-slug' => 'sample_ad_before_video',
    );

    dzsvg_generate_example_lib_item($args);


    $args = array(
      'featured_image' => $dzsvg->thepath . 'sampledata/img/sample_4.jpg',
      'title' => 'Balne Setup',
      'demo-slug' => 'sample_balne_setup',
    );

    dzsvg_generate_example_lib_item($args);


    $args = array(
      'featured_image' => "https://i.imgur.com/xZeF8kw.png",
      'title' => 'Vimeo Channel',
      'demo-slug' => 'sample_vimeo_channel',
    );

    dzsvg_generate_example_lib_item($args);


    $args = array(
      'featured_image' => "https://i.imgur.com/Gri5Ek1.jpg",
      'title' => 'List Mode',
      'demo-slug' => 'showcase_list_mode',
    );

    dzsvg_generate_example_lib_item($args);


    $args = array(
      'featured_image' => "https://i.imgur.com/rmeMMB4.jpg",
      'title' => 'Zfolio Mode with Categories',
      'demo-slug' => 'showcase_zfolio_mode',
    );

    dzsvg_generate_example_lib_item($args);


    $args = array(
      'featured_image' => "https://i.imgur.com/PqdCT88.jpg",
      'title' => 'Vimeo User Channel with Search',
      'demo-slug' => 'sample_vimeo_channel_with_search',
    );

    dzsvg_generate_example_lib_item($args);


    $args = array(
      'featured_image' => "https://i.imgur.com/BnXuIxn.jpg",
      'title' => 'Video Player with Cover image and ad at middle',
      'demo-slug' => 'video_player_with_cover',
    );

    dzsvg_generate_example_lib_item($args);


    $args = array(
      'featured_image' => "https://i.imgur.com/oK4NcZm.jpg",
      'title' => 'Showcase mode list',
      'demo-slug' => 'showcase_list',
    );

    dzsvg_generate_example_lib_item($args);


    $args = array(
      'featured_image' => "https://i.imgur.com/W381nhL.jpg",
      'title' => 'Showcase featured ',
      'demo-slug' => 'showcase_featured',
    );

    dzsvg_generate_example_lib_item($args);


    $args = array(
      'featured_image' => "https://i.imgur.com/mSEp2tb.jpg",
      'title' => 'Single youtube player with qualities',
      'demo-slug' => 'video_player_youtube_qualities',
    );

    dzsvg_generate_example_lib_item($args);


    $args = array(
      'featured_image' => "https://i.imgur.com/qc2FBfa.jpg",
      'title' => 'Youtube user channel with bottom outer',
      'demo-slug' => 'gallery_with_bottom_outer',
    );

    dzsvg_generate_example_lib_item($args);


    $args = array(
      'featured_image' => "https://i.imgur.com/gEBa0YO.jpg",
      'title' => 'Rotator 3d - vimeo videos',
      'demo-slug' => 'rotator-vimeo-videos',
    );

    dzsvg_generate_example_lib_item($args);


    $args = array(
      'featured_image' => "https://i.imgur.com/IO0UKQc.jpg",
      'title' => 'Select gallery',
      'demo-slug' => 'gallery-selector',
    );

    dzsvg_generate_example_lib_item($args);


    $args = array(
      'featured_image' => "https://i.imgur.com/dBOBmOx.jpg",
      'title' => 'Select gallery with dropdown',
      'demo-slug' => 'gallery-selector-with-dropdown',
    );

    dzsvg_generate_example_lib_item($args);


    $lab = 'gallery-with-laptop-players';
    $args = array(
      'featured_image' => $dzsvg->thepath . 'img/' . $lab . '.jpg',
      'title' => 'Gallery with laptop players',
      'demo-slug' => $lab,
    );

    dzsvg_generate_example_lib_item($args);


    ?>
  </div>
  </div><?php
}
