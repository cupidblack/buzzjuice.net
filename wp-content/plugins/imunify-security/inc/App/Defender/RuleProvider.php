<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App\Defender;

use CloudLinux\Imunify\App\DataStore;
use CloudLinux\Imunify\App\Debug;
use CloudLinux\Imunify\App\Defender\Model\Rule;
use CloudLinux\Imunify\App\Defender\Model\RuleCollection;
use CloudLinux\Imunify\App\Defender\Model\RuleMode;
use CloudLinux\Imunify\App\Defender\Model\Target;
use CloudLinux\Imunify\App\Defender\Model\TargetInfo;
use CloudLinux\Imunify\Composer\Semver\Semver;

/**
 * Rule provider class.
 *
 * Handles loading and providing rules for the Defender system.
 *
 * @since 2.1.0
 */
class RuleProvider {

	/**
	 * Rules file name.
	 */
	const RULES_FILE_NAME = 'rules.php';

	/**
	 * Transient name for caching rules.
	 */
	const RULES_TRANSIENT = 'imunify_security_rules';

	/**
	 * Transient lifetime in seconds (6 hours).
	 */
	const TRANSIENT_LIFETIME = 21600;

	/**
	 * Data store instance.
	 *
	 * @var DataStore
	 */
	private $dataStore;

	/**
	 * Debug instance.
	 *
	 * @var Debug
	 * @phpstan-ignore-next-line
	 */
	private $debug;

	/**
	 * List of plugins (local cache).
	 *
	 * @var array|null
	 */
	private $plugins;

	/**
	 * Cached rules (local cache for the duration of the request).
	 *
	 * @var RuleCollection|null
	 */
	private $rules = null;

	/**
	 * Ruleset version.
	 *
	 * @var string
	 */
	private $rulesetVersion = '';

	/**
	 * Constructor.
	 *
	 * @param Debug     $debug Debug instance.
	 * @param DataStore $dataStore Data store instance.
	 */
	public function __construct( $debug, $dataStore ) {
		$this->debug     = $debug;
		$this->dataStore = $dataStore;

		// Force reload rules when any plugin is activated or deactivated.
		add_action( 'activated_plugin', array( $this, 'onPluginActivation' ), 10, 2 );
		add_action( 'deactivated_plugin', array( $this, 'onPluginDeactivation' ), 10, 2 );

		// Force reload rules when theme is switched.
		add_action( 'switch_theme', array( $this, 'onThemeSwitch' ), 10, 3 );

		// Force reload rules when WordPress, plugins, or themes are updated.
		add_action( 'upgrader_process_complete', array( $this, 'onUpgraderProcessComplete' ), 10, 2 );
	}

	/**
	 * Force reload rules when any plugin is activated.
	 *
	 * @param string $plugin The plugin slug.
	 * @param bool   $network_wide Whether the plugin was activated network-wide.
	 * @return void
	 */
	public function onPluginActivation( $plugin, $network_wide ) {
		$this->forceRulesReload();
	}

	/**
	 * Force reload rules when any plugin is deactivated.
	 *
	 * @param string $plugin The plugin slug.
	 * @param bool   $network_deactivating_wide Whether the plugin was deactivated network-wide.
	 *
	 * @return void
	 */
	public function onPluginDeactivation( $plugin, $network_deactivating_wide ) {
		$this->forceRulesReload();
	}

	/**
	 * Force reload rules when theme is switched.
	 *
	 * @param string    $new_name The new theme name.
	 * @param \WP_Theme $new_theme The new theme object.
	 * @param \WP_Theme $old_theme The old theme object.
	 *
	 * @return void
	 */
	public function onThemeSwitch( $new_name, $new_theme, $old_theme ) {
		$this->forceRulesReload();
	}

	/**
	 * Force reload rules when WordPress, plugins, or themes are updated.
	 *
	 * Other entity types and actions are ignored.
	 *
	 * @param \WP_Upgrader $upgrader   Upgrader instance.
	 * @param array        $hook_extra Extra arguments passed to hooked filters.
	 *
	 * @return void
	 */
	public function onUpgraderProcessComplete( $upgrader, $hook_extra ) {
		if ( ! isset( $hook_extra['type'] ) || ! in_array( $hook_extra['type'], array( 'core', 'plugin', 'theme' ), true ) ) {
			return;
		}

		if ( ! isset( $hook_extra['action'] ) || 'update' !== $hook_extra['action'] ) {
			return;
		}

		$this->forceRulesReload();
	}

	/**
	 * Load rules from transient cache or rules file.
	 *
	 * @param bool $ignoreCache Whether to ignore cache and force reload from file.
	 *
	 * @return RuleCollection Collection of rules or empty collection if no rules are available.
	 */
	public function loadRules( $ignoreCache = false ) {

		// Return cached rules if already loaded during this request.
		if ( null !== $this->rules && ! $ignoreCache ) {
			return $this->rules;
		}

		if ( $this->dataStore->isDataFileAvailable( self::RULES_FILE_NAME ) ) {

			// Use atomic stat call to avoid race conditions between mtime and size checks.
			$rulesFilePath = $this->getRulesFilePath();
			$fileStats     = stat( $rulesFilePath );

			if ( is_array( $fileStats ) ) {
				// Create unique transient name using both modification time and file size.
				$transientName = $this->buildTransientName( $fileStats );

				// Try to load from transient first (unless cache should be ignored).
				if ( ! $ignoreCache ) {
					$cachedData = get_transient( $transientName );

					if ( is_array( $cachedData ) && isset( $cachedData['rules'] ) ) {
						// TEMPORARILY DISABLED: Force all cached rules to 'pass' mode.
						$cachedRules = $cachedData['rules'];
						if ( is_array( $cachedRules ) ) {
							foreach ( $cachedRules as $id => $ruleData ) {
								if ( is_array( $ruleData ) ) {
									$cachedRules[ $id ]['mode'] = RuleMode::PASS;
								}
							}
						}
						// Return cached rules if available.
						$this->rulesetVersion = isset( $cachedData['version'] ) ? $cachedData['version'] : '';
						$result               = RuleCollection::fromArray( $cachedRules );
						$this->rules          = $result;
						return $result;
					}
				}

				// If no cached rules or cache should be ignored, load from file and cache them.
				// WordPress transients provide built-in stampede protection.
				$rulesData = $this->loadRulesFromFile();
				$rules     = isset( $rulesData['rules'] ) ? $rulesData['rules'] : array();
				$result    = $this->getRelevantRules( $rules );

				// Cache the rules and version for 6 hours if we have valid rules.
				$cacheData = array(
					'version' => $this->rulesetVersion,
					'rules'   => $result->toArray(),
				);
				set_transient( $transientName, $cacheData, self::TRANSIENT_LIFETIME );
				$this->rules = $result;
				return $result;
			}
		}

		$result      = RuleCollection::withNoRules();
		$this->rules = $result;
		return $result;
	}

	/**
	 * Load rules from the rules file.
	 *
	 * @return array Array with 'version' and 'rules' keys, or empty array if file doesn't exist or is invalid.
	 */
	public function loadRulesFromFile() {
		if ( ! $this->dataStore->isDataFileAvailable( self::RULES_FILE_NAME ) ) {
			$this->rulesetVersion = '';
			return array(
				'version' => '',
				'rules'   => array(),
			);
		}

		// Invalidate OPcache to ensure we get the latest version of the rules file.
		if ( function_exists( 'opcache_invalidate' ) ) {
			$rulesFilePath = $this->getRulesFilePath();
			$this->invalidateOpcache( $rulesFilePath );
		}

		$data = $this->dataStore->load( self::RULES_FILE_NAME );
		if ( ! $data || ! is_array( $data ) ) {
			$this->rulesetVersion = '';
			return array(
				'version' => '',
				'rules'   => array(),
			);
		}

		// Extract version and rules from the new structure.
		$this->rulesetVersion = isset( $data['version'] ) ? $data['version'] : '';
		$rules                = isset( $data['rules'] ) && is_array( $data['rules'] ) ? $data['rules'] : array();

		return array(
			'version' => $this->rulesetVersion,
			'rules'   => $rules,
		);
	}

	/**
	 * Invalidate OPcache for a file.
	 *
	 * @param string $filePath The path to the file to invalidate.
	 *
	 * @return bool True if successful, false otherwise.
	 */
	protected function invalidateOpcache( $filePath ) {
		return opcache_invalidate( $filePath, true );
	}

	/**
	 * Get the rules file path.
	 *
	 * @return string The rules file path.
	 */
	public function getRulesFilePath() {
		return $this->dataStore->getDataDirectory() . DIRECTORY_SEPARATOR . self::RULES_FILE_NAME;
	}

	/**
	 * Build transient name from file statistics.
	 *
	 * @param array $fileStats File statistics from stat() call.
	 *
	 * @return string Transient name.
	 */
	public function buildTransientName( $fileStats ) {
		return self::RULES_TRANSIENT . '_' . $fileStats['mtime'] . '_' . $fileStats['size'];
	}

	/**
	 * Force reload rules from file and update cache.
	 *
	 * @return void
	 */
	public function forceRulesReload() {
		$this->plugins = null;
		$this->rules   = null;
		$this->loadRules( true );
	}

	/**
	 * Check if the rule has all required fields.
	 *
	 * @param Rule $rule Rule object.
	 *
	 * @return bool True if the rule is valid, false otherwise.
	 */
	public function isRuleValid( $rule ) {
		// Check required fields: target, versions.
		if ( empty( $rule->getTarget() ) || empty( $rule->getVersions() ) ) {
			return false;
		}

		// Check if target is valid.
		if ( ! Target::isValid( $rule->getTarget() ) ) {
			return false;
		}

		// Check slug requirement (not required for core target).
		if ( Target::requiresSlug( $rule->getTarget() ) && empty( $rule->getSlug() ) ) {
			return false;
		}

		// Check that rule has either action or ajax_action.
		if ( ! $rule->getAction() && ! $rule->getAjaxAction() ) {
			return false;
		}

		return true;
	}

	/**
	 * Get target information if the rule's target is present on the site.
	 *
	 * @param Rule $rule Rule object.
	 *
	 * @return TargetInfo|null Target information if target is present and meets version requirements, null otherwise.
	 */
	public function getTargetInfo( $rule ) {
		if ( empty( $rule->getVersions() ) ) {
			return null;
		}

		// Load plugins if not already loaded.
		if ( null === $this->plugins ) {
			$this->plugins = $this->loadPlugins();
		}

		if ( Target::PLUGIN === $rule->getTarget() && ! empty( $rule->getSlug() ) ) {
			// Check if plugin exists and is active.
			$pluginData = $this->getPluginData( $rule->getSlug() );
			if ( ! $pluginData ) {
				return null;
			}

			// Check if plugin is active.
			$pluginFile = $this->getPluginFile( $rule->getSlug() );
			if ( ! $pluginFile || ! is_plugin_active( $pluginFile ) ) {
				return null;
			}

			// Check if plugin version meets requirements.
			$pluginVersion = isset( $pluginData['Version'] ) ? $pluginData['Version'] : null;
			if ( ! $pluginVersion ) {
				return null;
			}

			// Check if the plugin version satisfies the constraint.
			if ( $this->versionSatisfiesConstraint( $pluginVersion, $rule->getVersions() ) ) {
				return new TargetInfo( $rule->getTarget(), $rule->getSlug(), $pluginVersion );
			}
		}

		if ( Target::THEME === $rule->getTarget() && ! empty( $rule->getSlug() ) ) {
			// Check if the rule target the current theme.
			$theme = wp_get_theme();
			if ( $theme->get_template() !== $rule->getSlug() ) {
				return null;
			}

			// Check if theme version meets requirements.
			$themeVersion = $theme->get( 'Version' );
			if ( $this->versionSatisfiesConstraint( $themeVersion, $rule->getVersions() ) ) {
				return new TargetInfo( $rule->getTarget(), $rule->getSlug(), $themeVersion );
			}
		}

		if ( Target::CORE === $rule->getTarget() ) {
			// Check if WordPress core version meets requirements.
			global $wp_version;
			if ( $this->versionSatisfiesConstraint( $wp_version, $rule->getVersions() ) ) {
				return new TargetInfo( $rule->getTarget(), '', $wp_version );
			}
		}

		return null;
	}

	/**
	 * Load plugins.
	 *
	 * @return array
	 */
	private function loadPlugins() {
		// Check if get_plugins() function exists. This is required on the front end of the
		// site, since it is in a file that is normally only loaded in the admin.
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return get_plugins();
	}

	/**
	 * Get plugin data by slug.
	 *
	 * @param string $pluginSlug Plugin slug.
	 *
	 * @return array|null Plugin data or null if not found.
	 */
	private function getPluginData( $pluginSlug ) {
		foreach ( $this->plugins as $pluginFile => $pluginData ) {
			if ( false !== stripos( $pluginFile, $pluginSlug ) ) {
				return $pluginData;
			}
		}

		return null;
	}

	/**
	 * Get plugin file path for is_plugin_active.
	 *
	 * @param string $pluginSlug Plugin slug.
	 *
	 * @return string|null Plugin file path or null if not found.
	 */
	private function getPluginFile( $pluginSlug ) {
		foreach ( $this->plugins as $pluginFile => $pluginData ) {
			if ( false !== stripos( $pluginFile, $pluginSlug ) ) {
				return $pluginFile;
			}
		}

		return null;
	}

	/**
	 * Check if version satisfies constraint.
	 *
	 * @param string $currentVersion Current version of the plugin or theme.
	 * @param string $constraint     Version constraint (e.g., ">=1.0.0 <2.0.0").
	 *
	 * @return bool
	 */
	private function versionSatisfiesConstraint( $currentVersion, $constraint ) {
		return Semver::satisfies( $currentVersion, $constraint );
	}

	/**
	 * Filter and return only relevant rules.
	 *
	 * Rule is relevant if it is valid and its target is present on the site.
	 *
	 * @param array $rules Array of rules.
	 *
	 * @return RuleCollection Collection of relevant rules.
	 */
	private function getRelevantRules( $rules ) {
		// Validate and filter rules.
		$result = new RuleCollection();
		if ( ! empty( $rules ) ) {
			foreach ( $rules as $id => $ruleData ) {
				// TEMPORARILY DISABLED: Force all rules to 'pass' mode.
				$ruleData['mode'] = RuleMode::PASS;
				$rule             = Rule::fromArray( $id, $ruleData );
				if ( $this->isRuleValid( $rule ) && $this->getTargetInfo( $rule ) ) {
					$result->addRule( $rule );
				}
			}
		}
		return $result;
	}

	/**
	 * Get the ruleset version.
	 *
	 * @return string Ruleset version.
	 */
	public function getRulesetVersion() {
		return $this->rulesetVersion;
	}
}
