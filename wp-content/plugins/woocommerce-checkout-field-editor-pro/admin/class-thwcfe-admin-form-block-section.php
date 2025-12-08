
<?php
/**
 * The admin section forms functionalities.
 *
 * @link       https://themehigh.com
 * @since      3.6.4
 *
 * @package    woocommerce-checkout-field-editor-pro
 * @subpackage woocommerce-checkout-field-editor-pro/admin
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWCFE_Admin_Form_Block_Section')):

class THWCFE_Admin_Form_Block_Section extends THWCFE_Admin_Form{
	private $section_props = array();

	public function __construct() {
		$this->section_props = $this->get_section_form_props();
	}

	public function get_section_form_props(){
		
		$html_text_tags = $this->get_html_text_tags();

		$suffix_types = array(
			'number' => 'Number',
			'alphabet' => 'Alphabet',
			'none' => 'None',
		);

		$suffix_types_1 = array(
			'number' => 'Number',
			'alphabet' => 'Alphabet',
		);
		
		return array(
		
			'name' 		 => array('name'=>'name', 'label'=>'Name/ID', 'type'=>'text', 'required'=>1),
			'position' 	 => array('name'=>'position', 'label'=>'Display Position', 'type'=>'hidden', 'note' => 'position can be changed from the Block Editor', 'value' => 'set_from_block' ),
			'cssclass' 	 => array('name'=>'cssclass', 'label'=>'CSS Class', 'type'=>'text'),
			
			'title' 	  => array('name'=>'title', 'label'=>'Title', 'type'=>'text', 'required'=>1 ),
			'title_class' => array('name'=>'title_class', 'label'=>'Title Class', 'type'=>'text'),

			'subtitle'    => array('name'=>'subtitle', 'label'=>'Description', 'type'=>'text'),

			'show_title' => array('name'=>'show_title', 'label'=>'Show section title in checkout page.', 'type'=>'checkbox', 'value'=>'yes', 'checked'=>1),
			
		);
	}

	public function output_section_forms(){
		?>
        <div id="thwcfe_section_form_pp" class="thpladmin-modal-mask thadmin-block-form">
          <?php $this->output_popup_form_section(); ?>
        </div>
        <?php
	}

	/*****************************************/
	/********** POPUP FORM WIZARD ************/
	/*****************************************/

	private function output_popup_form_section(){
		?>
		<div class="thpladmin-modal">
			<div class="modal-container">
				<span class="modal-close" onclick="thwcfeCloseModal(this)">×</span>
				<div class="modal-content">
					<div class="modal-body">
						<div class="form-wizard wizard">
							<aside>
								<side-title class="wizard-title">Save Section</side-title>
								<ul class="pp_nav_links">
									<li class="text-primary active first" data-index="0">
										<i class="dashicons dashicons-admin-generic text-primary"></i>Basic Info
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>
									<li class="text-primary" data-index="1">
										<i class="dashicons dashicons-art text-primary"></i>Display Styles
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>
									<li class="text-primary" data-index="2">
										<i class="dashicons dashicons-filter text-primary"></i>Display Rules
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>
								</ul>
							</aside>
							<main class="form-container main-full">
								<form method="post" id="thwcfe_section_form" class="thwcfe-block-section" action="">
									<input type="hidden" name="s_action" value="" />
									<input type="hidden" name="s_name" value="" />
									<input type="hidden" name="s_name_copy" value="" />
									<input type="hidden" name="i_position_old" value="" />
									<input type="hidden" name="i_rules" value="" />
									<input type="hidden" name="i_rules_ajax" value="" />
									<input type="hidden" name="i_repeat_rules" value="" />

									<div class="data-panel data_panel_0">
										<?php $this->render_form_tab_general_info(); ?>
									</div>
									<div class="data-panel data_panel_1">
										<?php $this->render_form_tab_display_details(); ?>
									</div>
									<div class="data-panel data_panel_2">
										<?php $this->render_form_tab_display_rules(); ?>
									</div>
								</form>
							</main>
							<footer>
								<span class="Loader"></span>
								<div class="btn-toolbar">
									<button class="save-btn pull-right btn btn-primary" onclick="thwcfeSaveSection(this)">
										<span>Save & Close</span>
									</button>
									<button class="next-btn pull-right btn btn-primary-alt" onclick="thwcfeWizardNext(this)">
										<span>Next</span><i class="i i-plus"></i>
									</button>
									<button class="prev-btn pull-right btn btn-primary-alt" onclick="thwcfeWizardPrevious(this)">
										<span>Back</span><i class="i i-plus"></i>
									</button>
								</div>
							</footer>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/*----- TAB - General Info -----*/
	private function render_form_tab_general_info(){
		$this->render_form_tab_main_title('Basic Details');

		?>
		<div style="display: inherit;" class="data-panel-content">
			<div class="err_msgs"></div>
			<table class="thwcfe_pp_table">
				<?php
				$this->render_form_elm_row($this->section_props['name']);
				$this->render_form_elm_row($this->section_props['title']);
				$this->render_form_elm_row($this->section_props['subtitle']);
				$this->render_form_elm_row($this->section_props['position']);
				$this->render_form_elm_row_cb($this->section_props['show_title']);
				//$this->render_form_elm_row($this->section_props['title_cell_with']);
				//$this->render_form_elm_row($this->section_props['field_cell_with']);
				//$this->render_form_elm_row_cb($this->section_props['show_title_my_account']);			
				?>
			</table>
		</div>
		<?php
	}

	/*----- TAB - Display Details -----*/
	private function render_form_tab_display_details(){
		$this->render_form_tab_main_title('Display Settings');

		?>
		<div style="display: inherit;" class="data-panel-content">
			<table class="thwcfe_pp_table">
				<?php
				$this->render_form_elm_row($this->section_props['cssclass']);
				//$this->render_form_elm_row($this->section_props['title_class']);
				?>
			</table>
		</div>
		<?php
	}

	/*----- TAB - Display Rules -----*/
	private function render_form_tab_display_rules(){
		$this->render_form_tab_main_title('Display Rules');
		$this->render_form_tab_sub_title('Display Rule Cart');

		?>
		<div style="display: inherit;" class="data-panel-content">
			<table class="thwcfe_pp_table thwcfe-display-rules">
				<?php
				$this->render_form_fragment_rules('section'); 
				$this->render_form_fragment_rules_ajax('section', 'block');
				?>
			</table>
		</div>
		<?php
	}
	
}

endif;