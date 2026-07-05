<?php
/**
 * ExpiryFlow
 *
 * @package           ExpiryFlow
 * @author            ctaflow
 * @copyright         2026 ctaflow
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       ExpiryFlow
 * Plugin URI:        https://gasatrya.com/wp-plugins/expiryflow/
 * Description:       Extend WordPress user management with expiration functionality for temporary user accounts. Set expiry dates, enable auto-deletion, and keep your user database clean.
 * Version:           1.0.3
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Ga Satrya
 * Author URI:        https://gasatrya.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       expiryflow
 * Domain Path:       /languages
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/Core.php';
require_once __DIR__ . '/includes/Utils/Helpers.php';
require_once __DIR__ . '/includes/Auth/Authentication.php';
require_once __DIR__ . '/includes/Cron/AutoDeletion.php';
require_once __DIR__ . '/includes/Admin/UserManagement.php';
require_once __DIR__ . '/includes/Admin/Settings.php';

\ExpiryFlow\Core::define_constants();

register_activation_hook( __FILE__, array( \ExpiryFlow\Core::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \ExpiryFlow\Core::class, 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		\ExpiryFlow\Core::get_instance();
	}
);
