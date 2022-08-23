<?php
/**
 * Plugin Name: Custom Plugin
 * Plugin URI: https://github.com/vijayhardaha/
 * Description: This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version: 1.0.0
 * Author: Vijay Hardaha
 * Author URI: https://twitter.com/vijayhardaha/
 * Text Domain: custom-plugin
 * Domain Path: /languages/
 * Requires at least: 5.4
 * Requires PHP: 5.6
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
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
 * @since 1.0.0
 * @return Custom_Plugin
 */
function custom_plugin() {
	return Custom_Plugin::instance();
}

// Global for backwards compatibility.
$GLOBALS['custom_plugin'] = custom_plugin();
