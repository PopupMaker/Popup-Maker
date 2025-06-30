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
	public function label(): string {
		return __( 'Link', 'popup-maker' );
	}

	/**
	 * Handle the CTA action.
	 *
	 * @param \PopupMaker\Models\CallToAction $call_to_action Call to action object.
	 * @param array                           $extra_args     Optional. Additional data passed to the handler (will include popup_id).
	 *
	 * @return void
	 */
	public function action_handler( \PopupMaker\Models\CallToAction $call_to_action, array $extra_args = [] ): void {
		/**
		 * @var string|false $url
		 */
		$url = $call_to_action->get_setting( 'url' );

		if ( ! $url ) {
			$cta_args = apply_filters( 'popup_maker/cta_valid_url_args', [ 'cta', 'pid' ] );
			// Strip query args and use the current page.
			$url = remove_query_arg( $cta_args );
		}

		$call_to_action->track_conversion( $extra_args );

		\PopupMaker\safe_redirect( $url );
		exit;
	}

	/**
	 * Array of options for this CTA.
	 *
	 * @return array
	 */
	public function fields(): array {
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
