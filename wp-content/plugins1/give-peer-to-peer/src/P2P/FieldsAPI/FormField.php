<?php

namespace GiveP2P\P2P\FieldsAPI;

use Give\Framework\FieldsAPI\Concerns\HasLabel;
use Give\Framework\FieldsAPI\Field;
use Give\Framework\FieldsAPI\Concerns\HasHelpText;
use Give\Framework\FieldsAPI\Concerns\HasPlaceholder;
use GiveP2P\P2P\FieldsAPI\Traits\Hooks;
use GiveP2P\P2P\FieldsAPI\Traits\Attributes;
use GiveP2P\P2P\FieldsAPI\Traits\Validators;
use GiveP2P\P2P\FieldsAPI\Traits\Visibility;
use GiveP2P\P2P\FieldsAPI\Traits\Repeater;

/**
 * Class FormField
 * Extends the base Field class of Fields API to add additional options to the P2P Field
 *
 * @package GiveP2P\P2P\FieldsAPI
 *
 * @since 1.0.0
 */
abstract class FormField extends Field {
	use Hooks;
	use Attributes;
	use HasPlaceholder;
	use Validators;
	use Visibility;
	use HasHelpText;
	use HasLabel;
	use Repeater;
}
