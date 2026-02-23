<?php

namespace GiveP2P\P2P\ValueObjects;


/**
 * Class Enum
 * @package GiveP2P\P2P\ValueObjects
 *
 * @since 1.0.0
 */
class Status extends Enum {
	const ACTIVE  = 'active';
	const INACTIVE  = 'inactive';
	const DRAFT = 'draft';
	const PENDING = 'pending';
}
