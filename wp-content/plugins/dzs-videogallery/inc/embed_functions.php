<?php
add_action('wp_head', 'dzsvg_embed_head_action', 556);
add_action('wp_head', 'dzsvg_embed_footer_action', 556);

function dzsvg_embed_head_action(){

  if (isset($_GET['action'])) {
    if ($_GET['action'] == 'embed_dzsvg') {

      // -- embedded css
      ?>
      <style>
        html, body {
          background-color: transparent;
        }

        body > * {
          display: none !important;
        }

        body > .dzsvg-main-con {
          display: block !important;
        }

        body .dzsvg-embed-con {
          display: block !important;
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
        }
      </style>
      <script>
        document.addEventListener("DOMContentLoaded", function () {
          var nodes = document.querySelector('.dzsvg-embed-con');
          document.body.append(nodes);
        });
      </script><?php

    }
  }
}
function dzsvg_embed_footer_action(){

  global $dzsvg;

  if (isset($_GET['action'])) {
    if ($_GET['action'] == 'embed_dzsvg') {


      echo '<div class="dzsvg-embed-con">';

      $args = array();


      if (isset($_GET['type']) && $_GET['type'] == 'gallery') {

        $args = array(
          'id' => $_GET['id'],
          'embedded' => 'on',
        );


        if (isset($_GET['db'])) {
          $args['db'] = sanitize_key($_GET['db']);
        };
        echo dzsvg_shortcode_videogallery($args);

      }




      $margsSanitized = sanitize_text_field($_GET['margs']);
      if (isset($_GET['type']) && $_GET['type'] == 'player') {


        $args = array();
        try {
          $args = @unserialize((stripslashes($margsSanitized)));
        } catch (Exception $e) {

        }



        if (is_array($args)) {

        } else {
          $args = array();




          $args = json_decode((stripslashes(base64_decode($margsSanitized))), true);


          if (is_object($args) || is_array($args)) {

          } else {
            $args = array();


          }

        }
        $args['embedded'] = 'on';
        $args['extra_classes'] = ' test';
        $args['called_from'] = 'embed';


        echo dzsvg_shortcode_player($args);

      }
      echo '</div>';
    }
  }
}
