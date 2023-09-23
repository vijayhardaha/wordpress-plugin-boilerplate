<?php
/**
 * Custom Plugin Frontend Class.
 *
 * @package Custom_Plugin
 */

// Prevent direct access to this file.
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( class_exists( 'Custom_Plugin_Frontend' ) ) {
	// The class already exists, so return an instance of it.
	return new Custom_Plugin_Frontend();
}

/**
 * Custom_Plugin_Frontend Class.
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
	public function enqueue_assets() {
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

// Create an instance of the Custom_Plugin_Frontend class and return it.
return new Custom_Plugin_Frontend();
