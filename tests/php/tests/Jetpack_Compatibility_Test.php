<?php
/**
 * Jetpack compatibility controller tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\Controllers\Compatibility\Plugin\Jetpack;

/**
 * Test Jetpack Forms rendering compatibility.
 */
class Jetpack_Compatibility_Test extends WP_UnitTestCase {

	/**
	 * Controller under test.
	 *
	 * @var Jetpack
	 */
	private $controller;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->controller = new Jetpack( new stdClass() );
		$this->controller->init();
	}

	/**
	 * Remove compatibility hooks.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		remove_filter( 'render_block_data', [ $this->controller, 'normalize_popup_form_block_data' ] );

		parent::tearDown();
	}

	/**
	 * Test object-valued Jetpack field attributes are normalized recursively.
	 */
	public function test_normalizes_jetpack_field_block_attribute_objects() {
		$parsed_block = [
			'blockName'   => 'jetpack/field-name',
			'attrs'       => [
				'label'    => 'Name',
				'metadata' => (object) [
					'bindings' => (object) [
						'label' => (object) [
							'value' => 'Contact name',
						],
					],
				],
			],
			'innerBlocks' => [
				[
					'blockName' => 'jetpack/label',
					'attrs'     => [
						'metadata' => (object) [
							'value' => 'Name',
						],
					],
				],
			],
		];

		$normalized = $this->normalize_during_popup_rendering( $parsed_block );

		$this->assertIsArray( $normalized['attrs']['metadata'] );
		$this->assertIsArray( $normalized['attrs']['metadata']['bindings'] );
		$this->assertSame( 'Contact name', $normalized['attrs']['metadata']['bindings']['label']['value'] );
		$this->assertIsArray( $normalized['innerBlocks'][0]['attrs']['metadata'] );
	}

	/**
	 * Test unrelated blocks and rendering outside popups remain unchanged.
	 */
	public function test_normalization_is_limited_to_jetpack_fields_in_popups() {
		$metadata      = (object) [ 'value' => 'unchanged' ];
		$jetpack_block = [
			'blockName' => 'jetpack/field-name',
			'attrs'     => [ 'metadata' => $metadata ],
		];
		$core_block    = [
			'blockName' => 'core/paragraph',
			'attrs'     => [ 'metadata' => $metadata ],
		];

		$this->assertSame( $metadata, apply_filters( 'render_block_data', $jetpack_block, $jetpack_block, null )['attrs']['metadata'] );
		$this->assertSame( $metadata, $this->normalize_during_popup_rendering( $core_block )['attrs']['metadata'] );
	}

	/**
	 * Test malformed filter values are left unchanged.
	 */
	public function test_malformed_block_data_is_left_unchanged() {
		$this->assertSame( 'invalid', $this->controller->normalize_popup_form_block_data( 'invalid' ) );
	}

	/**
	 * Apply render_block_data while popup content is being filtered.
	 *
	 * @param array<string, mixed> $parsed_block Parsed block data.
	 * @return array<string, mixed> Filtered block data.
	 */
	private function normalize_during_popup_rendering( $parsed_block ) {
		$normalized = null;
		$callback   = function ( $content ) use ( $parsed_block, &$normalized ) {
			$normalized = apply_filters( 'render_block_data', $parsed_block, $parsed_block, null );
			return $content;
		};

		add_filter( 'pum_popup_content', $callback, 1 );
		apply_filters( 'pum_popup_content', '', 0 );
		remove_filter( 'pum_popup_content', $callback, 1 );

		return $normalized;
	}
}
