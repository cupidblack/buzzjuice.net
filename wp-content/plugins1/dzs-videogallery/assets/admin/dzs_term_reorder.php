<?php


if (class_exists('Dzs_Term_Reorder') == false) {
  class Dzs_Term_Reorder {


    public $db_mainoptions = array();
    public $dbname_mainoptions = 'dzs_term_reorder_options';
    public $base_url_term_reorder = '';
    public $page_is_reorder = false;
    public $meta_order_arr = array();
    public $keysForTermSorting = array();


    function __construct($arg_thumbdisplay = array(), $arg_catdisplay_top = array(), $arg_catsort = array(), $url_location = '') {


      $this->base_url_term_reorder = $url_location;

      $defaultOpts = array(
        'extra_css' => '',
        'thumbdisplay' => array(), // only posts
        'catdisplay_top' => array(), // post type + tax
        'cat_sort' => array(), // only taxs
        'tax' => array(),
        'already_set_cpts' => array(), // -- set first custom posts
        'already_set_tax' => array(),
        'already_set_sort_tax' => array(),
      );
      $this->db_mainoptions = get_option($this->dbname_mainoptions);
      $this->keysForTermSorting = $arg_catsort;

      // -- default opts / inject into db
      if ($this->db_mainoptions == '') {
        $this->db_mainoptions = $defaultOpts;
        update_option($this->dbname_mainoptions, $this->db_mainoptions);
      }

      $this->db_mainoptions = array_merge($defaultOpts, $this->db_mainoptions);


      $sw_do_change_cpt = false;
      foreach ($arg_thumbdisplay as $td) {


        if (in_array($td . '', $this->db_mainoptions['already_set_cpts'])) {

        } else {


          array_push($this->db_mainoptions['thumbdisplay'], $td);
          array_push($this->db_mainoptions['already_set_cpts'], $td);
          $sw_do_change_cpt = true;
        }
      }

      foreach ($arg_catdisplay_top as $catsDisplayKey => $td) {


        if (in_array($td[0], $this->db_mainoptions['already_set_tax'])) {

        } else {


          $this->db_mainoptions['catdisplay_top'][$catsDisplayKey] = $td[0];
          array_push($this->db_mainoptions['already_set_tax'], $td[0]);
          $sw_do_change_cpt = true;
        }
      }

      foreach ($arg_catsort as $td) {
        if (in_array($td, $this->db_mainoptions['already_set_sort_tax'])) {

        } else {


          array_push($this->db_mainoptions['cat_sort'], $td);
          array_push($this->db_mainoptions['already_set_sort_tax'], $td);
          $sw_do_change_cpt = true;
        }
      }





      if ($sw_do_change_cpt) {
        update_option($this->dbname_mainoptions, $this->db_mainoptions);
      }


      if (defined('DZS_TERM_REORDER_STARTED') == false) {
        define('DZS_TERM_REORDER_STARTED', 'YES');


        if (Dzs_Term_Reorder::isOnAArchivePage($this->keysForTermSorting)) {
          foreach ($this->db_mainoptions['catdisplay_top'] as $catsDisplayKey => $cdt) {
            if (isset($_GET['post_type']) && $_GET['post_type'] == $catsDisplayKey) {
              add_action('in_admin_footer', array($this, 'print_parent_terms'));
            }
          }


          if (isset($_GET['zoom-term-reorder']) && $_GET['zoom-term-reorder'] == 'on') {
            add_action('in_admin_footer', array($this, 'term_reorder'));

            $this->page_is_reorder = true;


            foreach ($this->db_mainoptions['catdisplay_top'] as $catsDisplayKey => $cdt) {

              if ($_GET['post_type'] == $catsDisplayKey) {

                add_action('in_admin_footer', array($this, 'print_parent_terms'));
              }
            }


          }
          add_action('admin_head', array($this, 'action_admin_head'));


          if (count($this->db_mainoptions['thumbdisplay'])) {
            wp_enqueue_script('dzs_term_reorder', $this->base_url_term_reorder . 'dzs_term_reorder.js');
            wp_enqueue_style('dzs_term_reorder', $this->base_url_term_reorder . 'dzs_term_reorder.css');
          }

        }
        add_action('wp_ajax_dzs_update_term_order', array($this, 'post_dzs_update_term_order'));
        add_action('wp_ajax_dzs_get_all_post_thumb_url', array($this, 'post_get_all_post_thumb_url'));

      }


    }

    /**
     * @param array $allowedKeysArg - should have 0=> value allowed 1=>value allowed etc
     * @return bool
     */
    static function isOnAArchivePage($allowedKeysArg = array()) {



      $allowedKeys = $allowedKeysArg;

      array_push($allowedKeys, 'post_type');
      array_push($allowedKeys, 'paged');
      array_push($allowedKeys, 'zoom-term-reorder');



      foreach ($_GET as $getKey => $val) {

        if (!in_array($getKey, $allowedKeys)) {
          return false;
        }

      }

      return true;
    }


    function getPostTypesByTaxonomy($tax = 'category') {
      $out = array();
      $post_types = get_post_types();
      if (is_array($post_types)) {

        foreach ($post_types as $post_type) {
          $taxonomies = get_object_taxonomies($post_type);
          if (in_array($tax, $taxonomies)) {
            $out[] = $post_type;
          }
        }
      }
      return $out;
    }

    function action_admin_head() {
      ?>
      <script>
        window.dzs_term_order = {
          thumbdisplay: '<?php echo json_encode($this->db_mainoptions['thumbdisplay']); ?>'
          , cat_sort: '<?php echo json_encode($this->db_mainoptions['cat_sort']); ?>'
        }
      </script><?php


      if ($this->page_is_reorder) {
        ?>
        <style>
          .wp-list-table:not(.wp-list-meta-sort) {
            display: none;

            /*opacity: 0;*/
          }

          .tablenav {
            display: none;
          }

          .wp-list-table:not(.wp-list-meta-sort) + .tablenav {
            display: none;

          }

          .dzs-sort-portfolio {
            opacity: 0;
            transition-property: opacity;
            transition-duration: 0.3s;
            transition-timing-function: ease-in;
          }

          .dzs-sort-portfolio.loaded {
            opacity: 1;
          }
        </style>
        <?php
      }
    }


    function print_parent_terms() {

      // -- top category display


      $tax = '';
      $tax_init = '';
      $post_type_init = '';


      foreach ($this->db_mainoptions['catdisplay_top'] as $lab => $cdt) {

        if ($_GET['post_type'] == $lab) {

          $post_type_init = $lab;
          $tax = $cdt;
          break;
        }
      }

      if ($tax) {

        $tax_init = $tax;
        $archive_cats = get_terms($tax, 'orderby=count&hide_empty=0&parent=0');


        $tax = '';


        foreach ($this->db_mainoptions['catdisplay_top'] as $lab => $cst) {

          if (isset($_GET[$cst]) && $_GET[$cst]) {


            $tax = $cst;
            break;

          }
        }


        $curr_term = null;


        if ($tax) {

          $curr_term = get_term_by('slug', $_GET[$tax], $tax);
        }


        ?>
        <ul class="parent-cats-shower">
          <li class="<?php

          if (!$curr_term) {

            echo ' active';
          }


          ?>"><a class="active"
                 href="edit.php?<?php echo dzs_sanitize_attr($tax_init); ?>=<?php echo "all"; ?>&post_type=<?php echo $post_type_init; ?>&zoom-term-reorder=on"><?php echo esc_html__("All"); ?></a>
          </li>
          <?php foreach ($archive_cats as $cat) {


            ?>
          <li class="<?php


          if ($curr_term) {

            if ($curr_term->term_id == $cat->term_id) {

              echo ' active';
            }
          } else {
          }


          ?>"><a
              href="edit.php?<?php echo $tax_init; ?>=<?php echo $cat->slug; ?>&post_type=<?php echo $post_type_init; ?>&zoom-term-reorder=on"><?php

              echo $cat->name;
              ?></a></li><?php
          } ?>
        </ul>
        <?php
      }
    }


    function term_reorder() {


      foreach ($this->db_mainoptions['cat_sort'] as $lab => $cdt) {

      }

      if (isset($_GET['zoom-term-reorder']) && $_GET['zoom-term-reorder'] == 'on') {


        $tax = 'dzsvcs_port_items_cat';


        foreach ($this->db_mainoptions['cat_sort'] as $lab => $cst) {

          if (isset($_GET[$cst]) && $_GET[$cst]) {


            $tax = $cst;
            break;

          }
        }


        $this->page_is_reorder = true;
        $this->needs_js_reorder = false;

        $post_type = 'post';

        if (isset($_GET['post_type'])) {

        }

        $post_types = $this->getPostTypesByTaxonomy($tax);

        if (isset($post_types[0])) {
          $post_type = $post_types[0];
        }


        $slug_tax = 'all';


        if (isset($_GET[$tax]) && $_GET[$tax]) {
          $slug_tax = sanitize_text_field($_GET[$tax]);
        }

        $term = get_term_by('slug', $slug_tax, $tax);


        $meta_key = '';
        $posts = array();


        if ($term || $slug_tax == 'all') {

          $term_id = '';

          if ($term) {

            $term_id = $term->term_id;
          }
          $meta_key = 'dzs_meta_order_for_term_' . $term_id;

          if ($slug_tax == 'all') {
            $meta_key = 'dzs_meta_order_for_term';
          }


          $args = array(
            'post_type' => $post_type,
            'numberposts' => -1,
            'orderby' => array('meta_key' => 'DESC', 'meta_value_num' => 'DESC', 'date' => 'DESC'),
            'tax_query' => array(
              array(
                'taxonomy' => $tax,
                'field' => 'id',
                'terms' => $term_id, // -- Where term_id of Term 1 is "1".
                'include_children' => true
              )
            ),


            'meta_query' => array(
              'relation' => 'OR',
              array(
                'key' => $meta_key,
                'compare' => 'EXISTS'
              ),
              array(
                'key' => $meta_key,
                'compare' => 'NOT EXISTS'
              )
            ),
          );


          if ($slug_tax == 'all') {
            $args['tax_query'] = array();
          }



          $posts = get_posts($args);


        }





        ?>

        <div class="dzs-sort-portfolio" data-meta-key="<?php echo $meta_key; ?>">
          <table class="wp-list-table wp-list-meta-sort widefat fixed striped posts">
            <thead>
            <tr>

              <th scope="col" class="  sort-col sortable desc">
                <span>&nbsp;</span>

              </th>
              <th scope="col" id="title" class="manage-column column-title column-primary sortable desc">
                <span>&nbsp;&nbsp;<?php echo esc_html__("Title"); ?></span>

              </th>
              <th scope="col" class="manage-column column-order"><?php echo esc_html__("Order"); ?></th>
              <th scope="col" id="author" class="manage-column column-author"><?php echo esc_html__("Author"); ?></th>

              <th scope="col" id="date" class="manage-column column-date sortable asc">
                <span>Date</span>
              </th>

            </tr>
            </thead>

            <tbody id="the-list" class="the-sortable-list">


            <?php


            $i = 0;

            foreach ($posts as $po) {

              $po_id = $po->ID;

              $meta_order_new_set = false;


              $meta_order = get_post_meta($po_id, $meta_key, true);

              if ($meta_order) {

                array_push($this->meta_order_arr, $meta_order);
              } else {
                $meta_order = count($posts) - $i;

                while (in_array($meta_order, $this->meta_order_arr)) {

                  $meta_order++;
                }
                array_push($this->meta_order_arr, $meta_order);
                $this->needs_js_reorder = true;
                $meta_order_new_set = true;
              }


              ?>
            <tr id="<?php echo $po->ID; ?>"
                class="iedit author-self level-0 post-<?php echo $po->ID; ?> type-dzsvcs_port_items status-publish has-post-thumbnail hentry dzsvcs_port_items_cat-gallery-masonry meta-order-tr <?php
                if ($meta_order_new_set) {
                  echo ' meta-order-new-set';
                }

                ?>" data-post-id="<?php echo $po_id; ?>" data-meta-order="<?php echo $meta_order; ?>">

              <td class="sort-col">
                <div class="sort-controls">
                  <i class="fa fa-arrows"></i>
                  <div class="sort-up-down-conglomerate">
                    <i class="fa fa-caret-up meta-sort-up"></i>
                    <i class="fa fa-caret-down meta-sort-down"></i>
                  </div>
                </div>
              </td>


              <?php

              $thumb_src = get_the_post_thumbnail_url($po->ID, array(100, 100));
              $thumb = '';


              if ($thumb_src) {

              } else {
                if (get_post_meta($po->ID, 'dzsvp_thumb', true)) {
                  $thumb_src = get_post_meta($po->ID, 'dzsvp_thumb', true);
                } else {
                  if (get_post_meta($po->ID, 'dzsvg_meta_thumb', true)) {
                    $thumb_src = get_post_meta($po->ID, 'dzsvg_meta_thumb', true);
                  }
                }
              }

              if ($thumb_src) {

                $thumb = '<div style="width: 50px; height: 50px; display:inline-block; background-size:cover; background-position: center center; background-image:url(' . $thumb_src . '); "  class="attachment-100x100 size-100x100 wp-post-image" alt="" ></div>';;
              }



              ?>

              <td class="title column-title has-row-actions column-primary page-title <?php

              if ($thumb) {
                echo ' has-image';
              }

              ?>" data-colname="Title">


                <?php echo $thumb; ?>


                <strong><a class="row-title" href="post.php?post=<?php echo $po->ID; ?>&action=edit"
                           aria-label="“<?php echo $po->post_title; ?>” (Edit)">


                    <div class="post-label">

                      <?php echo $po->post_title; ?>
                    </div>


                  </a></strong>

                <?php
                $link_edit = admin_url('post.php?post=' . $po->ID . '&action=edit');
                ?>
                <div class="row-actions"><span class="edit"><a href="<?php echo $link_edit; ?>"  aria-label="Edit “<?php echo $po->post_title; ?>”"><?php $esc_html___ = esc_html__( "Edit", DZSVG_ID );
			                echo $esc_html___; ?></a> </span>
                </div>
                <button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span>
                </button>
              </td>


              <td class="author column-order post-term-order " data-colname="Order">
                <?php
                echo $meta_order;

                ?>
              </td>
              <td class="author column-author" data-colname="Author"><a
                  href="edit.php?post_type=dzsvcs_port_items&amp;author=1">admin</a></td>


              <td class="date column-date" data-colname="Date">Published<br><abbr
                  title=""><?php echo get_the_date('F j, Y', $po->ID); ?></abbr></td>    </tr><?php


              ++$i;
            }



            ?>

            </tbody>

            <tfoot>
            <tr>

              <th scope="col" class="manage-column column-order"></th>
              <th scope="col"
                  class="manage-column column-title column-primary sortable desc"> <?php echo esc_html__("Title"); ?> </th>
              <th scope="col" class="manage-column column-order"><?php echo esc_html__("Order"); ?></th>
              <th scope="col" class="manage-column column-author"><?php echo esc_html__("Author"); ?></th>
              <?php
              $link_edit = admin_url('edit.php?dzsvcs_port_items_cat=gallery-masonry&amp;post_type=dzsvcs_port_items&amp;zoom-reorder=on&amp;orderby=date&amp;order=desc');
              ?>
              <th scope="col" class="manage-column column-date sortable asc"><a href="<?php echo $link_edit; ?>"><span>Date</span><span
                    class="sorting-indicator"></span></a></th>
            </tr>
            </tfoot>

          </table>
        </div>


        <?php
        wp_enqueue_style('fontawesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');

        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-sortable');

        if ($this->needs_js_reorder) {
          echo '<script>window.needs_js_reorder = true;</script>';
        }
      }
    }


    /**
     * parsing post data
     */
    function post_get_all_post_thumb_url() {

      $auxarray = array();
      $arr = json_decode(stripslashes($_POST['postdata']), true);




      $final_arr = array();

      foreach ($arr as $po) {

        $aux = array(
          'id' => $po,
          'thumb' => get_the_post_thumbnail_url($po, array(100, 100)),
        );


        if ($aux['thumb']) {

        } else {
          if (get_post_meta($po, 'dzsvp_thumb', true)) {
            $aux['thumb'] = get_post_meta($po, 'dzsvp_thumb', true);
          } else {
            if (get_post_meta($po, 'dzsvg_meta_thumb', true)) {
              $aux['thumb'] = get_post_meta($po, 'dzsvg_meta_thumb', true);
            }
          }
        }


        array_push($final_arr, $aux);

      }

      echo json_encode($final_arr);
      die();
    }

    function post_dzs_update_term_order() {

      $auxarray = array();
      //parsing post data
      $arr = json_decode(stripslashes($_POST['postdata']), true);


      print_r($arr);

      foreach ($arr as $po) {

        update_post_meta($po['id'], $_POST['meta_key'], $po['order']);
      }
      die();
    }

  }
}



