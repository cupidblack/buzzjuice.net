<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App\Model;

use CloudLinux\Imunify\App\Bot\Preset;

/**
 * Configuration carried in the dedicated plugin_config.php file.
 *
 * Separate from scan_data.php so the mu-plugin can load just what it
 * needs on the hot path — no malware list, no license metadata, just
 * feature flags. The agent writes this file on every admin-config
 * toggle and alongside every scan-cycle rewrite.
 *
 * The plugin is distributed with the agent, so their versions are in
 * lockstep by construction — if this class reads a field, the agent
 * that wrote the file knew how to populate it. Any forward-compatible
 * gating therefore belongs on the writer side (the agent simply
 * omits a field it doesn't support); consumers of this class treat
 * missing fields as "unset, use safe default".
 *
 * @since 4.0.0
 */
class PluginConfig {

	/**
	 * Raw ai_bot_protection flag as written by the agent. Missing /
	 * malformed input defaults to false so a first-time install or a
	 * partially-written file never inadvertently turns the feature on.
	 *
	 * @var bool
	 */
	private $aiBotProtectionEnabled = false;

	/**
	 * Hoster-level default preset written by the agent. Null means
	 * "agent didn't set one" (or the value was malformed) — caller
	 * (Preset::resolve) decides the chain fallback.
	 *
	 * @var string|null One of Preset::BALANCED/STRICT/MONITOR, or null.
	 */
	private $preset = null;

	/**
	 * Hydrate from the decoded contents of plugin_config.php.
	 *
	 * @param mixed $data Decoded file contents (expected array).
	 * @return self
	 */
	public static function fromArray( $data ) {
		$instance = new self();
		if ( ! is_array( $data ) ) {
			return $instance;
		}
		if ( isset( $data['ai_bot_protection'] ) ) {
			$instance->aiBotProtectionEnabled = (bool) $data['ai_bot_protection'];
		}
		if ( isset( $data['preset'] ) && is_string( $data['preset'] ) ) {
			$normalized = strtolower( trim( $data['preset'] ) );
			if ( Preset::isValid( $normalized ) ) {
				$instance->preset = $normalized;
			}
		}
		return $instance;
	}

	/**
	 * Whether the server-level AI bot protection feature gate is enabled.
	 *
	 * @return bool
	 */
	public function isAiBotProtectionEnabled() {
		return $this->aiBotProtectionEnabled;
	}

	/**
	 * Hoster-level default preset, or null when unset / malformed.
	 *
	 * @return string|null
	 */
	public function getPreset() {
		return $this->preset;
	}
}
