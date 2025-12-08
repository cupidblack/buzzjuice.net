<?php

namespace GiveFunds\Email;

use GiveFunds\Repositories\Funds as FundsRepository;
use GiveFunds\Repositories\Revenue as RevenueRepository;
use Exception;

/**
 * Fund email tags
 *
 * @package GiveFunds\Email
 */
class Tags {

	/**
	 * @var FundsRepository
	 */
	private $fundsRepository;

	/**
	 * @var RevenueRepository
	 */
	private $revenueRepository;

	/**
	 * FundOptions constructor.
	 *
	 * @param FundsRepository $fundsRepository
	 * @param RevenueRepository $revenueRepository
	 */
	public function __construct(
		FundsRepository $fundsRepository,
		RevenueRepository $revenueRepository
	) {
		$this->fundsRepository   = $fundsRepository;
		$this->revenueRepository = $revenueRepository;
	}

	/**
	 * Register email tags.
	 *
	 * @param string $tags
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function registerEmailTags( $tags ) {
		$fundTags = [
			[
				'tag'     => 'fund',
				'desc'    => esc_html__( 'The fund name which this donation is designated to', 'give-funds' ),
				'func'    => [ $this, 'getFundPropByTag' ],
				'context' => 'donation',
			],
			[
				'tag'     => 'fund_description',
				'desc'    => esc_html__( 'The fund description which this donation is designated to', 'give-funds' ),
				'func'    => [ $this, 'getFundPropByTag' ],
				'context' => 'donation',
			],
		];

		return array_merge( $tags, $fundTags );
	}

	/**
	 * Get fund property by tag
	 *
	 * @param array $args
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getFundPropByTag( $args, $tag ) {
		$content = '';
		// Bailout.
		if ( ! give_check_variable( $args, 'isset', 0, 'payment_id' ) ) {
			return $content;
		}

		try {
			$fundId = $this->revenueRepository->getDonationFundId( $args['payment_id'] );

			$fund = ( $fundId )
				? $this->fundsRepository->getFund( $fundId )
				: $this->fundsRepository->getDefaultFund();

			// Bailout if fund not exists
			if ( ! $fund ) {
				return $content;
			}

			switch ( $tag ) {
				case 'fund_description':
					$content = $fund->getDescription();
					break;
				case 'fund':
					$content = $fund->getTitle();
					break;
			}
		} catch ( Exception $e ) {
			error_log(
				sprintf( 'There was an error within the Funds add-on while trying to process email tags. %s', $e->getMessage() )
			);
		}

		return $content;
	}

	/**
	 * Handle fund tags in the email preview
	 *
	 * @param string $template
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function handlePreviewEmailTags( $template ) {
		// Get default fund
		$fund = $this->fundsRepository->getDefaultFund();

		if ( ! $fund ) {
			return $template;
		}

		// Replace tags
		return str_replace(
			[ '{fund}', '{fund_description}' ],
			[ $fund->getTitle(), $fund->getDescription() ],
			$template
		);
	}
}
