<?php
/**
 * Custom Plugin setup
 *
 * @package Custom_Plugin
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main Custom_Plugin Class.
 *
 * @class Custom_Plugin
 */
final class Custom_Plugin {
	/**
	 * The single instance of the class.
	 *
	 * @var Custom_Plugin
	 * @since 1.0.0
	 */
	protected static $instance = null;

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
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * When WP has loaded all plugins, trigger the `custom_plugin_loaded` hook.
	 *
	 * This ensures `custom_plugin_loaded` is called only after all other plugins
	 * are loaded.
	 *
	 * @since 1.0.0
	 */
	public function on_plugins_loaded() {
		do_action( 'custom_plugin_loaded' );
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		register_activation_hook( CUSTOM_PLUGIN_PLUGIN_FILE, array( $this, 'install' ) );

		register_shutdown_function( array( $this, 'log_errors' ) );

		add_action( 'admin_notices', array( $this, 'build_dependencies_notice' ) );
		add_action( 'activated_plugin', array( $this, 'activated_plugin' ) );
		add_action( 'deactivated_plugin', array( $this, 'deactivated_plugin' ) );

		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), -1 );
		add_action( 'init', array( $this, 'init' ), 0 );
	}

	/**
	 * Output a admin notice when build dependencies not met.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function build_dependencies_notice() {
		$old_php = version_compare( phpversion(), CUSTOM_PLUGIN_MIN_PHP_VERSION, '<' );
		$old_wp  = version_compare( get_bloginfo( 'version' ), CUSTOM_PLUGIN_MIN_WP_VERSION, '<' );

		// Both PHP and WordPress up to date version => no notice.
		if ( ! $old_php && ! $old_wp ) {
			return;
		}

		if ( $old_php && $old_wp ) {
			$msg = sprintf(
				/* translators: 1: Minimum PHP version 2: Recommended PHP version 3: Minimum WordPress version */
				__( 'Update required: Custom Plugin require PHP version %1$s or newer (%2$s or higher recommended) and WordPress version %3$s or newer to work properly. Please update to required version to have best experience.', 'custom-plugin' ),
				CUSTOM_PLUGIN_MIN_PHP_VERSION,
				CUSTOM_PLUGIN_BEST_PHP_VERSION,
				CUSTOM_PLUGIN_MIN_WP_VERSION
			);
		} elseif ( $old_php ) {
			$msg = sprintf(
				/* translators: 1: Minimum PHP version 2: Recommended PHP version */
				__( 'Update required: Custom Plugin require PHP version %1$s or newer (%2$s or higher recommended) to work properly. Please update to required version to have best experience.', 'custom-plugin' ),
				CUSTOM_PLUGIN_MIN_PHP_VERSION,
				CUSTOM_PLUGIN_BEST_PHP_VERSION
			);
		} elseif ( $old_wp ) {
			$msg = sprintf(
				/* translators: %s: Minimum WordPress version */
				__( 'Update required: Custom Plugin require WordPress version %s or newer to work properly. Please update to required version to have best experience.', 'custom-plugin' ),
				CUSTOM_PLUGIN_MIN_WP_VERSION
			);
		}

		echo '<div class="error"><p>' . $msg . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Install custom_plugin
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function install() {
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
				/* translators: 1: error message 2: file name and path 3: line number */
				$error_message = sprintf( __( '%1$s in %2$s on line %3$s', 'custom-plugin' ), $error['message'], $error['file'], $error['line'] ) . PHP_EOL;
				// phpcs:disable WordPress.PHP.DevelopmentFunctions
				error_log( $error_message );
				// phpcs:enable
			}
		}
	}

	/**
	 * Define WC Constants.
	 */
	private function define_constants() {
		$this->define( 'CUSTOM_PLUGIN_ABSPATH', dirname( CUSTOM_PLUGIN_PLUGIN_FILE ) . '/' );
		$this->define( 'CUSTOM_PLUGIN_PLUGIN_BASENAME', plugin_basename( CUSTOM_PLUGIN_PLUGIN_FILE ) );
		$this->define( 'CUSTOM_PLUGIN_VERSION', '1.0.0' );
		$this->define( 'CUSTOM_PLUGIN_MIN_PHP_VERSION', '5.3' );
		$this->define( 'CUSTOM_PLUGIN_BEST_PHP_VERSION', '5.6' );
		$this->define( 'CUSTOM_PLUGIN_MIN_WP_VERSION', '4.0' );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * What type of request is this?
	 *
	 * @param  string $type admin, ajax, cron or frontend.
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
	 */
	public function includes() {
		/**
		 * Core classes.
		 */
		include_once CUSTOM_PLUGIN_ABSPATH . 'includes/custom-plugin-core-functions.php';

		if ( $this->is_request( 'admin' ) ) {
			include_once CUSTOM_PLUGIN_ABSPATH . 'includes/admin/class-custom-plugin-admin.php';
		}

		if ( $this->is_request( 'frontend' ) ) {
			$this->frontend_includes();
		}
	}

	/**
	 * Include required frontend files.
	 */
	public function frontend_includes() {
		include_once CUSTOM_PLUGIN_ABSPATH . 'includes/class-custom-plugin-frontend.php';
		include_once CUSTOM_PLUGIN_ABSPATH . 'includes/custom-plugin-frontend-functions.php';
	}

	/**
	 * Init Custom_Plugin when WordPress Initialises.
	 */
	public function init() {
		// Before init action.
		do_action( 'before_custom_plugin_init' );

		// Set up localisation.
		$this->load_plugin_textdomain();

		// Init action.
		do_action( 'custom_plugin_init' );
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/custom-plugin/custom-plugin-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/custom-plugin-LOCALE.mo
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
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', CUSTOM_PLUGIN_PLUGIN_FILE ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( CUSTOM_PLUGIN_PLUGIN_FILE ) );
	}

	/**
	 * Get Ajax URL.
	 *
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}

	/**
	 * Ran when any plugin is activated.
	 *
	 * @since 1.0.0
	 * @param string $filename The filename of the activated plugin.
	 */
	public function activated_plugin( $filename ) {
		// Add you plugin activation code here.
	}

	/**
	 * Ran when any plugin is deactivated.
	 *
	 * @since 1.0.0
	 * @param string $filename The filename of the deactivated plugin.
	 */
	public function deactivated_plugin( $filename ) {
		// Add you plugin deactivation code here.
	}
}
