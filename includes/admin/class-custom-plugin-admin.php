<?php
/**
 * Custom Plugin Admin Class.
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

if ( ! class_exists( 'Custom_Plugin_Admin' ) ) {

	/**
	 * Admin handler for the Custom Plugin – registers menus and enqueues admin assets.
	 *
	 * @since 1.0.0
	 */
	class Custom_Plugin_Admin {

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			// Add menus.
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );

			// Enqueue assets.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		}

		/**
		 * Add menu items.
		 *
		 * @since 1.0.0
		 */
		public function admin_menu(): void {
			// Add a top-level menu item.
			add_menu_page( __( 'Custom Plugin', 'custom-plugin' ), __( 'Custom Plugin', 'custom-plugin' ), 'manage_options', 'custom-plugin-page', array( $this, 'render_admin_menu_page' ), 'dashicons-wordpress', 60 );

			// Add a submenu item under the top-level menu item.
			add_submenu_page( 'custom-plugin-page', __( 'Submenu Item', 'custom-plugin' ), __( 'Submenu Item', 'custom-plugin' ), 'manage_options', 'custom-plugin-page-submenu', array( $this, 'render_submenu_page' ) );
		}

		/**
		 * Valid screen ids for plugin admin assets.
		 *
		 * @since 1.0.0
		 * @return bool
		 */
		public function is_valid_screen(): bool {
			// Get the current screen.
			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';

			// Define an array of valid screen IDs for the plugin.
			$valid_screen_ids = apply_filters(
				'custom_plugin_valid_admin_screen_ids',
				array(
					'custom-plugin-page-submenu',
					'custom-plugin-page',
				)
			);

			if ( empty( $valid_screen_ids ) ) {
				return false;
			}

			foreach ( $valid_screen_ids as $admin_screen_id ) {
				$matcher = '/' . $admin_screen_id . '/';
				if ( preg_match( $matcher, $screen_id ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Enqueue assets.
		 *
		 * @since 1.0.0
		 */
		public function enqueue_assets(): void {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			// Enqueue admin styles and scripts for valid plugin screens.
			if ( $this->is_valid_screen() ) {

				// Styles.
				wp_enqueue_style( 'custom-plugin-admin', custom_plugin()->plugin_url() . '/assets/css/admin' . $suffix . '.css', array(), CUSTOM_PLUGIN_VERSION );

				// Scripts.
				wp_enqueue_script( 'custom-plugin-admin', custom_plugin()->plugin_url() . '/assets/js/admin' . $suffix . '.js', array( 'jquery' ), CUSTOM_PLUGIN_VERSION, true );

				// Localize scripts.
				$localize_params = array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
				);
				wp_localize_script( 'custom-plugin-admin', 'custom_plugin_params', $localize_params );
			}
		}

		/**
		 * Render admin menu page.
		 *
		 * @since 1.0.0
		 */
		public function render_admin_menu_page(): void {
			?>
			<div class="wrap" id="custom-plugin-page">
				<h1><?php esc_html_e( 'Admin Menu Page', 'custom-plugin' ); ?></h1>
			</div>
			<?php
		}

		/**
		 * Render submenu page.
		 *
		 * @since 1.0.0
		 */
		public function render_submenu_page(): void {
			?>
			<div class="wrap" id="custom-plugin-page">
				<h1><?php esc_html_e( 'Submenu Page', 'custom-plugin' ); ?></h1>
			</div>
			<?php
		}
	}
}

// Create an instance of the Custom_Plugin_Admin class and return it.
return new Custom_Plugin_Admin();
