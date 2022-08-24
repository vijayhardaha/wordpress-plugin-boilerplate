<?php
/**
 * Custom Plugin Frontend Class.
 *
 * @since 1.0.0
 * @package Custom_Plugin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'Custom_Plugin_Frontend' ) ) {
	return new Custom_Plugin_Frontend();
}

/**
 * Custom_Plugin_Frontend Class.
 *
 * @class Custom_Plugin_Frontend
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

		// Enqueue assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Include classes/functions files that we need on frontend.
	 *
	 * @since 1.0.0
	 */
	public function includes() {
		// Include your required frontend files here.
	}

	/**
	 * Enqueue assets.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_assets() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Styles.
		wp_enqueue_style( 'custom-plugin-frontend-styles', custom_plugin()->plugin_url() . '/assets/css/frontend' . $suffix . '.css', array(), CUSTOM_PLUGIN_VERSION );

		// Scripts.
		wp_enqueue_script( 'custom-plugin-frontend', custom_plugin()->plugin_url() . '/assets/js/frontend' . $suffix . '.js', array( 'jquery' ), CUSTOM_PLUGIN_VERSION, true );

		// Localize scripts.
		$localize_params = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		);
		wp_localize_script( 'custom-plugin-frontend', 'custom_plugin_params', $localize_params );
	}
}

return new Custom_Plugin_Frontend();
