<?php
/**
 * Main class for setting up the Custom Plugin.
 *
 * @package Custom_Plugin
 */

// Prevent direct access to this file.
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Main Custom_Plugin Class.
 */
final class Custom_Plugin {

	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * This class instance.
	 *
	 * @since 1.0.0
	 * @var Custom_Plugin Single instance of this class.
	 */
	private static $instance;

	/**
	 * Main Custom_Plugin Instance.
	 *
	 * Ensures only one instance of Custom_Plugin is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @return Custom_Plugin Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Cloning instances is forbidden due to the singleton pattern.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, sprintf( 'You cannot clone instances of %s.', esc_html( get_class( $this ) ) ), '1.0.0' );
	}

	/**
	 * Unserializing instances is forbidden due to the singleton pattern.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, sprintf( 'You cannot unserialize instances of %s.', esc_html( get_class( $this ) ) ), '1.0.0' );
	}

	/**
	 * Custom_Plugin Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		register_shutdown_function( array( $this, 'log_errors' ) );

		add_action( 'init', array( $this, 'init' ), 0 );
	}

	/**
	 * Ensures fatal errors are logged so they can be picked up in the status report.
	 *
	 * @since 1.0.0
	 */
	public function log_errors() {
		$error = error_get_last();
		if ( $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ), true ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
				/* translators: 1: Error Message 2: File Name and Path 3: Line Number */
				$error_message = sprintf( __( '%1$s in %2$s on line %3$s', 'custom-plugin' ), $error['message'], $error['file'], $error['line'] ) . PHP_EOL;
				// phpcs:disable WordPress.PHP.DevelopmentFunctions
				error_log( $error_message );
				// phpcs:enable WordPress.PHP.DevelopmentFunctions
			}
		}
	}

	/**
	 * Define constants.
	 *
	 * @since 1.0.0
	 */
	private function define_constants() {
		$this->define( 'CUSTOM_PLUGIN_ABSPATH', dirname( CUSTOM_PLUGIN_PLUGIN_FILE ) . '/' );
		$this->define( 'CUSTOM_PLUGIN_PLUGIN_BASENAME', plugin_basename( CUSTOM_PLUGIN_PLUGIN_FILE ) );
		$this->define( 'CUSTOM_PLUGIN_VERSION', $this->version );
	}

	/**
	 * Define a constant if it's not already set.
	 *
	 * @since 1.0.0
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Returns true if the request is a non-legacy REST API request.
	 *
	 * Legacy REST requests should still run some extra code for backward compatibility.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_rest_api_request() {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$rest_prefix         = trailingslashit( rest_get_url_prefix() );
		$is_rest_api_request = ( false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix ) ); // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		/**
		 * Whether this is a REST API request.
		 *
		 * @since 1.0.0
		 */
		return apply_filters( 'custom_plugin_is_rest_api_request', $is_rest_api_request );
	}

	/**
	 * Check what type of request this is.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type admin, ajax, cron, or frontend.
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! $this->is_rest_api_request();
		}
	}

	/**
	 * Include required core files used in both admin and on the frontend.
	 *
	 * @since 1.0.0
	 */
	public function includes() {
		// Core files.
		include_once CUSTOM_PLUGIN_ABSPATH . 'includes/custom-plugin-core-functions.php';

		// Admin files.
		if ( $this->is_request( 'admin' ) ) {
			include_once CUSTOM_PLUGIN_ABSPATH . 'includes/admin/class-custom-plugin-admin.php';
		}

		// Frontend files.
		if ( $this->is_request( 'frontend' ) ) {
			$this->frontend_includes();
		}
	}

	/**
	 * Include required frontend files.
	 *
	 * @since 1.0.0
	 */
	public function frontend_includes() {
		include_once CUSTOM_PLUGIN_ABSPATH . 'includes/class-custom-plugin-frontend.php';
		include_once CUSTOM_PLUGIN_ABSPATH . 'includes/custom-plugin-frontend-functions.php';
	}

	/**
	 * Load localization files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/custom-plugin/custom-plugin-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/custom-plugin-LOCALE.mo
	 *
	 * @since 1.0.0
	 */
	public function load_plugin_textdomain() {
		$locale = determine_locale();

		/**
		 * Filter to adjust the Custom Plugin locale to use for translations.
		 *
		 * @since 1.0.0
		 */
		$locale = apply_filters( 'plugin_locale', $locale, 'custom-plugin' );

		unload_textdomain( 'custom-plugin' );
		load_textdomain( 'custom-plugin', WP_LANG_DIR . '/custom-plugin/custom-plugin-' . $locale . '.mo' );
		load_plugin_textdomain( 'custom-plugin', false, plugin_basename( dirname( CUSTOM_PLUGIN_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Initialize when WordPress initializes.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Before init action.
		do_action( 'before_custom_plugin_init' );

		// Set up localization.
		$this->load_plugin_textdomain();

		// Init action.
		do_action( 'custom_plugin_init' );
	}

	/**
	 * Get the plugin URL.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', CUSTOM_PLUGIN_PLUGIN_FILE ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( CUSTOM_PLUGIN_PLUGIN_FILE ) );
	}

	/**
	 * Get Ajax URL.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}
}
