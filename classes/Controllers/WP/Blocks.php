<?php
/**
 * Blocks registration.
 *
 * @author    Code Atlantic
 * @package   PopupMaker
 * @copyright (c) 2024, Code Atlantic LLC.
 */

namespace PopupMaker\Controllers\WP;

use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Class Blocks
 *
 * @since 1.21.0
 */
class Blocks extends Controller {

	/**
	 * Initializes this module.
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_blocks' ] );
	}

	/**
	 * Register Popup Maker blocks.
	 *
	 * @return void
	 */
	public function register_blocks() {
		$blocks = [
			'dist/blocks/cta-buttons.block.json' => [],
			'dist/blocks/cta-button.block.json' => [],
		];

		foreach( $blocks as $block => $args ) {
			register_block_type_from_metadata(
				$this->container->get_path( $block ),
				$args
			);
		}
	}
}
