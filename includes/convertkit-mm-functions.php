<?php
/**
 * General plugin functions.
 *
 * @package    ConvertKit_MM
 * @author     ConvertKit
 */

/**
 * Get the setting option requested.
 *
 * @since   1.0.0
 *
 * @param   string $option_name    Option name.
 * @return  string                  Option value
 */
function convertkit_mm_get_option( $option_name ) {

	$options = get_option( CONVERTKIT_MM_NAME . '-options' );
	$option  = '';

	if ( ! empty( $options[ $option_name ] ) ) {
		$option = $options[ $option_name ];
	}

	return $option;

}

/**
 * Debug log.
 *
 * @since 	1.0.2
 *
 * @param   string $log        Log filename.
 * @param   string $message    Message to put in the log.
 */
function convertkit_mm_log( $log, $message ) {

	$debug = convertkit_mm_get_option( 'debug' );

	if ( 'on' === $debug ) {
		$log     = fopen( CONVERTKIT_MM_PATH . '/log-' . $log . '.txt', 'a+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$message = '[' . gmdate( 'd-m-Y H:i:s' ) . '] ' . $message . PHP_EOL;
		fwrite( $log, $message ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fclose( $log ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}

}
