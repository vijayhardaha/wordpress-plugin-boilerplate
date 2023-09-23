<?php
/**
 * Plugin Name: Custom Plugin
 * Plugin URI: https://github.com/vijayhardaha/
 * Description: This is a short description of your plugin.
 * Version: 1.0.0
 * Author: Vijay Hardaha
 * Author URI: https://twitter.com/vijayhardaha/
 * License: GPLv2+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: custom-plugin
 * Domain Path: /languages/
 * Requires at least: 5.8
 * Requires PHP: 7.0
 * Tested up to: 6.0
 *
 * @package Custom_Plugin
 */

// Prevent direct access to this file.
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! defined( 'CUSTOM_PLUGIN_PLUGIN_FILE' ) ) {
	// Define the plugin file path constant if not already defined.
	define( 'CUSTOM_PLUGIN_PLUGIN_FILE', __FILE__ );
}

// Include the main Custom_Plugin class if it's not already defined.
if ( ! class_exists( 'Custom_Plugin', false ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-custom-plugin.php';
}

/**
 * Returns the main instance of Custom_Plugin.
 *
 * @since 1.0.0
 * @return Custom_Plugin The main Custom_Plugin instance.
 */
function custom_plugin() {
	return Custom_Plugin::instance();
}

// Global variable for backwards compatibility.
$GLOBALS['custom_plugin'] = custom_plugin();
