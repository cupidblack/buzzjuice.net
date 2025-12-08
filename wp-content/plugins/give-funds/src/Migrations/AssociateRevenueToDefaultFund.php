<?php
namespace GiveFunds\Migrations;

use GiveFunds\Repositories\Funds;
use Give\Framework\Migrations\Contracts\Migration;

/**
 * Class AssociateRevenueToDefaultFund
 * @package GiveFunds\Migrations
 *
 * @since 1.0.2
 */
class AssociateRevenueToDefaultFund extends Migration {

	/**
	 * @var Funds
	 */
	private $fundRepository;

	/**
	 * AssociateRevenueToDefaultFund constructor.
	 *
	 * @param Funds $fundRepository
	 */
	public function __construct( Funds $fundRepository ) {
		$this->fundRepository = $fundRepository;
	}

	/**
	 * @inheritdoc
	 */
	public function run() {
		$this->fundRepository->setNullFundIdsToDefaultFundId();
	}

	/**
	 * @inheritdoc
	 */
	public static function id() {
		return 'set_default_fund_id_in_revenue_table';
	}

	/**
	 * @inheritdoc
	 */
	public static function timestamp() {
		return strtotime( '2020-12-16' );
	}
}
