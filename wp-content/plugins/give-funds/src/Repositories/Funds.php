<?php

namespace GiveFunds\Repositories;

use GiveFunds\Models\Fund;
use GiveFunds\Factories\Funds as FundsFactory;
use RuntimeException;

class Funds {
	/**
	 * @var Funds
	 */
	private $fundsFactory;

	/**
	 * FundsRepository constructor.
	 *
	 * @param FundsFactory $fundsFactory
	 */
	public function __construct( FundsFactory $fundsFactory ) {
		$this->fundsFactory = $fundsFactory;
	}

	/**
	 * Get all funds
	 *
	 * @since 1.0.0
	 *
	 * @return array of FundEntity objects
	 */
	public function getFunds() {
		global $wpdb;

		$funds = [];

		$result = $wpdb->get_results( "SELECT * FROM {$wpdb->give_funds}" );

		if ( $result ) {
			foreach ( $result as $fund ) {
				$funds[] = $this->fundsFactory->make(
					$fund->id,
					$fund->title,
					$fund->description,
					$fund->author_id,
					$fund->is_default
				);
			}
		}

		return $funds;
	}

	/**
	 * Get Funds count.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function getFundsCount() {
		global $wpdb;

		$result = $wpdb->get_var( "SELECT count(id) FROM {$wpdb->give_funds}" );

		return (int) $result;
	}

	/**
	 * Get fund by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $fundId
	 *
	 * @return null|Fund
	 */
	public function getFund( $fundId ) {
		global $wpdb;

		$fund = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$wpdb->give_funds} WHERE id = %d", $fundId )
		);

		if ( ! $fund ) {
			return null;
		}

		return $this->fundsFactory->make(
			$fund->id,
			$fund->title,
			$fund->description,
			$fund->author_id,
			$fund->is_default,
			$this->getFundAssociatedFormIds( $fund->id )
		);
	}

	/**
	 * Get default fund.
	 *
	 * @return Fund
	 * @throws RuntimeException
	 * @since 1.0.0
	 */
	public function getDefaultFund() {
		global $wpdb;

		$fund = $wpdb->get_row( "SELECT * FROM {$wpdb->give_funds} WHERE is_default = 1" );

		if ( ! $fund ) {
			throw new RuntimeException( 'Could not find a default fund' );
		}

		return $this->fundsFactory->make(
			$fund->id,
			$fund->title,
			$fund->description,
			$fund->author_id,
			$fund->is_default
		);
	}


	/**
	 * Get default fund.
	 *
	 * @return int
	 * @throws RuntimeException
	 * @since 1.0.0
	 */
	public function getDefaultFundId() {
		global $wpdb;

		$fund = $wpdb->get_row( "SELECT id FROM {$wpdb->give_funds} WHERE is_default = 1" );

		if ( ! $fund ) {
			throw new RuntimeException( 'Could not find a default fund' );
		}

		return (int) $fund->id;
	}


	/**
	 * Get fund associated forms
	 *
	 * @since 1.0.0
	 *
	 * @param int $fundId
	 *
	 * @return array of associated form IDs
	 */
	public function getFundAssociatedFormIds( $fundId ) {
		global $wpdb;

		$ids = [];

		$result = $wpdb->get_results(
			$wpdb->prepare( "SELECT form_id FROM {$wpdb->give_fund_form_relationship} WHERE fund_id = %d", $fundId )
		);

		foreach ( $result as $relation ) {
			$ids[] = (int) $relation->form_id;
		}

		return $ids;
	}

	/**
	 * Get form associated funds
	 *
	 * @since 1.0.0
	 *
	 * @param int $formId
	 *
	 * @return array of Fund objects
	 */
	public function getFormAssociatedFunds( $formId ) {
		global $wpdb;

		$funds = [];

		$result = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT funds_table.*
				FROM {$wpdb->give_funds} funds_table
				INNER JOIN {$wpdb->give_fund_form_relationship} relation_table
				ON funds_table.id = relation_table.fund_id
				WHERE relation_table.form_id = %d
                ORDER BY relation_table.position ASC
				",
				$formId
			)
		);

		foreach ( $result as $fund ) {
			$funds[] = $this->fundsFactory->make(
				$fund->id,
				$fund->title,
				$fund->description,
				$fund->author_id,
				$fund->is_default
			);
		}

		return $funds;
	}

	/**
	 * Delete fund by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id
	 *
	 * @return void
	 */
	public function deleteFund( $id ) {
		global $wpdb;

		// Start DB transaction
		$wpdb->query( 'START TRANSACTION' );

		$fund = $this->getFund( $id );

		/**
		 * We cannot simply delete the fund
		 * First we have to check if fund is NOT the default fund
		 * If is not, then we have to reassign his revenue to the default fund
		 */
		if ( $fund && ! $fund->isDefault() ) {
			$revenueRepository = give( Revenue::class );
			$revenueRepository->assignRevenue( $this->getDefaultFundId(), $id );
		}

		// Delete fund from relation table
		$deleteRelation = $wpdb->delete(
			$wpdb->give_fund_form_relationship,
			[
				'fund_id' => $id,
			]
		);

		// Delete fund from funds table
		$deleteFund = $wpdb->delete(
			$wpdb->give_funds,
			[
				'id'         => $id,
				'is_default' => 0,
			]
		);

		if (
			false === $deleteRelation
			|| false === $deleteFund
		) {
			$wpdb->query( 'ROLLBACK' );
			// Bail out.
			throw new RuntimeException( 'Something went wrong' );
		}

		$wpdb->query( 'COMMIT' );
	}

	/**
	 * Associate fund with forms.
	 *
	 * @since 1.0.0
	 *
	 * @param array $forms
	 *
	 * @param int   $fundId
	 *
	 * @return void
	 */
	public function associateFundWithForms( $fundId, $forms ) {
		global $wpdb;

		// Delete old relations
		$wpdb->delete( $wpdb->give_fund_form_relationship, [ 'fund_id' => $fundId ], [ '%d' ] );

		// Insert new relationships
		foreach ( $forms as $formId ) {
			$wpdb->insert(
				$wpdb->give_fund_form_relationship,
				[
					'fund_id' => $fundId,
					'form_id' => $formId,
				]
			);
		}
	}


	/**
	 * Associate form with funds
	 *
	 * @since 1.0.0
	 *
	 * @param array $funds
	 *
	 * @param int   $formId
	 *
	 * @return void
	 */
	public function associateFormWithFunds( $formId, $funds ) {
		global $wpdb;

		// Delete old relations
		$wpdb->delete( $wpdb->give_fund_form_relationship, [ 'form_id' => $formId ] );

		// Insert new relations
		foreach ( (array) $funds as $position => $fundId ) {
			$wpdb->insert(
				$wpdb->give_fund_form_relationship,
				[
					'form_id' => $formId,
					'fund_id' => $fundId,
					'position' => $position,
				]
			);
		}
	}

	/**
	 * Save Fund
	 *
	 * @since 1.0.0
	 *
	 * @param Fund $fund
	 *
	 * @return void
	 * @throws RuntimeException if set default query or insert/update fund fails
	 *
	 */
	public function saveFund( Fund $fund ) {
		global $wpdb;

		$currentDate = date( 'Y-m-d H:i:s' );

		$data = [
			'id'         => $fund->getId(),
			'title'       => $fund->getTitle(),
			'description' => $fund->getDescription(),
			'author_id'   => $fund->getAuthorId(),
		];

		// Start DB transaction
		$wpdb->query( 'START TRANSACTION' );

		if ( empty( $fund->getId() ) ) {
			$data['date_created'] = $data['date_modified'] = $currentDate;
			// Insert fund.
			$fundQuery = $wpdb->insert( $wpdb->give_funds, $data );
			// Get last insert ID. wpdb::insert doesn't provide that
			$fundId = $wpdb->insert_id;
		} else { // Update existing fund
			$fundId = $fund->getId();
			$data['date_modified'] = $currentDate;
			// Update
			$fundQuery = $wpdb->update( $wpdb->give_funds, $data, [ 'id' => $fund->getId() ] );
		}

		// Rollback if one of the queries returns false
		if ( false === $fundQuery ) {
			$wpdb->query( 'ROLLBACK' );
			// Bail out.
			throw new RuntimeException( 'Something went wrong' );
		}

		$wpdb->query( 'COMMIT' );
	}

	/**
	 * Return whether or not fund exist.
	 *
	 * @since 1.0.0
	 *
	 * @param int $fundId
	 *
	 * @return bool
	 */
	public function isFundExist( $fundId ) {
		global $wpdb;

		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->give_funds} WHERE id = %d",
				$fundId
			)
		);
	}

	/**
	 * Set all NULL fund ids in revenue table to default fund id.
	 *
	 * @since 2.9.0
	 */
	public function setNullFundIdsToDefaultFundId() {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'give_revenue',
			[ 'fund_id' => $this->getDefaultFundId() ],
			[ 'fund_id' => null ],
			[ 'fund_id' => '%d' ],
			[ 'fund_id' => '%d' ]
		);
	}
}
