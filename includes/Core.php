<?php
/**
 * Core Plugin Class
 *
 * @package ExpiryFlow
 */

namespace ExpiryFlow;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core Plugin Class
 */
class Core {

	/**
	 * Instance of this class.
	 *
	 * @var Core
	 */
	private static $instance = null;

	/**
	 * Get instance of this class.
	 *
	 * @return Core
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		self::define_constants();
		$this->init();
	}

	/**
	 * Define plugin constants.
	 */
	public static function define_constants() {
		if ( ! defined( 'EXPIRYFLOW_VERSION' ) ) {
			define( 'EXPIRYFLOW_VERSION', '1.0.3' );
		}
		if ( ! defined( 'EXPIRYFLOW_PLUGIN_DIR' ) ) {
			define( 'EXPIRYFLOW_PLUGIN_DIR', plugin_dir_path( __DIR__ ) );
		}
		if ( ! defined( 'EXPIRYFLOW_PLUGIN_URL' ) ) {
			define( 'EXPIRYFLOW_PLUGIN_URL', plugin_dir_url( __DIR__ ) );
		}
		if ( ! defined( 'EXPIRYFLOW_BASENAME' ) ) {
			define( 'EXPIRYFLOW_BASENAME', plugin_basename( EXPIRYFLOW_PLUGIN_DIR . 'expiryflow.php' ) );
		}

		// Define user meta keys.
		if ( ! defined( 'EXPIRYFLOW_USER_EXPIRY_DATE' ) ) {
			define( 'EXPIRYFLOW_USER_EXPIRY_DATE', '_expiryflow_expiry_date' );
		}
		if ( ! defined( 'EXPIRYFLOW_USER_ACCOUNT_STATUS' ) ) {
			define( 'EXPIRYFLOW_USER_ACCOUNT_STATUS', '_expiryflow_account_status' );
		}
		if ( ! defined( 'EXPIRYFLOW_USER_AUTO_DELETE' ) ) {
			define( 'EXPIRYFLOW_USER_AUTO_DELETE', '_expiryflow_auto_delete' );
		}
		if ( ! defined( 'EXPIRYFLOW_USER_GRACE_PERIOD' ) ) {
			define( 'EXPIRYFLOW_USER_GRACE_PERIOD', '_expiryflow_grace_period' );
		}

		// Define account status constants.
		if ( ! defined( 'EXPIRYFLOW_STATUS_ACTIVE' ) ) {
			define( 'EXPIRYFLOW_STATUS_ACTIVE', 'active' );
		}
		if ( ! defined( 'EXPIRYFLOW_STATUS_EXPIRED' ) ) {
			define( 'EXPIRYFLOW_STATUS_EXPIRED', 'expired' );
		}

		// Define configurable constants.
		if ( ! defined( 'EXPIRYFLOW_AUTO_DELETE_BATCH_SIZE' ) ) {
			define( 'EXPIRYFLOW_AUTO_DELETE_BATCH_SIZE', 50 );
		}
		if ( ! defined( 'EXPIRYFLOW_GRACE_PERIOD_DAYS' ) ) {
			define( 'EXPIRYFLOW_GRACE_PERIOD_DAYS', 2 );
		}
	}

	/**
	 * Initialize plugin components.
	 */
	private function init() {
		// Initialize Authentication.
		new Auth\Authentication();

		// Initialize Admin components.
		if ( is_admin() ) {
			new Admin\UserManagement();
			new Admin\Settings();
		}

		// Initialize Cron tasks.
		new Cron\AutoDeletion();

		// Failsafe: Ensure cron is scheduled if it was missed during activation.
		if ( ! wp_next_scheduled( 'expiryflow_auto_delete_cron' ) ) {
			wp_schedule_event( time(), 'hourly', 'expiryflow_auto_delete_cron' );
		}
	}

	/**
	 * Plugin activation hook.
	 */
	public static function activate() {
		self::define_constants();

		update_option( 'expiryflow_plugin_version', EXPIRYFLOW_VERSION );

		// Schedule auto-deletion cron job.
		if ( ! wp_next_scheduled( 'expiryflow_auto_delete_cron' ) ) {
			wp_schedule_event( time(), 'hourly', 'expiryflow_auto_delete_cron' );
		}
	}

	/**
	 * Plugin deactivation hook.
	 */
	public static function deactivate() {
		delete_option( 'expiryflow_plugin_version' );

		// Remove auto-deletion cron job.
		wp_clear_scheduled_hook( 'expiryflow_auto_delete_cron' );
	}
}
