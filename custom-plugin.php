<?php
/**
 * Plugin Name: Custom Plugin
 * Plugin URI: https://github.com/vijayhardaha/
 * Description: This is a short description of your plugin.
 * Version: 1.0.0
 * Author: Vijay Hardaha
 * Author URI: https://pph.me/vijayhardaha/
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: custom-plugin
 * Domain Path: /languages/
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Tested up to: 6.9.4
 *
 * @package Custom_Plugin
 *
 * Copyright (C) 2026 Vijay Hardaha
 *
 * Licensed under the GNU General Public License v2 or later.
 * You may redistribute and/or modify this software under
 * the terms of the GPL as published by the Free Software Foundation.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * Attribution: This code is part of the ZT User Tags plugin, developed by
 *
 * ██╗   ██╗██╗     ██╗ █████╗ ██╗   ██╗    ██╗  ██╗ █████╗ ██████╗ ██████╗  █████╗ ██╗  ██╗ █████╗
 * ██║   ██║██║     ██║██╔══██╗╚██╗ ██╔╝    ██║  ██║██╔══██╗██╔══██╗██╔══██╗██╔══██╗██║  ██║██╔══██╗
 * ██║   ██║██║     ██║███████║ ╚████╔╝     ███████║███████║██████╔╝██║  ██║███████║███████║███████║
 * ╚██╗ ██╔╝██║██   ██║██╔══██║  ╚██╔╝      ██╔══██║██╔══██║██╔══██╗██║  ██║██╔══██║██╔══██║██╔══██║
 *  ╚████╔╝ ██║╚█████╔╝██║  ██║   ██║       ██║  ██║██║  ██║██║  ██║██████╔╝██║  ██║██║  ██║██║  ██║
 *   ╚═══╝  ╚═╝ ╚════╝ ╚═╝  ╚═╝   ╚═╝       ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═╝╚═════╝ ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═╝
 */

// Exit if undefined.
defined( 'ABSPATH' ) || exit;

if ( ! defined( 'CUSTOM_PLUGIN_PLUGIN_FILE' ) ) {
	// Define the plugin file path constant if not already defined.
	define( 'CUSTOM_PLUGIN_PLUGIN_FILE', __FILE__ );
	define( 'CUSTOM_PLUGIN_PLUGIN_VERSION', '1.0.1' );
	define( 'CUSTOM_PLUGIN_PLUGIN_SLUG', 'custom-plugin' );
	define( 'CUSTOM_PLUGIN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	define( 'CUSTOM_PLUGIN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
	define( 'CUSTOM_PLUGIN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

// Include the main Custom_Plugin class if it's not already defined.
if ( ! class_exists( 'Custom_Plugin', false ) ) {
	include_once __DIR__ . '/includes/class-custom-plugin.php';
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

/**
 * Global variable for backwards compatibility.
 *
 * @global Custom_Plugin $custom_plugin Global reference to the plugin instance.
 */
$GLOBALS['custom_plugin'] = custom_plugin();
