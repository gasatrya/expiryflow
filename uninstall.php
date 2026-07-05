<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package ExpiryFlow
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$expiryflow_cleanup_site = static function (): void {
	wp_clear_scheduled_hook( 'expiryflow_auto_delete_cron' );

	delete_option( 'expiryflow_plugin_version' );
	delete_option( 'expiryflow_grace_period' );
	delete_option( 'expiryflow_auto_delete_lock' );
	delete_transient( 'expiryflow_cron_token' );
};

if ( is_multisite() ) {
	$expiryflow_site_ids = get_sites(
		array(
			'fields' => 'ids',
		)
	);

	foreach ( $expiryflow_site_ids as $expiryflow_site_id ) {
		switch_to_blog( (int) $expiryflow_site_id );
		$expiryflow_cleanup_site();
		restore_current_blog();
	}
} else {
	$expiryflow_cleanup_site();
}

delete_metadata( 'user', 0, '_expiryflow_expiry_date', '', true );
delete_metadata( 'user', 0, '_expiryflow_account_status', '', true );
delete_metadata( 'user', 0, '_expiryflow_auto_delete', '', true );
delete_metadata( 'user', 0, '_expiryflow_grace_period', '', true );
