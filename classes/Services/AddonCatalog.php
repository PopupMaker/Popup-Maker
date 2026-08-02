<?php
/**
 * Static add-on catalog.
 *
 * @package PopupMaker\Services
 */

namespace PopupMaker\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Provides the bundled marketing catalog used by the WordPress.org build.
 */
class AddonCatalog {

	/**
	 * Get the ordered catalog categories.
	 *
	 * @return array<string,string>
	 */
	public function get_categories() {
		return [
			'ecommerce'  => __( 'Ecommerce', 'popup-maker' ),
			'forms'      => __( 'Forms & Email', 'popup-maker' ),
			'targeting'  => __( 'Targeting', 'popup-maker' ),
			'content'    => __( 'Content', 'popup-maker' ),
			'compliance' => __( 'Compliance', 'popup-maker' ),
		];
	}

	/**
	 * Get the hard-coded catalog.
	 *
	 * This is intentionally local. The WordPress.org build does not fetch
	 * remote catalog, entitlement, update, or delivery data to render the page.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_items() {
		return [
			'popup-maker-age-verification-modals' => [
				'name'            => __( 'Age Verification Modals', 'popup-maker' ),
				'description'     => __( 'Require visitors to confirm their age before viewing protected content.', 'popup-maker' ),
				'longDescription' => __( 'A blocking modal that gates restricted content until a visitor confirms their age, with a remembered choice per device.', 'popup-maker' ),
				'features'        => [
					__( 'Date-of-birth or yes/no gate', 'popup-maker' ),
					__( 'Remembers the answer', 'popup-maker' ),
					__( 'Per-page targeting', 'popup-maker' ),
				],
				'category'        => 'compliance',
				'pluginBasename'  => 'popup-maker-age-verification-modals/popup-maker-age-verification-modals.php',
				'image'           => $this->image_url( 'age-verification-modals.png' ),
				'url'             => 'https://wppopupmaker.com/extensions/age-verification-modals/',
			],
			'popup-maker-ajax-login-modals'       => [
				'name'            => __( 'AJAX Login Modals', 'popup-maker' ),
				'description'     => __( 'Add login, registration, and password recovery forms to your popups.', 'popup-maker' ),
				'longDescription' => __( 'Full account flows inside a popup — sign in, register, and reset a password without ever leaving the page.', 'popup-maker' ),
				'features'        => [
					__( 'Login, register, and reset', 'popup-maker' ),
					__( 'Inline validation', 'popup-maker' ),
					__( 'Redirect after login', 'popup-maker' ),
				],
				'category'        => 'forms',
				'pluginBasename'  => 'popup-maker-ajax-login-modals/popup-maker-ajax-login-modals.php',
				'image'           => $this->image_url( 'ajax-login-modals.png' ),
				'url'             => 'https://wppopupmaker.com/extensions/ajax-login-modals/',
			],
			'popup-maker-aweber-integration'      => [
				'name'            => __( 'AWeber Integration', 'popup-maker' ),
				'description'     => __( 'Connect Popup Maker forms directly to your AWeber mailing lists.', 'popup-maker' ),
				'longDescription' => __( 'Send subscribers straight into AWeber lists with tags and custom fields, no third-party connector needed.', 'popup-maker' ),
				'features'        => [
					__( 'List and tag mapping', 'popup-maker' ),
					__( 'Custom field support', 'popup-maker' ),
					__( 'Per-popup list routing', 'popup-maker' ),
				],
				'category'        => 'forms',
				'pluginBasename'  => 'popup-maker-aweber-integration/pum-aweber-integration.php',
				'legacyBasename'  => 'pum-aweber-integration/pum-aweber-integration.php',
				'image'           => $this->image_url( 'aweber-integration.png' ),
				'url'             => 'https://wppopupmaker.com/extensions/aweber-integration/',
			],
			'popup-maker-ecommerce-popups'        => [
				'name'            => __( 'Ecommerce Popups', 'popup-maker' ),
				'description'     => __( 'Target shoppers and measure popup-driven revenue across your ecommerce store.', 'popup-maker' ),
				'longDescription' => __( 'Trigger popups from cart value, product category, or purchase history, then attribute revenue back to the popup that earned it.', 'popup-maker' ),
				'features'        => [
					__( 'Cart-value and product triggers', 'popup-maker' ),
					__( 'Revenue attribution per popup', 'popup-maker' ),
					__( 'WooCommerce and EDD support', 'popup-maker' ),
				],
				'category'        => 'ecommerce',
				'pluginBasename'  => 'popup-maker-ecommerce-popups/popup-maker-ecommerce-popups.php',
				'image'           => POPMAKE_URL . '/assets/images/mark.svg',
				'url'             => 'https://wppopupmaker.com/extensions/ecommerce-popups/',
				'isProPlus'       => true,
			],
			'popup-maker-geotargeting'            => [
				'name'            => __( 'Geotargeting', 'popup-maker' ),
				'description'     => __( 'Show personalized popups based on a visitor’s geographic location.', 'popup-maker' ),
				'longDescription' => __( 'Target down to country, region, or city and localize your offer for the visitors who can actually take it.', 'popup-maker' ),
				'features'        => [
					__( 'Country, region, and city rules', 'popup-maker' ),
					__( 'Radius targeting', 'popup-maker' ),
					__( 'Cached lookups', 'popup-maker' ),
				],
				'category'        => 'targeting',
				'pluginBasename'  => 'popup-maker-geotargeting/popup-maker-geotargeting.php',
				'image'           => $this->image_url( 'geotargeting.png' ),
				'url'             => 'https://wppopupmaker.com/extensions/geotargeting/',
			],
			'popup-maker-leaving-notices'         => [
				'name'            => __( 'Leaving Notices', 'popup-maker' ),
				'description'     => __( 'Warn visitors before they follow links that leave your website.', 'popup-maker' ),
				'longDescription' => __( 'Catch outbound clicks with a confirmation popup — useful for affiliate links, partner sites, and compliance notices.', 'popup-maker' ),
				'features'        => [
					__( 'Outbound link detection', 'popup-maker' ),
					__( 'Per-domain allowlist', 'popup-maker' ),
					__( 'Custom notice copy', 'popup-maker' ),
				],
				'category'        => 'targeting',
				'pluginBasename'  => 'popup-maker-leaving-notices/popup-maker-leaving-notices.php',
				'image'           => '',
				'url'             => 'https://wppopupmaker.com/extensions/leaving-notices/',
			],
			'popup-maker-lms-popups'              => [
				'name'            => __( 'LMS Popups', 'popup-maker' ),
				'description'     => __( 'Target students by enrollment, course progress, membership, and more.', 'popup-maker' ),
				'longDescription' => __( 'Show the right message at the right point in a course — nudge enrollment, celebrate completion, or upsell the next tier.', 'popup-maker' ),
				'features'        => [
					__( 'Enrollment and progress conditions', 'popup-maker' ),
					__( 'LearnDash and LifterLMS', 'popup-maker' ),
					__( 'Membership-level targeting', 'popup-maker' ),
				],
				'category'        => 'ecommerce',
				'pluginBasename'  => 'popup-maker-lms-popups/popup-maker-lms-popups.php',
				'image'           => '',
				'url'             => 'https://wppopupmaker.com/extensions/lms-popups/',
				'isProPlus'       => true,
			],
			'popup-maker-mailchimp-integration'   => [
				'name'            => __( 'MailChimp Integration', 'popup-maker' ),
				'description'     => __( 'Subscribe popup visitors to Mailchimp audiences and interest groups.', 'popup-maker' ),
				'longDescription' => __( 'Connect any Popup Maker form to a Mailchimp audience, map custom fields, and set interest groups per popup.', 'popup-maker' ),
				'features'        => [
					__( 'Audience and group mapping', 'popup-maker' ),
					__( 'Double opt-in support', 'popup-maker' ),
					__( 'Custom merge fields', 'popup-maker' ),
				],
				'category'        => 'forms',
				'pluginBasename'  => 'popup-maker-mailchimp-integration/pum-mailchimp-integration.php',
				'legacyBasename'  => 'pum-mailchimp-integration/pum-mailchimp-integration.php',
				'image'           => $this->image_url( 'mailchimp-integration.png' ),
				'url'             => 'https://wppopupmaker.com/extensions/mailchimp-integration/',
			],
			'popup-maker-remote-content'          => [
				'name'            => __( 'Remote Content', 'popup-maker' ),
				'description'     => __( 'Load external or dynamic content into popups only when it is needed.', 'popup-maker' ),
				'longDescription' => __( 'Fetch popup content on open instead of on page load, so heavy or personalized content never slows down the page.', 'popup-maker' ),
				'features'        => [
					__( 'Lazy-loaded content', 'popup-maker' ),
					__( 'External URL or shortcode', 'popup-maker' ),
					__( 'Caching controls', 'popup-maker' ),
				],
				'category'        => 'content',
				'pluginBasename'  => 'popup-maker-remote-content/popup-maker-remote-content.php',
				'image'           => $this->image_url( 'remote-content.png' ),
				'url'             => 'https://wppopupmaker.com/extensions/remote-content/',
			],
			'popup-maker-secure-idle-user-logout' => [
				'name'            => __( 'Secure Idle User Logout', 'popup-maker' ),
				'description'     => __( 'Protect sessions by securely logging out users after a period of inactivity.', 'popup-maker' ),
				'longDescription' => __( 'Warn users before an idle timeout and log them out cleanly when the timer runs down.', 'popup-maker' ),
				'features'        => [
					__( 'Configurable idle window', 'popup-maker' ),
					__( 'Countdown warning popup', 'popup-maker' ),
					__( 'Per-role rules', 'popup-maker' ),
				],
				'category'        => 'compliance',
				'pluginBasename'  => 'popup-maker-secure-idle-user-logout/popup-maker-secure-idle-user-logout.php',
				'image'           => $this->image_url( 'secure-idle-user-logout.png' ),
				'url'             => 'https://wppopupmaker.com/extensions/secure-idle-user-logout/',
			],
			'popup-maker-terms-conditions-popups' => [
				'name'            => __( 'Terms & Conditions Popups', 'popup-maker' ),
				'description'     => __( 'Require visitors to review and accept terms before continuing.', 'popup-maker' ),
				'longDescription' => __( 'Present terms in a scrollable modal and require an explicit accept before the visitor can continue.', 'popup-maker' ),
				'features'        => [
					__( 'Scroll-to-accept option', 'popup-maker' ),
					__( 'Logs acceptance', 'popup-maker' ),
					__( 'Works with forms and checkout', 'popup-maker' ),
				],
				'category'        => 'compliance',
				'pluginBasename'  => 'popup-maker-terms-conditions-popups/popup-maker-terms-conditions-popups.php',
				'image'           => $this->image_url( 'terms-conditions-popups.png' ),
				'url'             => 'https://wppopupmaker.com/extensions/terms-conditions-popups/',
			],
			'popup-maker-videos'                  => [
				'name'            => __( 'Videos', 'popup-maker' ),
				'description'     => __( 'Create responsive video popups with reliable playback controls.', 'popup-maker' ),
				'longDescription' => __( 'Drop YouTube, Vimeo, or self-hosted video into a popup that sizes itself and stops playback on close.', 'popup-maker' ),
				'features'        => [
					__( 'YouTube, Vimeo, and self-hosted', 'popup-maker' ),
					__( 'Auto-pause on close', 'popup-maker' ),
					__( 'Responsive sizing', 'popup-maker' ),
				],
				'category'        => 'content',
				'pluginBasename'  => 'popup-maker-videos/pum-videos.php',
				'legacyBasename'  => 'pum-videos/pum-videos.php',
				'image'           => $this->image_url( 'videos.png' ),
				'url'             => 'https://wppopupmaker.com/extensions/videos/',
			],
		];
	}

	/**
	 * Get public catalog records with installed status.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_public_items() {
		$this->load_plugin_dependencies();

		$plugins = get_plugins();
		$items   = [];

		foreach ( $this->get_items() as $slug => $item ) {
			$basename = $this->find_installed_basename( $item, $plugins );
			$record   = array_merge(
				$item,
				[
					'slug'             => $slug,
					'isProPlus'        => ! empty( $item['isProPlus'] ),
					'isUpsell'         => true,
					'installed'        => '' !== $basename,
					'activated'        => '' !== $basename && is_plugin_active( $basename ),
					'networkActivated' => '' !== $basename && is_multisite() && is_plugin_active_for_network( $basename ),
					'version'          => '' !== $basename && isset( $plugins[ $basename ]['Version'] )
						? (string) $plugins[ $basename ]['Version']
						: '',
				]
			);

			unset( $record['legacyBasename'] );
			$record['pluginBasename'] = $basename;
			$items[]                  = $record;
		}

		return $items;
	}

	/**
	 * Resolve an exact installed basename for an allowlisted catalog item.
	 *
	 * @param array<string,mixed>        $item    Catalog item.
	 * @param array<string,array<mixed>> $plugins Installed plugins.
	 *
	 * @return string
	 */
	public function find_installed_basename( $item, $plugins = null ) {
		if ( null === $plugins ) {
			$this->load_plugin_dependencies();
			$plugins = get_plugins();
		}

		if ( ! is_array( $plugins ) ) {
			return '';
		}

		$basenames = [ isset( $item['pluginBasename'] ) ? $item['pluginBasename'] : '' ];

		if ( ! empty( $item['legacyBasename'] ) ) {
			$basenames[] = $item['legacyBasename'];
		}

		foreach ( $basenames as $basename ) {
			if ( is_string( $basename ) && '' !== $basename && isset( $plugins[ $basename ] ) ) {
				return $basename;
			}
		}

		return '';
	}

	/**
	 * Get an allowlisted catalog item.
	 *
	 * @param mixed $slug Catalog slug.
	 *
	 * @return array<string,mixed>|null
	 */
	public function get_item( $slug ) {
		if ( ! is_string( $slug ) || sanitize_key( $slug ) !== $slug ) {
			return null;
		}

		$items = $this->get_items();

		return isset( $items[ $slug ] ) ? $items[ $slug ] : null;
	}

	/**
	 * Build a bundled catalog image URL.
	 *
	 * @param string $filename Image filename.
	 *
	 * @return string
	 */
	private function image_url( $filename ) {
		return POPMAKE_URL . '/assets/images/extensions/' . $filename;
	}

	/**
	 * Load WordPress plugin helpers.
	 *
	 * @return void
	 */
	private function load_plugin_dependencies() {
		if ( ! function_exists( 'get_plugins' ) ) {
			// @phpstan-ignore requireOnce.fileNotFound
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}
}
