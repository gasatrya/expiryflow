<?php
/**
 * Authentication class for ExpiryFlow plugin.
 *
 * @package ExpiryFlow\Auth
 */

namespace ExpiryFlow\Auth;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ExpiryFlow\Utils\Helpers;
use WP_User;
use WP_Error;

/**
 * Authentication class
 */
class Authentication {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Set up plugin hooks.
	 */
	private function setup_hooks(): void {
		add_filter( 'authenticate', array( $this, 'authenticate_user' ), 30, 3 );
		add_filter( 'auth_cookie_expiration', array( $this, 'auth_cookie_expiration' ), 10, 3 );
		add_action( 'init', array( $this, 'enforce_expiry_on_request' ), 20 );
	}

	/**
	 * Check if user account is expired.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function is_user_expired( int $user_id ): bool {
		// Check manual status first.
		$status = get_user_meta( $user_id, EXPIRYFLOW_USER_ACCOUNT_STATUS, true );
		if ( EXPIRYFLOW_STATUS_EXPIRED === $status ) {
			return true;
		}

		$expiry_date = get_user_meta( $user_id, EXPIRYFLOW_USER_EXPIRY_DATE, true );
		// Check if expiry date has passed.
		if ( ! empty( $expiry_date ) ) {
			$current_time     = Helpers::get_current_timestamp();
			$expiry_timestamp = Helpers::get_expiry_timestamp( $expiry_date );

			if ( $expiry_timestamp && $expiry_timestamp <= $current_time ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Authenticate user and check for expiry.
	 *
	 * @param WP_User|WP_Error|null $user     User object, error, or null.
	 * @param string                $username Username.
	 * @param string                $password Password.
	 * @return WP_User|WP_Error|null
	 */
	public function authenticate_user( $user, string $username, string $password ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// If authentication already failed, return early.
		if ( is_wp_error( $user ) || null === $user ) {
			return $user;
		}

		// Skip check for admin users.
		if ( Helpers::is_user_admin( $user->ID ) ) {
			return $user;
		}

		// Check if user account is expired.
		if ( self::is_user_expired( $user->ID ) ) {
			return new WP_Error(
				'account_expired',
				__( '<strong>Error</strong>: Your account has expired. Please contact the administrator.', 'expiryflow' )
			);
		}

		return $user;
	}

	/**
	 * Enforce expiry on every request for logged-in non-admin users.
	 *
	 * The `authenticate` filter only fires at login. Once a session cookie
	 * is issued it remains valid for its full duration, so revoked or
	 * date-expired users keep access until the cookie expires. This hook
	 * re-validates on each front-end/admin request and destroys the session
	 * if the account is no longer valid.
	 */
	public function enforce_expiry_on_request(): void {
		// Skip non-interactive contexts.
		if ( ( defined( 'DOING_CRON' ) && DOING_CRON ) || wp_doing_ajax() ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id || Helpers::is_user_admin( $user_id ) ) {
			return;
		}

		if ( ! self::is_user_expired( $user_id ) ) {
			return;
		}

		wp_logout();

		if ( ! headers_sent() ) {
			wp_safe_redirect( add_query_arg( 'expired', '1', wp_login_url() ) );
			exit;
		}
	}

	/**
	 * Set auth cookie expiration for users with expiry dates.
	 *
	 * @param int $expiration Expiration time in seconds.
	 * @param int $user_id    User ID.
	 * @return int
	 */
	public function auth_cookie_expiration( int $expiration, int $user_id ): int {
		// Admin users get normal expiration.
		if ( Helpers::is_user_admin( $user_id ) ) {
			return $expiration;
		}

		// Check if user has an expiry date set.
		$expiry_date = get_user_meta( $user_id, EXPIRYFLOW_USER_EXPIRY_DATE, true );

		if ( empty( $expiry_date ) ) {
			return $expiration;
		}

		$expiry_timestamp = Helpers::get_expiry_timestamp( $expiry_date );

		if ( false === $expiry_timestamp ) {
			return HOUR_IN_SECONDS;
		}

		if ( ( $expiry_timestamp - Helpers::get_current_timestamp() ) > 7 * DAY_IN_SECONDS ) {
			return $expiration;
		}

		return min( $expiration, HOUR_IN_SECONDS );
	}
}
