<?php



/**
 * Get the setting option requested.
 *
 * @since   1.0.0
 * @param   $option_name
 * @return  string $option
 */
function convertkit_mm_get_option( $option_name ) {

	$options = get_option( 'convertkit-mm-options' );
	$option = '';

	if ( ! empty( $options[ $option_name ] ) ) {
		$option = $options[ $option_name ];
	}

	return $option;
}

/**
 * Debug log.
 *
 * @since 1.0.2
 * @param string $message Message to put in the log.
 */
function convertkit_mm_log( $log, $message ) {
	$debug = convertkit_mm_get_option( 'debug' );

	if ( 'on' === $debug ) {
		$log     = fopen( CONVERTKIT_MM_PATH . '/log-' . $log . '.txt', 'a+' );
		$message = '[' . date( 'd-m-Y H:i:s' ) . '] ' . $message . PHP_EOL;
		fwrite( $log, $message );
		fclose( $log );
	}

}
