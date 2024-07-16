<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://www.convertkit.com
 * @since             1.0.0
 * @package           ConvertKit_MM
 *
 * @wordpress-plugin
 * Plugin Name:       ConvertKit MemberMouse Integration
 * Plugin URI:        http://www.convertkit.com
 * Description:       This plugin integrates ConvertKit with MemberMouse.
 * Version:           1.2.1
 * Author:            ConvertKit
 * Author URI:        https://convertkit.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       convertkit-mm
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define ConverKit Plugin paths and version number.
define( 'CONVERTKIT_MM_NAME', 'convertkit-mm' ); // Used for settings.
define( 'CONVERTKIT_MM_FILE', plugin_basename( __FILE__ ) );
define( 'CONVERTKIT_MM_URL', plugin_dir_url( __FILE__ ) );
define( 'CONVERTKIT_MM_PATH', plugin_dir_path( __FILE__ ) );
define( 'CONVERTKIT_MM_VERSION', '1.2.1' );

// Load plugin files that are always required.
require CONVERTKIT_MM_PATH . 'includes/class-convertkit-mm-actions.php';
require CONVERTKIT_MM_PATH . 'includes/class-convertkit-mm-api.php';
require CONVERTKIT_MM_PATH . 'includes/class-convertkit-mm.php';
require CONVERTKIT_MM_PATH . 'includes/convertkit-mm-functions.php';

// Load files that are only used in the WordPress Administration interface.
if ( is_admin() ) {
	require CONVERTKIT_MM_PATH . 'admin/class-convertkit-mm-admin.php';
}

/**
 * Main function to return Plugin instance.
 *
 * @since   1.2.0
 */
function ConvertKit_MM() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName

	return ConvertKit_MM::get_instance();

}

// Finally, initialize the Plugin.
ConvertKit_MM();
