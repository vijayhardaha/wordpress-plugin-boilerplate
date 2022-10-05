<?php
/**
 * Main class for plugin setup.
 *
 * @package Custom_Plugin
 */

defined( 'ABSPATH' ) || die( 'Don\'t run this file directly!' );

/**
 * Main Custom_Plugin Class.
 */
final class Custom_Plugin {

	/**
	 * This class instance.
	 *
	 * @since 1.0.0
	 * @var Custom_Plugin single instance of this class.
	 */
	private static $instance;

	/**
	 * Main Custom_Plugin Instance.
	 *
	 * Ensures only one instance of Custom_Plugin is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @return Custom_Plugin - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Custom_Plugin Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->define_constants();

		register_activation_hook( CUSTOM_PLUGIN_PLUGIN_FILE, array( $this, 'activation_check' ) );

		register_shutdown_function( array( $this, 'log_errors' ) );

		add_action( 'admin_init', array( $this, 'check_environment' ) );
		add_action( 'admin_init', array( $this, 'add_plugin_notices' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );

		// If the environment check fails, initialize the plugin.
		if ( $this->is_environment_compatible() ) {
			add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
		}
	}

	/**
	 * Cloning instances is forbidden due to singleton pattern.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, sprintf( 'You cannot clone instances of %s.', esc_html( get_class( $this ) ) ), '1.0.0' );
	}

	/**
	 * Unserializing instances is forbidden due to singleton pattern.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, sprintf( 'You cannot unserialize instances of %s.', esc_html( get_class( $this ) ) ), '1.0.0' );
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
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_data = get_plugin_data( CUSTOM_PLUGIN_PLUGIN_FILE );

		$this->define( 'CUSTOM_PLUGIN_ABSPATH', dirname( CUSTOM_PLUGIN_PLUGIN_FILE ) . '/' );
		$this->define( 'CUSTOM_PLUGIN_PLUGIN_BASENAME', plugin_basename( CUSTOM_PLUGIN_PLUGIN_FILE ) );
		$this->define( 'CUSTOM_PLUGIN_PLUGIN_NAME', $plugin_data['Name'] );
		$this->define( 'CUSTOM_PLUGIN_VERSION', $plugin_data['Version'] );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @since 1.0.0
	 * @param string      $name     Constant name.
	 * @param string|bool $value    Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * What type of request is this?
	 *
	 * @since 1.0.0
	 * @param  string $type Admin, ajax, cron or frontend.
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
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
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
	 * Load Localisation files.
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
		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else {
			$locale = is_admin() ? get_user_locale() : get_locale();
		}

		$locale = apply_filters( 'plugin_locale', $locale, 'custom-plugin' );

		unload_textdomain( 'custom-plugin' );
		load_textdomain( 'custom-plugin', WP_LANG_DIR . '/custom-plugin/custom-plugin-' . $locale . '.mo' );
		load_plugin_textdomain( 'custom-plugin', false, plugin_basename( dirname( CUSTOM_PLUGIN_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 1.0.0
	 */
	public function init_plugin() {
		if ( ! $this->plugins_compatible() ) {
			return;
		}

		// Include required files.
		$this->includes();

		// Before init action.
		do_action( 'before_custom_plugin_init' );

		// Set up localisation.
		$this->load_plugin_textdomain();

		// Init action.
		do_action( 'custom_plugin_init' );
	}

	/**
	 * Get the plugin url.
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
