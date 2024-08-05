<?php
/**
 * General plugin functions.
 *
 * @package    ConvertKit_MM
 * @author     ConvertKit
 */

/**
 * Debug log.
 *
 * @since   1.0.2
 *
 * @param   string $log        Log filename.
 * @param   string $message    Message to put in the log.
 */
function convertkit_mm_log( $log, $message ) {

	// Initialize settings class.
	$settings = new ConvertKit_MM_Settings();

	// Bail if debugging isn't enabled.
	if ( ! $settings->debug_enabled() ) {
		return;
	}

	// Write to log.
	$log     = fopen( CONVERTKIT_MM_PATH . '/log-' . $log . '.txt', 'a+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$message = '[' . gmdate( 'd-m-Y H:i:s' ) . '] ' . $message . PHP_EOL;
	fwrite( $log, $message ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fclose( $log ); // phpcs:ignore WordPress.WP.AlternativeFunctions

}
