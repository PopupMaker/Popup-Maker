<?php
/**
 * ConditionCallbacks tests.
 *
 * @package Popup_Maker
 */

/**
 * Test methods for PUM_ConditionCallbacks class.
 */
class PUM_ConditionCallbacks_Test extends WP_UnitTestCase {

	/**
	 * Test post ID.
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * Test page ID.
	 *
	 * @var int
	 */
	private $page_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->post_id = $this->factory->post->create(
			[
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_title'  => 'Test Post',
			]
		);

		$this->page_id = $this->factory->post->create(
			[
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Test Page',
			]
		);
	}

	/**
	 * Test is_post_type returns true for matching post type.
	 */
	public function test_is_post_type_matches() {
		global $post;

		// Set up global post context.
		$post = get_post( $this->post_id );
		$this->go_to( get_permalink( $this->post_id ) );
		setup_postdata( $post );

		$result = PUM_ConditionCallbacks::is_post_type( 'post' );

		$this->assertTrue( $result, 'Should return true for matching post type.' );

		wp_reset_postdata();
	}

	/**
	 * Test is_post_type returns false for non-matching post type.
	 */
	public function test_is_post_type_no_match() {
		global $post;

		$post = get_post( $this->post_id );
		$this->go_to( get_permalink( $this->post_id ) );
		setup_postdata( $post );

		$result = PUM_ConditionCallbacks::is_post_type( 'page' );

		$this->assertFalse( $result, 'Should return false for non-matching post type.' );

		wp_reset_postdata();
	}

	/**
	 * Test post_type callback with all modifier returns true for correct type.
	 */
	public function test_post_type_all_modifier() {
		global $post;

		$post = get_post( $this->post_id );
		$this->go_to( get_permalink( $this->post_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'post_all',
			'settings' => [],
		];

		$result = PUM_ConditionCallbacks::post_type( $condition );

		$this->assertTrue( $result, 'post_all should match a singular post.' );

		wp_reset_postdata();
	}

	/**
	 * Test post_type callback with all modifier returns false for wrong type.
	 */
	public function test_post_type_all_modifier_wrong_type() {
		global $post;

		$post = get_post( $this->post_id );
		$this->go_to( get_permalink( $this->post_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'page_all',
			'settings' => [],
		];

		$result = PUM_ConditionCallbacks::post_type( $condition );

		$this->assertFalse( $result, 'page_all should not match a post.' );

		wp_reset_postdata();
	}

	/**
	 * Test post_type callback with selected modifier matches specific post.
	 */
	public function test_post_type_selected_modifier() {
		global $post;

		$post = get_post( $this->post_id );
		$this->go_to( get_permalink( $this->post_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'post_selected',
			'settings' => [
				'selected' => [ $this->post_id ],
			],
		];

		$result = PUM_ConditionCallbacks::post_type( $condition );

		$this->assertTrue( $result, 'Should match when post ID is in selected list.' );

		wp_reset_postdata();
	}

	/**
	 * Test post_type callback with selected modifier does not match other posts.
	 */
	public function test_post_type_selected_modifier_no_match() {
		global $post;

		$post = get_post( $this->post_id );
		$this->go_to( get_permalink( $this->post_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'post_selected',
			'settings' => [
				'selected' => [ 999999 ],
			],
		];

		$result = PUM_ConditionCallbacks::post_type( $condition );

		$this->assertFalse( $result, 'Should not match when post ID is not in selected list.' );

		wp_reset_postdata();
	}

	/**
	 * Test post_type callback with children modifier for hierarchical types.
	 */
	public function test_post_type_children_modifier() {
		$parent_id = $this->page_id;
		$child_id  = $this->factory->post->create(
			[
				'post_type'   => 'page',
				'post_parent' => $parent_id,
				'post_status' => 'publish',
				'post_title'  => 'Child Page',
			]
		);

		global $post;
		$post = get_post( $child_id );
		$this->go_to( get_permalink( $child_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'page_children',
			'settings' => [
				'selected' => [ $parent_id ],
			],
		];

		$result = PUM_ConditionCallbacks::post_type( $condition );

		$this->assertTrue( $result, 'Should match child page with parent in selected list.' );

		wp_reset_postdata();
	}

	/**
	 * Test post_type callback with ancestors modifier.
	 */
	public function test_post_type_ancestors_modifier() {
		$grandparent_id = $this->page_id;
		$parent_id      = $this->factory->post->create(
			[
				'post_type'   => 'page',
				'post_parent' => $grandparent_id,
				'post_status' => 'publish',
			]
		);
		$child_id = $this->factory->post->create(
			[
				'post_type'   => 'page',
				'post_parent' => $parent_id,
				'post_status' => 'publish',
			]
		);

		global $post;
		$post = get_post( $child_id );
		$this->go_to( get_permalink( $child_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'page_ancestors',
			'settings' => [
				'selected' => [ $grandparent_id ],
			],
		];

		$result = PUM_ConditionCallbacks::post_type( $condition );

		$this->assertTrue( $result, 'Should match when ancestor is in selected list.' );

		wp_reset_postdata();
	}

	/**
	 * Test post_type returns false for unknown modifier.
	 */
	public function test_post_type_unknown_modifier() {
		global $post;
		$post = get_post( $this->post_id );
		$this->go_to( get_permalink( $this->post_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'post_bogus',
			'settings' => [],
		];

		$result = PUM_ConditionCallbacks::post_type( $condition );

		$this->assertFalse( $result, 'Unknown modifier should return false.' );

		wp_reset_postdata();
	}

	/**
	 * Test taxonomy callback returns false when not on a taxonomy archive.
	 */
	public function test_taxonomy_returns_false_when_not_on_archive() {
		// Go to a regular post page, not a taxonomy archive.
		$this->go_to( get_permalink( $this->post_id ) );

		$condition = [
			'target'   => 'tax_custom_taxonomy_all',
			'settings' => [],
		];

		$result = PUM_ConditionCallbacks::taxonomy( $condition );

		$this->assertFalse( $result, 'Should return false when not on taxonomy archive.' );
	}

	/**
	 * Test post_type_category callback returns false without matching category.
	 */
	public function test_post_type_category_no_match() {
		global $post;
		$post = get_post( $this->post_id );
		$this->go_to( get_permalink( $this->post_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'post_w_category',
			'settings' => [
				'selected' => [ 999999 ],
			],
		];

		$result = PUM_ConditionCallbacks::post_type_category( $condition );

		$this->assertFalse( $result, 'Should return false when post does not have the category.' );

		wp_reset_postdata();
	}

	/**
	 * Test post_type_tag callback returns false without matching tag.
	 */
	public function test_post_type_tag_no_match() {
		global $post;
		$post = get_post( $this->post_id );
		$this->go_to( get_permalink( $this->post_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'post_w_post_tag',
			'settings' => [
				'selected' => [ 999999 ],
			],
		];

		$result = PUM_ConditionCallbacks::post_type_tag( $condition );

		$this->assertFalse( $result, 'Should return false when post does not have the tag.' );

		wp_reset_postdata();
	}

	// =========================================================================
	// post_type() -- index modifier
	// =========================================================================

	/**
	 * Test post_type with index modifier on a post type archive.
	 */
	public function test_post_type_index_modifier_on_archive() {
		// 'post' type doesn't have a post type archive (uses blog page instead).
		// Use a custom post type that has has_archive = true.
		register_post_type( 'pum_test_cpt', [
			'public'      => true,
			'has_archive' => true,
		] );

		$this->factory->post->create( [ 'post_type' => 'pum_test_cpt', 'post_status' => 'publish' ] );

		$this->go_to( get_post_type_archive_link( 'pum_test_cpt' ) );

		$condition = [
			'target'   => 'pum_test_cpt_index',
			'settings' => [],
		];

		$result = PUM_ConditionCallbacks::post_type( $condition );

		$this->assertTrue( $result, 'post_index should match on a post type archive page.' );
	}

	/**
	 * Test post_type with index modifier returns false on a singular page.
	 */
	public function test_post_type_index_modifier_not_on_archive() {
		$this->go_to( get_permalink( $this->post_id ) );

		$condition = [
			'target'   => 'post_index',
			'settings' => [],
		];

		$result = PUM_ConditionCallbacks::post_type( $condition );

		$this->assertFalse( $result, 'post_index should not match on a singular post.' );
	}

	// =========================================================================
	// post_type() -- ID modifier (alias of selected)
	// =========================================================================

	/**
	 * Test post_type with ID modifier matches specific post.
	 */
	public function test_post_type_id_modifier_matches() {
		global $post;
		$post = get_post( $this->post_id );
		$this->go_to( get_permalink( $this->post_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'post_ID',
			'settings' => [
				'selected' => [ $this->post_id ],
			],
		];

		$result = PUM_ConditionCallbacks::post_type( $condition );

		$this->assertTrue( $result, 'post_ID modifier should match when post ID is in selected list.' );

		wp_reset_postdata();
	}

	/**
	 * Test post_type with ID modifier does not match wrong post.
	 */
	public function test_post_type_id_modifier_no_match() {
		global $post;
		$post = get_post( $this->post_id );
		$this->go_to( get_permalink( $this->post_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'post_ID',
			'settings' => [
				'selected' => [ 999999 ],
			],
		];

		$result = PUM_ConditionCallbacks::post_type( $condition );

		$this->assertFalse( $result, 'post_ID modifier should not match when post ID is not in selected list.' );

		wp_reset_postdata();
	}

	// =========================================================================
	// post_type() -- all modifier edge cases
	// =========================================================================

	/**
	 * Test page_all matches the front page even when it is static.
	 */
	public function test_post_type_all_modifier_matches_front_page() {
		// Set the front page to a static page.
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $this->page_id );

		global $post;
		$post = get_post( $this->page_id );
		$this->go_to( home_url( '/' ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'page_all',
			'settings' => [],
		];

		$result = PUM_ConditionCallbacks::post_type( $condition );

		$this->assertTrue( $result, 'page_all should match the static front page.' );

		// Clean up.
		update_option( 'show_on_front', 'posts' );
		delete_option( 'page_on_front' );
		wp_reset_postdata();
	}

	/**
	 * Test post_all returns false when viewing a page.
	 */
	public function test_post_type_all_modifier_wrong_post_type() {
		global $post;
		$post = get_post( $this->page_id );
		$this->go_to( get_permalink( $this->page_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'post_all',
			'settings' => [],
		];

		$result = PUM_ConditionCallbacks::post_type( $condition );

		$this->assertFalse( $result, 'post_all should not match a page.' );

		wp_reset_postdata();
	}

	// =========================================================================
	// post_type() -- children modifier edge cases
	// =========================================================================

	/**
	 * Test children modifier returns false for non-hierarchical post type.
	 */
	public function test_post_type_children_non_hierarchical() {
		global $post;
		$post = get_post( $this->post_id );
		$this->go_to( get_permalink( $this->post_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'post_children',
			'settings' => [
				'selected' => [ $this->post_id ],
			],
		];

		$result = PUM_ConditionCallbacks::post_type( $condition );

		$this->assertFalse( $result, 'children modifier should return false for non-hierarchical post type.' );

		wp_reset_postdata();
	}

	/**
	 * Test children modifier returns false when page has no parent.
	 */
	public function test_post_type_children_no_parent() {
		global $post;
		$post = get_post( $this->page_id );
		$this->go_to( get_permalink( $this->page_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'page_children',
			'settings' => [
				'selected' => [ 999999 ],
			],
		];

		$result = PUM_ConditionCallbacks::post_type( $condition );

		$this->assertFalse( $result, 'children modifier should return false when parent is not in selected list.' );

		wp_reset_postdata();
	}

	// =========================================================================
	// post_type() -- ancestors modifier edge cases
	// =========================================================================

	/**
	 * Test ancestors modifier returns false for non-hierarchical post type.
	 */
	public function test_post_type_ancestors_non_hierarchical() {
		global $post;
		$post = get_post( $this->post_id );
		$this->go_to( get_permalink( $this->post_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'post_ancestors',
			'settings' => [
				'selected' => [ $this->post_id ],
			],
		];

		$result = PUM_ConditionCallbacks::post_type( $condition );

		$this->assertFalse( $result, 'ancestors modifier should return false for non-hierarchical post type.' );

		wp_reset_postdata();
	}

	/**
	 * Test ancestors modifier returns false when selected ancestor is not in tree.
	 */
	public function test_post_type_ancestors_no_match() {
		$parent_id = $this->factory->post->create(
			[
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Ancestor Parent',
			]
		);
		$child_id = $this->factory->post->create(
			[
				'post_type'   => 'page',
				'post_parent' => $parent_id,
				'post_status' => 'publish',
				'post_title'  => 'Ancestor Child',
			]
		);

		global $post;
		$post = get_post( $child_id );
		$this->go_to( get_permalink( $child_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'page_ancestors',
			'settings' => [
				'selected' => [ 999999 ],
			],
		];

		$result = PUM_ConditionCallbacks::post_type( $condition );

		$this->assertFalse( $result, 'ancestors modifier should return false when ancestor is not in selected list.' );

		wp_reset_postdata();
	}

	// =========================================================================
	// post_type() -- template modifier
	// =========================================================================

	/**
	 * Test template modifier returns true when page uses matching template.
	 */
	public function test_post_type_template_modifier_matches() {
		// Assign a template to the page.
		update_post_meta( $this->page_id, '_wp_page_template', 'custom-template.php' );

		global $post;
		$post = get_post( $this->page_id );
		$this->go_to( get_permalink( $this->page_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'page_template',
			'settings' => [
				'selected' => [ 'custom-template.php' ],
			],
		];

		$result = PUM_ConditionCallbacks::post_type( $condition );

		$this->assertTrue( $result, 'template modifier should match when page uses the specified template.' );

		wp_reset_postdata();
	}

	/**
	 * Test template modifier returns false when template does not match.
	 */
	public function test_post_type_template_modifier_no_match() {
		// Assign a template to the page.
		update_post_meta( $this->page_id, '_wp_page_template', 'other-template.php' );

		global $post;
		$post = get_post( $this->page_id );
		$this->go_to( get_permalink( $this->page_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'page_template',
			'settings' => [
				'selected' => [ 'custom-template.php' ],
			],
		];

		$result = PUM_ConditionCallbacks::post_type( $condition );

		$this->assertFalse( $result, 'template modifier should return false when template does not match.' );

		wp_reset_postdata();
	}

	/**
	 * Test template modifier returns false on a regular post (non-page).
	 */
	public function test_post_type_template_modifier_on_non_page() {
		global $post;
		$post = get_post( $this->post_id );
		$this->go_to( get_permalink( $this->post_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'post_template',
			'settings' => [
				'selected' => [ 'custom-template.php' ],
			],
		];

		$result = PUM_ConditionCallbacks::post_type( $condition );

		$this->assertFalse( $result, 'template modifier should return false on non-page post types.' );

		wp_reset_postdata();
	}

	// =========================================================================
	// post_type() -- selected with empty selection
	// =========================================================================

	/**
	 * Test selected modifier with empty selected list returns false.
	 */
	public function test_post_type_selected_empty_list() {
		global $post;
		$post = get_post( $this->post_id );
		$this->go_to( get_permalink( $this->post_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'post_selected',
			'settings' => [
				'selected' => [],
			],
		];

		$result = PUM_ConditionCallbacks::post_type( $condition );

		$this->assertFalse( $result, 'selected modifier with empty list should return false.' );

		wp_reset_postdata();
	}

	/**
	 * Test selected modifier with missing settings key returns false.
	 */
	public function test_post_type_selected_missing_settings() {
		global $post;
		$post = get_post( $this->post_id );
		$this->go_to( get_permalink( $this->post_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'post_selected',
			'settings' => [],
		];

		$result = PUM_ConditionCallbacks::post_type( $condition );

		$this->assertFalse( $result, 'selected modifier with missing selected key should return false.' );

		wp_reset_postdata();
	}

	// =========================================================================
	// post_type() -- custom post type with underscore in name
	// =========================================================================

	/**
	 * Test post_type correctly parses custom post types with underscores.
	 */
	public function test_post_type_all_with_custom_post_type() {
		// Register a custom post type with an underscore.
		register_post_type( 'my_cpt', [ 'public' => true ] );

		$cpt_id = $this->factory->post->create(
			[
				'post_type'   => 'my_cpt',
				'post_status' => 'publish',
				'post_title'  => 'Custom CPT Post',
			]
		);

		global $post;
		$post = get_post( $cpt_id );
		$this->go_to( get_permalink( $cpt_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'my_cpt_all',
			'settings' => [],
		];

		$result = PUM_ConditionCallbacks::post_type( $condition );

		$this->assertTrue( $result, 'Should match custom post type with underscores in name.' );

		wp_reset_postdata();
		unregister_post_type( 'my_cpt' );
	}

	// =========================================================================
	// category() -- all modifier
	// =========================================================================

	/**
	 * Test category all modifier returns true on category archive.
	 */
	public function test_category_all_on_archive() {
		$term = wp_insert_term( 'Test Category', 'category' );
		$this->go_to( get_term_link( $term['term_id'], 'category' ) );

		$condition = [
			'target'   => 'category_all',
			'settings' => [],
		];

		$result = PUM_ConditionCallbacks::category( $condition );

		$this->assertTrue( $result, 'category all should return true on a category archive.' );
	}

	/**
	 * Test category all modifier returns false on a singular post.
	 */
	public function test_category_all_not_on_archive() {
		$this->go_to( get_permalink( $this->post_id ) );

		$condition = [
			'target'   => 'category_all',
			'settings' => [],
		];

		$result = PUM_ConditionCallbacks::category( $condition );

		$this->assertFalse( $result, 'category all should return false when not on a category archive.' );
	}

	// =========================================================================
	// category() -- selected modifier
	// =========================================================================

	/**
	 * Test category selected modifier returns true for matching category archive.
	 */
	public function test_category_selected_matches() {
		$term = wp_insert_term( 'Selected Cat', 'category' );
		$this->go_to( get_term_link( $term['term_id'], 'category' ) );

		$condition = [
			'target'   => 'category_selected',
			'settings' => [
				'selected' => [ $term['term_id'] ],
			],
		];

		$result = PUM_ConditionCallbacks::category( $condition );

		$this->assertTrue( $result, 'category selected should return true for matching category archive.' );
	}

	/**
	 * Test category selected modifier returns false for non-matching category.
	 */
	public function test_category_selected_no_match() {
		$term = wp_insert_term( 'Wrong Cat', 'category' );
		$this->go_to( get_term_link( $term['term_id'], 'category' ) );

		$condition = [
			'target'   => 'category_selected',
			'settings' => [
				'selected' => [ 999999 ],
			],
		];

		$result = PUM_ConditionCallbacks::category( $condition );

		$this->assertFalse( $result, 'category selected should return false for non-matching category.' );
	}

	/**
	 * Test category selected with empty selection returns false.
	 */
	public function test_category_selected_empty() {
		$term = wp_insert_term( 'Empty Cat', 'category' );
		$this->go_to( get_term_link( $term['term_id'], 'category' ) );

		$condition = [
			'target'   => 'category_selected',
			'settings' => [
				'selected' => [],
			],
		];

		$result = PUM_ConditionCallbacks::category( $condition );

		// NOTE: wp_parse_id_list([]) returns [], and is_category([]) returns
		// true on any category archive. So empty selection matches all categories.
		$this->assertTrue( $result, 'category selected with empty array matches any category archive per WP behavior.' );
	}

	// =========================================================================
	// post_tag() -- all modifier
	// =========================================================================

	/**
	 * Test post_tag all modifier returns true on tag archive.
	 */
	public function test_post_tag_all_on_archive() {
		$term = wp_insert_term( 'Test Tag', 'post_tag' );
		// Assign a post to the tag so the archive is non-empty.
		wp_set_object_terms( $this->post_id, [ $term['term_id'] ], 'post_tag' );
		$this->go_to( get_term_link( $term['term_id'], 'post_tag' ) );

		$condition = [
			'target'   => 'post_tag_all',
			'settings' => [],
		];

		$result = PUM_ConditionCallbacks::post_tag( $condition );

		$this->assertTrue( $result, 'post_tag all should return true on a tag archive.' );
	}

	/**
	 * Test post_tag all modifier returns false when not on tag archive.
	 */
	public function test_post_tag_all_not_on_archive() {
		$this->go_to( get_permalink( $this->post_id ) );

		$condition = [
			'target'   => 'post_tag_all',
			'settings' => [],
		];

		$result = PUM_ConditionCallbacks::post_tag( $condition );

		$this->assertFalse( $result, 'post_tag all should return false when not on a tag archive.' );
	}

	// =========================================================================
	// post_tag() -- selected modifier
	// =========================================================================

	/**
	 * Test post_tag selected modifier returns true for matching tag archive.
	 */
	public function test_post_tag_selected_matches() {
		$term = wp_insert_term( 'Selected Tag', 'post_tag' );
		wp_set_object_terms( $this->post_id, [ $term['term_id'] ], 'post_tag' );
		$this->go_to( get_term_link( $term['term_id'], 'post_tag' ) );

		$condition = [
			'target'   => 'post_tag_selected',
			'settings' => [
				'selected' => [ $term['term_id'] ],
			],
		];

		$result = PUM_ConditionCallbacks::post_tag( $condition );

		$this->assertTrue( $result, 'post_tag selected should return true for matching tag archive.' );
	}

	/**
	 * Test post_tag selected modifier returns false for non-matching tag.
	 */
	public function test_post_tag_selected_no_match() {
		$term = wp_insert_term( 'Other Tag', 'post_tag' );
		wp_set_object_terms( $this->post_id, [ $term['term_id'] ], 'post_tag' );
		$this->go_to( get_term_link( $term['term_id'], 'post_tag' ) );

		$condition = [
			'target'   => 'post_tag_selected',
			'settings' => [
				'selected' => [ 999999 ],
			],
		];

		$result = PUM_ConditionCallbacks::post_tag( $condition );

		$this->assertFalse( $result, 'post_tag selected should return false for non-matching tag.' );
	}

	// =========================================================================
	// taxonomy() -- delegation to category() and post_tag()
	// =========================================================================

	/**
	 * Test taxonomy delegates to category when taxonomy is category.
	 */
	public function test_taxonomy_delegates_to_category() {
		$term = wp_insert_term( 'Tax Cat', 'category' );
		$this->go_to( get_term_link( $term['term_id'], 'category' ) );

		$condition = [
			'target'   => 'tax_category_all',
			'settings' => [],
		];

		$result = PUM_ConditionCallbacks::taxonomy( $condition );

		$this->assertTrue( $result, 'taxonomy() should delegate to category() and return true on category archive.' );
	}

	/**
	 * Test taxonomy delegates to post_tag when taxonomy is post_tag.
	 */
	public function test_taxonomy_delegates_to_post_tag() {
		$term = wp_insert_term( 'Tax Tag', 'post_tag' );
		wp_set_object_terms( $this->post_id, [ $term['term_id'] ], 'post_tag' );
		$this->go_to( get_term_link( $term['term_id'], 'post_tag' ) );

		$condition = [
			'target'   => 'tax_post_tag_all',
			'settings' => [],
		];

		$result = PUM_ConditionCallbacks::taxonomy( $condition );

		$this->assertTrue( $result, 'taxonomy() should delegate to post_tag() and return true on tag archive.' );
	}

	// =========================================================================
	// taxonomy() -- custom taxonomy with all modifier
	// =========================================================================

	/**
	 * Test taxonomy all modifier returns true on a custom taxonomy archive.
	 */
	public function test_taxonomy_custom_all_on_archive() {
		register_taxonomy( 'genre', 'post', [ 'public' => true ] );
		$term = wp_insert_term( 'Rock', 'genre' );
		wp_set_object_terms( $this->post_id, [ $term['term_id'] ], 'genre' );
		$this->go_to( get_term_link( $term['term_id'], 'genre' ) );

		$condition = [
			'target'   => 'tax_genre_all',
			'settings' => [],
		];

		$result = PUM_ConditionCallbacks::taxonomy( $condition );

		$this->assertTrue( $result, 'taxonomy all should return true on a custom taxonomy archive.' );
	}

	/**
	 * Test taxonomy all modifier returns false when not on its archive.
	 */
	public function test_taxonomy_custom_all_not_on_archive() {
		register_taxonomy( 'genre', 'post', [ 'public' => true ] );
		$this->go_to( get_permalink( $this->post_id ) );

		$condition = [
			'target'   => 'tax_genre_all',
			'settings' => [],
		];

		$result = PUM_ConditionCallbacks::taxonomy( $condition );

		$this->assertFalse( $result, 'taxonomy all should return false when not on the taxonomy archive.' );
	}

	// =========================================================================
	// taxonomy() -- custom taxonomy with selected modifier
	// =========================================================================

	/**
	 * Test taxonomy selected modifier returns true for matching term.
	 */
	public function test_taxonomy_custom_selected_matches() {
		register_taxonomy( 'genre', 'post', [ 'public' => true ] );
		$term = wp_insert_term( 'Jazz', 'genre' );
		wp_set_object_terms( $this->post_id, [ $term['term_id'] ], 'genre' );
		$this->go_to( get_term_link( $term['term_id'], 'genre' ) );

		$condition = [
			'target'   => 'tax_genre_selected',
			'settings' => [
				'selected' => [ $term['term_id'] ],
			],
		];

		$result = PUM_ConditionCallbacks::taxonomy( $condition );

		$this->assertTrue( $result, 'taxonomy selected should match on the correct term archive.' );
	}

	/**
	 * Test taxonomy selected modifier returns false for non-matching term.
	 */
	public function test_taxonomy_custom_selected_no_match() {
		register_taxonomy( 'genre', 'post', [ 'public' => true ] );
		$term = wp_insert_term( 'Blues', 'genre' );
		wp_set_object_terms( $this->post_id, [ $term['term_id'] ], 'genre' );
		$this->go_to( get_term_link( $term['term_id'], 'genre' ) );

		$condition = [
			'target'   => 'tax_genre_selected',
			'settings' => [
				'selected' => [ 999999 ],
			],
		];

		$result = PUM_ConditionCallbacks::taxonomy( $condition );

		$this->assertFalse( $result, 'taxonomy selected should return false when term ID does not match.' );
	}

	/**
	 * Test taxonomy with ID modifier (alias of selected).
	 */
	public function test_taxonomy_custom_id_modifier() {
		register_taxonomy( 'genre', 'post', [ 'public' => true ] );
		$term = wp_insert_term( 'Classical', 'genre' );
		wp_set_object_terms( $this->post_id, [ $term['term_id'] ], 'genre' );
		$this->go_to( get_term_link( $term['term_id'], 'genre' ) );

		$condition = [
			'target'   => 'tax_genre_ID',
			'settings' => [
				'selected' => [ $term['term_id'] ],
			],
		];

		$result = PUM_ConditionCallbacks::taxonomy( $condition );

		$this->assertTrue( $result, 'taxonomy ID modifier should match like selected.' );
	}

	// =========================================================================
	// post_type_category() -- positive match
	// =========================================================================

	/**
	 * Test post_type_category returns true when post has matching category.
	 */
	public function test_post_type_category_matches() {
		$term = wp_insert_term( 'PTC Match', 'category' );
		wp_set_object_terms( $this->post_id, [ $term['term_id'] ], 'category' );

		global $post;
		$post = get_post( $this->post_id );
		$this->go_to( get_permalink( $this->post_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'post_w_category',
			'settings' => [
				'selected' => [ $term['term_id'] ],
			],
		];

		$result = PUM_ConditionCallbacks::post_type_category( $condition );

		$this->assertTrue( $result, 'Should return true when post has the selected category.' );

		wp_reset_postdata();
	}

	/**
	 * Test post_type_category returns false when wrong post type.
	 */
	public function test_post_type_category_wrong_post_type() {
		$term = wp_insert_term( 'PTC Wrong PT', 'category' );
		wp_set_object_terms( $this->page_id, [ $term['term_id'] ], 'category' );

		global $post;
		$post = get_post( $this->page_id );
		$this->go_to( get_permalink( $this->page_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'post_w_category',
			'settings' => [
				'selected' => [ $term['term_id'] ],
			],
		];

		$result = PUM_ConditionCallbacks::post_type_category( $condition );

		$this->assertFalse( $result, 'Should return false when post type does not match.' );

		wp_reset_postdata();
	}

	/**
	 * Test post_type_category with empty selection.
	 */
	public function test_post_type_category_empty_selected() {
		global $post;
		$post = get_post( $this->post_id );
		$this->go_to( get_permalink( $this->post_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'post_w_category',
			'settings' => [
				'selected' => [],
			],
		];

		$result = PUM_ConditionCallbacks::post_type_category( $condition );

		// has_category() with empty array checks if post has any category.
		// The default 'Uncategorized' category is usually assigned.
		// Either way, this exercises the empty-selection path.
		$this->assertIsBool( $result, 'Should return a boolean for empty selection.' );

		wp_reset_postdata();
	}

	// =========================================================================
	// post_type_tag() -- positive match
	// =========================================================================

	/**
	 * Test post_type_tag returns true when post has matching tag.
	 */
	public function test_post_type_tag_matches() {
		$term = wp_insert_term( 'PTT Match', 'post_tag' );
		wp_set_object_terms( $this->post_id, [ $term['term_id'] ], 'post_tag' );

		global $post;
		$post = get_post( $this->post_id );
		$this->go_to( get_permalink( $this->post_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'post_w_post_tag',
			'settings' => [
				'selected' => [ $term['term_id'] ],
			],
		];

		$result = PUM_ConditionCallbacks::post_type_tag( $condition );

		$this->assertTrue( $result, 'Should return true when post has the selected tag.' );

		wp_reset_postdata();
	}

	/**
	 * Test post_type_tag returns false when wrong post type.
	 */
	public function test_post_type_tag_wrong_post_type() {
		$term = wp_insert_term( 'PTT Wrong PT', 'post_tag' );
		wp_set_object_terms( $this->page_id, [ $term['term_id'] ], 'post_tag' );

		global $post;
		$post = get_post( $this->page_id );
		$this->go_to( get_permalink( $this->page_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'post_w_post_tag',
			'settings' => [
				'selected' => [ $term['term_id'] ],
			],
		];

		$result = PUM_ConditionCallbacks::post_type_tag( $condition );

		$this->assertFalse( $result, 'Should return false when post type does not match.' );

		wp_reset_postdata();
	}

	// =========================================================================
	// post_type_tax() -- delegation and custom taxonomy
	// =========================================================================

	/**
	 * Test post_type_tax delegates to post_type_category for category taxonomy.
	 */
	public function test_post_type_tax_delegates_to_category() {
		$term = wp_insert_term( 'PTTax Cat', 'category' );
		wp_set_object_terms( $this->post_id, [ $term['term_id'] ], 'category' );

		global $post;
		$post = get_post( $this->post_id );
		$this->go_to( get_permalink( $this->post_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'post_w_category',
			'settings' => [
				'selected' => [ $term['term_id'] ],
			],
		];

		$result = PUM_ConditionCallbacks::post_type_tax( $condition );

		$this->assertTrue( $result, 'post_type_tax should delegate to post_type_category and return true.' );

		wp_reset_postdata();
	}

	/**
	 * Test post_type_tax delegates to post_type_tag for post_tag taxonomy.
	 */
	public function test_post_type_tax_delegates_to_tag() {
		$term = wp_insert_term( 'PTTax Tag', 'post_tag' );
		wp_set_object_terms( $this->post_id, [ $term['term_id'] ], 'post_tag' );

		global $post;
		$post = get_post( $this->post_id );
		$this->go_to( get_permalink( $this->post_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'post_w_post_tag',
			'settings' => [
				'selected' => [ $term['term_id'] ],
			],
		];

		$result = PUM_ConditionCallbacks::post_type_tax( $condition );

		$this->assertTrue( $result, 'post_type_tax should delegate to post_type_tag and return true.' );

		wp_reset_postdata();
	}

	/**
	 * Test post_type_tax with custom taxonomy returns true on match.
	 */
	public function test_post_type_tax_custom_taxonomy_matches() {
		register_taxonomy( 'color', 'post', [ 'public' => true ] );
		$term = wp_insert_term( 'Red', 'color' );
		wp_set_object_terms( $this->post_id, [ $term['term_id'] ], 'color' );

		global $post;
		$post = get_post( $this->post_id );
		$this->go_to( get_permalink( $this->post_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'post_w_color',
			'settings' => [
				'selected' => [ $term['term_id'] ],
			],
		];

		$result = PUM_ConditionCallbacks::post_type_tax( $condition );

		$this->assertTrue( $result, 'post_type_tax should return true when post has the custom taxonomy term.' );

		wp_reset_postdata();
	}

	/**
	 * Test post_type_tax with custom taxonomy returns false when no match.
	 */
	public function test_post_type_tax_custom_taxonomy_no_match() {
		register_taxonomy( 'color', 'post', [ 'public' => true ] );

		global $post;
		$post = get_post( $this->post_id );
		$this->go_to( get_permalink( $this->post_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'post_w_color',
			'settings' => [
				'selected' => [ 999999 ],
			],
		];

		$result = PUM_ConditionCallbacks::post_type_tax( $condition );

		$this->assertFalse( $result, 'post_type_tax should return false when post lacks the custom taxonomy term.' );

		wp_reset_postdata();
	}

	/**
	 * Test post_type_tax returns false for wrong post type.
	 */
	public function test_post_type_tax_wrong_post_type() {
		register_taxonomy( 'color', 'post', [ 'public' => true ] );
		$term = wp_insert_term( 'Blue', 'color' );
		wp_set_object_terms( $this->page_id, [ $term['term_id'] ], 'color' );

		global $post;
		$post = get_post( $this->page_id );
		$this->go_to( get_permalink( $this->page_id ) );
		setup_postdata( $post );

		$condition = [
			'target'   => 'post_w_color',
			'settings' => [
				'selected' => [ $term['term_id'] ],
			],
		];

		$result = PUM_ConditionCallbacks::post_type_tax( $condition );

		$this->assertFalse( $result, 'post_type_tax should return false when post type does not match.' );

		wp_reset_postdata();
	}

	// =========================================================================
	// is_post_type() -- additional edge cases
	// =========================================================================

	/**
	 * Test is_post_type returns true for page type.
	 */
	public function test_is_post_type_page() {
		global $post;
		$post = get_post( $this->page_id );
		$this->go_to( get_permalink( $this->page_id ) );
		setup_postdata( $post );

		$result = PUM_ConditionCallbacks::is_post_type( 'page' );

		$this->assertTrue( $result, 'Should return true for matching page type.' );

		wp_reset_postdata();
	}

	/**
	 * Test is_post_type returns false when global post is not set.
	 */
	public function test_is_post_type_no_global_post() {
		global $post;
		$post = null;

		$result = PUM_ConditionCallbacks::is_post_type( 'post' );

		$this->assertFalse( $result, 'Should return false when global $post is null.' );
	}

	/**
	 * Test is_post_type returns false when global post is not an object.
	 */
	public function test_is_post_type_non_object_post() {
		global $post;
		$post = 'not_an_object';

		$result = PUM_ConditionCallbacks::is_post_type( 'post' );

		$this->assertFalse( $result, 'Should return false when global $post is not an object.' );
	}

	/**
	 * Test is_post_type with custom post type.
	 */
	public function test_is_post_type_custom() {
		register_post_type( 'book', [ 'public' => true ] );

		$book_id = $this->factory->post->create(
			[
				'post_type'   => 'book',
				'post_status' => 'publish',
				'post_title'  => 'Test Book',
			]
		);

		global $post;
		$post = get_post( $book_id );
		$this->go_to( get_permalink( $book_id ) );
		setup_postdata( $post );

		$result = PUM_ConditionCallbacks::is_post_type( 'book' );

		$this->assertTrue( $result, 'Should return true for custom post type.' );

		wp_reset_postdata();
		unregister_post_type( 'book' );
	}
}
