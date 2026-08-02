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
			'ecommerce'  => 'Ecommerce',
			'forms'      => 'Forms & Email',
			'targeting'  => 'Targeting',
			'content'    => 'Content',
			'compliance' => 'Compliance',
		];
	}

	/**
	 * Get the hard-coded catalog.
	 *
	 * This is intentionally local. The WordPress.org build does not fetch
	 * remote catalog, entitlement, update, or delivery data to render the page.
	 * Product names and marketing copy intentionally remain English so this
	 * optional catalog does not add dozens of strings to community translation
	 * packs. Functional interface and error text remain translatable elsewhere.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_items() {
		return [
			'popup-maker-age-verification-modals' => [
				'name'            => 'Age Verification Modals',
				'description'     => 'Require visitors to confirm their age before viewing protected content.',
				'longDescription' => 'A blocking modal that gates restricted content until a visitor confirms their age, with a remembered choice per device.',
				'features'        => [
					'Date-of-birth or yes/no gate',
					'Remembers the answer',
					'Per-page targeting',
				],
				'category'        => 'compliance',
				'pluginBasename'  => 'popup-maker-age-verification-modals/popup-maker-age-verification-modals.php',
				'image'           => $this->image_url( 'age-verification-modals.png' ),
				'url'             => 'https://wppopupmaker.com/extensions/age-verification-modals/',
			],
			'popup-maker-ajax-login-modals'       => [
				'name'            => 'AJAX Login Modals',
				'description'     => 'Add login, registration, and password recovery forms to your popups.',
				'longDescription' => 'Full account flows inside a popup — sign in, register, and reset a password without ever leaving the page.',
				'features'        => [
					'Login, register, and reset',
					'Inline validation',
					'Redirect after login',
				],
				'category'        => 'forms',
				'pluginBasename'  => 'popup-maker-ajax-login-modals/popup-maker-ajax-login-modals.php',
				'image'           => $this->image_url( 'ajax-login-modals.png' ),
				'url'             => 'https://wppopupmaker.com/extensions/ajax-login-modals/',
			],
			'popup-maker-aweber-integration'      => [
				'name'            => 'AWeber Integration',
				'description'     => 'Connect Popup Maker forms directly to your AWeber mailing lists.',
				'longDescription' => 'Send subscribers straight into AWeber lists with tags and custom fields, no third-party connector needed.',
				'features'        => [
					'List and tag mapping',
					'Custom field support',
					'Per-popup list routing',
				],
				'category'        => 'forms',
				'pluginBasename'  => 'popup-maker-aweber-integration/pum-aweber-integration.php',
				'legacyBasename'  => 'pum-aweber-integration/pum-aweber-integration.php',
				'image'           => $this->image_url( 'aweber-integration.png' ),
				'url'             => 'https://wppopupmaker.com/extensions/aweber-integration/',
			],
			'popup-maker-ecommerce-popups'        => [
				'name'            => 'Ecommerce Popups',
				'description'     => 'Target shoppers and measure popup-driven revenue across your ecommerce store.',
				'longDescription' => 'Trigger popups from cart value, product category, or purchase history, then attribute revenue back to the popup that earned it.',
				'features'        => [
					'Cart-value and product triggers',
					'Revenue attribution per popup',
					'WooCommerce and EDD support',
				],
				'category'        => 'ecommerce',
				'pluginBasename'  => 'popup-maker-ecommerce-popups/popup-maker-ecommerce-popups.php',
				'image'           => POPMAKE_URL . '/assets/images/mark.svg',
				'url'             => 'https://wppopupmaker.com/extensions/ecommerce-popups/',
				'isProPlus'       => true,
			],
			'popup-maker-geotargeting'            => [
				'name'            => 'Geotargeting',
				'description'     => 'Show personalized popups based on a visitor’s geographic location.',
				'longDescription' => 'Target down to country, region, or city and localize your offer for the visitors who can actually take it.',
				'features'        => [
					'Country, region, and city rules',
					'Radius targeting',
					'Cached lookups',
				],
				'category'        => 'targeting',
				'pluginBasename'  => 'popup-maker-geotargeting/popup-maker-geotargeting.php',
				'image'           => $this->image_url( 'geotargeting.png' ),
				'url'             => 'https://wppopupmaker.com/extensions/geotargeting/',
			],
			'popup-maker-leaving-notices'         => [
				'name'            => 'Leaving Notices',
				'description'     => 'Warn visitors before they follow links that leave your website.',
				'longDescription' => 'Catch outbound clicks with a confirmation popup — useful for affiliate links, partner sites, and compliance notices.',
				'features'        => [
					'Outbound link detection',
					'Per-domain allowlist',
					'Custom notice copy',
				],
				'category'        => 'targeting',
				'pluginBasename'  => 'popup-maker-leaving-notices/popup-maker-leaving-notices.php',
				'image'           => '',
				'url'             => 'https://wppopupmaker.com/extensions/leaving-notices/',
			],
			'popup-maker-lms-popups'              => [
				'name'            => 'LMS Popups',
				'description'     => 'Target students by enrollment, course progress, membership, and more.',
				'longDescription' => 'Show the right message at the right point in a course — nudge enrollment, celebrate completion, or upsell the next tier.',
				'features'        => [
					'Enrollment and progress conditions',
					'LearnDash and LifterLMS',
					'Membership-level targeting',
				],
				'category'        => 'ecommerce',
				'pluginBasename'  => 'popup-maker-lms-popups/popup-maker-lms-popups.php',
				'image'           => '',
				'url'             => 'https://wppopupmaker.com/extensions/lms-popups/',
				'isProPlus'       => true,
			],
			'popup-maker-mailchimp-integration'   => [
				'name'            => 'MailChimp Integration',
				'description'     => 'Subscribe popup visitors to Mailchimp audiences and interest groups.',
				'longDescription' => 'Connect any Popup Maker form to a Mailchimp audience, map custom fields, and set interest groups per popup.',
				'features'        => [
					'Audience and group mapping',
					'Double opt-in support',
					'Custom merge fields',
				],
				'category'        => 'forms',
				'pluginBasename'  => 'popup-maker-mailchimp-integration/pum-mailchimp-integration.php',
				'legacyBasename'  => 'pum-mailchimp-integration/pum-mailchimp-integration.php',
				'image'           => $this->image_url( 'mailchimp-integration.png' ),
				'url'             => 'https://wppopupmaker.com/extensions/mailchimp-integration/',
			],
			'popup-maker-remote-content'          => [
				'name'            => 'Remote Content',
				'description'     => 'Load external or dynamic content into popups only when it is needed.',
				'longDescription' => 'Fetch popup content on open instead of on page load, so heavy or personalized content never slows down the page.',
				'features'        => [
					'Lazy-loaded content',
					'External URL or shortcode',
					'Caching controls',
				],
				'category'        => 'content',
				'pluginBasename'  => 'popup-maker-remote-content/popup-maker-remote-content.php',
				'image'           => $this->image_url( 'remote-content.png' ),
				'url'             => 'https://wppopupmaker.com/extensions/remote-content/',
			],
			'popup-maker-secure-idle-user-logout' => [
				'name'            => 'Secure Idle User Logout',
				'description'     => 'Protect sessions by securely logging out users after a period of inactivity.',
				'longDescription' => 'Warn users before an idle timeout and log them out cleanly when the timer runs down.',
				'features'        => [
					'Configurable idle window',
					'Countdown warning popup',
					'Per-role rules',
				],
				'category'        => 'compliance',
				'pluginBasename'  => 'popup-maker-secure-idle-user-logout/popup-maker-secure-idle-user-logout.php',
				'image'           => $this->image_url( 'secure-idle-user-logout.png' ),
				'url'             => 'https://wppopupmaker.com/extensions/secure-idle-user-logout/',
			],
			'popup-maker-terms-conditions-popups' => [
				'name'            => 'Terms & Conditions Popups',
				'description'     => 'Require visitors to review and accept terms before continuing.',
				'longDescription' => 'Present terms in a scrollable modal and require an explicit accept before the visitor can continue.',
				'features'        => [
					'Scroll-to-accept option',
					'Logs acceptance',
					'Works with forms and checkout',
				],
				'category'        => 'compliance',
				'pluginBasename'  => 'popup-maker-terms-conditions-popups/popup-maker-terms-conditions-popups.php',
				'image'           => $this->image_url( 'terms-conditions-popups.png' ),
				'url'             => 'https://wppopupmaker.com/extensions/terms-conditions-popups/',
			],
			'popup-maker-videos'                  => [
				'name'            => 'Videos',
				'description'     => 'Create responsive video popups with reliable playback controls.',
				'longDescription' => 'Drop YouTube, Vimeo, or self-hosted video into a popup that sizes itself and stops playback on close.',
				'features'        => [
					'YouTube, Vimeo, and self-hosted',
					'Auto-pause on close',
					'Responsive sizing',
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
