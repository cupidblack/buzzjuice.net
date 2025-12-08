<?php

include_once 'configs/default-configs.php';
include_once 'inc/layout-builder-helpers.php';

class DzsLayoutBuilder {

  public $layouts = array();
  /**
   * @var DZSVideoGallery
   */
  private $mainClass;
	/**
	 * @var mixed
	 */
	private $configOptions;
	private $initOptions;

	/**
   * <p>universal .. just plugin options</p>
   * will select a layout by slug
   * @param DZSVideoGallery $mainClass parent class
   * @param array $pargs array(
   * 'optionName' => 'dzs_layout_builder', // -- option name in the database
   * 'pageName' => 'layout_builder',
   * 'i18n_id' => 'layout_builder',
   * 'parent_url' => admin_url(),
   * )
   */
  function __construct($mainClass, $pargs = array()) {
    $this->mainClass = $mainClass;
    $this->initOptions = array(
      'ajaxActionName' => 'dzs_layout_builder_save', // -- option name in the database
      'optionName' => 'dzs_layout_builder', // -- option name in the database
      'pageName' => 'layout_builder',
      'settingsArray' => array(),
      'i18n_id' => 'layout_builder',
      'parent_url' => admin_url(),
    );

    $this->initOptions = array_merge($this->initOptions, $pargs);
    $this->configOptions = $this->initOptions['settingsArray'];

    if (!defined('DZSLB_I18N_ID')) {
      define('DZSLB_I18N_ID', $this->initOptions['i18n_id']);
    }


    add_action('admin_init', array($this, 'handle_admin_init'));
    add_action('wp_ajax_' . $this->initOptions['ajaxActionName'], array($this, 'ajax_save_layout'));
    add_action('admin_footer', array($this, 'action_admin_footer'));
  }

  function handle_admin_init() {

    $this->layouts = get_option($this->initOptions['optionName'] . '_layouts_summary');
    if (!is_array($this->layouts)) {
      $this->layouts = array();
    }
  }

  function ajax_save_layout() {

    $layoutData = json_decode(stripslashes($_POST['postdata']), true);
    update_option($this->initOptions['optionName'] . '_layout_' . $_POST['layout_builder_id'], $layoutData);
    $this->layouts[$_POST['layout_builder_id']] = array(
      'name' => $layoutData['mainsettings']['config_name']
    );
    update_option($this->initOptions['optionName'] . '_layouts_summary', $this->layouts);
    die();
  }

  public function action_admin_footer() {

    $structure = include('configs/structure.php');

    $builderLayerFields = include('configs/structure-layer-fields.php');

    $structure['builder_item'] = str_replace('{{builderLayerFields}}', dzs_layout_builder_mapBuilderLayerFieldsToHtml($builderLayerFields, 'main'), $structure['builder_item']);

    ?>
    <script>window.layout_builder_settings = <?php
      echo json_encode(array(
        'ajaxActionName' => $this->initOptions['ajaxActionName']
      ));
      ?>;
      var layoutBuilderStructure = <?php echo json_encode($structure); ?>;</script><?php
  }

  public function generate_link_for_add_new_layout() {
    $idmax = 0;

    foreach ($this->layouts as $layout) {
      if (intval($layout['id']) > $idmax) {
        $idmax = $layout['id'];
      }
    }
    return add_query_arg('layout_builder_id', $idmax + 1, admin_url('admin.php?page=' . $this->initOptions['pageName']));
  }

  /**
   * @param $layoutId
   */
  public function adminpage_generate_item_selector__single($layoutId) {

    $layoutName = 'new-layout';

    $layoutData = get_option($this->initOptions['optionName'] . '_layout_' . $layoutId);

    if (!$layoutData) {
      $layoutData = array(
        'mainsettings' => array(
          'config_name' => ''
        )
      );
    }
    ?>
    <div class="wrap">
      <h1><?php echo esc_html__('Designing', DZSLB_I18N_ID); ?> <strong
          class="layout-name"><?php echo $layoutName; ?></strong></h1>

      <textarea id="lb-output" style="width: 100%;;" rows="5"><?= json_encode($layoutData); ?></textarea>
      <form class="layout--main-con">

        <div class="dzs--input-con">
          <?php
          $lab = 'config_name';
          ?>
          <input name="<?= $lab ?>>" type="input" placeholder="<?php echo esc_html__('Layout name', DZSLB_I18N_ID); ?>"
                 value='<?= $layoutData['mainsettings'][$lab] ?>'
                 data-json-structure='<?php
                 echo json_encode(array(
                   'mainsettings' => array(
                     $lab => 'the-value',
                   ),
                 ));
                 ?>'/>
          <span class="dzs--input--icon dashicons dashicons-edit"></span>
        </div>

        <div class="layout-builder--mainbuilder-con">

          <div class="layout-builder--builder-and-preview--con">
            <div class="layout-builder--builder-con">

              <h4 class="layout-builder--title"><?php echo esc_html__('Builder', DZSLB_I18N_ID); ?></h4>

              <div class="dd" id="layout-builder--layers-con" style=" ">
                <ol class="dd-list layout-builder--layers">
                </ol>
              </div>
              <div class="add-builder-elements-con">
                <span class="layout-builder--add-builder-btn add-element"><i
                    class="fa fa-plus"></i> <?php echo esc_html__("Add Element", DZSLB_I18N_ID);; ?></span>
                <span class="layout-builder--add-builder-btn add-container"><i
                    class="fa fa-circle-o-notch"></i> <?php echo esc_html__("Add Container", DZSLB_I18N_ID); ?></span>
              </div>
            </div>
            <div class="layout-builder--preview-con">
              test
            </div>
            <aside class="layout-builder--editor-con">
              <div class="layout-builder--editor--breadcrumps">
                <span
                  class="layout-builder--editor--breadcrumps--layer"><?= esc_html__("General", DZSLB_I18N_ID) ?></span>
              </div>

              <div class="layout-builder--editor--settings-general">
                <?php
                // todo: replace with general function

                foreach ($this->configOptions as $configLab => $configOption) {

                  $configOption['extraAttr'] = ' data-json-structure=\'';

                  $configOption['extraAttr'] .= json_encode(array(
                    'mainsettings' => array(
                      $configLab => 'the-value',
                    ),
                  ));
                  $configOption['extraAttr'] .= '\'';

                  echo ClassDzsvgHelpers::generateOptionsFromConfigForMainOptions(array(
                    $configLab => $configOption
                  ), 'main', $layoutData['mainsettings']);
                }

                ?>
              </div>
            </aside>
          </div>
        </div>
        <div class="save-btn-con">
          <button
            class="button-primary btn-save-layout">
            &#10003; <?php echo esc_html__('Save layout', DZSLB_I18N_ID); ?></button>
        </div>
      </form>


    </div>
    <?php


    wp_enqueue_style('fontawesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
  }

  public function adminpage_generate_item_selector__summary() {
    ?>
    <div class="wrap">
      <h1><?php echo esc_html__('Layout builder', DZSLB_I18N_ID); ?></h1>
      <div id="zfolio-preview" class="zfolio skin-forwall auto-init delay-effects dzs-layout--3-cols" data-margin="10"
           data-options='{
"design_item_thumb_height":"1"
,"item_extra_class":""
,"settings_useLinksForCategories":"off"
,"selector_con_skin":"none"
,"settings_useLinksForCategories_enableHistoryApi":"on"
,"excerpt_con_transition": "wipe"
}'>


        <div class="items ">
          <div class="zfolio-item zfolio-item-id-7 " data-thumbnail="img/01.jpg">


            <div href="img/01.jpg"
                 class="zfolio-item--inner custom-a " data-biggallery="zfolio0" data-lightbox-title="Default 1"
                 data-type="">
              <div class="zfolio-item--inner--inner">
                <div class="zfolio-item--inner--inner--inner">
                  <div class="item-meta">

                    <div class="the-title">tada</div>
                    <div class="the-desc">tada</div>

                  </div><!--end item-meta-->

                </div>
              </div>


            </div><!--end zfolio-item--inner-->
            <div class="the-overlay-anchor">
            </div>

          </div><!-- end zfolio-item-->

        </div>
        <div class="zfolio-preloader-circle-con zfolio-preloader-con">
          <div class="zfolio-preloader-circle"></div>
        </div>
      </div>
      <div class="add-btn-con">
        <a href="<?php echo $this->generate_link_for_add_new_layout(); ?>"
           class="button-primary">+ <?php echo esc_html__('Add layout', DZSLB_I18N_ID); ?></a>
      </div>
    </div>
    <?php


    wp_enqueue_style('dzszfl', $this->initOptions['parent_url'] . 'libs/zfolio/zfolio.css');
    wp_enqueue_script('dzszfl', $this->initOptions['parent_url'] . "libs/zfolio/zfolio.js", array('jquery'));
  }

  public function adminpage_generate_item_selector() {

    if (isset($_GET['layout_builder_id'])) {
      $this->adminpage_generate_item_selector__single($_GET['layout_builder_id']);
    } else {
      $this->adminpage_generate_item_selector__summary();
    }

    wp_enqueue_style('dzs-layout-builder', $this->initOptions['parent_url'] . 'inc/layout-builder/css/layout-builder.css');
    wp_enqueue_script('dzs-layout-builder', $this->initOptions['parent_url'] . 'inc/layout-builder/js/layout-builder.js');
    wp_enqueue_script('jquery-nestable', $this->initOptions['parent_url'] . 'inc/layout-builder/js/jquery.nestable.js');

  }

  public function get_frontend_struct($playlistSettings, $tempSkin = '') {


    if ($playlistSettings['displaymode'] === 'wall') {

      // todo: layout builder
      return DEFAULT_CONFIG_WALL;
    } else {

      if ($tempSkin === 'skin-boxy') {
        return DEFAULT_CONFIG_SKIN_BOXY;
      }

      // -- default
      return DEFAULT_CONFIG_LAYOUT_BUILDER;
    }


  }

  public function get_frontend_css() {
    // todo: alpha
    $fout = '.vg1{
                height: auto;
              }
              .layout-builder--menu-items--layout-custom-customid{
                padding: 10px;
                background-color: #222222;
                width: 30vw;
                max-width: 250px;
              }
              .layout-builder--menu-items--layout-custom-customid .layout-builder--item--11241412321{
              position:relative
              }
              .layout-builder--menu-items--layout-custom-customid .layout-builder--item--type-thumbnail{
                background-size: cover;
                background-position: center center;
                box-shadow:inset 0px 0px 0px 0px #4e5c42;
              }
              .layout-builder--menu-items--layout-custom-customid .layout-builder--item--type-title{
                margin-top: 10px;
                margin-bottom: 10px;
                font-size: 17px
              }
              .layout-builder--item--3314421{
              background-color: #ffffff;
              border-radius: 50%;
              width: 30px;
              height: 30px;
              color: #444;
              opacity:0.3;
              }
              
              .navigationThumb.active .layout-builder--menu-items--layout-custom-customid .layout-builder--item--type-thumbnail, .navigationThumb:hover .layout-builder--menu-items--layout-custom-customid .layout-builder--item--type-thumbnail{
                box-shadow:inset 0px 0px 0px 5px #4e5c42;
              }
              .navigationThumb.active .layout-builder--menu-items--layout-custom-customid .layout-builder--item--3314421, .navigationThumb:hover .layout-builder--menu-items--layout-custom-customid .layout-builder--item--3314421{
                opacity:0.8;
              }
              ';

    return $fout;
  }
}
