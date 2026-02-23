<?php

namespace GiveP2P\P2P\FieldsAPI;

use Give\Framework\FieldsAPI\Concerns\HasOptions;

/**
 * Class Editor
 * @package GiveP2P\P2P\FieldsAPI
 *
 * @since 1.0.0
 */
class Editor extends FormField {

	use HasOptions;

	const TYPE = 'editor';
}
