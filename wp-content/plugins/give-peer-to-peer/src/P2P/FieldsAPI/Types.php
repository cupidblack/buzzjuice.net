<?php

namespace GiveP2P\P2P\FieldsAPI;

use Give\Framework\FieldsAPI\Types as GiveFieldTypes;

/**
 * Class Types
 * @package GiveP2P\P2P\FieldsAPI
 */
class Types extends GiveFieldTypes {
	const COLOR    = Color::TYPE;
	const REPEATER = Repeater::TYPE;
	const EDITOR   = Editor::TYPE;
	const IMAGE    = Image::TYPE;
	const MONEY    = Money::TYPE;
	const VIRTUAL  = Virtual::TYPE;
}
