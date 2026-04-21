<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App;

use CloudLinux\Imunify\App\Api\AjaxHandler;
use CloudLinux\Imunify\App\Defender\ChangelogWriter;
use CloudLinux\Imunify\App\Defender\Defender;
use CloudLinux\Imunify\App\Defender\DisabledRulesManager;
use CloudLinux\Imunify\App\Defender\IncidentRecorder;
use CloudLinux\Imunify\App\Defender\RateLimiter;
use CloudLinux\Imunify\App\Defender\Request;
use CloudLinux\Imunify\App\Defender\RuleHitTracker;
use CloudLinux\Imunify\App\Defender\RuleProvider;
use CloudLinux\Imunify\App\Views\AdminPage;
use CloudLinux\Imunify\App\Views\Widget;

/**
 * Initial class
 */
class Plugin {
	/**
	 * Self instance
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Container.
	 *
	 * @var array
	 */
	private $container = array();

	/**
	 * Private constructor
	 */
	private function __construct() {
		// Empty constructor - no instantiation here.
	}

	/**
	 * Private clone
	 */
	private function __clone() {
	}

	/**
	 * Get instance
	 *
	 * @return self
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get service
	 *
	 * @param string $key class.
	 *
	 * @return mixed
	 */
	public function get( $key ) {
		if ( array_key_exists( $key, $this->container ) ) {
			return $this->container[ $key ];
		}

		return null;
	}

	/**
	 * Setup container.
	 *
	 * @return void
	 */
	private function coreSetup() {
		$environment                             = new Environment();
		$this->container[ Environment::class ]   = $environment;
		$this->container[ Debug::class ]         = new Debug( $environment );
		$this->container[ DataStore::class ]     = new DataStore( $this->container[ Debug::class ] );
		$this->container[ AccessManager::class ] = new AccessManager();

		// Create ChangelogWriter and DisabledRulesManager for rule management.
		$dataDirectory        = $this->container[ DataStore::class ]->getDataDirectory();
		$changelogWriter      = new ChangelogWriter( $dataDirectory );
		$disabledRulesManager = new DisabledRulesManager(
			$this->container[ DataStore::class ],
			$changelogWriter
		);

		$this->container[ ChangelogWriter::class ]      = $changelogWriter;
		$this->container[ DisabledRulesManager::class ] = $disabledRulesManager;

		// Create AjaxHandler with DisabledRulesManager for local rule management.
		$this->container[ AjaxHandler::class ] = new AjaxHandler(
			$this->container[ DataStore::class ],
			$disabledRulesManager
		);

		$ruleProvider                             = new RuleProvider( $this->container[ Debug::class ], $this->container[ DataStore::class ] );
		$this->container[ RuleProvider::class ]   = $ruleProvider;
		$this->container[ RuleHitTracker::class ] = new RuleHitTracker();
		$rules                                    = $ruleProvider->loadRules();

		if ( ! empty( $rules ) ) {
			$request          = new Request();
			$rateLimiter      = new RateLimiter();
			$incidentRecorder = new IncidentRecorder( $rateLimiter );
			$defender         = new Defender( $ruleProvider, $incidentRecorder, $this->container[ RuleHitTracker::class ], $disabledRulesManager );
			$defender->processRules( $request );

			$this->container[ RateLimiter::class ]      = $rateLimiter;
			$this->container[ IncidentRecorder::class ] = $incidentRecorder;
			$this->container[ Defender::class ]         = $defender;
		}

		add_action( 'init', array( $this, 'load_translations' ) );
	}

	/**
	 * Additional setup for WP Admin env.
	 *
	 * @return void
	 */
	private function adminSetup() {
		// Create widget first.
		$this->container[ Widget::class ] = new Widget(
			$this->container[ AccessManager::class ],
			$this->container[ DataStore::class ],
			$this->container[ RuleProvider::class ],
			$this->container[ RuleHitTracker::class ]
		);

		// Instantiate AdminPage.
		$this->container[ AdminPage::class ] = new AdminPage(
			$this->container[ AccessManager::class ],
			$this->container[ DataStore::class ]
		);

		// Create asset loader with widget dependency.
		$this->container[ AssetLoader::class ] = new AssetLoader(
			$this->container[ Widget::class ]
		);

		$this->container[ PluginUpdateManager::class ] = new PluginUpdateManager();
	}

	/**
	 * Init plugin.
	 *
	 * @return void
	 */
	public function init() {
		$this->coreSetup();
		if ( is_admin() ) {
			$this->adminSetup();
		}
	}

	/**
	 * Load plugin translations.
	 *
	 * @return void
	 */
	public function load_translations() {
		load_plugin_textdomain( 'imunify-security', false, dirname( plugin_basename( IMUNIFY_SECURITY_FILE_PATH ) ) . '/languages' );
	}
}
