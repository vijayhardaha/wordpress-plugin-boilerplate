<?php
/**
 * Main class of Custom Plugin.
 *
 * @class Custom_Plugin
 * @package Custom_Plugin
 * @subpackage Custom_Plugin\Classes
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
	 * This class instance.
	 *
	 * @var Custom_Plugin single instance of this class.
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * Admin notices to add.
	 *
	 * @var array Array of admin notices.
	 * @since 1.0.0
	 */
	private $notices = array();

	/**
	 * Required plugins to check.
	 *
	 * @var array Array of required plugins.
	 * @since 1.0.0
	 */
	private $required_plugins = array(
		'woocommerce/woocommerce.php'       => array(
			'url'  => 'https://wordpress.org/plugins/woocommerce/',
			'name' => 'WooCommerce',
		),
		'classic-editor/classic-editor.php' => array(
			'url'  => 'https://wordpress.org/plugins/classic-editor/',
			'name' => 'Classic Editor',
		),
	);

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
				/* translators: 1: error message 2: file name and path 3: line number */
				$error_message = sprintf( __( '%1$s in %2$s on line %3$s', 'custom-plugin' ), $error['message'], $error['file'], $error['line'] ) . PHP_EOL;
				// phpcs:disable WordPress.PHP.DevelopmentFunctions
				error_log( $error_message );
				// phpcs:enable WordPress.PHP.DevelopmentFunctions
			}
		}
	}

	/**
	 * Define WC Constants.
	 *
	 * @since 1.0.0
	 */
	private function define_constants() {
		$plugin_data = get_plugin_data( CUSTOM_PLUGIN_PLUGIN_FILE );
		$this->define( 'CUSTOM_PLUGIN_ABSPATH', dirname( CUSTOM_PLUGIN_PLUGIN_FILE ) . '/' );
		$this->define( 'CUSTOM_PLUGIN_PLUGIN_BASENAME', plugin_basename( CUSTOM_PLUGIN_PLUGIN_FILE ) );
		$this->define( 'CUSTOM_PLUGIN_PLUGIN_NAME', $plugin_data['Name'] );
		$this->define( 'CUSTOM_PLUGIN_VERSION', $plugin_data['Version'] );
		$this->define( 'CUSTOM_PLUGIN_MIN_PHP_VERSION', $plugin_data['RequiresPHP'] );
		$this->define( 'CUSTOM_PLUGIN_MIN_WP_VERSION', $plugin_data['RequiresWP'] );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name     Constant name.
	 * @param string|bool $value    Constant value.
	 *
	 * @since 1.0.0
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
	 *
	 * @param string $type  Admin, ajax, cron or frontend.
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
	 * Checks the server environment and other factors and deactivates plugins as necessary.
	 *
	 * Based on http://wptavern.com/how-to-prevent-wordpress-plugins-from-activating-on-sites-with-incompatible-hosting-environments
	 *
	 * @since 1.0.0
	 */
	public function activation_check() {
		if ( ! $this->is_environment_compatible() ) {
			$this->deactivate_plugin();
			wp_die(
				sprintf(
					/* translators: %s Plugin Name */
					esc_html__(
						'%1$s could not be activated. %2$s',
						'custom-plugin'
					),
					esc_html( CUSTOM_PLUGIN_PLUGIN_NAME ),
					esc_html( $this->get_environment_message() )
				)
			);
		}
	}

	/**
	 * Checks the environment on loading WordPress, just in case the environment changes after activation.
	 *
	 * @since 1.0.0
	 */
	public function check_environment() {
		if ( ! $this->is_environment_compatible() && is_plugin_active( CUSTOM_PLUGIN_PLUGIN_BASENAME ) ) {
			$this->deactivate_plugin();
			$this->add_admin_notice(
				'bad_environment',
				'error',
				sprintf(
					/* translators: %s Plugin Name */
					__( '%s has been deactivated.', 'custom-plugin' ),
					CUSTOM_PLUGIN_PLUGIN_NAME
				) . ' ' . $this->get_environment_message()
			);
		}
	}

	/**
	 * Adds notices for out-of-date WordPress and/or WooCommerce versions.
	 *
	 * @since 1.0.0
	 */
	public function add_plugin_notices() {
		if ( ! $this->is_wp_compatible() ) {
			$this->add_admin_notice(
				'update_wordpress',
				'error',
				sprintf(
					/* translators: 1: Plugin Name 2: Minimum WP Version 3: Update Url */
					__( '%1$s requires WordPress version %2$s or higher. Please %3$supdate WordPress &raquo;%4$s', 'custom-plugin' ),
					CUSTOM_PLUGIN_PLUGIN_NAME,
					CUSTOM_PLUGIN_MIN_WP_VERSION,
					'<a href="' . esc_url( admin_url( 'update-core.php' ) ) . '">',
					'</a>'
				)
			);
		}

		$missing_dependencies = $this->missing_dependencies();
		if ( ! empty( $missing_dependencies ) ) {
			$this->add_admin_notice(
				'install_required_plugins',
				'error',
				sprintf(
					/* translators: 1: Plugin Name 2: Required Plugins Names */
					__( '%1$s  is enabled but not effective. It requires %2$s in order to work.', 'custom-plugin' ),
					CUSTOM_PLUGIN_PLUGIN_NAME,
					join( ', ', $missing_dependencies )
				)
			);
		}
	}

	/**
	 * Determines if the required plugins are compatible.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function plugins_compatible() {
		return $this->is_wp_compatible() && empty( $this->missing_dependencies() );
	}

	/**
	 * Find the missing dependency plugins names.
	 *
	 * @since 1.0.0
	 * @return Array
	 */
	private function missing_dependencies() {
		$missing_dependencies = array();
		if ( empty( $this->required_plugins ) ) {
			return $missing_dependencies;
		}

		foreach ( $this->required_plugins as $plugin_base => $plugin ) {
			if ( ! is_plugin_active( $plugin_base ) ) {
				$missing_dependencies[] = sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( $plugin['url'] ), $plugin['name'] );
			}
		}

		return $missing_dependencies;
	}

	/**
	 * Determines if the WordPress compatible.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function is_wp_compatible() {
		if ( ! CUSTOM_PLUGIN_MIN_WP_VERSION ) {
			return true;
		}
		return version_compare( get_bloginfo( 'version' ), CUSTOM_PLUGIN_MIN_WP_VERSION, '>=' );
	}

	/**
	 * Deactivates the plugin.
	 *
	 * @since 1.0.0
	 */
	protected function deactivate_plugin() {
		deactivate_plugins( CUSTOM_PLUGIN_PLUGIN_FILE );

		if ( isset( $_GET['activate'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			unset( $_GET['activate'] ); // phpcs:ignore WordPress.Security.NonceVerification
		}
	}

	/**
	 * Adds an admin notice to be displayed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug    The slug for the notice.
	 * @param string $class   The css class for the notice.
	 * @param string $message The notice message.
	 */
	private function add_admin_notice( $slug, $class, $message ) {
		$this->notices[ $slug ] = array(
			'class'   => $class,
			'message' => $message,
		);
	}

	/**
	 * Displays any admin notices added with Custom_Plugin::add_admin_notice()
	 *
	 * @since 1.0.0
	 */
	public function admin_notices() {
		foreach ( (array) $this->notices as $notice_key => $notice ) {
			?>
			<div class="<?php echo esc_attr( $notice['class'] ); ?>">
				<p>
				<?php
				echo wp_kses(
					$notice['message'],
					array(
						'strong' => array(),
						'a'      => array(
							'href'   => array(),
							'target' => array(),
						),
					)
				);
				?>
					</p>
			</div>
			<?php
		}
	}

	/**
	 * Determines if the server environment is compatible with this plugin.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function is_environment_compatible() {
		return version_compare( phpversion(), CUSTOM_PLUGIN_MIN_PHP_VERSION, '>=' );
	}

	/**
	 * Gets the message for display when the environment is incompatible with this plugin.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function get_environment_message() {
		return sprintf(
			/* translators: 1: Minimum PHP Version 2: Current PHP Version */
			__( 'The minimum PHP version required for this plugin is %1$s. You are running %2$s.', 'custom-plugin' ),
			CUSTOM_PLUGIN_MIN_PHP_VERSION,
			phpversion()
		);
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
