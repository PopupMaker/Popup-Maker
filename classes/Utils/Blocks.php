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
	 * Recursively searches through WordPress block structures, including nested blocks
	 * within core/columns and core/column containers.
	 *
	 * @param array<string, mixed>[] $blocks Array of WordPress block structures.
	 * @param string                 $search_name Block name to look for (supports wildcards like 'pum/*').
	 *
	 * @return array<string, mixed>[] Array of matching WordPress blocks.
	 */
	public static function find_blocks( $blocks, $search_name = 'pum/*' ) {
		$found_blocks = [];

		foreach ( $blocks as $block ) {
			if ( in_array( $block['blockName'], [ 'core/columns', 'core/column' ], true ) ) {
				$found_blocks = array_merge( $found_blocks, self::find_blocks( $block['innerBlocks'], $search_name ) );
			}

			if ( $search_name === $block['blockName'] ) {
				$found_blocks[] = $block;
			}
		}

		return $found_blocks;
	}
}
