<?php
/**
 * Block utilities.
 *
 * @since       1.14
 * @package     PUM
 * @copyright   Copyright (c) 2020, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Utils_Blocks
 */
class PUM_Utils_Blocks {

	/**
	 * Find blocks with matching name in given list of blocks.
	 *
	 * @param array  $blocks Array of blocks.
	 * @param string $search Block name to look for. Defaults to finding all blocks with pum/.* prefix. Accepts regex string.
	 *
	 * @return array
	 */
	public static function find_blocks( $blocks, $block_name = 'pum\/.*' ) {
		$foundBlocks = [];

		foreach ( $blocks as $block ) {
			if ( in_array( $block['blockName'], [ 'core/columns', 'core/column' ], true ) ) {
				$foundBlocks = array_merge( $foundBlocks, self::find_blocks( $block['innerBlocks'] ) );
			}

			if ( $block_name === $block['blockName'] ) {
				$foundBlocks[] = $block;
			}
		}

		return $foundBlocks;
	}

}
