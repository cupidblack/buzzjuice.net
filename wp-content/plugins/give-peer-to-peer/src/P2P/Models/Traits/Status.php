<?php
namespace GiveP2P\P2P\Models\Traits;

use GiveP2P\P2P\ValueObjects\Status as ModelStatus;

/**
 * Trait Status
 * @package GiveP2P\P2P\Models\Traits
 *
 * @since 1.0.0
 */
trait Status {
	/**
	 * @var string
	 */
	protected $status;

	/**
	 * @return bool
	 */
	public function hasStatus( $status ) {
		return $status === $this->status;
	}

	/**
	 * @return bool
	 */
	public function hasApprovalStatus() {
		return ModelStatus::ACTIVE === $this->status;
	}
}
