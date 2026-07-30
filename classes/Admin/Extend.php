<?php
/**
 * Class for Admin Extend
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Admin_Extend
 */
class PUM_Admin_Extend {
	/**
	 * Return array of Popup Maker extensions.
	 *
	 * @return array|mixed|object
	 */
	public static function available_extensions() {
		$json_data = file_get_contents( Popup_Maker::$DIR . 'includes/extension-list.json' );

		return json_decode( $json_data, true );
	}

	/**
	 * Support Page
	 *
	 * Renders the support page contents.
	 */
	public static function page() {
		$upgrade_link = \PopupMaker\get_upgrade_link( [
			'utm_source'   => 'plugin-extension-page',
			'utm_medium'   => 'text-link',
			'utm_campaign' => 'upsell',
			'utm_content'  => 'hero-cta',
		] );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Upgrade', 'popup-maker' ); ?></h1>
			<?php PUM_Upsell::display_addon_tabs(); ?>
			<article class="upgrade-wrapper">
				<section class="upgrade-wrapper-hero">
					<h2>Drive Even More Opt-Ins and Sales With Our Premium Features</h2>
					<p>Our premium plans give you more:</p>
					<ul>
						<li>Triggers - Scroll, Exit-intent, Add-to-cart, and more</li>
						<li>Integrations - MailChimp, WooCommerce, and more</li>
						<li>Conditions - Show popups to visitors from a certain site, from search engines, using certain browsers, who has viewed X pages, and more </li>
						<li>And much more!</li>
					</ul>
					<a href="<?php echo esc_url( $upgrade_link ); ?>" class="button button-primary" target="_blank" rel="noreferrer noopener">View pricing</a>
				</section>
				<section class="upgrade-wrapper-features">
					<h2>Our Most Popular Premium Features</h2>
					<?php self::render_extension_list(); ?>
					<a href="https://wppopupmaker.com/extensions/?utm_campaign=upsell&utm_medium=plugin&utm_source=plugin-extension-page&utm_content=browse-all-bottom" class="button-primary" title="<?php esc_attr_e( 'See All Premium Features', 'popup-maker' ); ?>" target="_blank" rel="noreferrer noopener"><?php esc_html_e( 'See All Premium Features', 'popup-maker' ); ?></a>
				</section>
			</article>
		</div>
		<?php
	}


	/**
	 * Render extension tab extensions list.
	 */
	public static function render_extension_list() {
		// Set a new campaign for tracking purposes
		$campaign   = 'PUMExtensionsPage';
		$extensions = self::available_extensions();

		?>
		<ul class="extensions-available">
			<?php
			$existing_extension_images = self::extensions_with_local_image();

			if ( ! empty( $extensions ) ) {
				shuffle( $extensions );

				foreach ( $extensions as $key => $ext ) {
					unset( $extensions[ $key ] );
					$extensions[ $ext['slug'] ] = $ext;
				}

				$i = 0;

				foreach ( $extensions as $extension ) :
					?>
					<li class="available-extension-inner <?php echo esc_attr( $extension['slug'] ); ?>">
						<h3>
							<a target="_blank" href="<?php echo esc_url( $extension['homepage'] ); ?>?utm_source=plugin-extension-page&utm_medium=plugin&utm_campaign=upsell&utm_content=<?php echo esc_attr( rawurlencode( str_replace( ' ', '+', $extension['name'] ) ) ); ?>-<?php echo esc_attr( $i ); ?>">
								<?php echo esc_html( $extension['name'] ); ?>
							</a>
						</h3>
						<?php $image = in_array( $extension['slug'], $existing_extension_images, true ) ? POPMAKE_URL . '/assets/images/extensions/' . $extension['slug'] . '.png' : $extension['image']; ?>
						<img class="extension-thumbnail" src="<?php echo esc_attr( $image ); ?>" />

						<p><?php echo wp_kses( $extension['excerpt'], wp_kses_allowed_html( 'data' ) ); ?></p>

						<span class="action-links">
						<a class="button" target="_blank" href="<?php echo esc_url( $extension['homepage'] ); ?>?utm_source=plugin-extension-page&utm_medium=plugin&utm_campaign=upsell&utm_content=<?php echo esc_attr( rawurlencode( str_replace( ' ', '+', $extension['name'] ) ) ); ?>-<?php echo esc_attr( $i ); ?>"><?php esc_html_e( 'Learn more', 'popup-maker' ); ?></a>
					</span>

					</li>
					<?php
					++$i;
				endforeach;
			}
			?>
		</ul>

		<?php
	}

	/**
	 * @return array
	 */
	public static function extensions_with_local_image() {
		return apply_filters(
			'pum_extensions_with_local_image',
			[
				'core-extensions-bundle',
				'aweber-integration',
				'mailchimp-integration',
				'remote-content',
				'scroll-triggered-popups',
				'popup-analytics',
				'forced-interaction',
				'age-verification-modals',
				'advanced-theme-builder',
				'exit-intent-popups',
				'ajax-login-modals',
				'advanced-targeting-conditions',
				'secure-idle-user-logout',
				'terms-conditions-popups',
				'videos',
				'edd-pro',
				'woocommerce-pro',
				'geotargeting',
				'scheduling',
			]
		);
	}
}
