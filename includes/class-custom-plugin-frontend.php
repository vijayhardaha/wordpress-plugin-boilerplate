<?php
/**
 * Custom Plugin Frontend
 *
 * @class Custom_Plugin_Frontend
 * @package Custom_Plugin
 * @subpackage Custom_Plugin/Frontend
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'Custom_Plugin_Frontend' ) ) {
	return new Custom_Plugin_Frontend();
}

/**
 * Custom_Plugin_Frontend class.
 */
class Custom_Plugin_Frontend {
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Includes files.
		add_action( 'init', array( $this, 'includes' ) );

		// Enqueue scripts.
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
	}

	/**
	 * Include any classes/functions we need within frontend.
	 *
	 * @since 1.0.0
	 */
	public function includes() {
		// Include your required frontend files here.
	}

	/**
	 * Enqueue styles.
	 */
	public function frontend_styles() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register frontend styles.
		wp_register_style( 'custom-plugin-frontend-styles', custom_plugin()->plugin_url() . '/assets/css/frontend' . $suffix . '.css', array(), CUSTOM_PLUGIN_VERSION );

		// Enqueue frontend styles.
		wp_enqueue_style( 'custom-plugin-frontend-styles' );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.0.0
	 */
	public function frontend_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register scripts.
		wp_register_script( 'custom-plugin-frontend', custom_plugin()->plugin_url() . '/assets/js/frontend' . $suffix . '.js', array( 'jquery' ), CUSTOM_PLUGIN_VERSION, true );

		// Enqueue frontend scripts.
		wp_enqueue_script( 'custom-plugin-frontend' );
		$params = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		);
		wp_localize_script( 'custom-plugin-frontend', 'custom_plugin_params', $params );
	}
}

return new Custom_Plugin_Frontend();
