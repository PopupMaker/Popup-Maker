<?php
/**
 * Tests for PopupMaker\Services\TemplateLibrary.
 *
 * @package Popup_Maker
 */

/**
 * Test the popup template library service & pattern registration.
 */
class TemplateLibrary_Test extends WP_UnitTestCase {

	/**
	 * Get a fresh service instance (no memoized templates).
	 *
	 * @return \PopupMaker\Services\TemplateLibrary
	 */
	private function fresh_service() {
		return new \PopupMaker\Services\TemplateLibrary( \PopupMaker\plugin() );
	}

	/**
	 * Categories include the expected set.
	 */
	public function test_categories_registered() {
		$categories = $this->fresh_service()->get_categories();

		foreach ( [ 'subscribe', 'sales-promotions', 'announcements', 'lead-capture', 'engagement', 'compliance', 'ecommerce' ] as $slug ) {
			$this->assertArrayHasKey( $slug, $categories );
		}
	}

	/**
	 * Built-in free templates load from disk.
	 */
	public function test_built_in_templates_load() {
		$templates = $this->fresh_service()->get_templates();

		$this->assertArrayHasKey( 'newsletter-signup', $templates );
		$this->assertArrayHasKey( 'welcome-mat', $templates );

		$free = array_filter( $templates, function ( $template ) {
			return 'free' === $template['tier'] && ! empty( $template['content'] );
		} );

		$this->assertCount( 12, $free, 'All 12 free templates should ship with content.' );
	}

	/**
	 * Premium templates appear as locked teasers when nothing registers them.
	 */
	public function test_premium_teasers_backfilled() {
		$service   = $this->fresh_service();
		$templates = $service->get_templates();

		$this->assertArrayHasKey( 'exit-intent-offer', $templates );
		$this->assertArrayHasKey( 'cart-abandonment-offer', $templates );

		$teaser = $templates['exit-intent-offer'];

		$this->assertSame( 'pro', $teaser['tier'] );
		$this->assertFalse( $service->is_insertable( $teaser ) );
		$this->assertStringContainsString( 'utm_content=exit-intent-offer', $teaser['upgrade_url'] );

		$this->assertSame( 'pro_plus', $templates['cart-abandonment-offer']['tier'] );
	}

	/**
	 * Filter registrations with content replace teasers of the same slug.
	 */
	public function test_filter_registration_overrides_teaser() {
		add_filter( 'popup_maker/popup_templates', function ( $templates ) {
			$templates['exit-intent-offer'] = [
				'slug'    => 'exit-intent-offer',
				'name'    => 'Exit Intent Offer',
				'tier'    => 'pro',
				'content' => '<!-- wp:paragraph --><p>Real content</p><!-- /wp:paragraph -->',
			];
			return $templates;
		} );

		$service  = $this->fresh_service();
		$template = $service->get_template( 'exit-intent-offer' );

		$this->assertTrue( $service->is_insertable( $template ) );

		remove_all_filters( 'popup_maker/popup_templates' );
	}

	/**
	 * Invalid definitions are dropped or normalized.
	 */
	public function test_normalization() {
		add_filter( 'popup_maker/popup_templates', function ( $templates ) {
			$templates['no-name']  = [ 'slug' => 'no-name' ];
			$templates['bad-tier'] = [
				'slug'     => 'bad-tier',
				'name'     => 'Bad Tier',
				'tier'     => 'platinum',
				'category' => 'not-a-category',
			];
			return $templates;
		} );

		$templates = $this->fresh_service()->get_templates();

		$this->assertArrayNotHasKey( 'no-name', $templates, 'Templates without a name are dropped.' );
		$this->assertSame( 'free', $templates['bad-tier']['tier'] );
		$this->assertSame( 'engagement', $templates['bad-tier']['category'] );

		remove_all_filters( 'popup_maker/popup_templates' );
	}

	/**
	 * Editor data exposes teasers without content and marks them pro-required.
	 */
	public function test_editor_data_shape() {
		$data = $this->fresh_service()->get_editor_data();

		$this->assertArrayHasKey( 'templates', $data );
		$this->assertArrayHasKey( 'categories', $data );

		$by_slug = [];
		foreach ( $data['templates'] as $template ) {
			$by_slug[ $template['slug'] ] = $template;
		}

		$this->assertFalse( $by_slug['newsletter-signup']['proRequired'] );
		$this->assertNotEmpty( $by_slug['newsletter-signup']['content'] );

		$this->assertTrue( $by_slug['exit-intent-offer']['proRequired'] );
		$this->assertSame( '', $by_slug['exit-intent-offer']['content'] );
		$this->assertNotEmpty( $by_slug['exit-intent-offer']['upgradeUrl'] );
	}

	/**
	 * Every insertable template parses into valid blocks.
	 */
	public function test_template_content_parses_as_blocks() {
		$service = $this->fresh_service();

		foreach ( $service->get_templates() as $slug => $template ) {
			if ( ! $service->is_insertable( $template ) ) {
				continue;
			}

			$blocks = parse_blocks( $template['content'] );

			$named = array_filter( $blocks, function ( $block ) {
				return ! empty( $block['blockName'] );
			} );

			$this->assertNotEmpty( $named, "Template {$slug} should parse into named blocks." );

			// No stray top-level freeform chunks (stray HTML outside block comments).
			foreach ( $blocks as $block ) {
				if ( null === $block['blockName'] ) {
					$this->assertSame( '', trim( implode( '', $block['innerContent'] ) ), "Template {$slug} contains stray non-block content." );
				}
			}
		}
	}

	/**
	 * Block patterns & categories register for the popup post type.
	 */
	public function test_block_patterns_registered() {
		$controller = \PopupMaker\plugin()->get_controller( 'TemplateLibrary' );

		$this->assertNotNull( $controller );

		$controller->register_block_patterns();

		$this->assertTrue( \WP_Block_Pattern_Categories_Registry::get_instance()->is_registered( 'popup-maker-templates' ) );
		$this->assertTrue( \WP_Block_Patterns_Registry::get_instance()->is_registered( 'popup-maker/newsletter-signup' ) );

		$pattern = \WP_Block_Patterns_Registry::get_instance()->get_registered( 'popup-maker/newsletter-signup' );

		$this->assertSame( [ 'popup' ], $pattern['postTypes'] );

		// Locked teasers must not register as patterns.
		$this->assertFalse( \WP_Block_Patterns_Registry::get_instance()->is_registered( 'popup-maker/exit-intent-offer' ) );
	}
}
