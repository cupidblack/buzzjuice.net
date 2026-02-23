<?php

namespace GiveFunds\Admin;

use GiveFunds\Models\Fund;
use GiveFunds\Repositories\Funds as FundsRepository;
use GiveFunds\Repositories\Revenue as RevenueRepository;
use GiveFunds\Infrastructure\View;
use WP_List_Table;

/**
 *  Funds list table.
 *
 * @package     GiveFunds\Admin
 * @copyright   Copyright (c) 2020, GiveWP
 */
class FundsListTable extends WP_List_Table {

	/**
	 * Items per page
	 * @var int
	 */
	public $per_page;

	/**
	 * Column filters
	 * @var array
	 */
	private $filters = [];

	/**
	 * @var FundsRepository
	 */
	private $fundsRepository;
	/**
	 * @var RevenueRepository
	 */
	private $revenueRepository;

	/**
	 * @var int
	 */
	private $total;

	/**
	 * FundsListTable constructor.
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
		$this->per_page          = get_option( 'give_funds_per_page', 20 );

		parent::__construct(
			[
				'singular' => esc_html__( 'Fund', 'give-funds' ),
				'plural'   => esc_html__( 'Funds', 'give-funds' ),
				'ajax'     => false
			]
		);
	}

	/**
	 * Render the list table
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function prepare_items() {
		// Process bulk actions
		$this->process_bulk_action();

		$funds        = $this->fundsRepository->getFunds();
		$this->total  = count( $funds );
		$current_page = $this->get_pagenum();
		$this->items  = array_slice( $funds, ( ( $current_page - 1 ) * $this->per_page ), $this->per_page );

		$this->set_pagination_args(
			[
				'per_page'    => $this->per_page,
				'total_items' => $this->total,
				'total_pages' => ceil( $this->total / $this->per_page ),
			]
		);

		$this->_column_headers = [
			$this->get_columns(),
			$this->get_hidden_columns(),
			$this->get_sortable_columns()
		];

		$this->filter( 'title', [ $this, 'filter_title_column' ] );
		$this->filter( 'revenue', [ $this, 'filter_revenue_column' ] );
		$this->filter( 'donations', [ $this, 'filter_donations_column' ] );
	}

	/**
	 * Get table columns
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_columns() {
		return [
			'cb'          => '<input type="checkbox" />',
			'title'       => esc_html__( 'Title', 'give-funds' ),
			'description' => esc_html__( 'Description', 'give-funds' ),
			'revenue'     => esc_html__( 'Revenue', 'give-funds' ),
			'donations'   => esc_html__( 'Donations', 'give-funds' ),
		];
	}

	/**
	 * Get hidden columns
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_hidden_columns() {
		return [];
	}

	/**
	 * Get sortable columns
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return [];
	}

	/**
	 * Get sortable columns
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		if ( $this->total > 1 ) {
			return [
				'reassign' => esc_html__( 'Reassign Revenue to Fund', 'give-funds' ),
				'delete'   => esc_html__( 'Delete', 'give-funds' )
			];
		}

		return [];
	}

	/**
	 * Process bulk actions.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function process_bulk_action() {
		// Bailout.
		if ( ! isset(
			$_POST['give-funds-bulk-nonce'],
			$_POST['action'],
			$_POST['give-fund']
		) ) {
			return;
		}

		// Handle actions
		switch ( $this->current_action() ) {
			// Delete funds.
			case 'delete':
				foreach ( $_POST['give-fund'] as $fundId => $value ) {
					if ( 'on' === $value ) {
						$this->fundsRepository->deleteFund( $fundId );
					}
				}

				break;

			// Reassign revenue
			case 'reassign':
				// Bailout.
				if ( ! isset( $_POST['give-funds-selected-fund'] ) ) {
					return;
				}

				$fundId = (int) $_POST['give-funds-selected-fund'];

				$funds = [];

				// Collect fund IDs.
				foreach ( $_POST['give-fund'] as $id => $value ) {
					if ( 'on' === $value ) {
						$funds[] = $id;
					}
				}

				// Assign revenue
				$this->revenueRepository->assignRevenue( $fundId, $funds );

				break;
		}
	}

	/**
	 * Register filter for a column.
	 *
	 * @param string $name
	 * @param callable $callback
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function filter( $name, $callback ) {
		$this->filters[ $name ] = $callback;
	}

	/**
	 * Show default column value for the current row
	 *
	 * @param Fund $fund
	 * @param string $name
	 *
	 * @since 1.0.0
	 *
	 * @return string|void
	 */
	public function column_default( $fund, $name ) {
		// Apply filters if any
		if ( isset( $this->filters[ $name ] ) ) {
			return call_user_func_array( $this->filters[ $name ], [ $fund, $name ] );
		}

		return $fund->get( $name );
	}

	/**
	 * @param array $item
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function column_cb( $fund ) {
		printf( '<input type="checkbox" name="give-fund[%s]" />', $fund->getId() );
	}

	/**
	 * Filter title column
	 *
	 * @param Fund $fund
	 * @param string $columnName
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function filter_title_column( $fund, $columnName ) {
		View::render(
			'admin/funds-row-actions',
			[
				'fund_id'       => $fund->getId(),
				'fund_name'     => $fund->getTitle(),
				'is_default'    => $fund->isDefault(),
				'edit_link'     => admin_url( 'edit.php?post_type=give_forms&page=give-edit-fund&id=' . $fund->getId() ),
				'overview_link' => admin_url( 'edit.php?post_type=give_forms&page=give-fund-overview&id=' . $fund->getId() ),
				'delete_link'   => wp_nonce_url(
					admin_url( 'edit.php?post_type=give_forms&page=give-funds&give-funds-action=delete-fund&id=' . $fund->getId() ),
					'give-funds-delete-' . $fund->getId(),
					'give-funds-nonce'
				)
			]
		);
	}

	/**
	 * Filter revenue column
	 *
	 * @param Fund $fund
	 * @param string $columnName
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function filter_revenue_column( $fund, $columnName ) {
		$revenue = $this->revenueRepository->getFundRevenue( $fund->getId() );
		return give_currency_filter( give_format_amount( $revenue, [ 'sanitize' => false ] ) );
	}

	/**
	 * Filter donations column
	 *
	 * @param Fund $fund
	 * @param string $columnName
	 *
	 * @since 1.0.0
	 * @return int
	 */
	public function filter_donations_column( $fund, $columnName ) {

		$count = $this->revenueRepository->getFundDonationsCount( $fund->getId() );

		if ( ! $count ) {
			return 0;
		}

		printf(
			'<a href="%1$s">%2$s</a>',
			esc_url( admin_url( 'edit.php?post_type=give_forms&page=give-payment-history' ) ),
			$count
		);
	}
}
