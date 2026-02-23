<?php

namespace GiveP2P\P2P\FieldsAPI\Views;

use DOMDocument;
use Give\Framework\FieldsAPI\Field;
use GiveP2P\P2P\FieldsAPI\Contracts\FieldViewContract;
use NumberFormatter;

/**
 * Class Money
 * @package GiveP2P\P2P\FieldsAPI\Views
 *
 * @since 1.0.0
 */
class Money extends FieldViewContract {
	/**
	 * @inheritDoc
	 */
	public function template( Field $field ) {

		$document = new DOMDocument;

		$currency = give_get_currency();

		$value = \Give\ValueObjects\Money::ofMinor( $field->getDefaultValue(), $currency )->getAmount();

		$currencySymbol = give_currency_symbol( $currency, true );

		// Field container
		$container = $document->createElement( 'div' );

		// Currency symbol container
		$currencySymbolContainer = $document->createElement( 'span', $currencySymbol );
		$currencySymbolContainer->setAttribute( 'class', 'give-money-symbol give-money-symbol-before give-money-symbol-before' );

		// Currency input
		$input = $document->createElement( 'input' );
		$input->setAttribute( 'id', $field->getId() );
		$input->setAttribute( 'class', 'give-money-field ' . $field->getClass() );
		$input->setAttribute( 'name', $field->getName() );
		$input->setAttribute( 'type', 'text' );
		$input->setAttribute( 'placeholder', $field->getPlaceholder() );
		$input->setAttribute( 'value', esc_attr( $value ) );

		// Is required
		if ( $field->isRequired() ) {
			$input->setAttribute( 'required', 'required' );
		}

		// Additional attributes
		foreach( $field->getAttributes() as $name => $value ) {
			$input->setAttribute( $name, esc_attr( $value ) );
		}

		// Build
		$container->appendChild( $currencySymbolContainer );
		$container->appendChild( $input );

		$document->appendChild( $container );

		return $document->saveHTML();
	}
}
