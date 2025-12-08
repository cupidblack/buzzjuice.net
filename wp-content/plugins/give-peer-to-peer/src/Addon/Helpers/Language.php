<?php

namespace GiveP2P\Addon\Helpers;

/**
 * Helper class responsible for loading add-on translations.
 *
 * @package     GiveP2P\Addon\Helpers
 * @copyright   Copyright (c) 2020, GiveWP
 */
class Language {
	/**
	 * Load language.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function load() {

		// Set filter for plugin's languages directory.
		$langDir = apply_filters(
			sprintf( '%s_languages_directory', 'give-peer-to-peer' ), // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores, WordPress.NamingConventions.ValidHookName.NotLowercase
			dirname( GIVE_P2P_BASENAME ) . '/languages/'
		);

		// Traditional WordPress plugin locale filter.
		$locale = apply_filters( 'plugin_locale', get_locale(), 'give-peer-to-peer' );
		$moFile = sprintf( '%1$s-%2$s.mo', 'give-peer-to-peer', $locale );

		// Setup paths to current locale file.
		$moFileLocal  = $langDir . $moFile;
		$moFileGlobal = WP_LANG_DIR . 'give-peer-to-peer' . $moFile;

		if ( file_exists( $moFileGlobal ) ) {
			// Look in global /wp-content/languages/TEXTDOMAIN folder.
			load_textdomain( 'give-peer-to-peer', $moFileGlobal );
		} elseif ( file_exists( $moFileLocal ) ) {
			// Look in local /wp-content/plugins/TEXTDOMAIN/languages/ folder.
			load_textdomain( 'give-peer-to-peer', $moFileLocal );
		} else {
			// Load the default language files.
			load_plugin_textdomain( 'give-peer-to-peer', false, $langDir );
		}
	}

    /**
     * Localize the translations for a given domain.
     *
     * @link https://wordpress.stackexchange.com/a/312127
     * @param $domain
     *
     * @return void
     */
    public static function localize($domain)
    {
        $translations = get_translations_for_domain( $domain );

        $locale = array(
            '' => array(
                'domain' => $domain,
                'lang'   => is_admin() ? get_user_locale() : get_locale(),
            ),
        );

        if ( ! empty( $translations->headers['Plural-Forms'] ) ) {
            $locale['']['plural_forms'] = $translations->headers['Plural-Forms'];
        }

        foreach ( $translations->entries as $msgid => $entry ) {
            $locale[ $msgid ] = $entry->translations;
        }
        wp_add_inline_script(
            'wp-i18n',
            'wp.i18n.setLocaleData( ' . json_encode( $locale ) . ', "' . $domain . '" );'
        );
    }
}
