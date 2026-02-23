<?php

if (!defined('ABSPATH')) // --- Or some other WordPress constant
  exit;

global $dzsvg;
if (isset($_GET['dzsvp_shortcode_builder']) && $_GET['dzsvp_shortcode_builder'] == 'on') {

  do_action('dzsvg_mainoptions_before_wrap');
} elseif (isset($_GET['dzsvg_shortcode_builder']) && $_GET['dzsvg_shortcode_builder'] == 'on') {
  dzsvg_shortcode_builder();
} elseif (isset($_GET['dzsvg_reclam_builder']) && $_GET['dzsvg_reclam_builder'] == 'on') {
  dzsvg_ad_builder();
} elseif (isset($_GET['dzsvg_quality_builder']) && $_GET['dzsvg_quality_builder'] == 'on') {
  dzsvg_quality_builder();
} elseif (isset($_GET['dzsvg_shortcode_showcase_builder']) && $_GET['dzsvg_shortcode_showcase_builder'] == 'on') {
  dzsvg_shortcode_showcase_builder();
} elseif (isset($_GET['dzsvg_shortcode_player_builder']) && $_GET['dzsvg_shortcode_player_builder'] == 'on') {
  dzsvg_shortcode_player_builder();
} else {


  if (current_user_can('video_gallery_edit_options') || current_user_can('manage_options')) {

  } else {
    die(esc_html__("You are not allowed to edit video gallery options"));
  }

  if (isset($_POST['dzsvg_delete_cache']) && $_POST['dzsvg_delete_cache'] == 'on') {
    delete_option('dzsvg_cache_ytuserchannel');
    delete_option('dzsvg_cache_ytplaylist');
    delete_option(DZSVG_PARSER_YOUTUBE_KEYWORDS_CACHE_NAME);
    delete_option(DZSVG_PARSER_VIMEO_FOLDER_CACHE_NAME);
    delete_option(DZSVG_PARSER_VIMEO_ALBUM_CACHE_NAME);
    delete_option(DZSVG_PARSER_VIMEO_CHANNEL_CACHE_NAME);
    delete_option(DZSVG_PARSER_VIMEO_USER_CHANNEL_CACHE_NAME);
    delete_option(DZSVG_PARSER_VIMEO_USER_CHANNEL_CACHE_NAME);
  }


  if (isset($_POST['dzsvg_delete_all_options']) && $_POST['dzsvg_delete_all_options'] == 'on') {


    if (!wp_verify_nonce($_REQUEST['dzsvg_delete_all_options_nonce'], 'dzsvg_delete_all_options_nonce')) {

      die('Security check');

    }


    delete_option('dzsvg_cache_ytuserchannel');
    delete_option('dzsvg_cache_ytplaylist');
    delete_option(DZSVG_PARSER_YOUTUBE_KEYWORDS_CACHE_NAME);
    delete_option('cache_dzsvg_vmuser');
    delete_option('cache_dzsvg_vmchannel');
    delete_option('cache_dzsvg_vmalbum');
    delete_option('dzsvg_cache_vmalbum');
    delete_option('dzsvg_cache_vmchannel');
    delete_option('dzsvg_cache_vmuser');
    /** @var stdClass $dzsvgObject coming from outside */
    delete_option($dzsvgObject->dbitemsname);
    delete_option($dzsvgObject->dbvpconfigsname);
    delete_option($dzsvgObject->dboptionsname);
    delete_option($dzsvgObject->dbdcname);
    delete_option($dzsvgObject->dbdbsname);


    global $wpdb;
    $table_name = $wpdb->prefix . 'posts';

    $user_id = get_current_user_id();


    $wpdb->delete($table_name, array('post_type' => DZSVG_POST_NAME));;


  }

  $config_main_options = include(DZSVG_PATH . 'configs/config-main-options.php');




  $arr_vpconfigs = array();
  $i = 0;
  $arr_vpconfigs[$i] = array('lab' => esc_html__('Default Configuration', 'dzsvp'), 'val' => 'default');
  $i++;
  foreach ($dzsvgObject->mainvpconfigs as $vpconfig) {
    $arr_vpconfigs[$i] = array('lab' => $vpconfig['settings']['id'], 'val' => $vpconfig['settings']['id']);
    $i++;
  };
  ?>

  <div class="wrap <?php

  if (isset($_GET['dzsvg_shortcode_builder']) && $_GET['dzsvg_shortcode_builder'] == 'on') {
    echo ' wrap-shortcode-builder';
  }
  ?>">
    <h2><?php echo esc_html__('Video Gallery Main Settings', DZSVG_ID); ?></h2>
    <br/>

    <div class="dzs--main-setings--search-con">
      <br>
      <div>
        <input class="dzs-big-input" id="dzs--settings-search" type="search"
               placeholder="<?= esc_html__('Search...', DZSVG_ID); ?>"/>
        <i class="dzs--settings-search--search-icon">
          <?php

          echo dzs_read_from_file_ob(DZSVG_PATH . 'assets/svg/search.svg');
          ?>
        </i>
      </div>
    </div>

    <form class="mainsettings">

      <a class="zoombox button-secondary" href="<?php echo DZSVG_URL; ?>readme/index.html"
         data-bigwidth="1100" data-scaling="fill"
         data-bigheight="700"><?php echo esc_html__("Documentation", DZSVG_ID); ?></a>

      <a
        href="<?php echo admin_url('admin.php?page=' . DZSVG_PAGENAME_MAINOPTIONS . '&dzsvg_shortcode_showcase_builder=on'); ?>"
        target="_blank"
        class="button-secondary action"><?php _e('Showcase Shortcode Generator', DZSVG_ID); ?></a>

      <a href="<?php echo admin_url('admin.php?page=' . DZSVG_PAGENAME_MAINOPTIONS . '&dzsvg_shortcode_builder=on'); ?>"
         target="_blank"
         class="button-secondary action"><?php _e(' Gallery Shortcode Generator', DZSVG_ID); ?></a>

      <a
        href="<?php echo admin_url('admin.php?page=' . DZSVG_PAGENAME_MAINOPTIONS . '&dzsvg_shortcode_player_builder=on'); ?>"
        target="_blank"
        class="button-secondary action"><?php _e(' Player Generator', DZSVG_ID); ?></a>


      <?php
      do_action('dzsvg_mainoptions_before_tabs');
      ?>

      <h3><?php echo esc_html__('Admin Options', DZSVG_ID); ?></h3>

      <?php
      if (in_array($dzsvg->mainoptions['youtube_api_key'], DZSVG_YOUTUBE_SAMPLE_API_KEY)) {
        ?>
        <div class="warning notice is-dismissible notice-warning">
        <p><?php echo esc_html__("Warning! You are using one of the predefined youtube api keys. You will have to create your own api key for this section - ", DZSVG_ID) . '<a href="' . admin_url('admin.php?page=' . DZSVG_PAGENAME_MAINOPTIONS . '&tab=8') . '">' . esc_html__("here", DZSVG_ID) . '</a>'; ?></p>
        <button type="button" class="notice-dismiss"><span
            class="screen-reader-text"><?= esc_html__("here", DZSVG_ID) ?>></span></button></div><?php
      }
      ?>

      <div id="dzs-tabs--main-options" class="dzs-tabs auto-init" data-options="{ 'design_tabsposition' : 'top'
,design_transition: 'fade'
,design_tabswidth: 'default'
,toggle_breakpoint : '400'
,toggle_type: 'accordion'
,settings_enable_linking : 'on'
,settings_appendWholeContent: true
,refresh_tab_height: '1000'
}">

        <div class="dzs-tab-tobe">
          <div class="tab-menu with-tooltip">
            <i class="fa fa-tachometer"></i> <?php echo esc_html__("Settings", DZSVG_ID); ?>
          </div>
          <div class="tab-content">
            <br>


            <div class="setting">

              <?php
              $lab = 'playlists_mode';
              ?>
              <h4 class="setting-label"><?php echo esc_html__('Playlists mode', DZSVG_ID); ?></h4><?php
              echo DZSHelpers::generate_select($lab, array('id' => $lab,
                'class' => 'dzs-style-me skin-beige',
                'options' => array(
                  array(
                    'label' => esc_html__("Legacy"),
                    'value' => 'legacy',
                  ),
                  array(
                    'label' => esc_html__("Normal"),
                    'value' => 'normal',
                  ),
                ),
                'seekval' => $dzsvgObject->mainoptions[$lab]));
              ?>
              <div
                class="sidenote"><?php echo esc_html__('by default scripts and styles from this gallery are included only when needed for optimizations reasons, but you can choose to always use them ( useful for when you are using a ajax theme that does not reload the whole page on url change )', DZSVG_ID); ?></div>
            </div>


            <?php
            echo ClassDzsvgHelpers::generateOptionsFromConfigForMainOptions($config_main_options, 'main', $dzsvg->mainoptions);
            ?>


            <?php
            $lab = 'enable_video_showcase';
            echo DZSHelpers::generate_input_text($lab, array('id' => $lab, 'val' => 'on', 'input_type' => 'hidden'));
            ?>



            <?php
            $lab = 'replace_default_video_embeds';
            ?>

            <div class="setting">

              <h4 class="setting-label"><?php echo esc_html__('Replace default video embeds', DZSVG_ID); ?></h4>

              <?php


              $val = $dzsvgObject->mainoptions[$lab];


              $opts = array(
                array(
                  'label' => esc_html__('Do not replace', DZSVG_ID),
                  'value' => '',
                ),
              );

              foreach ($arr_vpconfigs as $vpconf) {
                array_push($opts, $vpconf);
              }


              echo DZSHelpers::generate_select($lab, array('options' => $opts, 'class' => 'dzs-style-me skin-beige', 'seekval' => $val));

              ?>
              <div class="sidenote"><?php echo esc_html__('Track views on video posts', DZSVG_ID); ?></div>
            </div>


            <?php


            ?>


            <!-- end general settings -->


          </div>
        </div>

        <div class="dzs-tab-tobe tab-disabled">
          <div class="tab-menu ">
            &nbsp;&nbsp;
          </div>
          <div class="tab-content">

          </div>
        </div>

        <div class="dzs-tab-tobe">
          <div class="tab-menu with-tooltip">
            <i class="fa fa-paint-brush"></i> <?php echo esc_html__("Appearance", DZSVG_ID) ?>
          </div>
          <div class="tab-content">
            <br>


            <?php
            echo ClassDzsvgHelpers::generateOptionsFromConfigForMainOptions($config_main_options, 'appearance', $dzsvg->mainoptions);
            ?>


            <?php
            $lab = 'translate_skipad';
            echo '
                                   <div class="setting">
                                       <div class="setting-label">' . esc_html__('Translate Skip Ad', DZSVG_ID) . '</div>
                                       ' . DzsvgAdmin::formsGenerate_addInputText($lab, array('val' => '', 'seekval' => $dzsvgObject->mainoptions[$lab])) . '
                                   </div>';
            ?>



            <?php
            $lab = 'translate_all';
            echo '<div class="setting"> <div class="setting-label">';


            $label_html = sprintf(
	            esc_html__( 'Translate %sAll%s', DZSVG_ID ),
	            '<em>',
	            '</em>'
            );
            echo wp_kses_post( $label_html );


            echo '</div>' . DzsvgAdmin::formsGenerate_addInputText($lab, array('val' => '', 'seekval' => $dzsvgObject->mainoptions[$lab])) . '
                                       <div class="sidenote">' . esc_html__('leave blank here and you can translate in multiple languages via WPML or poedit', DZSVG_ID) . '</div>
                                   </div>';
            ?>




            <?php
            $lab = 'translate_share';
            echo '<div class="setting"> <div class="setting-label">';


            // Allows <em> tags only in translation
            $label_html = sprintf(
	            esc_html__( 'Translate %1$sShare%2$s', DZSVG_ID ),
	            '<em>',
	            '</em>'
            );
            echo wp_kses_post( $label_html );


            echo '</div>
                                       ' . DzsvgAdmin::formsGenerate_addInputText($lab, array('val' => '', 'seekval' => $dzsvgObject->mainoptions[$lab])) . '
                                       <div class="sidenote">' . esc_html__('leave blank here and you can translate in multiple languages via WPML or poedit', DZSVG_ID) . '</div>
                                   </div>';
            ?>


            <div class="setting">
              <div class="setting-label"><?php echo esc_html__('Extra CSS', DZSVG_ID); ?></div>
              <?php echo DZSHelpers::generate_input_textarea('extra_css',
                array(
                  'val' => '',
                  'extraattr' => ' style="width: 100%; "',
                  'seekval' => $dzsvgObject->mainoptions['extra_css'],
                )
              ); ?>
              <div class="sidenote"></div>
            </div>

          </div>
        </div>


        <div class="dzs-tab-tobe tab-disabled">
          <div class="tab-menu ">
            &nbsp;&nbsp;
          </div>
          <div class="tab-content">

          </div>
        </div>

        <div class="dzs-tab-tobe">
          <div class="tab-menu with-tooltip">
            <i class="fa fa-external-link"></i> <?php echo esc_html__("Video Page") ?>
          </div>
          <div class="tab-content">
            <br>


            <h3><?php echo esc_html__('Video Page', DZSVG_ID); ?></h3>


            <div class="setting">
              <h4 class="setting-label"><?php echo esc_html__('Post Name', 'dzsvp'); ?></h4>
              <?php
              $lab = 'dzsvp_post_name';
              $val = $dzsvgObject->mainoptions[$lab];
              echo DZSHelpers::generate_input_text($lab, array('class' => '', 'seekval' => $val));
              ?>

            </div>


            <div class="setting">
              <h4 class="setting-label"><?php echo esc_html__('Post Name Singular', 'dzsvp'); ?></h4>
              <?php
              $lab = 'dzsvp_post_name_singular';
              $val = $dzsvgObject->mainoptions[$lab];
              echo DZSHelpers::generate_input_text($lab, array('class' => '', 'seekval' => $val));
              ?>

            </div>


            <div class="setting">
              <h4 class="setting-label"><?php echo esc_html__('Video Player Configuration', 'dzsvp'); ?></h4>
              <?php
              $lab = 'dzsvp_video_config';
              $val = $dzsvgObject->mainoptions[$lab];
              echo DZSHelpers::generate_select($lab, array('options' => $arr_vpconfigs, 'class' => 'dzs-style-me skin-beige', 'seekval' => $val));
              ?>

            </div>


            <div class="setting">

              <?php
              $lab = 'videopage_show_views';
              echo DZSHelpers::generate_input_text($lab, array('id' => $lab, 'val' => 'off', 'input_type' => 'hidden'));
              ?>
              <h4 class="setting-label"><?php echo esc_html__('Show Play Count ', DZSVG_ID); ?></h4>
              <div class="dzscheckbox skin-nova">
                <?php
                echo DZSHelpers::generate_input_checkbox($lab, array('id' => $lab, 'val' => 'on', 'seekval' => $dzsvgObject->mainoptions[$lab])); ?>
                <label for="<?php echo $lab; ?>"></label>
              </div>
              <div class="sidenote"><?php echo esc_html__('Yes / No', DZSVG_ID); ?></div>
            </div>
            <div class="setting">

              <?php
              $lab = 'videopage_autoplay';
              echo DZSHelpers::generate_input_text($lab, array('id' => $lab, 'val' => 'off', 'input_type' => 'hidden'));
              ?>
              <h4 class="setting-label"><?php echo esc_html__('Autoplay', DZSVG_ID); ?></h4>
              <div class="dzscheckbox skin-nova">
                <?php
                echo DZSHelpers::generate_input_checkbox($lab, array('id' => $lab, 'val' => 'on', 'seekval' => $dzsvgObject->mainoptions[$lab])); ?>
                <label for="<?php echo $lab; ?>"></label>
              </div>
              <div class="sidenote"><?php echo esc_html__('autoplay videos on video page', DZSVG_ID); ?></div>
            </div>


            <div class="setting">

              <?php
              $lab = 'videopage_autoplay_next';
              echo DZSHelpers::generate_input_text($lab, array('id' => $lab,
                'val' => 'off',
                'class' => 'fake-input',
                'input_type' => 'hidden'));
              ?>
              <h4 class="setting-label"><?php echo esc_html__('Autoplay Next Video', DZSVG_ID); ?></h4>
              <div class="dzscheckbox skin-nova">
                <?php
                echo DZSHelpers::generate_input_checkbox($lab, array('id' => $lab,
                  'val' => 'on',
                  'class' => 'dzs-dependency-field',
                  'seekval' => $dzsvgObject->mainoptions[$lab]));
                ?>
                <label for="<?php echo $lab; ?>"></label>
              </div>
              <div class="sidenote"><?php echo esc_html__('autoplay the next video item post', DZSVG_ID); ?></div>
            </div>


            <?php
            $lab = 'post_is_public';
            ?>
            <div class="setting">

              <?php
              echo DZSHelpers::generate_input_text($lab, array('id' => $lab, 'val' => 'off', 'input_type' => 'hidden'));
              ?>
              <h4 class="setting-label"><?php echo esc_html__('Post is public', DZSVG_ID); ?></h4>
              <div class="dzscheckbox skin-nova">
                <?php
                echo DZSHelpers::generate_input_checkbox($lab, array('id' => $lab, 'val' => 'on', 'seekval' => $dzsvgObject->mainoptions[$lab])); ?>
                <label for="<?php echo $lab; ?>"></label>
              </div>
              <div class="sidenote"><?php echo esc_html__('show the videos as pages', DZSVG_ID); ?></div>
            </div>

            <?php
            $lab = 'post_show_in_nav_menus';
            ?>
            <div class="setting">

              <?php
              echo DZSHelpers::generate_input_text($lab, array('id' => $lab, 'val' => 'off', 'input_type' => 'hidden'));
              ?>
              <h4 class="setting-label"><?php echo esc_html__('Post show in nav menu', DZSVG_ID); ?></h4>
              <div class="dzscheckbox skin-nova">
                <?php
                echo DZSHelpers::generate_input_checkbox($lab, array('id' => $lab, 'val' => 'on', 'seekval' => $dzsvgObject->mainoptions[$lab])); ?>
                <label for="<?php echo $lab; ?>"></label>
              </div>
              <div class="sidenote"><?php echo esc_html__('show the Video Items menu', DZSVG_ID); ?></div>
            </div>


            <?php


            $dependency = array(

              array(
                'label' => 'videopage_autoplay_next',
                'value' => array('on'),
              ),
            );


            $dependency = json_encode($dependency);

            ?>


            <div class="setting" data-dependency='<?php echo($dependency); ?>'>

              <?php
              $lab = 'videopage_autoplay_next_direction';
              ?>
              <h4 class="setting-label"><?php echo esc_html__('Autoplay Next Video', DZSVG_ID); ?></h4>
              <?php
              echo DZSHelpers::generate_select($lab, array('id' => $lab,
                'class' => 'dzs-style-me skin-beige',
                'options' => array(
                  array(
                    'label' => esc_html__("Normal"),
                    'value' => 'normal',
                  ),
                  array(
                    'label' => esc_html__("Reverse"),
                    'value' => 'reverse',
                  ),
                ),
                'seekval' => $dzsvgObject->mainoptions[$lab]));
              ?>

              <div class="sidenote"><?php echo esc_html__('autoplay the next video item post', DZSVG_ID); ?></div>
            </div>
            <div class="setting">

              <?php
              $lab = 'videopage_resize_proportional';
              echo DZSHelpers::generate_input_text($lab, array('id' => $lab, 'val' => 'off', 'input_type' => 'hidden'));
              ?>
              <h4 class="setting-label"><?php echo esc_html__('Resize proportional ?', DZSVG_ID); ?></h4>
              <div class="dzscheckbox skin-nova">
                <?php
                echo DZSHelpers::generate_input_checkbox($lab, array('id' => $lab, 'val' => 'on', 'seekval' => $dzsvgObject->mainoptions[$lab])); ?>
                <label for="<?php echo $lab; ?>"></label>
              </div>
              <div class="sidenote"><?php echo esc_html__('resize proportionally to try and hide black bars', DZSVG_ID); ?></div>
            </div>

            <h3><?php echo esc_html__('Lightbox Settings', DZSVG_ID); ?></h3>
            <div class="setting">

              <?php
              $lab = 'zoombox_autoplay';
              echo DZSHelpers::generate_input_text($lab, array('id' => $lab, 'val' => 'off', 'input_type' => 'hidden'));
              ?>
              <h4 class="setting-label"><?php echo esc_html__('Autoplay Video in Zoombox', DZSVG_ID); ?></h4>
              <div class="dzscheckbox skin-nova">
                <?php
                echo DZSHelpers::generate_input_checkbox($lab, array('id' => $lab, 'val' => 'on', 'seekval' => $dzsvgObject->mainoptions[$lab])); ?>
                <label for="<?php echo $lab; ?>"></label>
              </div>
              <div class="sidenote"><?php echo esc_html__('Yes / No', DZSVG_ID); ?></div>
            </div>


            <div class="setting">
              <h4 class="setting-label"><?php echo esc_html__('Video Player Configuration', 'dzsvp'); ?></h4>
              <?php
              $lab = 'zoombox_video_config';
              $val = $dzsvgObject->mainoptions[$lab];
              echo DZSHelpers::generate_select($lab, array('options' => $arr_vpconfigs, 'class' => 'dzs-style-me skin-beige', 'seekval' => $val));
              ?>

            </div>


          </div>
        </div>


        <div class="dzs-tab-tobe tab-disabled">
          <div class="tab-menu ">
            &nbsp;&nbsp;
          </div>
          <div class="tab-content">
            <br>


          </div>
        </div>

        <div class="dzs-tab-tobe">
          <div class="tab-menu with-tooltip">
            <i class="fa fa-bar-chart"></i> <?php echo esc_html__("Analytics") ?>
          </div>
          <div class="tab-content">
            <br>


            <div class="dzs-container">
              <div class="full">
                <div class="setting">

                  <?php
                  $lab = 'analytics_enable';
                  echo DZSHelpers::generate_input_text($lab, array('id' => $lab, 'val' => 'off', 'input_type' => 'hidden'));
                  ?>
                  <h4 class="setting-label"><?php echo esc_html__('Enable Analytics', DZSVG_ID); ?></h4>
                  <div class="dzscheckbox skin-nova">
                    <?php
                    echo DZSHelpers::generate_input_checkbox($lab, array('id' => $lab, 'val' => 'on', 'seekval' => $dzsvgObject->mainoptions[$lab])); ?>
                    <label for="<?php echo $lab; ?>"></label>
                  </div>
                  <div
                    class="sidenote"><?php echo esc_html__('activate analytics for the galleries', DZSVG_ID); ?></div>
                </div>


                <div class="setting">

                  <?php
                  $lab = 'analytics_enable_location';
                  echo DZSHelpers::generate_input_text($lab, array('id' => $lab, 'val' => 'off', 'input_type' => 'hidden'));
                  ?>
                  <h4 class="setting-label"><?php echo esc_html__('Track Users Country?', DZSVG_ID); ?></h4>
                  <div class="dzscheckbox skin-nova">
                    <?php
                    echo DZSHelpers::generate_input_checkbox($lab, array('id' => $lab, 'val' => 'on', 'seekval' => $dzsvgObject->mainoptions[$lab])); ?>
                    <label for="<?php echo $lab; ?>"></label>
                  </div>
                  <div
                    class="sidenote"><?php echo esc_html__('use geolocation to track users country', DZSVG_ID); ?></div>
                </div>

                <div class="setting">

                  <?php
                  $lab = 'analytics_enable_user_track';
                  echo DZSHelpers::generate_input_text($lab, array('id' => $lab, 'val' => 'off', 'input_type' => 'hidden'));
                  ?>
                  <h4 class="setting-label"><?php echo esc_html__('Track Statistic by User?', DZSVG_ID); ?></h4>
                  <div class="dzscheckbox skin-nova">
                    <?php
                    echo DZSHelpers::generate_input_checkbox($lab, array('id' => $lab, 'val' => 'on', 'seekval' => $dzsvgObject->mainoptions[$lab])); ?>
                    <label for="<?php echo $lab; ?>"></label>
                  </div>
                  <div
                    class="sidenote"><?php echo esc_html__('track views and minutes watched of each user', DZSVG_ID); ?></div>
                </div>


              </div>


            </div>


          </div>
        </div>

        <div class="dzs-tab-tobe tab-disabled">
          <div class="tab-menu ">
            &nbsp;&nbsp;
          </div>
          <div class="tab-content">

          </div>
        </div>

        <div class="dzs-tab-tobe">
          <div class="tab-menu with-tooltip">
            <i class="fa fa-youtube"></i> <?php echo esc_html__("YouTube") ?>
          </div>
          <div class="tab-content">
            <br>


            <?php


            echo '<div class="setting">
                    <div class="setting-label label">' . esc_html__('YouTube API Key', DZSVG_ID) . '</div>
                    ' . DzsvgAdmin::formsGenerate_addInputText('youtube_api_key', array('val' => '', 'seekval' => $dzsvgObject->mainoptions['youtube_api_key'])) . '
                    <div class="sidenote">';




            // Allows <em> tags only in translation
            $label_html = sprintf(
	            __( 'get a api key %shere%s, create a new project, access API > %sAPIs%s and enabled YouTube Data API, then create your Public API Access from API > Credentials', DZSVG_ID )
	            , '<a href="https://console.developers.google.com">', '</a>', '<strong>', '</strong>'
            );
            echo wp_kses_post( $label_html );

            echo '</div><div class="sidenote">';


            $label_html = sprintf(
	            __('remember, do not enter anything in referers field, unless you know what you are doing, leave it clear like so - %shere%s', DZSVG_ID), '<a href="https://lh3.googleusercontent.com/5eps7rIYzxwpO5ftxy4D6GiMdimShMRWM7XE0-pQ5lI=w1221-h950-no">', '</a>'            );
            echo wp_kses_post( $label_html );

            echo '</div>
                    <span class="display-inline-block align-center"><span style="font-style:italic;">' . esc_html__('Youtube API Guide', DZSVG_ID) . '</span> - </span> <span class="display-inline-block align-center"><a class="button-secondary" target="_blank" href="https://bit.ly/dzs-youtube-api-guide">' . esc_html__('guide', DZSVG_ID) . '</a></span>
                </div>';


            $lab = 'youtube_playfrom';
            echo ' <div class="setting"><div class="setting-label label">' . esc_html__('YouTube Play From', DZSVG_ID) . '</div>
                    ' . DzsvgAdmin::formsGenerate_addInputText($lab, array('val' => '', 'seekval' => $dzsvgObject->mainoptions[$lab])) . '
                    <div class="sidenote">';


            $label_html = sprintf(__('Set a play from for youtube channel and playlist feeds. For example you can input here %slast%s and the youtube video will play from the last position.', DZSVG_ID), '<strong>', '</strong>');


            echo wp_kses_post( $label_html );


            echo '</div>
                    
                </div>';


            ?>

            <div class="setting">
              <div class="setting-label"><?php echo esc_html__('Hide non-embeddable movies', 'dzsvp'); ?></div>
              <?php
              $lab = 'youtube_hide_non_embeddable';
              $arr_opts = array(
                array(
                  'lab' => esc_html__('Off'),
                  'val' => 'off',
                ),
                array(
                  'lab' => esc_html__('On'),
                  'val' => 'on',
                ),

              );

              $val = $dzsvgObject->mainoptions[$lab];
              echo DZSHelpers::generate_select($lab, array('options' => $arr_opts, 'class' => 'styleme', 'seekval' => $val));


              echo '<div class="sidenote">' . (esc_html__('do not retrieve videos that cannot be embedded outside of youtube', DZSVG_ID)) . '</div>';
              ?>
            </div>
            <?php


            ?>


          </div>
        </div>


        <div class="dzs-tab-tobe tab-disabled">
          <div class="tab-menu ">
            &nbsp;&nbsp;
          </div>
          <div class="tab-content">

          </div>
        </div>

        <div class="dzs-tab-tobe">
          <div class="tab-menu with-tooltip">
            <i class="fa fa-facebook"></i> <?php echo esc_html__("Facebook") ?>
          </div>
          <div class="tab-content">
            <br>
            <br>
            <br>


            <?php

            echo ClassDzsvgHelpers::generateOptionsFromConfigForMainOptions($config_main_options, 'facebook', $dzsvg->mainoptions);


            $lab = 'facebook_access_token';


            $extra_attr = '';

            if ($dzsvgObject->mainoptions['facebook_app_id']) {


            } else {
              $extra_attr = ' disabled';

              echo '<br><br><div class="sidenote warning warning-bg" style="color: #222; font-weight:bold;">' . esc_html__("Input application ID and application secret, then click Save Options, refresh, then click LOG IN WITH FACEBOOK bellow ", DZSVG_ID) . '</div>';
            }
            echo '<div class="setting"><div class="setting-label label">' . esc_html__('Access Token', DZSVG_ID) . '</div>' . DZSHelpers::generate_input_text($lab, array('val' => '', 'seekval' => $dzsvgObject->mainoptions[$lab], 'extraattr' => $extra_attr)) . '</div>';;


            $app_id = $dzsvgObject->mainoptions['facebook_app_id'];
            $app_secret = $dzsvgObject->mainoptions['facebook_app_secret'];


            if ($app_id && $app_secret) {


              require_once 'src/Facebook/autoload.php'; // change path as needed


              $fb = new Facebook\Facebook(array(
                'app_id' => $app_id,
                'app_secret' => $app_secret,
                'default_graph_version' => 'v8.00',
                //'default_access_token' => '{access-token}', // optional
              ));


              $accessToken = '';

              $helper = $fb->getRedirectLoginHelper();


              $redir_url = admin_url(DZSVG_FACEBOOK_LOGIN_REDIRECT_URL);


              $permissions = array('email'); // Optional permissions
              $loginUrl = $helper->getLoginUrl($redir_url, $permissions);


              echo '<a href="' . htmlspecialchars($loginUrl) . '">' . esc_html__('Log in with', DZSVG_ID) . ' Facebook</a>';

              echo '<div class="sidenote">' . esc_html__('Redirect URL', DZSVG_ID) . ': ' . $redir_url . ' - ' . esc_html__('you can use it to whitelist redirect url.', DZSVG_ID) . '</div>';


              ?>


              <?php
            }
            ?>

          </div>


        </div>


        <div class="dzs-tab-tobe tab-disabled">
          <div class="tab-menu ">
            &nbsp;&nbsp;
          </div>
          <div class="tab-content">
            <br>


          </div>
        </div>

        <div class="dzs-tab-tobe">
          <div class="tab-menu with-tooltip">
            <i class="fa fa-vimeo"></i> <?php echo esc_html__("Vimeo") ?>
          </div>
          <div class="tab-content">
            <br>


            <div class="setting">
              <h4 class="setting-label"><?php echo esc_html__('Vimeo Thumbnail Quality', 'dzsvp'); ?></h4>
              <?php
              $arr_opts = array(array('lab' => esc_html__('Low Quality'), 'val' => 'low',), array('lab' => esc_html__('Medium Quality'), 'val' => 'medium',), array('lab' => esc_html__('High Quality'), 'val' => 'high',),);

              $lab = 'vimeo_thumb_quality';
              $val = $dzsvgObject->mainoptions[$lab];
              echo DZSHelpers::generate_select($lab, array('options' => $arr_opts, 'class' => 'styleme', 'seekval' => $val));
              ?>
            </div>


            <div class="setting">
              <h4 class="setting-label"><?php echo esc_html__('Show only public videos', DZSVG_ID); ?></h4>
              <?php
              $arr_opts = array(
                array(
                  'lab' => esc_html__('Default', DZSVG_ID),
                  'val' => '',
                ),
                array(
                  'lab' => esc_html__('Enabled', DZSVG_ID),
                  'val' => 'on',


                )

              );

              $lab = 'vimeo_show_only_public_videos';
              $val = $dzsvgObject->mainoptions[$lab];
              echo DZSHelpers::generate_select($lab, array('options' => $arr_opts, 'class' => 'styleme', 'seekval' => $val));
              ?>
            </div>


            <?php

            $lab = 'vimeo_api_client_id';
            echo '
                                   <div class="setting">
                                       <h4 class="setting-label">' . esc_html__('Client ID', DZSVG_ID) . '</h4>
                                       ' . DZSHelpers::generate_input_text($lab, array('val' => '', 'seekval' => $dzsvgObject->mainoptions[$lab])) . '
                                       <div class="sidenote">';


            $htmlSafe = sprintf(__('you can get an api key from %shere%s - section %soAuth2%s from the app ', DZSVG_ID), '<a href="https://developer.vimeo.com/apps">', '</a>', '<strong>', '</strong>');

            echo wp_kses_post($htmlSafe);

            echo ' / ';



            $htmlSafe = sprintf(__(' additional tutorial %s here %s'), '<a target="_blank" href="http://digitalzoomstudio.net/docs/wpvideogallery/#faq-vimeoapi">', '</a>');

            echo wp_kses_post($htmlSafe);



            $htmlSafe = sprintf(__(' additional tutorial %s here %s'), '<a target="_blank" href="http://digitalzoomstudio.net/docs/wpvideogallery/#faq-vimeoapi">', '</a>');

            echo wp_kses_post($htmlSafe);

            echo  '</div>
                                   </div>';


            $lab = 'vimeo_api_client_secret';
            echo '
                                   <div class="setting">
                                       <h4 class="setting-label">' . esc_html__('Client Secret', DZSVG_ID) . '</h4>
                                       ' . DZSHelpers::generate_input_text($lab, array('val' => '', 'seekval' => $dzsvgObject->mainoptions[$lab])) . '
                                   </div>';


            $lab = 'vimeo_api_access_token';
            echo '
                                   <div class="setting">
                                       <h4 class="setting-label">' . esc_html__('Access Token', DZSVG_ID) . '</h4>
                                       ' . DZSHelpers::generate_input_text($lab, array('val' => '', 'seekval' => $dzsvgObject->mainoptions[$lab])) . '
                                       <div class="sidenote">';



            $htmlSafe = sprintf(__(' make sure API key is correct - see %s here %s - make sure it DOES NOT look like this -  %s'), '<a target="_blank" href="http://digitalzoomstudio.net/docs/wpvideogallery/#faq-vimeoapi">', '</a>', 'https://api.vimeo.com/oauth/access_token');

            echo wp_kses_post($htmlSafe);

            echo   '
                                       </div>
                                   </div>';
            ?>


          </div>


        </div>


        <div class="dzs-tab-tobe tab-disabled">
          <div class="tab-menu ">
            &nbsp;&nbsp;
          </div>
          <div class="tab-content">
            <br>


          </div>
        </div>


        <div class="dzs-tab-tobe">
          <div class="tab-menu with-tooltip">
            <i class="fa fa-share-alt"></i> <?php echo esc_html__("Social") ?>
          </div>
          <div class="tab-content">
            <br>


            <div class="setting">

              <?php
              $lab = 'merge_social_into_one';
              echo DZSHelpers::generate_input_text($lab, array('id' => $lab, 'val' => 'off', 'input_type' => 'hidden'));
              ?>
              <h4 class="setting-label"><?php echo esc_html__('Merge social options into one lightbox', DZSVG_ID); ?></h4>
              <div class="dzscheckbox skin-nova">
                <?php
                echo DZSHelpers::generate_input_checkbox($lab, array('id' => $lab, 'val' => 'on', 'seekval' => $dzsvgObject->mainoptions[$lab])); ?>
                <label for="<?php echo $lab; ?>"></label>
              </div>
              <div
                class="sidenote"><?php echo esc_html__('enable a single lightbox for share and embed links', DZSVG_ID); ?></div>
            </div>


            <?php


            $lab = 'social_social_networks';
            ?>


            <div class="setting">
              <div class="setting-label"><?php echo esc_html__('Social Networks HTML', DZSVG_ID); ?></div>
              <?php
              echo DZSHelpers::generate_input_textarea($lab, array(
                'val' => '',
                'extraattr' => ' rows="4" style="width: 100%;"',
                'seekval' => (stripslashes($dzsvgObject->mainoptions[$lab])),
              ));
              ?>
              <div class="sidenote"><?php echo esc_html__('', DZSVG_ID); ?></div>
            </div>


            <?php


            $lab = 'social_share_link';
            ?>


            <div class="setting">
              <div class="setting-label"><?php echo esc_html__('Social Networks Share Link HTML', DZSVG_ID); ?></div>
              <?php
              echo DZSHelpers::generate_input_textarea($lab, array(
                'val' => '',
                'extraattr' => ' rows="4" style="width: 100%;"',
                'seekval' => $dzsvgObject->mainoptions[$lab],
              ));
              ?>
              <div class="sidenote"><?php echo esc_html__('', DZSVG_ID); ?></div>
            </div>


            <?php


            $lab = 'social_embed_link';
            ?>


            <div class="setting">
              <div class="setting-label"><?php echo esc_html__('Social Networks Embed Code HTML', DZSVG_ID); ?></div>
              <?php
              echo DzsvgAdmin::formsGenerate_addInputTextarea($lab, array(
                'val' => '',
                'extraattr' => ' rows="4" style="width: 100%;"',
                'seekval' => htmlentities($dzsvgObject->mainoptions[$lab]),
              ));
              ?>
              <div class="sidenote"><?php echo esc_html__('', DZSVG_ID); ?></div>
            </div>


            <?php


            $lab = 'dzsvp_tab_share_content';
            ?>


            <div class="setting">
              <div class="setting-label"><?php echo esc_html__('Video Page -> Share tab content', DZSVG_ID); ?></div>
              <?php
              echo DzsvgAdmin::formsGenerate_addInputTextarea($lab, array(
                'val' => '',
                'extraattr' => ' rows="4" style="width: 100%;"',
                'seekval' => $dzsvgObject->mainoptions[$lab],
              ));
              ?>
              <div class="sidenote"><?php echo esc_html__('', DZSVG_ID); ?></div>
            </div>


          </div>


        </div>


        <div class="dzs-tab-tobe tab-disabled">
          <div class="tab-menu ">
            &nbsp;&nbsp;
          </div>
          <div class="tab-content">
            <br>


          </div>
        </div>


        <!-- system check -->
        <div class="dzs-tab-tobe tab-disabled">
          <div class="tab-menu ">
            &nbsp;&nbsp;
          </div>
          <div class="tab-content">

          </div>
        </div>

        <div class="dzs-tab-tobe">
          <div class="tab-menu with-tooltip">
            <i class="fa fa-gear"></i> <?php echo esc_html__("System Check"); ?>
          </div>
          <div class="tab-content">
            <br>


            <?php
            include DZSVG_PATH . 'class_parts/settings-page/report-generator.php';
            ?>
          </div>


        </div>
      </div>
      <!-- system check END -->


      <?php
      if ($dzsvgObject->mainoptions['enable_developer_options'] == 'on') {

        ?>


        <div class="dzs-tab-tobe tab-disabled">
          <div class="tab-menu ">
            &nbsp;&nbsp;
          </div>
          <div class="tab-content">

          </div>
        </div>

        <div class="dzs-tab-tobe">
          <div class="tab-menu with-tooltip">
            <i class="fa fa-gears"></i> <?php echo esc_html__("Developer", DZSVG_ID); ?>
          </div>
          <div class="tab-content">
            <br>
            <?php
            echo ClassDzsvgHelpers::generateOptionsFromConfigForMainOptions($config_main_options, 'developer_options', $dzsvg->mainoptions);
            ?>


            <?php

            if (ini_get('allow_url_fopen')) {
              $lab = 'force_file_get_contents';
              ?>

              <div class="setting">

                <?php

                echo DZSHelpers::generate_input_text($lab, array('id' => $lab, 'val' => 'off', 'input_type' => 'hidden'));
                ?>
                <h4 class="setting-label"><?php echo esc_html__('Force File Get Contents', DZSVG_ID); ?></h4>
                <div class="dzscheckbox skin-nova">
                  <?php
                  echo DZSHelpers::generate_input_checkbox($lab, array('id' => $lab, 'val' => 'on', 'seekval' => $dzsvgObject->mainoptions[$lab])); ?>
                  <label for="<?php echo $lab; ?>"></label>
                </div>
                <div
                  class="sidenote"><?php echo esc_html__('sometimes curl will not work for retrieving youtube user name / playlist - try enabling this option if so...', DZSVG_ID); ?></div>
              </div>

              <?php

            } else {
              echo DZSHelpers::generate_input_text($lab, array('id' => $lab, 'val' => 'off', 'input_type' => 'hidden'));
            }
            ?>

            <!-- end developer settings -->


          </div>
        </div>

        <?php
      }
      ?>


      <!-- system check END --><?php

      do_action('dzsvg_mainoptions_extra_in_tab');

      ?>

  </div><!-- end .dzs-tabs -->


  <?php

  ClassDzsvgHelpers::enqueueDzsToggle();

  do_action('dzsvg_mainoptions_extra');
  ?>
  <br/>
  <a href='#'
     class="button-primary dzsvg-mo-save-mainoptions"><?php echo esc_html__('Save Options', DZSVG_ID); ?></a>
  </form>
  <br/><br/>
  <div class="dzstoggle toggle1" rel="">
    <div class="toggle-title" style=""><?php echo esc_html__('Delete Settings', DZSVG_ID); ?></div>
    <div class="toggle-content">
      <br>
      <form class="mainsettings" method="POST" style="">
        <button name="dzsvg_delete_cache" value="on"
                class="button-secondary"><?php echo esc_html__('Delete All Caches', DZSVG_ID); ?></button>
        <div class="sidenote">
          <?php echo esc_html__('Delete caches like youtube and vimeo playlist cache', DZSVG_ID); ?>
        </div>
      </form>

      <form class="delete-all-settings" method="POST" style="">
        <button name="dzsvg_delete_all_options" value="on"
                class="button-secondary"><?php echo esc_html__('Delete All Content', DZSVG_ID); ?></button>


        <?php
        wp_nonce_field('dzsvg_delete_all_options_nonce', 'dzsvg_delete_all_options_nonce');
        ?>
        <div class="sidenote">
          <?php echo esc_html__('Delete all video gallery settings, and start from defaults.', DZSVG_ID); ?>
        </div>
      </form>
    </div>
  </div>

  <div class="sidenote"><?php echo esc_html__("Delete all YouTube and Vimeo channel feeds caches", DZSVG_ID); ?></div>
  <br/>

  <div class="dzs-feedbacker saveconfirmer" style=""><img alt="" style="" id="save-ajax-loading2"
                                                          src="<?php echo site_url(); ?>/wp-admin/images/wpspin_light.gif"/>
  </div>
  </div>
  <div class="clear"></div><br/>
  <?php
  wp_enqueue_style('dzstooltip', DZSVG_URL . 'libs/dzstooltip/dzstooltip.css');
  wp_enqueue_script('dzstooltip', DZSVG_URL . 'libs/dzstooltip/dzstooltip.js');
}
