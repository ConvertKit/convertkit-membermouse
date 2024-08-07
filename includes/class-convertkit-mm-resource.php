<?php
/**
 * ConvertKit Resource class.
 *
 * @package CKWC
 * @author ConvertKit
 */

/**
 * Abstract class defining variables and functions for a ConvertKit API Resource
 * (forms, sequences, tags).
 *
 * @since   1.3.0
 */
class ConvertKit_MM_Resource extends ConvertKit_Resource_V4 {

	/**
	 * Constructor.
	 *
	 * @since   1.3.0
	 */
	public function __construct() {

		// Initialize the API if the Access Token has been defined in the Plugin Settings.
		$settings = new ConvertKit_MM_Settings();
		if ( $settings->has_access_and_refresh_token() ) {
			$this->api = new ConvertKit_API_V4(
				CONVERTKIT_MM_OAUTH_CLIENT_ID,
				CONVERTKIT_MM_OAUTH_CLIENT_REDIRECT_URI,
				$settings->get_access_token(),
				$settings->get_refresh_token(),
				$settings->debug_enabled()
			);
		}

		// Call parent initialization function.
		parent::init();

	}

}
