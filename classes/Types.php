<?php

use function PopupMaker\plugin;

/**
 * Class for Types
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

class PUM_Types {

	/**
	 * Hook the initialize method to the WP init action.
	 */
	public static function init() {
		add_filter( 'post_updated_messages', [ __CLASS__, 'updated_messages' ] );
	}

	/**
	 * @param $singular
	 * @param $plural
	 *
	 * @return mixed
	 *
	 * @deprecated X.X.X
	 */
	public static function post_type_labels( $singular, $plural ) {
		return plugin( 'PostTypes' )->post_type_labels( $singular, $plural );
	}

	/**
	 * Updated Messages
	 *
	 * Returns an array of with all updated messages.
	 *
	 * @since 1.0
	 *
	 * @param array $messages Post updated message
	 *
	 * @return array $messages New post updated messages
	 */
	public static function updated_messages( $messages ) {

		$labels = [
			/* translators: %1$s: Post Type Singular: Popup, Theme */
			1 => _x( '%1$s updated.', 'Post Type Singular: Popup, Theme', 'popup-maker' ),
			/* translators: %1$s: Post Type Singular: Popup, Theme */
			4 => _x( '%1$s updated.', 'Post Type Singular: Popup, Theme', 'popup-maker' ),
			/* translators: %1$s: Post Type Singular: Popup, Theme */
			6 => _x( '%1$s published.', 'Post Type Singular: Popup, Theme', 'popup-maker' ),
			/* translators: %1$s: Post Type Singular: Popup, Theme */
			7 => _x( '%1$s saved.', 'Post Type Singular: Popup, Theme', 'popup-maker' ),
			/* translators: %1$s: Post Type Singular: Popup, Theme */
			8 => _x( '%1$s submitted.', 'Post Type Singular: Popup, Theme', 'popup-maker' ),
		];

		$messages['popup']       = [];
		$messages['popup_theme'] = [];

		$popup = __( 'Popup', 'popup-maker' );
		$theme = __( 'Popup Theme', 'popup-maker' );

		foreach ( $labels as $k => $string ) {
			$messages['popup'][ $k ]       = sprintf( $string, $popup );
			$messages['popup_theme'][ $k ] = sprintf( $string, $theme );
		}

		return $messages;
	}
}
