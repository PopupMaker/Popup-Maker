<?php
/**
 * Popup Maker PHP 8.5 runtime smoke assertions.
 *
 * Executed with `wp eval-file` inside wp-env.
 *
 * @package Popup_Maker
 */

/**
 * Fail a smoke assertion.
 *
 * @param bool   $condition Condition to assert.
 * @param string $message   Failure message.
 * @return void
 * @throws RuntimeException When the condition is false.
 */
function pum_php85_assert( $condition, $message ) {
	if ( ! $condition ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the assertion diagnostic.
		throw new RuntimeException( $message );
	}
}

pum_php85_assert( PHP_VERSION_ID >= 80500, 'The runtime is not PHP 8.5 or newer.' );
pum_php85_assert( class_exists( 'Popup_Maker' ), 'Popup Maker did not load.' );
pum_php85_assert( function_exists( 'pum' ), 'Popup Maker functions did not load.' );

$admin = get_user_by( 'login', 'admin' );
pum_php85_assert( false !== $admin, 'The wp-env administrator account was not found.' );
wp_set_password( 'php85-runtime-smoke', $admin->ID );

$settings = [
	'triggers'   => [
		[
			'type'     => 'auto_open',
			'settings' => [ 'delay' => 0 ],
		],
	],
	'conditions' => [],
	'cookies'    => [
		[
			'event'    => 'on_popup_close',
			'settings' => [
				'name' => 'pum-php85-smoke',
				'time' => '1 month',
			],
		],
	],
	'size'       => 'medium',
	'location'   => 'center',
];

$popup = pum()->popups->create_item(
	[
		'title'      => 'PHP 8.5 Runtime Smoke Popup',
		'content'    => '<p>PHP 8.5 runtime smoke content.</p>',
		'meta_input' => [
			'popup_settings' => $settings,
			'enabled'        => 1,
			'data_version'   => 3,
		],
	]
);
pum_php85_assert( pum_is_popup( $popup ), 'Popup model creation failed.' );
$popup_id = $popup->ID;
pum_php85_assert( 'PHP 8.5 Runtime Smoke Popup' === $popup->post_title, 'Declared post properties are not readable.' );
pum_php85_assert( $popup->has_trigger( 'auto_open' ), 'Popup triggers were not loaded.' );
pum_php85_assert( 1 === count( $popup->get_cookies() ), 'Popup cookies were not loaded.' );
pum_php85_assert( [] === $popup->get_conditions(), 'Popup conditions were not loaded.' );

$updated = pum()->popups->update_item(
	$popup_id,
	[
		'title' => 'PHP 8.5 Runtime Smoke Popup Updated',
	]
);
pum_php85_assert( pum_is_popup( $updated ), 'Popup update did not return a popup model.' );
pum_php85_assert( 'PHP 8.5 Runtime Smoke Popup Updated' === $updated->post_title, 'Popup update failed.' );

$frontend = \PopupMaker\plugin()->get_controller( 'Frontend\\Popups' );
$frontend->preload_popup( $popup );
ob_start();
$frontend->render_popups();
$rendered = ob_get_clean();

pum_php85_assert( false !== strpos( $rendered, 'PHP 8.5 runtime smoke content.' ), 'Popup rendering failed.' );

pum_update_option( 'disable_asset_caching', false );
update_option( 'pum_files_writeable', true );
add_filter(
	'popup_maker/get_upload_dir_url',
	function () {
		return '//wordpress/wp-content/uploads';
	}
);
PUM_AssetCache::$disabled = false;
pum_php85_assert( PUM_AssetCache::writeable(), 'Asset-cache directory is not writeable.' );
PUM_AssetCache::regenerate_cache();

$cache_dir = PUM_AssetCache::get_cache_dir();
$js_file   = trailingslashit( $cache_dir ) . PUM_AssetCache::generate_cache_filename( 'pum-site-scripts' ) . '.js';
$css_file  = trailingslashit( $cache_dir ) . PUM_AssetCache::generate_cache_filename( 'pum-site-styles' ) . '.css';

pum_php85_assert( is_file( $js_file ), 'JavaScript asset-cache generation failed.' );
pum_php85_assert( is_file( $css_file ), 'CSS asset-cache generation failed.' );

pum()->cron->schedule_events();
pum_php85_assert( false !== wp_next_scheduled( 'pum_weekly_scheduled_events' ), 'Weekly cron scheduling failed.' );
pum_php85_assert( false !== wp_next_scheduled( 'pum_daily_scheduled_events' ), 'Daily cron scheduling failed.' );

echo 'PHP85_SMOKE_POPUP_ID=' . absint( $popup_id ) . PHP_EOL;
