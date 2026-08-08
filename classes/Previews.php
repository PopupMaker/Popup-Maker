<?php
/**
 * Legacy popup preview API.
 *
 * @package PopupMaker
 */

defined( 'ABSPATH' ) || exit;

/**
 * Backward-compatible facade for the popup preview controller.
 *
 * @deprecated 1.25.0 Use the Previews controller from PopupMaker\plugin().
 */
class PUM_Previews {

	/**
	 * Initialize preview hooks.
	 *
	 * @deprecated 1.25.0 Preview hooks are initialized by the plugin container.
	 *
	 * @return void
	 */
	public static function init() {
		_deprecated_function( __METHOD__, '1.25.0', 'PopupMaker\Controllers\Previews::init()' );

		$controller = static::controller();

		if ( $controller ) {
			$controller->init();
		}
	}

	/**
	 * Get the popup ID for a core editor preview.
	 *
	 * @deprecated 1.25.0 Use PopupMaker\Controllers\Previews::get_popup_preview().
	 *
	 * @return false|int
	 */
	public static function get_popup_preview() {
		_deprecated_function( __METHOD__, '1.25.0', 'PopupMaker\Controllers\Previews::get_popup_preview()' );

		$controller = static::controller();

		return $controller ? $controller->get_popup_preview() : false;
	}

	/**
	 * Force the current core editor preview popup to load.
	 *
	 * @deprecated 1.25.0 Use PopupMaker\Controllers\Previews::force_load_preview().
	 *
	 * @return void
	 */
	public static function force_load_preview() {
		_deprecated_function( __METHOD__, '1.25.0', 'PopupMaker\Controllers\Previews::force_load_preview()' );

		$controller = static::controller();

		if ( $controller ) {
			$controller->force_load_preview();
		}
	}

	/**
	 * Filter popup loadability during a preview.
	 *
	 * @deprecated 1.25.0 Use PopupMaker\Controllers\Previews::is_loadable().
	 *
	 * @param bool $loadable Whether the popup is loadable.
	 * @param int  $popup_id Popup ID.
	 *
	 * @return bool
	 */
	public static function is_loadable( $loadable, $popup_id ) {
		_deprecated_function( __METHOD__, '1.25.0', 'PopupMaker\Controllers\Previews::is_loadable()' );

		$controller = static::controller();

		return $controller ? $controller->is_loadable( $loadable, $popup_id ) : $loadable;
	}

	/**
	 * Filter legacy popup data attributes during a preview.
	 *
	 * @deprecated 1.25.0 Use PopupMaker\Controllers\Previews::data_attr().
	 *
	 * @param array $data_attr Popup data attributes.
	 * @param int   $popup_id Popup ID.
	 *
	 * @return mixed
	 */
	public static function data_attr( $data_attr, $popup_id ) {
		_deprecated_function( __METHOD__, '1.25.0', 'PopupMaker\Controllers\Previews::data_attr()' );

		$controller = static::controller();

		return $controller ? $controller->data_attr( $data_attr, $popup_id ) : $data_attr;
	}

	/**
	 * Filter popup public settings during a preview.
	 *
	 * @deprecated 1.25.0 Use PopupMaker\Controllers\Previews::get_public_settings().
	 *
	 * @param array           $settings Popup public settings.
	 * @param PUM_Model_Popup $popup Popup model.
	 *
	 * @return array
	 */
	public static function get_public_settings( $settings, $popup ) {
		_deprecated_function( __METHOD__, '1.25.0', 'PopupMaker\Controllers\Previews::get_public_settings()' );

		$controller = static::controller();

		return $controller ? $controller->get_public_settings( $settings, $popup ) : $settings;
	}

	/**
	 * Restore the popup query for an authorized builder request.
	 *
	 * @deprecated 1.25.0 Handled by PopupMaker\Controllers\Builders.
	 *
	 * @param mixed $query_vars Parsed query variables.
	 *
	 * @return mixed
	 */
	public static function allow_builder_preview_request( $query_vars ) {
		_deprecated_function( __METHOD__, '1.25.0', 'PopupMaker\Controllers\Builders::allow_builder_request()' );

		$builders = PopupMaker\plugin()->get_controller( 'Builders' );

		return $builders instanceof PopupMaker\Controllers\Builders
			? $builders->allow_builder_request( $query_vars )
			: $query_vars;
	}

	/**
	 * Select the isolated popup canvas for a builder request.
	 *
	 * @deprecated 1.25.0 Handled by PopupMaker\Controllers\Builders.
	 *
	 * @param string $template Selected template path.
	 *
	 * @return string
	 */
	public static function use_builder_preview_template( $template ) {
		_deprecated_function( __METHOD__, '1.25.0', 'PopupMaker\Controllers\Builders::use_popup_canvas()' );

		$builders = PopupMaker\plugin()->get_controller( 'Builders' );

		return $builders instanceof PopupMaker\Controllers\Builders
			? $builders->use_popup_canvas( $template )
			: $template;
	}

	/**
	 * Get the modern preview controller.
	 *
	 * @return PopupMaker\Controllers\Previews|null
	 */
	private static function controller() {
		$controller = PopupMaker\plugin()->get_controller( 'Previews' );

		return $controller instanceof PopupMaker\Controllers\Previews ? $controller : null;
	}
}
