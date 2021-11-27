<?php
/**
 * Plugin Name: Custom Plugin
 * Plugin URI: https://github.com/vijayhardaha/
 * Description: This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version: 1.0.0
 * Author: Vijay Hardaha
 * Author URI: https://twitter.com/vijayhardaha/
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: custom-plugin
 * Domain Path: /languages/
 * Requires at least: 5.6
 * Requires PHP: 7.0
 *
 * @package Custom_Plugin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'get_plugin_data' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( ! defined( 'CUSTOM_PLUGIN_PLUGIN_FILE' ) ) {
	define( 'CUSTOM_PLUGIN_PLUGIN_FILE', __FILE__ );
}

// Include the main Custom_Plugin class.
if ( ! class_exists( 'Custom_Plugin', false ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-custom-plugin.php';
}

/**
 * Returns the main instance of Custom_Plugin.
 *
 * @since  1.0.0
 * @return Custom_Plugin
 */
function Custom_Plugin() { // @codingStandardsIgnoreLine
	return Custom_Plugin::instance();
}

// Global for backwards compatibility.
$GLOBALS['Custom_Plugin'] = Custom_Plugin();
