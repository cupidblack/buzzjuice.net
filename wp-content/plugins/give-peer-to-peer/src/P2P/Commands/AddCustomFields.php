<?php

namespace GiveP2P\P2P\Commands;

/**
 * @since 1.0.0
 */
class AddCustomFields {

	/**
	 * @since 1.0.0
	 * @param $collection
	 */
	public function __invoke( $collection ) {
		$collection->append(
			give_field( 'hidden', 'p2pSourceID' )
				->defaultValue( isset( $_GET[ 'p2pSourceID' ] ) ? absint( $_GET[ 'p2pSourceID' ] ) : '' )
		);
		$collection->append(
			give_field( 'hidden', 'p2pSourceType' )
				->defaultValue( isset( $_GET[ 'p2pSourceType' ] ) ? sanitize_text_field( $_GET[ 'p2pSourceType' ] ) : '' )
		);
	}
}
