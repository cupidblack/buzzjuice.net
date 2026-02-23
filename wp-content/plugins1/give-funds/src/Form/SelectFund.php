<?php

namespace GiveFunds\Form;

use GiveFunds\Infrastructure\View;
use GiveFunds\Repositories\Form;
use GiveFunds\Repositories\Funds as FundsRepository;


/**
 * Add Fund page
 *
 * @package GiveFunds\Funds\Pages
 */
class SelectFund {
	/**
	 * @var FundsRepository
	 */
	private $fundsRepository;

    /**
     * AddFund constructor.
     *
     * @param  FundsRepository  $fundsRepository
     */
	public function __construct(FundsRepository $fundsRepository) {
		$this->fundsRepository   = $fundsRepository;
	}

	/**
	 * Render Add fund page
	 *
	 * @param int $formId
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function renderDropdown( $formId ) {
		/* @var Form $formRepository */
		$formRepository = give( Form::class );

		if ( 'donor_choice' !== $formRepository->getFundDisplayType( $formId ) ) {
			return;
		}

		$funds = $this->fundsRepository->getFormAssociatedFunds( $formId );

		if ( count( $funds ) > 0 ) {
			View::render(
				'form/select-fund',
				[
					'funds' => $funds,
					'label' => give_get_meta( $formId, 'give_funds_label', true )
				]
			);
		}
	}
}
