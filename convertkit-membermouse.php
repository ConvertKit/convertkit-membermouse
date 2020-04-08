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
 * Version:           1.1.2
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

define( 'CONVERTKIT_MM_PATH', plugin_dir_path( __FILE__ ) );
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-convertkit-mm-activator.php
 */
function activate_convertkit_mm() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-convertkit-mm-activator.php';
	ConvertKit_MM_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-convertkit-mm-deactivator.php
 */
function deactivate_convertkit_mm() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-convertkit-mm-deactivator.php';
	ConvertKit_MM_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_convertkit_mm' );
register_deactivation_hook( __FILE__, 'deactivate_convertkit_mm' );

/**
 * Helper functions
 */
require plugin_dir_path( __FILE__ ) . 'includes/convertkit-mm-functions.php';

/**
 * The core plugin class
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-convertkit-mm.php';

/**
 * Start execution of the plugin.
 *
 * @since    1.0.0
 */
function run_convertkit_mm() {

	$plugin = new ConvertKit_MM();
	$plugin->run();

}
run_convertkit_mm();