<?php
/**
 * Compatibility Check for Old WordPress/PHP Versions
 *
 * @package Email Scheduler
 * @since 1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_notices', 'simesc_version_notice' );

/**
 * Display admin notice for incompatible WordPress/PHP versions.
 *
 * @since 1.0.0
 */
function simesc_version_notice() {
	/* translators: 1: WordPress version, 2: PHP version */
	$message = sprintf(
		/* translators: 1: WordPress version, 2: PHP version */
		__( 'Simple Email Scheduler requires WordPress %1$s or higher and PHP %2$s or higher. Please update your WordPress and PHP versions.', 'simple-email-scheduler' ),
		SIMESC_MIN_WP,
		SIMESC_MIN_PHP
	);
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html( $message )
	);
}














