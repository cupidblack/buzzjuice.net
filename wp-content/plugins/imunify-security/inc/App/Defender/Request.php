<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App\Defender;

/**
 * Request class for handling HTTP request data.
 *
 * Provides a clean abstraction over global variables like $_SERVER, $_GET, $_POST, etc.
 * This makes the code more testable and provides a consistent interface.
 *
 * @since 2.1.0
 */
class Request {

	/**
	 * Request method.
	 *
	 * @var string
	 */
	private $method;

	/**
	 * Server variables.
	 *
	 * @var array
	 */
	private $server;

	/**
	 * GET parameters.
	 *
	 * @var array
	 */
	private $get;

	/**
	 * POST parameters.
	 *
	 * @var array
	 */
	private $post;

	/**
	 * Cookies.
	 *
	 * @var array
	 */
	private $cookies;

	/**
	 * File uploads.
	 *
	 * @var array
	 */
	private $files;

	/**
	 * Constructor.
	 *
	 * @param array $server  Server variables (defaults to $_SERVER).
	 * @param array $get     GET parameters (defaults to $_GET).
	 * @param array $post    POST parameters (defaults to $_POST).
	 * @param array $cookies Cookies (defaults to $_COOKIE).
	 * @param array $files   File uploads (defaults to $_FILES).
	 */
	public function __construct( $server = null, $get = null, $post = null, $cookies = null, $files = null ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended
		$this->server  = null !== $server ? $server : $_SERVER;
		$this->get     = null !== $get ? $get : $_GET;
		$this->post    = null !== $post ? $post : $_POST;
		$this->cookies = null !== $cookies ? $cookies : $_COOKIE;
		$this->files   = null !== $files ? $files : $_FILES;
		$this->method  = $this->extractMethod( $this->server );
		// phpcs:enable WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Get the request method.
	 *
	 * @return string The HTTP method (GET, POST, PUT, DELETE, etc.).
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * Check if the request method matches the given method.
	 *
	 * @param string $method The method to check against.
	 *
	 * @return bool True if the request method matches, false otherwise.
	 */
	public function isMethod( $method ) {
		return strtolower( $this->method ) === strtolower( $method );
	}

	/**
	 * Get a GET parameter.
	 *
	 * @param string $key     The parameter key.
	 * @param mixed  $default Default value if the parameter doesn't exist.
	 *
	 * @return mixed The parameter value or default.
	 */
	public function get( $key, $default = null ) {
		return isset( $this->get[ $key ] ) ? $this->get[ $key ] : $default;
	}

	/**
	 * Check if a GET parameter exists.
	 *
	 * @param string $key The parameter key.
	 *
	 * @return bool True if the parameter exists, false otherwise.
	 */
	public function hasGet( $key ) {
		return isset( $this->get[ $key ] );
	}

	/**
	 * Get a POST parameter.
	 *
	 * @param string $key     The parameter key.
	 * @param mixed  $default Default value if the parameter doesn't exist.
	 *
	 * @return mixed The parameter value or default.
	 */
	public function post( $key, $default = null ) {
		return isset( $this->post[ $key ] ) ? $this->post[ $key ] : $default;
	}

	/**
	 * Check if a POST parameter exists.
	 *
	 * @param string $key The parameter key.
	 *
	 * @return bool True if the parameter exists, false otherwise.
	 */
	public function hasPost( $key ) {
		return isset( $this->post[ $key ] );
	}

	/**
	 * Get a cookie.
	 *
	 * @param string $key     The cookie key.
	 * @param mixed  $default Default value if the cookie doesn't exist.
	 *
	 * @return mixed The cookie value or default.
	 */
	public function cookie( $key, $default = null ) {
		return isset( $this->cookies[ $key ] ) ? $this->cookies[ $key ] : $default;
	}

	/**
	 * Check if a cookie exists.
	 *
	 * @param string $key The cookie key.
	 *
	 * @return bool True if the cookie exists, false otherwise.
	 */
	public function hasCookie( $key ) {
		return isset( $this->cookies[ $key ] );
	}

	/**
	 * Get all GET parameters.
	 *
	 * @return array All GET parameters.
	 */
	public function getAllGet() {
		return $this->get;
	}

	/**
	 * Get all POST parameters.
	 *
	 * @return array All POST parameters.
	 */
	public function getAllPost() {
		return $this->post;
	}

	/**
	 * Get all cookies.
	 *
	 * @return array All cookies.
	 */
	public function getAllCookies() {
		return $this->cookies;
	}

	/**
	 * Get all file uploads.
	 *
	 * @return array All file uploads.
	 */
	public function getAllFiles() {
		return $this->files;
	}

	/**
	 * Get all server variables.
	 *
	 * @return array All server variables.
	 */
	public function getAllServer() {
		return $this->server;
	}

	/**
	 * Get the request URI.
	 *
	 * @return string The request URI.
	 */
	public function getUri() {
		return isset( $this->server['REQUEST_URI'] ) ? $this->server['REQUEST_URI'] : '';
	}

	/**
	 * Check if a file upload exists.
	 *
	 * @param string $key The file key.
	 *
	 * @return bool True if the file exists, false otherwise.
	 */
	public function hasFile( $key ) {
		return isset( $this->files[ $key ] ) && ! empty( $this->files[ $key ]['name'] );
	}

	/**
	 * Get a file upload.
	 *
	 * @param string $key The file key.
	 *
	 * @return array|null The file data or null if not found.
	 */
	public function getFile( $key ) {
		return isset( $this->files[ $key ] ) ? $this->files[ $key ] : null;
	}

	/**
	 * Get a request header.
	 *
	 * @param string $key The header key.
	 *
	 * @return string|null The header value or null if not found.
	 */
	public function getHeader( $key ) {
		$header_key = 'HTTP_' . strtoupper( str_replace( '-', '_', $key ) );
		return isset( $this->server[ $header_key ] ) ? $this->server[ $header_key ] : null;
	}

	/**
	 * Extract the request method from server variables.
	 *
	 * @param array $server Server variables.
	 *
	 * @return string The HTTP method.
	 */
	private function extractMethod( $server ) {
		// Check for X-HTTP-METHOD-OVERRIDE header (used by some frameworks).
		if ( isset( $server['HTTP_X_HTTP_METHOD_OVERRIDE'] ) ) {
			return $server['HTTP_X_HTTP_METHOD_OVERRIDE'];
		}

		// Check for _method parameter (used by some frameworks).
		if ( isset( $this->post['_method'] ) ) {
			return $this->post['_method'];
		}

		// Check for the standard REQUEST_METHOD.
		if ( isset( $server['REQUEST_METHOD'] ) ) {
			return $server['REQUEST_METHOD'];
		}

		// Default to GET if no method is found.
		return 'GET';
	}
}
