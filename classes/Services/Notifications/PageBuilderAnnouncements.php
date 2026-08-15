<?php
/**
 * Page builder support announcements.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Services\Notifications;

use PopupMaker\Base\Service;
use PopupMaker\Base\PageBuilder;
use PopupMaker\Controllers\Builders;

defined( 'ABSPATH' ) || exit;

/**
 * Announces complete support for page builders active on the site.
 *
 * @since 1.25.0
 */
class PageBuilderAnnouncements extends Service implements Provider {

	/**
	 * Page builder integrations hub.
	 *
	 * @var string
	 */
	const GUIDE_URL = 'https://wppopupmaker.com/integrations/page-builder-integrations/';

	/**
	 * Hook into the alert list.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'pum_alert_list', [ $this, 'register_announcement' ], 14 );
	}

	/**
	 * Register one announcement for all supported builders active on the site.
	 *
	 * @param mixed $alerts Existing alerts.
	 *
	 * @return mixed
	 */
	public function register_announcement( $alerts ) {
		if (
			! is_array( $alerts ) ||
			! current_user_can( $this->container->get_permission( 'edit_popups' ) )
		) {
			return $alerts;
		}

		$builders = $this->get_available_builders();

		if ( ! $builders ) {
			return $alerts;
		}

		$labels = wp_list_pluck( $builders, 'label' );
		$slugs  = wp_list_pluck( $builders, 'slug' );
		$names  = wp_sprintf_l( '%l', $labels );

		if ( 1 === count( $builders ) ) {
			/* translators: %s: page builder name. */
			$title   = sprintf( __( 'Full Popup Maker support for %s is here', 'popup-maker' ), $names );
			$message = sprintf(
				/* translators: %s: page builder name. */
				__( 'Design popup content directly in <strong>%s</strong> while Popup Maker handles the popup theme, triggers, targeting, and analytics. Builder styles and interactive elements work in the editor and on your site.', 'popup-maker' ),
				esc_html( $names )
			);
		} else {
			$title   = __( 'Full Popup Maker support for your page builders is here', 'popup-maker' );
			$message = sprintf(
				/* translators: %s: comma-separated list of page builder names. */
				__( 'Popup Maker now fully supports the builders active on this site: <strong>%s</strong>. Design popup content in each builder while Popup Maker handles the popup theme, triggers, targeting, and analytics.', 'popup-maker' ),
				esc_html( $names )
			);
		}

		$alerts[] = [
			'code'        => 'pm_feat_page_builder_support_2026_' . implode( '_', $slugs ),
			'type'        => 'info',
			'category'    => 'feature',
			'priority'    => 88,
			'title'       => $title,
			'message'     => $message,
			'subtitle'    => __( 'new in Popup Maker', 'popup-maker' ),
			'icon'        => 'layout',
			'dismissible' => true,
			'actions'     => [
				[
					'text'     => __( 'See how it works', 'popup-maker' ),
					'type'     => 'link',
					'action'   => '',
					'href'     => add_query_arg(
						[
							'utm_source'   => 'plugin',
							'utm_medium'   => 'notification',
							'utm_campaign' => 'page-builder-support',
						],
						self::GUIDE_URL
					),
					'primary'  => true,
					'external' => true,
				],
				[
					'text'   => __( 'Dismiss', 'popup-maker' ),
					'type'   => 'action',
					'action' => 'dismiss',
				],
			],
		];

		return $alerts;
	}

	/**
	 * Get display details for available builder adapters.
	 *
	 * @return array<int,array{slug:string,label:string}>
	 */
	protected function get_available_builders() {
		$controller = $this->container->get_controller( 'Builders' );

		if ( ! $controller instanceof Builders ) {
			return [];
		}

		$available = [];

		foreach ( $controller->get_available_builders() as $builder ) {
			if ( ! $builder instanceof PageBuilder || ! is_string( $builder->key ) || '' === $builder->key ) {
				continue;
			}

			$label = $builder->label();

			if ( ! is_string( $label ) || '' === $label ) {
				continue;
			}

			$available[] = [
				'slug'  => sanitize_key( $builder->key ),
				'label' => $label,
			];
		}

		return $available;
	}
}
