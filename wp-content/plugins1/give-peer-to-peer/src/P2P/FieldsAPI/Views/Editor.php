<?php

namespace GiveP2P\P2P\FieldsAPI\Views;

use Give\Framework\FieldsAPI\Field;
use GiveP2P\P2P\FieldsAPI\Editor as EditorField;
use GiveP2P\P2P\FieldsAPI\Contracts\FieldViewContract;

/**
 * Class Editor
 * @package GiveP2P\P2P\FieldsAPI\Views
 *
 * @since 1.0.0
 */
class Editor extends FieldViewContract {
	/**
	 * @param Field|EditorField $field
	 *
	 * @return string
	 */
	public function template( Field $field ) {
		ob_start();

		$options = [];

		foreach( $field->getOptions() as $option ) {
			$options[ $option->getValue() ] = $option->getLabel();
		}

		wp_editor(
			$field->getDefaultValue(),
			$field->getName(),
			$options
		);

		return ob_get_clean();
	}
}
