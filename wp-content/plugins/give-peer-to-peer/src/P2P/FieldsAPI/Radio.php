<?php

namespace GiveP2P\P2P\FieldsAPI;

use Give\Framework\FieldsAPI\Concerns\HasOptions;

/**
 * Class Radio
 * @package GiveP2P\P2P\FieldsAPI
 *
 * @since 1.0.0
 */
class Radio extends FormField {
	use HasOptions;

	const TYPE = 'radio';
}
