<?php
/** Isolated regression harness; no WordPress installation or database required. */
define( 'ABSPATH', __DIR__ );
date_default_timezone_set( 'UTC' );
$options = [];
$settings = [];
$admin = true;
$admin_page = true;
$offset_seconds = 0;
function get_option( $key, $default = false ) { global $options; return $options[ $key ] ?? $default; }
function delete_option( $key ) { global $options; unset( $options[ $key ] ); }
function update_option( $key, $value ) { global $options; $options[ $key ] = $value; }
function add_filter( ...$args ) {}
function add_action( ...$args ) {}
function current_time( $type ) { global $offset_seconds; return gmdate( 'Y-m-d H:i:s', time() + $offset_seconds ); }
function current_user_can( $capability ) { global $admin; return $admin; }
function pum_get_option( $key, $default = false ) { global $settings; return $settings[ $key ] ?? $default; }
function pum_is_admin_page() { global $admin_page; return $admin_page; }
$root = dirname( __DIR__, 3 );
require $root . '/includes/namespaced/install.php';
require $root . '/classes/Telemetry.php';
require $root . '/classes/Admin/Onboarding.php';
require $root . '/includes/modules/reviews.php';
function check( $condition, $name ) {
	if ( ! $condition ) { fwrite( STDERR, "FAIL: $name\n" ); exit( 1 ); }
	echo "PASS: $name\n";
}
$old = gmdate( 'Y-m-d H:i:s', time() - 30 * 86400 );
$options = [ 'pum_installed_on' => $old, 'popup_maker_version_info' => [ 'installed_on' => $old ] ];
\PopupMaker\cleanup_old_install_data();
check( get_option( 'pum_installed_on' ) === false, 'legacy cleanup executed' );
check( PUM_Telemetry::get_installed_on() === $old && PUM_Admin_Onboarding::get_installed_on() === $old, 'canonical date survives cleanup' );
check( PUM_Modules_Reviews::installed_on() === $old && get_option( 'pum_reviews_installed_on' ) === false, 'review age uses canonical date without resetting timer' );
$options['pum_reviews_installed_on'] = '2021-01-01 00:00:00';
check( PUM_Modules_Reviews::installed_on() === '2021-01-01 00:00:00', 'existing review timer is preserved' );
unset( $options['pum_reviews_installed_on'] );
check( PUM_Telemetry::should_show_alert() && PUM_Admin_Onboarding::should_show_tip(), 'mature eligible site passes age gates' );
$settings = [ 'telemetry' => true, 'disable_tips' => true ];
check( ! PUM_Telemetry::should_show_alert() && ! PUM_Admin_Onboarding::should_show_tip(), 'existing consent and tip opt-out honored' );
$settings = [];
$admin = false;
check( ! PUM_Telemetry::should_show_alert() && ! PUM_Admin_Onboarding::should_show_tip(), 'non-admin remains excluded' );
$admin = true;
$admin_page = false;
check( ! PUM_Admin_Onboarding::should_show_tip(), 'tips remain restricted to plugin admin pages' );
$admin_page = true;
$options = [ 'pum_installed_on' => $old ];
check( PUM_Telemetry::get_installed_on() === $old && PUM_Admin_Onboarding::get_installed_on() === $old, 'legacy fallback before migration' );
$options = [];
foreach ( [ -8 * 3600, 0, 10 * 3600 ] as $offset_seconds ) {
	check( abs( time() - strtotime( PUM_Telemetry::get_installed_on() ) ) < 3 && abs( time() - strtotime( PUM_Admin_Onboarding::get_installed_on() ) ) < 3 && ! PUM_Telemetry::should_show_alert() && ! PUM_Admin_Onboarding::should_show_tip(), 'missing date falls back to now at offset ' . $offset_seconds );
}
