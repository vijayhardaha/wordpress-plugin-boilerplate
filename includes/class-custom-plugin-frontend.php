<?php
/**
 * Custom Plugin Frontend Class.
 *
 * @version 1.0.0
 * @package Custom_Plugin
 * @author Vijay Hardaha <https://pph.me/vijayhardaha/>
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
 * Attribution: This code is part of the Custom Plugin plugin, developed by
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

if ( class_exists( 'Custom_Plugin_Frontend' ) ) {

	/**
	 * Custom_Plugin_Frontend Class.
	 *
	 * @since 1.0.0
	 */
	class Custom_Plugin_Frontend {

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			// Add action to enqueue assets when WordPress loads scripts/styles.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		}

		/**
		 * Enqueue assets.
		 *
		 * @since 1.0.0
		 */
		public function enqueue_assets(): void {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			// Enqueue custom styles.
			wp_enqueue_style( 'custom-plugin-frontend', custom_plugin()->plugin_url() . '/assets/css/frontend' . $suffix . '.css', array(), CUSTOM_PLUGIN_VERSION );

			// Enqueue custom scripts.
			wp_enqueue_script( 'custom-plugin-frontend', custom_plugin()->plugin_url() . '/assets/js/frontend' . $suffix . '.js', array( 'jquery' ), CUSTOM_PLUGIN_VERSION, true );

			// Localize scripts with custom parameters.
			$localize_params = array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			);
			wp_localize_script( 'custom-plugin-frontend', 'custom_plugin_params', $localize_params );
		}
	}
}

// Create an instance of the Custom_Plugin_Frontend class and return it.
return new Custom_Plugin_Frontend();
