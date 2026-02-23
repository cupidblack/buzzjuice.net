<?php
namespace GiveP2P\Routing;

use Give\Helpers\Hooks;
use Give\ServiceProviders\ServiceProvider as GiveServiceProvider;

/**
 * @since 1.0.0
 */
class ServiceProvider implements GiveServiceProvider {

	/**
	 * @inheritDoc
	 * @since 1.0.0
	 */
	public function register() {
		give()->singleton( RouteProxy::class );
		give()->singleton( RouteRegister::class );
	}

	/**
	 * @inheritDoc
	 * @since 1.0.0
	 */
	public function boot() {
		Hooks::addAction( 'init', RouteProxy::class, 'registerRewriteTags' );
		Hooks::addAction( 'init', RouteProxy::class, 'registerRewriteRules' );
		Hooks::addFilter( 'query_vars', RouteProxy::class, 'registerVariables' );
		Hooks::addFilter( 'template_include', RouteProxy::class, 'includeTemplate' );
	}
}
