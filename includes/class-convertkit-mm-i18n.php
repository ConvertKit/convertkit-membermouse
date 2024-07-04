<?php
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       http://www.convertkit.com
 * @since      1.0.0
 *
 * @package    ConvertKit_MM
 * @subpackage ConvertKit_MM/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    ConvertKit_MM
 * @subpackage ConvertKit_MM/includes
 * @author     Daniel Espinoza <daniel@growdevelopment.com>
 */
class ConvertKit_MM_I18n {

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		// If the .mo file for a given language is available in WP_LANG_DIR/convertkit-membermouse
		// i.e. it's available as a translation at https://translate.wordpress.org/projects/wp-plugins/convertkit-membermouse/,
		// it will be used instead of the .mo file in convertkit-membermouse/languages.
		load_plugin_textdomain( 'convertkit-mm', false, 'convertkit-membermouse/languages' );

	}

}
