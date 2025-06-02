<?php
/**
 * Link Call To Action class.
 *
 * @since       X.X.X
 * @package     PopupMaker
 * @copyright   Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\CallToAction;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Link
 */
class Link extends \PopupMaker\Base\CallToAction {

	/**
	 * Key identifier.
	 *
	 * @var string
	 */
	public $key = 'link';

	/**
	 * Label for this cta.
	 *
	 * @return string
	 */
	public function label() {
		return __( 'Link', 'popup-maker' );
	}

	/**
	 * Handle the CTA action.
	 *
	 * @param \PopupMaker\Models\CallToAction $call_to_action Call to action object.
	 * @param array                           $extra_args     Optional. Additional data passed to the handler (will include popup_id).
	 *
	 * @return mixed The result of the action
	 */
	public function action_handler( $call_to_action, $extra_args = [] ) {
		/**
		 * @var string|false $url
		 */
		$url = $call_to_action->get_setting( 'url' );

		if ( ! $url ) {
			// Strip query args and use the current page.
			$url = remove_query_arg( [ 'cta', 'pid' ] );
		}

		$call_to_action->increase_event_count( 'conversion' );

		wp_safe_redirect( esc_url_raw( $url ) );
		exit;
	}

	/**
	 * Array of options for this CTA.
	 *
	 * @return array
	 */
	public function fields() {
		return [
			'general' => [
				'url' => [
					// 'type'     => 'url',
					// 'label'    => __( 'Link', 'popup-maker' ),
					// 'priority' => 1.2,
					'std' => '',
				],
			],
		];
	}
}
