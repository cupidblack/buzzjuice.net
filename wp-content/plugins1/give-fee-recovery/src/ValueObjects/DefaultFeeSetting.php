<?php

namespace GiveFeeRecovery\ValueObjects;

use MyCLabs\Enum\Enum;

/**
 * Class DefaultFeeSettings
 * @package GiveFeeRecovery\ValueObjects
 * @since 1.9.1
 *
 * @method static self percentage()
 * @method static self additionalAmount()
 * @method static self maximumFeeAmount()
 */
class DefaultFeeSetting extends Enum {
	const percentage = 2.90;
	const additionalAmount = 0.30;
	const maximumFeeAmount = 0.00;
}
