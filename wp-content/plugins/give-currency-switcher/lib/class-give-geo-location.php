<?php
/**
 * Give Currency Switcher GeoLocation.
 *
 * Get the Location from user's IP address.
 *
 * @package    Give_Currency_Switcher
 * @copyright  Copyright (c) 2016, GiveWP
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use GeoIp2\Database\Reader;

/**
 * Get and handle the Geo-location information from the an IPAddress.
 *
 * @since 1.0
 */
class Give_Geo_location {

	/**
	 * GeoLite Country Database file.
	 *
	 * @access protected
	 * @since  1.0
	 * @var string $geolite_database
	 */
	protected static $geolite_database = 'lib/geolite-db/GeoLite2-Country.mmdb';

	/**
	 * Collect all of the errors.
	 *
	 * @access private
	 * @since  1.0
	 * @var array|bool $errors
	 */
	private $errors = false;

	/**
	 * Store currency based on country code.
	 *
	 * @since 1.0
	 * @var array $currencies
	 */
	protected $currencies = array();

	/**
	 * Store Form ID.
	 *
	 * @since 1.0
	 * @var integer $form_id Form ID.
	 */
	private $form_id;

	/**
	 * Donor's IP Address.
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	private $ip_address;

	/**
	 * Give_Geo_location constructor.
	 *
	 * @since 1.0
	 *
	 * @param integer $form_id Form ID.
	 */
	public function __construct( $form_id = 0 ) {

		// Set the Form ID.
		$this->form_id = $form_id;

		// Create an array list for all supported currencies.
		$this->currencies = array(
			'AD' => 'EUR',
			'AS' => 'USD',
			'AT' => 'EUR',
			'AU' => 'AUD',
			'AX' => 'EUR',
			'BE' => 'EUR',
			'BL' => 'EUR',
			'BQ' => 'USD',
			'BR' => 'BRL',
			'BV' => 'NOK',
			'CA' => 'CAD',
			'CC' => 'AUD',
			'CH' => 'CHF',
			'CK' => 'NZD',
			'CX' => 'AUD',
			'CY' => 'EUR',
			'CZ' => 'CZK',
			'DE' => 'EUR',
			'DK' => 'DKK',
			'EE' => 'EUR',
			'EH' => 'MAD',
			'ES' => 'EUR',
			'FI' => 'EUR',
			'FM' => 'USD',
			'FO' => 'DKK',
			'FR' => 'EUR',
			'GB' => 'GBP',
			'GF' => 'EUR',
			'GG' => 'GBP',
			'GL' => 'DKK',
			'GP' => 'EUR',
			'GR' => 'EUR',
			'GS' => 'GBP',
			'GU' => 'USD',
			'HK' => 'HKD',
			'HM' => 'AUD',
			'HU' => 'HUF',
			'IE' => 'EUR',
			'IL' => 'ILS',
			'IM' => 'GBP',
			'IN' => 'INR',
			'IO' => 'USD',
			'IT' => 'EUR',
			'JE' => 'GBP',
			'JP' => 'JPY',
			'KI' => 'AUD',
			'KR' => 'KRW',
			'LI' => 'CHF',
			'LU' => 'EUR',
			'MA' => 'MAD',
			'MC' => 'EUR',
			'ME' => 'EUR',
			'MF' => 'EUR',
			'MH' => 'USD',
			'MP' => 'USD',
			'MQ' => 'EUR',
			'MT' => 'EUR',
			'MX' => 'MXN',
			'MY' => 'MYR',
			'NF' => 'AUD',
			'NL' => 'EUR',
			'NO' => 'NOK',
			'NR' => 'AUD',
			'NU' => 'NZD',
			'NZ' => 'NZD',
			'PH' => 'PHP',
			'PL' => 'PLN',
			'PM' => 'EUR',
			'PN' => 'NZD',
			'PR' => 'USD',
			'PS' => 'ILS',
			'PT' => 'EUR',
			'PW' => 'USD',
			'RE' => 'EUR',
			'RU' => 'RUB',
			'SE' => 'SEK',
			'SG' => 'SGD',
			'SI' => 'EUR',
			'SJ' => 'NOK',
			'SK' => 'EUR',
			'SM' => 'EUR',
			'SV' => 'USD',
			'TC' => 'USD',
			'TF' => 'EUR',
			'TH' => 'THB',
			'TK' => 'NZD',
			'TL' => 'USD',
			'TR' => 'TRY',
			'TV' => 'AUD',
			'TW' => 'TWD',
			'UM' => 'USD',
			'US' => 'USD',
			'VA' => 'EUR',
			'VG' => 'USD',
			'VI' => 'USD',
			'YT' => 'EUR',
			'ZA' => 'ZAR',
			'ZW' => 'USD',
		);

		/**
		 * Require PHP >= 5.4 to use this class.
		 */
		if ( version_compare( '5.4', phpversion(), '>' ) ) {
			$this->errors[] = sprintf( __( 'Geo Location is not support with PHP %s. It required minimum PHP 5.4 or greater than it.', 'give-currency-switcher' ), phpversion() );
		}
	}

	/**
	 * Get the geo lite database file.
	 *
	 * @access private
	 * @since  1.0
	 */
	private static function get_db_file() {
		return apply_filters( 'give_cs_geo_location_database_url', GIVE_CURRENCY_SWITCHER_PLUGIN_DIR . self::$geolite_database );
	}

	/**
	 * Get the error lists.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_errors() {
		if ( is_array( $this->errors ) ) {
			return implode( "\n", $this->errors );
		}

		return $this->errors;
	}

	/**
	 * Returns the Donor's country, deriving it from his IP address.
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_visitor_country() {
		return $this->get_donor_country_code( $this->get_donor_ip() );
	}

	/**
	 * Get the donor's country code.
	 *
	 * @access public
	 * @since  1.0
	 *
	 * @param string $ip_address The IP address of what you want country code.
	 *
	 * @return string Country code or false if fails.
	 */
	public function get_donor_country_code( $ip_address ) {

		// Store country code.
		$country_code = '';

		// Set Donor's IP Address.
		$this->ip_address = $ip_address;

		// IP address must be either an IPv4 or an IPv6.
		if ( ( filter_var( $ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) === false ) && ( filter_var( $ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) === false ) ) {
			$this->errors[] = sprintf( __( 'Method ' . __METHOD__ . ' expects a valid IPv4 or IPv6 ' . 'address (it will not work with host names). "%s" was passed, which is ' . 'not a valid address.', 'give-currency-switcher' ), $ip_address );
			$country_code   = false;
		}

		if ( $country_code !== false ) {
			try {
				// Create the Reader object, which should be reused across look-ups.
				$reader = new Reader( self::get_db_file() );
				$record = $reader->country( $ip_address );

				// Get donor's country code.
				$country_code = $record->country->isoCode;
			} catch ( \Exception $e ) {
				$this->errors[] = sprintf( __( 'Error(s) occurred while retrieving Geolocation information ' . 'for IP Address "%s". Error: %s.', 'give-currency-switcher' ), $ip_address, $e->getMessage() );
				$country_code   = false;
			}
		}

		return apply_filters( 'give_cs_donor_country_code', $country_code, $ip_address );
	}

	/**
	 * Get donor country name.
	 *
	 * @since 1.0
	 *
	 * @param string $country_code Country code.
	 *
	 * @return string
	 */
	public function get_donor_country_name( $country_code ) {

		// Get give currencies.
		$give_countries = give_get_country_list();

		if ( isset( $give_countries[ $country_code ] ) ) {
			return apply_filters( 'give_cs_donor_currency_name', $give_countries[ $country_code ], $this->ip_address, $country_code );
		}
	}

	/**
	 * Get the Donor's IP Address.
	 *
	 * @access public
	 * @since  1.0
	 *
	 * @return bool|string
	 */
	public function get_donor_ip() {

		if ( ! isset( $_SERVER ) ) {
			return false;
		}

		// Get value from the ser
		$ip_data = isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];

		/**
		 * HTTP_X_FORWARDED_FOR can contains multiple IP Address.
		 * So let's split all of the them into comma.
		 */
		$ip_addresses = explode( ',', $ip_data );
		$donor_ip     = give_clean( array_shift( $ip_addresses ) );

		// Get the donor IP Address.
		$donor_ip = apply_filters( 'give_cs_donor_ip_address', $donor_ip, $ip_data );

		return $donor_ip;
	}

	/**
	 * Get the Currency CODE by donor's country code.
	 *
	 * @since 1.0
	 *
	 * @param string $country_code Country code.
	 *
	 * @return string
	 */
	public function get_currency_by_country( $country_code ) {

		// If currency exists.
		if ( ! in_array( $country_code, array_keys( $this->currencies ), true ) ) {

			// Return geo location base currency, if fails.
			return get_geo_location_base_currency( $this->form_id );
		}

		// Get the currency.
		$currency_code = give_clean( $this->currencies[ $country_code ] );

		return apply_filters( 'give_cs_donor_currency_code', $currency_code, $country_code );
	}
}
