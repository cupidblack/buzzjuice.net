<?php

namespace TotalPoll\Modules\Extensions\RestAPI;
! defined( 'ABSPATH' ) && exit();


/**
 * Class Extension
 * @package TotalPoll\Modules\Extensions\RestAPI
 */
class Extension extends \TotalPoll\Modules\Extension {
	protected $root = __FILE__;

	/**
	 * Run the extension.
	 *
	 * @return mixed
	 */
	public function run() {
		add_filter( 'totalpoll/filters/admin/editor/integration/tabs', [ $this, 'tab' ] );
		add_action( 'rest_api_init', [ $this, 'bootstrapRestAPI' ] );

		$container = \TotalPoll()->container();
		$container->share( 'polls.restApi', function () use ( $container ) {
			return new PollRestAPI( $container->get( 'http.request' ), $container->get( 'polls.repository' ), $container->get( 'env' ) );
		} );
	}

	public function bootstrapRestAPI() {
		\TotalPoll( 'polls.restApi' )->registerRoutes();
	}

	public function tab( $tabs ) {
		$tabs['rest-api'] = [
			'label'       => esc_html__( 'REST API', 'totalpoll' ),
			'description' => esc_html__( 'Advanced', 'totalpoll' ),
			'icon'        => 'cloud',
			'file'        => $this->getPath( 'views/tab.php' ),
		];

		return $tabs;
	}
}
