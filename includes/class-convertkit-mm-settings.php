<?php
/**
 * ConvertKit Plugin Settings class.
 *
 * @package ConvertKit_MM
 * @author ConvertKit
 */

/**
 * Class to read ConvertKit Plugin Settings.
 *
 * @since   1.2.2
 */
class ConvertKit_MM_Settings {

	/**
	 * Holds the Settings Key that stores site wide ConvertKit settings
	 *
	 * @since 	1.2.2
	 *
	 * @var     string
	 */
	const SETTINGS_NAME = 'convertkit-mm-options';

	/**
	 * Holds the Settings
	 *
	 * @since 	1.2.2
	 *
	 * @var     array
	 */
	private $settings = array();

	/**
	 * Constructor. Reads settings from options table, falling back to defaults
	 * if no settings exist.
	 *
	 * @since   1.2.2
	 */
	public function __construct() {

		// Get Settings.
		$settings = get_option( self::SETTINGS_NAME );

		// If no Settings exist, falback to default settings.
		if ( ! $settings ) {
			$this->settings = $this->get_defaults();
		} else {
			$this->settings = array_merge( $this->get_defaults(), $settings );
		}

	}

	/**
	 * Returns Plugin settings.
	 *
	 * @since   1.2.2
	 *
	 * @return  array
	 */
	public function get() {

		return $this->settings;

	}

	/**
	 * Returns the API Key Plugin setting.
	 *
	 * @since   1.2.2
	 *
	 * @return  string
	 */
	public function get_api_key() {

		// Return API Key from settings.
		return $this->settings['api_key'];

	}

	/**
	 * Returns whether the API Key has been set in the Plugin settings.
	 *
	 * @since   1.2.2
	 *
	 * @return  bool
	 */
	public function has_api_key() {

		return ( ! empty( $this->get_api_key() ) ? true : false );

	}

	/**
	 * Returns the mapping setting for the given MemberMouse Membership Level ID.
	 * 
	 * @since 	1.2.2
	 * 
	 * @param 	int 	$id 	Membership Level ID.
	 * @return 	string 			Setting
	 */
	public function get_membership_level_mapping( $id ) {

		if ( ! array_key_exists( 'convertkit-mapping-' . $key, $this->settings ) ) {
			return '';
		}

		return $this->settings[ 'convertkit-mapping-' . $key ];

	}

	/**
	 * Returns the mapping setting for the given MemberMouse Product ID.
	 * 
	 * @since 	1.2.2
	 * 
	 * @param 	int 	$id 	Membership Level ID.
	 * @return 	string 			Setting
	 */
	public function get_product_mapping( $id ) {

		if ( ! array_key_exists( 'convertkit-mapping-product-' . $key, $this->settings ) ) {
			return '';
		}

		return $this->settings[ 'convertkit-mapping-product-' . $key ];

	}

	/**
	 * Returns the mapping setting for the given MemberMouse Bundle ID.
	 * 
	 * @since 	1.2.2
	 * 
	 * @param 	int 	$id 	Membership Level ID.
	 * @return 	string 			Setting
	 */
	public function get_bundle_mapping( $id ) {

		if ( ! array_key_exists( 'convertkit-mapping-bundle-' . $key, $this->settings ) ) {
			return '';
		}

		return $this->settings[ 'convertkit-mapping-bundle-' . $key ];

	}

	/**
	 * The default settings, used when the ConvertKit Plugin Settings haven't been saved
	 * e.g. on a new installation.
	 *
	 * @since   1.2.2
	 *
	 * @return  array
	 */
	public function get_defaults() {

		$defaults = array(
			'api_key'         => '', // string.
		);

		/**
		 * The default settings, used when the ConvertKit Plugin Settings haven't been saved
		 * e.g. on a new installation.
		 *
		 * @since   1.2.2
		 *
		 * @param   array   $defaults   Default Settings.
		 */
		$defaults = apply_filters( 'convertkit_settings_get_defaults', $defaults );

		return $defaults;

	}

	/**
	 * Saves the given array of settings to the WordPress options table.
	 *
	 * @since   1.2.2
	 *
	 * @param   array $settings   Settings.
	 */
	public function save( $settings ) {

		update_option( self::SETTINGS_NAME, array_merge( $this->get(), $settings ) );

		// Reload settings in class, to reflect changes.
		$this->settings = get_option( self::SETTINGS_NAME );

	}

}
