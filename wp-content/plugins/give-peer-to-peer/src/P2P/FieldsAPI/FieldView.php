<?php

namespace GiveP2P\P2P\FieldsAPI;

use Give\Framework\FieldsAPI\Field;
use Give\Framework\Support\Facades\Facade;
use GiveP2P\P2P\FieldsAPI\Factory\FieldViewFactory;

/**
 * Class FieldView
 * @package GiveP2P\P2P\FieldsAPI
 *
 * @since 1.0.0
 *
 * @method static make( Field $name )
 */
class FieldView extends Facade {
	protected function getFacadeAccessor() {
		return FieldViewFactory::class;
	}
}
