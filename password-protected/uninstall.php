<?php
/**
 * Uninstall Password Protected.
 *
 * Removes plugin options and the activity log table when the plugin is
 * deleted from the WordPress admin. Fires only on deletion, not on
 * deactivation.
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$options = array(
	'password_protected_status',
	'password_protected_feeds',
	'password_protected_rest',
	'password_protected_administrators',
	'password_protected_users',
	'password_protected_password',
	'password_protected_allowed_ip_addresses',
	'password_protected_remember_me',
	'password_protected_remember_me_lifetime',
	'password_protected_text_above_password',
	'password_protected_text_below_password',
	'password_protected_use_transient',
	'pp_enable_dynamic_args',
	'password_protected_recaptcha',
	'password_protected_allcaptchas',
	'password_protected_activity_report_enable',
	'password_protected_version',
	'password_protected_1.5_update_database',
	'pp_activity_logs_db_updated',
	'pp_activity_logs_scrubbed',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// Remove the activity log table if it exists.
$logs_table = $wpdb->prefix . 'pp_activity_logs';
$wpdb->query( "DROP TABLE IF EXISTS `{$logs_table}`" );

// Clear the scheduled weekly report event.
wp_clear_scheduled_hook( 'password_protected_email_notification_hook' );

// Remove Freemius SDK storage options (namespaced under fs_*).
$fs_options = $wpdb->get_col(
	"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'fs\_%' AND option_name LIKE '%password-protected%'"
);
if ( is_array( $fs_options ) ) {
	foreach ( $fs_options as $option_name ) {
		delete_option( $option_name );
	}
}
