<?php
/**
 * Execute SCSS snippets and return their output.
 *
 * @package wpcode
 */

/**
 * WPCode_Snippet_Execute_Text class.
 */
class WPCode_Snippet_Execute_SCSS extends WPCode_Snippet_Execute_Type {

	/**
	 * The snippet type, scss for this one.
	 *
	 * @var string
	 */
	public $type = 'scss';

	/**
	 * Grab snippet code and return its output.
	 *
	 * @return string
	 */
	protected function prepare_snippet_output() {
		$code = $this->get_snippet_compiled_code();

		if ( ! empty( $code ) ) {
			// Wrap our code in a style tag.
			$code = '<style class="wpcode-scss-compiled-snippet">' . $code . '</style>';
		}

		return $code;
	}
}
