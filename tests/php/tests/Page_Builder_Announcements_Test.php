<?php
/**
 * Page builder announcement tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\Services\Notifications\PageBuilderAnnouncements;

/**
 * Verify builder-aware admin announcements.
 */
class Page_Builder_Announcements_Test extends WP_UnitTestCase {

	/** @return void */
	public function tearDown(): void {
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/** @return void */
	public function test_announcement_requires_popup_edit_access() {
		$provider = $this->make_provider(
			[
				[
					'slug'  => 'elementor',
					'label' => 'Elementor',
				],
			]
		);

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'subscriber' ] ) );

		$this->assertSame( [], $provider->register_announcement( [] ) );
	}

	/** @return void */
	public function test_announcement_is_omitted_without_an_available_builder() {
		$provider = $this->make_provider( [] );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertSame( [], $provider->register_announcement( [] ) );
	}

	/** @return void */
	public function test_single_builder_announcement_names_the_builder() {
		$provider = $this->make_provider(
			[
				[
					'slug'  => 'elementor',
					'label' => 'Elementor',
				],
			]
		);

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );
		$alerts = $provider->register_announcement( [] );

		$this->assertCount( 1, $alerts );
		$this->assertSame( 'pm_feat_page_builder_support_2026_elementor', $alerts[0]['code'] );
		$this->assertStringContainsString( 'Elementor', $alerts[0]['title'] );
		$this->assertStringContainsString( '<strong>Elementor</strong>', $alerts[0]['message'] );
		$this->assertSame( 'feature', $alerts[0]['category'] );
		$this->assertTrue( $alerts[0]['dismissible'] );
		$this->assertStringStartsWith(
			'https://wppopupmaker.com/page-builder-integrations/elementor/',
			$alerts[0]['actions'][0]['href']
		);
	}

	/** @return void */
	public function test_single_builder_announcements_use_the_published_guides() {
		$guide_urls = [
			'elementor'       => 'https://wppopupmaker.com/page-builder-integrations/elementor/',
			'bricks'          => 'https://wppopupmaker.com/page-builder-integrations/bricks/',
			'divi'            => 'https://wppopupmaker.com/page-builder-integrations/divi/',
			'beaver-builder'  => 'https://wppopupmaker.com/page-builder-integrations/beaver-builder/',
			'siteorigin'      => 'https://wppopupmaker.com/page-builder-integrations/siteorigin/',
			'brizy'           => 'https://wppopupmaker.com/page-builder-integrations/brizy/',
			'visual-composer' => 'https://wppopupmaker.com/page-builder-integrations/visual-composer/',
			'etch'            => 'https://wppopupmaker.com/page-builder-integrations/etch/',
		];

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		foreach ( $guide_urls as $slug => $guide_url ) {
			$provider = $this->make_provider(
				[
					[
						'slug'  => $slug,
						'label' => ucfirst( $slug ),
					],
				]
			);
			$alerts   = $provider->register_announcement( [] );

			$this->assertStringStartsWith( $guide_url, $alerts[0]['actions'][0]['href'] );
		}
	}

	/** @return void */
	public function test_multiple_builders_share_one_scoped_announcement() {
		$provider = $this->make_provider(
			[
				[
					'slug'  => 'elementor',
					'label' => 'Elementor',
				],
				[
					'slug'  => 'bricks',
					'label' => 'Bricks',
				],
			]
		);

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );
		$alerts = $provider->register_announcement( [] );

		$this->assertCount( 1, $alerts );
		$this->assertSame( 'pm_feat_page_builder_support_2026_elementor_bricks', $alerts[0]['code'] );
		$this->assertStringContainsString( 'Elementor', $alerts[0]['message'] );
		$this->assertStringContainsString( 'Bricks', $alerts[0]['message'] );
		$this->assertCount( 2, $alerts[0]['actions'] );
		$this->assertStringStartsWith( PageBuilderAnnouncements::GUIDE_URL, $alerts[0]['actions'][0]['href'] );
	}

	/** @return void */
	public function test_unknown_single_builder_falls_back_to_the_hub() {
		$provider = $this->make_provider(
			[
				[
					'slug'  => 'future-builder',
					'label' => 'Future Builder',
				],
			]
		);

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );
		$alerts = $provider->register_announcement( [] );

		$this->assertStringStartsWith( PageBuilderAnnouncements::GUIDE_URL, $alerts[0]['actions'][0]['href'] );
	}

	/**
	 * Create a provider with deterministic builder details.
	 *
	 * @param array<int,array{slug:string,label:string}> $builders Builder details.
	 *
	 * @return PageBuilderAnnouncements
	 */
	private function make_provider( $builders ) {
		return new class( \PopupMaker\plugin(), $builders ) extends PageBuilderAnnouncements {

			/** @var array<int,array{slug:string,label:string}> */
			private $test_builders;

			/**
			 * @param \PopupMaker\Plugin\Core                    $container Plugin container.
			 * @param array<int,array{slug:string,label:string}> $builders  Builder details.
			 */
			public function __construct( $container, $builders ) {
				parent::__construct( $container );

				$this->test_builders = $builders;
			}

			/** @return array<int,array{slug:string,label:string}> */
			protected function get_available_builders() {
				return $this->test_builders;
			}
		};
	}
}
