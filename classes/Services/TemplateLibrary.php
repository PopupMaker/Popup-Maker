<?php
/**
 * Template library service.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Popup template library registry.
 *
 * Collects popup content templates (Gutenberg block layouts) from core,
 * Pro & addons via the `popup_maker/popup_templates` filter, and exposes
 * them for block pattern registration and the editor template picker.
 *
 * @since X.X.X
 */
class TemplateLibrary {

	/**
	 * Plugin container.
	 *
	 * @var \PopupMaker\Plugin\Core
	 */
	public $container;

	/**
	 * Resolved template collection.
	 *
	 * @var array<string,array<string,mixed>>|null
	 */
	private $templates;

	/**
	 * Initialize the service.
	 *
	 * @param \PopupMaker\Plugin\Core $container Plugin container.
	 */
	public function __construct( $container ) {
		$this->container = $container;
	}

	/**
	 * Get all registered template categories.
	 *
	 * @return array<string,string> Category slug => label.
	 */
	public function get_categories() {
		$categories = [
			'subscribe'        => __( 'Subscribe & Opt-in', 'popup-maker' ),
			'sales-promotions' => __( 'Sales & Promotions', 'popup-maker' ),
			'announcements'    => __( 'Announcements', 'popup-maker' ),
			'lead-capture'     => __( 'Lead Capture', 'popup-maker' ),
			'engagement'       => __( 'Engagement', 'popup-maker' ),
			'compliance'       => __( 'Compliance', 'popup-maker' ),
			'ecommerce'        => __( 'Ecommerce', 'popup-maker' ),
		];

		/**
		 * Filter the registered popup template categories.
		 *
		 * @param array<string,string> $categories Category slug => label.
		 *
		 * @since X.X.X
		 */
		return apply_filters( 'popup_maker/popup_template_categories', $categories );
	}

	/**
	 * Get all registered popup templates keyed by slug.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_templates() {
		if ( null !== $this->templates ) {
			return $this->templates;
		}

		$templates = $this->load_built_in_templates();

		/**
		 * Filter the registered popup templates.
		 *
		 * Pro & addons register their templates here. Registrations that
		 * include `content` replace core placeholder (teaser) entries of
		 * the same slug.
		 *
		 * @param array<string,array<string,mixed>> $templates Templates keyed by slug.
		 *
		 * @since X.X.X
		 */
		$templates = apply_filters( 'popup_maker/popup_templates', $templates );

		// Backfill teasers for premium templates that nothing registered.
		foreach ( $this->get_teaser_templates() as $slug => $teaser ) {
			if ( ! isset( $templates[ $slug ] ) ) {
				$templates[ $slug ] = $teaser;
			}
		}

		$normalized = [];

		foreach ( $templates as $slug => $template ) {
			$template = $this->normalize_template( is_string( $slug ) ? $slug : '', $template );

			if ( false !== $template ) {
				$normalized[ $template['slug'] ] = $template;
			}
		}

		$this->templates = $normalized;

		return $this->templates;
	}

	/**
	 * Get a single template by slug.
	 *
	 * @param string $slug Template slug.
	 *
	 * @return array<string,mixed>|null
	 */
	public function get_template( $slug ) {
		$templates = $this->get_templates();

		return isset( $templates[ $slug ] ) ? $templates[ $slug ] : null;
	}

	/**
	 * Whether a template has insertable content.
	 *
	 * @param array<string,mixed> $template Template definition.
	 *
	 * @return bool
	 */
	public function is_insertable( $template ) {
		return ! empty( $template['content'] ) && is_string( $template['content'] );
	}

	/**
	 * Get template data formatted for the block editor picker.
	 *
	 * @return array<string,mixed>
	 */
	public function get_editor_data() {
		$templates = [];

		foreach ( $this->get_templates() as $template ) {
			$insertable = $this->is_insertable( $template );

			$templates[] = [
				'slug'          => $template['slug'],
				'name'          => $template['name'],
				'description'   => $template['description'],
				'category'      => $template['category'],
				'tier'          => $template['tier'],
				'keywords'      => $template['keywords'],
				'viewportWidth' => $template['viewport_width'],
				'content'       => $insertable ? $template['content'] : '',
				'recommended'   => $template['recommended'],
				'proRequired'   => ! $insertable && 'free' !== $template['tier'],
				'upgradeUrl'    => $template['upgrade_url'],
			];
		}

		return [
			'templates'  => $templates,
			'categories' => $this->get_categories(),
			'i10n'       => [
				'proLabel'     => _x( 'Pro', 'Template picker pro tier badge', 'popup-maker' ),
				'proPlusLabel' => _x( 'Pro+', 'Template picker pro plus tier badge', 'popup-maker' ),
			],
		];
	}

	/**
	 * Load built-in template definitions from disk.
	 *
	 * Each file in `includes/popup-templates/` returns a full template
	 * definition array.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function load_built_in_templates() {
		$templates = [];

		$path  = trailingslashit( $this->container->get_path( 'includes/popup-templates' ) );
		$files = glob( $path . '*.php' );

		if ( false === $files ) {
			return $templates;
		}

		foreach ( $files as $file ) {
			$template = include $file;

			if ( is_array( $template ) && ! empty( $template['slug'] ) ) {
				$templates[ $template['slug'] ] = $template;
			}
		}

		return $templates;
	}

	/**
	 * Normalize a template definition.
	 *
	 * @param string              $slug     Registration key, used as fallback slug.
	 * @param array<string,mixed> $template Raw template definition.
	 *
	 * @return array<string,mixed>|false Normalized template or false when invalid.
	 */
	private function normalize_template( $slug, $template ) {
		if ( ! is_array( $template ) ) {
			return false;
		}

		$template = wp_parse_args( $template, [
			'slug'           => $slug,
			'name'           => '',
			'description'    => '',
			'category'       => 'engagement',
			'tier'           => 'free',
			'source'         => 'popup-maker',
			'keywords'       => [],
			'viewport_width' => 480,
			'content'        => '',
			'recommended'    => [],
			'upgrade_url'    => '',
		] );

		if ( empty( $template['slug'] ) || empty( $template['name'] ) ) {
			return false;
		}

		if ( ! isset( $this->get_categories()[ $template['category'] ] ) ) {
			$template['category'] = 'engagement';
		}

		if ( ! in_array( $template['tier'], [ 'free', 'pro', 'pro_plus' ], true ) ) {
			$template['tier'] = 'free';
		}

		$template['recommended'] = wp_parse_args( is_array( $template['recommended'] ) ? $template['recommended'] : [], [
			'triggers' => [],
			'cookies'  => [],
			'notes'    => '',
		] );

		return $template;
	}

	/**
	 * Teaser entries for premium templates.
	 *
	 * Shown locked in the template picker when Pro (or the required Pro+
	 * addon) has not registered the real template. Mirrors the preview
	 * conventions used by \PUM_Upsell for triggers & conditions.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function get_teaser_templates() {
		$teasers = [
			// Pro tier.
			'exit-intent-offer'       => [
				'name'        => __( 'Exit Intent Offer', 'popup-maker' ),
				'description' => __( 'Catch abandoning visitors with a last-chance discount before they leave.', 'popup-maker' ),
				'category'    => 'sales-promotions',
				'tier'        => 'pro',
			],
			'deadline-sale'           => [
				'name'        => __( 'Deadline Sale', 'popup-maker' ),
				'description' => __( 'High-urgency sale layout built around a hard deadline.', 'popup-maker' ),
				'category'    => 'sales-promotions',
				'tier'        => 'pro',
			],
			'holiday-sale'            => [
				'name'        => __( 'Holiday Sale', 'popup-maker' ),
				'description' => __( 'Seasonal promotion layout ready for Black Friday & holiday campaigns.', 'popup-maker' ),
				'category'    => 'sales-promotions',
				'tier'        => 'pro',
			],
			'yes-no-multistep'        => [
				'name'        => __( 'Yes / No Multistep', 'popup-maker' ),
				'description' => __( 'Two-step engagement popup that qualifies visitors before the offer.', 'popup-maker' ),
				'category'    => 'lead-capture',
				'tier'        => 'pro',
			],
			'webinar-registration'    => [
				'name'        => __( 'Webinar Registration', 'popup-maker' ),
				'description' => __( 'Promote your next live event and drive registrations.', 'popup-maker' ),
				'category'    => 'lead-capture',
				'tier'        => 'pro',
			],
			'scroll-content-upgrade'  => [
				'name'        => __( 'Scroll Content Upgrade', 'popup-maker' ),
				'description' => __( 'Offer a bonus resource to engaged readers as they scroll.', 'popup-maker' ),
				'category'    => 'lead-capture',
				'tier'        => 'pro',
			],
			'nps-feedback'            => [
				'name'        => __( 'NPS Feedback', 'popup-maker' ),
				'description' => __( 'Ask visitors how likely they are to recommend you.', 'popup-maker' ),
				'category'    => 'engagement',
				'tier'        => 'pro',
			],
			'loyalty-signup'          => [
				'name'        => __( 'Loyalty Program Signup', 'popup-maker' ),
				'description' => __( 'Invite repeat visitors to join your rewards program.', 'popup-maker' ),
				'category'    => 'engagement',
				'tier'        => 'pro',
			],
			'referral-invite'         => [
				'name'        => __( 'Referral Invite', 'popup-maker' ),
				'description' => __( 'Turn happy customers into advocates with a share-and-earn invite.', 'popup-maker' ),
				'category'    => 'engagement',
				'tier'        => 'pro',
			],
			// Pro+ (ecommerce addon) tier.
			'cart-abandonment-offer'  => [
				'name'        => __( 'Cart Abandonment Offer', 'popup-maker' ),
				'description' => __( 'Recover abandoning shoppers with a targeted discount at exit.', 'popup-maker' ),
				'category'    => 'ecommerce',
				'tier'        => 'pro_plus',
			],
			'free-shipping-threshold' => [
				'name'        => __( 'Free Shipping Threshold', 'popup-maker' ),
				'description' => __( 'Nudge shoppers to add more to their cart to unlock free shipping.', 'popup-maker' ),
				'category'    => 'ecommerce',
				'tier'        => 'pro_plus',
			],
			'product-cross-sell'      => [
				'name'        => __( 'Product Cross-sell', 'popup-maker' ),
				'description' => __( 'Recommend a complementary product with a one-click add to cart.', 'popup-maker' ),
				'category'    => 'ecommerce',
				'tier'        => 'pro_plus',
			],
			'back-in-stock-notify'    => [
				'name'        => __( 'Back in Stock Notify', 'popup-maker' ),
				'description' => __( 'Capture demand for sold-out products with restock alerts.', 'popup-maker' ),
				'category'    => 'ecommerce',
				'tier'        => 'pro_plus',
			],
			'first-purchase-discount' => [
				'name'        => __( 'First Purchase Discount', 'popup-maker' ),
				'description' => __( 'Convert first-time visitors into customers with a welcome discount.', 'popup-maker' ),
				'category'    => 'ecommerce',
				'tier'        => 'pro_plus',
			],
		];

		foreach ( $teasers as $slug => $teaser ) {
			$teasers[ $slug ]['slug']        = $slug;
			$teasers[ $slug ]['upgrade_url'] = $this->get_upgrade_url( $slug, $teaser['tier'] );
		}

		return $teasers;
	}

	/**
	 * Build an upgrade URL for a locked template.
	 *
	 * @param string $slug Template slug.
	 * @param string $tier Template tier.
	 *
	 * @return string
	 */
	private function get_upgrade_url( $slug, $tier ) {
		$url = 'pro_plus' === $tier
			? 'https://wppopupmaker.com/addons/ecommerce-popups/'
			: 'https://wppopupmaker.com/pricing/';

		return add_query_arg( [
			'utm_campaign' => 'upgrade-to-pro',
			'utm_source'   => 'popup-template-library',
			'utm_medium'   => 'plugin-ui',
			'utm_content'  => $slug,
		], $url );
	}
}
