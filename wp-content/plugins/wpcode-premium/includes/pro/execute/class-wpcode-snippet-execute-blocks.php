<?php
/**
 * Execute blocks snippets and return their output.
 *
 * @package wpcode
 */

/**
 * WPCode_Snippet_Execute_Text class.
 */
class WPCode_Snippet_Execute_Blocks extends WPCode_Snippet_Execute_Type {

	/**
	 * The snippet type, blocks for this one.
	 *
	 * @var string
	 */
	public $type = 'blocks';

	/**
	 * Grab snippet code and return its output.
	 *
	 * @return string
	 */
	protected function prepare_snippet_output() {
		return wpcode()->snippet_block_editor->get_blocks_content( $this->snippet );
	}
}
