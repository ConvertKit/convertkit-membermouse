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
		return $this->settings['api-key'];

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

		return $this->get_mapping( $id, 'level' );

	}

	/**
	 * Returns the mapping setting for the given MemberMouse Membership Level ID
	 * when the Level is removed from the User.
	 * 
	 * @since 	1.2.2
	 * 
	 * @param 	int 	$id 	Membership Level ID.
	 * @return 	string 			Setting
	 */
	public function get_membership_level_cancellation_mapping( $id ) {

		return $this->get_mapping( $id, 'level', true );

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

		return $this->get_mapping( $id, 'product' );

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

		return $this->get_mapping( $id, 'bundle' );

	}

	/**
	 * Returns the mapping setting for the given MemberMouse Bundle ID
	 * when the Bundle is removed from the User.
	 * 
	 * @since 	1.2.2
	 * 
	 * @param 	int 	$id 	Bundle ID.
	 * @return 	string 			Setting
	 */
	public function get_bundle_cancellation_mapping( $id ) {

		return $this->get_mapping( $id, 'bundle', true );

	}

	/**
	 * Returns the mapping setting for the given MemberMouse resource ID, type
	 * and whether the mapping is for the 'cancellation'.
	 * 
	 * @since 	1.2.2
	 * 
	 * @param 	int 	$id 						Level, Product or Bundle ID.
	 * @param 	string  $type 						Mapping type (level,bundle,product).
	 * @param 	bool 	$is_cancellation_mapping 	If the mapping setting is for the 'cancel' action.
	 * @return 	string 								Setting
	 */
	private function get_mapping( $id, $type = 'level', $is_cancellation_mapping = false ) {

		// Build key we're looking for in the array of settings.
		// Membership levels are stored as `convertkit-mapping-ID`, so don't append the type in this instance.
		$key = 'convertkit-mapping-' . ( $type !== 'level' ? $type . '-' : '' ) . $id;

		// If requesting a 'cancel' setting, append this to the key.
		// Products don't support cancellation, so ignore this flag if it's for a product.
		if ( $is_cancellation_mapping && $type !== 'product' ) {
			$key .= '-cancel';
		}

		// Return a blank string if the setting key doesn't exist i.e. settings were not saved
		// or the level, product or bundle was added to MemberMouse but we don't yet have a setting
		// mapped for it.
		if ( ! array_key_exists( $key, $this->settings ) ) {
			return '';
		}

		return $this->settings[ $key ];

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
			'api-key'         => '', // string.
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
