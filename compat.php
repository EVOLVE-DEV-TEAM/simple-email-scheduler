<?php
/**
 * PHP 8 Compatibility Functions
 *
 * @package Email Scheduler
 * @since 1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'str_starts_with' ) ) {
	/**
	 * Polyfill for str_starts_with() function added in PHP 8.0.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle The substring to search for in the haystack.
	 * @return bool True if haystack begins with needle, false otherwise.
	 */
	function str_starts_with( $haystack, $needle ) {
		return (string) $needle !== '' && strncmp( $haystack, $needle, strlen( $needle ) ) === 0;
	}
}

if ( ! function_exists( 'str_ends_with' ) ) {
	/**
	 * Polyfill for str_ends_with() function added in PHP 8.0.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle The substring to search for in the haystack.
	 * @return bool True if haystack ends with needle, false otherwise.
	 */
	function str_ends_with( $haystack, $needle ) {
		return $needle !== '' && substr( $haystack, -strlen( $needle ) ) === (string) $needle;
	}
}

if ( ! function_exists( 'str_contains' ) ) {
	/**
	 * Polyfill for str_contains() function added in PHP 8.0.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle The substring to search for in the haystack.
	 * @return bool True if haystack contains needle, false otherwise.
	 */
	function str_contains( $haystack, $needle ) {
		return '' !== $needle && false !== strpos( $haystack, $needle );
	}
}














