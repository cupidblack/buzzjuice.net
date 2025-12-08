<?php

namespace GiveP2P\P2P\Exceptions;

use Give\Framework\Exceptions\Primitives\Exception;

class DonationSourceNotFound extends Exception {
	public function __construct( $donationID, $code = 0, Exception $previous = null ) {
		$message = sprintf(__('Source not found for donation ID %d'), $donationID);
		parent::__construct( $message, $code, $previous );
	}
}
