<?php

function dzsvg_view_multisharer_output($vpsettings) {

  global $dzsvg;
  echo '<div hidden class="dzsvg-main-con dzs-social-box--main-con skin-default gallery-skin-default transition-slideup "> <div class="overlay-background"></div> <div class="box-mains-con"> <div class="box-main box-main-for-share" style=""> <div class="box-main-media-con transition-target"> <div class="close-btn-con"><span class="close-btn--icon">&times;</span></div> <div class="box-main-media type-inlinecontent" style="width: 530px; height: 280px;"><div class=" real-media" style=""><div class="hidden-content share-content" > <div class="social-networks-con"></div> <div class="share-link-con"></div> <div class="embed-link-con"></div> </div> </div> </div> <div class="box-main-under"></div> </div> </div> </div><!-- end .box-mains-con--> </div>';
  ?>
  <script class="dzsvg-multisharer-script">"use strict";
    <?php
      if ($dzsvg->mainoptions['merge_social_into_one'] == 'on' || $dzsvg->view_isMultisharerOnPage) {
        if ($dzsvg->mainoptions['social_social_networks']) {
          $aux = stripslashes($dzsvg->mainoptions['social_social_networks']);
          $aux = ClassDzsvgHelpers::sanitize_forJsSnippet($aux);

          echo 'window.dzsvg_social_feed_for_social_networks = \'' . $aux . '\'; ';
        }
        if ($dzsvg->mainoptions['social_share_link']) {
          $aux = stripslashes($dzsvg->mainoptions['social_share_link']);


          $aux = str_replace(array("\r", "\r\n", "\n"), '', $aux);
          $aux = str_replace(array("'"), '&quot;', $aux);


          echo 'window.dzsvg_social_feed_for_share_link = \'' . $aux . '\'; ';
        }
        if ($dzsvg->mainoptions['social_embed_link']) {


          if (strpos($dzsvg->mainoptions['social_embed_link'], '<textarea') !== false && strpos($dzsvg->mainoptions['social_embed_link'], '</textarea>') === false) {

            $dzsvg->mainoptions['social_embed_link'] = $dzsvg->mainoptions['social_embed_link'] . '</textarea>';
          }


          $aux = stripslashes($dzsvg->mainoptions['social_embed_link']);


          $aux = str_replace(array("\r", "\r\n", "\n"), '', $aux);
          $aux = str_replace(array("'"), '&quot;', $aux);


          echo 'window.dzsvg_social_feed_for_embed_link = \'' . $aux . '\'; ';
        }
      } ?>
  </script><?php

  ClassDzsvgHelpers::enqueueDzsVgPlaylist();
}
