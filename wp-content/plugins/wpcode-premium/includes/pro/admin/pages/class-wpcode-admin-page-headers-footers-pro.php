<?php
/**
 * Headers & Footers admin page.
 *
 * @package WPCode
 */

/**
 * Class for the headers & footers admin page.
 */
class WPCode_Admin_Page_Headers_Footers_Pro extends WPCode_Admin_Page_Headers_Footers {
	use WPCode_Revisions_Display;

	/**
	 * The page slug to be used when adding the submenu.
	 *
	 * @var string
	 */
	public $snippet_id = 0;

	/**
	 * Output the revisions box.
	 *
	 * @return void
	 */
	public function revisions_box() {
		$html = sprintf(
			'<p>%s</p><hr />',
			esc_html__( 'As you make changes to your code and save, you will get a list of previous versions with all the changes made in each revision. You can compare revisions to the current version or see changes as they have been saved by going through each revision. Any of the revisions can then be restored as needed.', 'wpcode-premium' )
		);

		$html .= $this->code_revisions_list();
		$this->metabox(
			__( 'Code Revisions', 'wpcode-premium' ),
			$html,
			__( 'Easily switch back to a previous version of your global scripts.', 'wpcode-premium' )
		);
	}
}
