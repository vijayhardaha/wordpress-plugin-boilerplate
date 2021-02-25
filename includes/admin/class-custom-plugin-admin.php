<?php
/**
 * Custom Plugin Admin
 *
 * @class Custom_Plugin_Admin
 * @package Custom_Plugin
 * @subpackage Custom_Plugin/Admin
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'Custom_Plugin_Admin' ) ) {
	return new Custom_Plugin_Admin();
}

/**
 * Custom_Plugin_Admin class.
 */
class Custom_Plugin_Admin {
	/**
	 * Constructor.
	 */
	public function __construct() {
		// Includes files.
		add_action( 'init', array( $this, 'includes' ) );

		// Add menus.
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		// Enqueue scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
	}

	/**
	 * Include any classes/functions we need within admin.
	 */
	public function includes() {
		// Include your required backend files here.
	}

	/**
	 * Add menu items.
	 */
	public function admin_menu() {
		add_menu_page( __( 'Custom Plugin Page Title', 'custom-plugin' ), __( 'Custom Plugin Menu Title', 'custom-plugin' ), 'manage_options', 'custom-plugin-page', array( $this, 'admin_menu_page' ), 'dashicons-screenoptions', '60' );
		add_submenu_page( 'custom-plugin-page', __( 'Settings', 'custom-plugin' ), __( 'Settings', 'custom-plugin' ), 'manage_options', 'custom-plugin-page-settings', array( $this, 'settings_menu_page' ) );
	}

	/**
	 * Valid screen ids for plugin scripts & styles
	 *
	 * @return  array
	 */
	public function is_valid_screen() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		$valid_screen_ids = apply_filters(
			'custom_plugin_valid_admin_screen_ids',
			array(
				'custom-plugin-page-settings',
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
	 * Enqueue styles.
	 */
	public function admin_styles() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register admin styles.
		wp_register_style( 'custom-plugin-admin-styles', custom_plugin()->plugin_url() . '/assets/css/admin' . $suffix . '.css', array(), CUSTOM_PLUGIN_VERSION );

		// Admin styles for custom_plugin pages only.
		if ( $this->is_valid_screen() ) {
			wp_enqueue_style( 'custom-plugin-admin-styles' );
		}
	}

	/**
	 * Enqueue scripts.
	 */
	public function admin_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register scripts.
		wp_register_script( 'custom-plugin-admin', custom_plugin()->plugin_url() . '/assets/js/admin' . $suffix . '.js', array( 'jquery' ), CUSTOM_PLUGIN_VERSION, true );

		// Admin scripts for custom_plugin pages only.
		if ( $this->is_valid_screen() ) {
			wp_enqueue_script( 'custom-plugin-admin' );
			$params = array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			);
			wp_localize_script( 'custom-plugin-admin', 'custom_plugin_params', $params );
		}
	}

	/**
	 * Display admin page
	 */
	public function admin_menu_page() {
		?>
		<div class="wrap" id="custom-plugin">
			<h2><?php esc_html_e( 'Page title', 'custom-plugin' ); ?></h2>
		</div>
		<?php
	}

	/**
	 * Display settings page
	 */
	public function settings_menu_page() {
		?>
		<div class="wrap" id="custom-plugin">
			<h2><?php esc_html_e( 'Settings', 'custom-plugin' ); ?></h2>
		</div>
		<?php
	}
}

return new Custom_Plugin_Admin();
