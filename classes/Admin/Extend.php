<?php
/**
 * Popup Maker Extend admin page.
 *
 * @package PopupMaker
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders the bundled WordPress.org add-on catalog.
 */
class PUM_Admin_Extend {

	/**
	 * Register page assets.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
	}

	/**
	 * Return the legacy static extension catalog for third-party callers.
	 *
	 * @deprecated The Extend page now uses PopupMaker\Services\AddonCatalog.
	 *
	 * @return array<int,array<string,mixed>>|mixed
	 */
	public static function available_extensions() {
		$json_data = file_get_contents( Popup_Maker::$DIR . 'includes/extension-list.json' );

		return json_decode( $json_data, true );
	}

	/**
	 * Render the legacy marketing list for backward compatibility.
	 *
	 * This intentionally contains no installation controls.
	 *
	 * @deprecated The Extend page now renders the bundled React catalog.
	 *
	 * @return void
	 */
	public static function render_extension_list() {
		$extensions = self::available_extensions();

		if ( ! is_array( $extensions ) ) {
			return;
		}

		$local_images = self::extensions_with_local_image();
		?>
		<ul class="extensions-available">
			<?php foreach ( $extensions as $extension ) : ?>
				<?php
				if ( ! is_array( $extension ) ) {
					continue;
				}

				$slug     = isset( $extension['slug'] ) ? sanitize_key( $extension['slug'] ) : '';
				$name     = isset( $extension['name'] ) ? (string) $extension['name'] : '';
				$homepage = isset( $extension['homepage'] ) ? (string) $extension['homepage'] : '';
				$excerpt  = isset( $extension['excerpt'] ) ? (string) $extension['excerpt'] : '';
				$image    = isset( $extension['image'] ) ? (string) $extension['image'] : '';

				if ( in_array( $slug, $local_images, true ) ) {
					$image = POPMAKE_URL . '/assets/images/extensions/' . $slug . '.png';
				}
				?>
				<li class="available-extension-inner <?php echo esc_attr( $slug ); ?>">
					<h3><a target="_blank" rel="noreferrer noopener" href="<?php echo esc_url( $homepage ); ?>"><?php echo esc_html( $name ); ?></a></h3>
					<?php if ( '' !== $image ) : ?>
						<img class="extension-thumbnail" src="<?php echo esc_url( $image ); ?>" alt="" />
					<?php endif; ?>
					<p><?php echo wp_kses( $excerpt, wp_kses_allowed_html( 'data' ) ); ?></p>
					<span class="action-links"><a class="button" target="_blank" rel="noreferrer noopener" href="<?php echo esc_url( $homepage ); ?>"><?php esc_html_e( 'Learn more', 'popup-maker' ); ?></a></span>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	/**
	 * Return legacy extension slugs with bundled marketing images.
	 *
	 * @deprecated The Extend page catalog owns its image map.
	 *
	 * @return array<int,string>
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

	/**
	 * Enqueue the Core catalog app when Pro has not replaced the page.
	 *
	 * Hooked to WordPress and therefore intentionally untyped.
	 *
	 * @param string $hook_suffix Current admin hook.
	 *
	 * @return void
	 */
	public static function enqueue_assets( $hook_suffix = '' ) {
		unset( $hook_suffix );

		if ( ! function_exists( 'pum_is_extensions_page' ) || ! pum_is_extensions_page() ) {
			return;
		}

		if ( 'core' !== apply_filters( 'pum_admin_extend_page_owner', 'core' ) ) {
			return;
		}

		$handle = 'popup-maker-addons-page';

		wp_enqueue_script( $handle );
		wp_enqueue_style( $handle );
		wp_enqueue_style( 'popup-maker-layout' );

		wp_localize_script(
			$handle,
			'popupMakerAddonsPage',
			[
				'restPath'          => '/popup-maker/v2/addons',
				'canActivate'       => current_user_can( 'activate_plugins' ),
				'proUpgradeUrl'     => \PopupMaker\generate_upgrade_url( 'addons-catalog', 'pro-addons' ),
				'proPlusUpgradeUrl' => \PopupMaker\generate_upgrade_url( 'addons-catalog', 'pro-plus-addons' ),
				'supportUrl'        => 'https://wppopupmaker.com/support/?utm_campaign=plugin-support&utm_source=addons-catalog&utm_medium=plugin-ui&utm_content=action-error',
				'pluginsUrl'        => admin_url( 'plugins.php' ),
				'categories'        => \PopupMaker\plugin( 'addon_catalog' )->get_categories(),
				'planLogoUrl'       => POPMAKE_URL . '/assets/images/mark-light.svg',
				'planState'         => 'default',
				'planStatus'        => '',
				'planTitle'         => __( 'Unlock every Popup Maker add-on', 'popup-maker' ),
				'planSubtitle'      => __( 'Add advanced targeting, integrations, ecommerce tools, and more with Popup Maker Pro.', 'popup-maker' ),
				'upgradeLabel'      => __( 'View Pro plans', 'popup-maker' ),
				'upgradeUrl'        => \PopupMaker\generate_upgrade_url( 'addons-catalog', 'catalog-banner' ),
				'upgradeExternal'   => true,
			]
		);
	}

	/**
	 * Render the React mount point.
	 *
	 * @return void
	 */
	public static function page() {
		$capability = PUM_Admin_Pages::get_submenu_capability( 'extensions' );

		if ( ! current_user_can( $capability ) ) {
			wp_die(
				esc_html__( 'You do not have permission to manage Popup Maker add-ons.', 'popup-maker' ),
				esc_html__( 'Popup Maker Add-ons', 'popup-maker' ),
				[ 'response' => 403 ]
			);
		}
		?>
		<div id="popup-maker-addons">
			<p><?php esc_html_e( 'Loading add-ons…', 'popup-maker' ); ?></p>
		</div>
		<?php
	}
}
