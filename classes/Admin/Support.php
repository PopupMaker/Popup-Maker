<?php
/**
 * Class for Admin Support
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Admin_Support
 */
class PUM_Admin_Support {

	/**
	 * Support Page
	 *
	 * Renders the support page contents.
	 */
	public static function page() {
		$resources = [
			[
				'icon'        => 'welcome-learn-more',
				'title'       => __( 'Getting Started', 'popup-maker' ),
				'description' => __( 'Learn the basics and build your first popup.', 'popup-maker' ),
				'url'         => 'https://wppopupmaker.com/guides/?utm_source=plugin-support&utm_medium=referral&utm_campaign=guides',
			],
			[
				'icon'        => 'book-alt',
				'title'       => __( 'Documentation', 'popup-maker' ),
				'description' => __( 'Browse setup guides, common questions, and advanced documentation.', 'popup-maker' ),
				'url'         => 'https://wppopupmaker.com/docs/',
			],
			[
				'icon'        => 'groups',
				'title'       => __( 'Community', 'popup-maker' ),
				'description' => __( 'Ask questions and share solutions with other Popup Maker users.', 'popup-maker' ),
				'url'         => 'https://wppopupmaker.com/community/',
			],
			[
				'icon'        => 'admin-plugins',
				'title'       => __( 'Extensions', 'popup-maker' ),
				'description' => __( 'Find documentation for Popup Maker extensions.', 'popup-maker' ),
				'url'         => 'https://wppopupmaker.com/docs/category/extensions/',
			],
			[
				'icon'        => 'editor-code',
				'title'       => __( 'Developer Wiki', 'popup-maker' ),
				'description' => __( 'Contribute to or extend Popup Maker.', 'popup-maker' ),
				'url'         => 'https://github.com/PopupMaker/Popup-Maker/wiki',
			],
		];
		?>
		<div class="wrap pum-support-page">
			<h1><?php esc_html_e( 'Help & Support', 'popup-maker' ); ?></h1>
			<p class="pum-support-page__intro">
				<?php esc_html_e( 'Find answers, connect with the community, or contact the Popup Maker support team.', 'popup-maker' ); ?>
			</p>

			<div class="pum-support-page__resources">
				<?php foreach ( $resources as $resource ) : ?>
					<a class="pum-support-card" href="<?php echo esc_url( $resource['url'] ); ?>" target="_blank" rel="noopener noreferrer">
						<span class="dashicons dashicons-<?php echo esc_attr( $resource['icon'] ); ?>" aria-hidden="true"></span>
						<span>
							<strong><?php echo esc_html( $resource['title'] ); ?></strong>
							<span><?php echo esc_html( $resource['description'] ); ?></span>
						</span>
					</a>
				<?php endforeach; ?>
			</div>

			<div class="pum-support-page__contact">
				<div>
					<h2><?php esc_html_e( 'Still need help?', 'popup-maker' ); ?></h2>
					<p><?php esc_html_e( 'Open a support request and include the site URL, Popup Maker version, steps to reproduce, and any relevant error messages.', 'popup-maker' ); ?></p>
				</div>
				<a class="button button-primary button-hero" href="<?php echo esc_url( 'https://wppopupmaker.com/support/?utm_source=plugin-support&utm_medium=referral&utm_campaign=plugin-support' ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Contact Support', 'popup-maker' ); ?>
				</a>
			</div>
		</div>
		<?php
	}
}
