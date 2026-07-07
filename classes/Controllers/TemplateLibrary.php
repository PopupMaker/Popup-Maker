<?php
/**
 * TemplateLibrary controller.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Controllers;

use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Registers popup templates as block patterns.
 *
 * Templates are collected by the `template_library` service and exposed
 * two ways: as post-type-scoped block patterns for the native inserter,
 * and as picker data for the popup editor template modal (localized via
 * the Assets controller).
 *
 * @since X.X.X
 */
class TemplateLibrary extends Controller {

	/**
	 * Initialize the controller.
	 *
	 * @return void
	 */
	public function init() {
		// Late enough for Pro (plugins_loaded@12) & addons (@13) to hook the filter.
		add_action( 'init', [ $this, 'register_block_patterns' ], 15 );
	}

	/**
	 * Register pattern categories & popup template patterns.
	 *
	 * @return void
	 */
	public function register_block_patterns() {
		if ( ! function_exists( 'register_block_pattern' ) || ! function_exists( 'register_block_pattern_category' ) ) {
			return;
		}

		$service = $this->container->get( 'template_library' );

		register_block_pattern_category( 'popup-maker-templates', [
			'label'       => __( 'Popup Templates', 'popup-maker' ),
			'description' => __( 'Ready-made popup layouts from Popup Maker.', 'popup-maker' ),
		] );

		foreach ( $service->get_categories() as $slug => $label ) {
			register_block_pattern_category( 'popup-maker-' . $slug, [
				/* translators: %s: Template category label. */
				'label' => sprintf( _x( 'Popup: %s', 'Block pattern category label', 'popup-maker' ), $label ),
			] );
		}

		foreach ( $service->get_templates() as $template ) {
			if ( ! $service->is_insertable( $template ) ) {
				continue;
			}

			register_block_pattern( 'popup-maker/' . $template['slug'], [
				'title'         => $template['name'],
				'description'   => $template['description'],
				'content'       => $template['content'],
				'categories'    => [ 'popup-maker-templates', 'popup-maker-' . $template['category'] ],
				'keywords'      => $template['keywords'],
				'postTypes'     => [ 'popup' ],
				'viewportWidth' => $template['viewport_width'],
				'inserter'      => true,
			] );
		}
	}
}
