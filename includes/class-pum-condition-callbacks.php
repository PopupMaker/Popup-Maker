<?php

// All Conditions should return false by default and only true if 100% matched.
/**
 * Class PUM_Condition_Callbacks
 */
class PUM_Condition_Callbacks {

	/**
	 * Checks if this is one of the selected post_type items.
	 *
	 * @param array $settings
	 *
	 * @return bool
	 */
	public static function post_type( $settings = array() ) {
		global $post;

		$target = explode( '_', $settings['target'] );

		// Modifier should be the last key.
		$modifier = array_pop( $target );

		// Post type is the remaining keys combined.
		$post_type = implode( '_', $target );

		switch ( $modifier ) {
			case 'index':
				if ( is_post_type_archive( $post_type ) ) {
					return true;
				}
				break;

			case 'all':
				// Checks for valid post type, if $post_type is page, then include the front page as most users simply expect this.
				if ( is_singular( $post_type ) || ( $post_type == 'page' && is_front_page() ) ) {
					return true;
				}
				break;

			case 'ID':
			case 'selected':
				if ( is_singular( $post_type ) && in_array( $post->ID, wp_parse_id_list( $settings['selected'] ) ) ) {
					return true;
				}
				break;
			case 'children':
				if ( ! is_post_type_hierarchical( $post_type ) || ! is_singular( $post_type ) ) {
					return false;
				}

				// Chosen parents.
				$selected = wp_parse_id_list( $settings['selected'] );

				foreach ( $selected as $id ) {
					if ( $post->post_parent == $id ) {
						return true;
					}
				}
				break;
			case 'ancestors':
				if ( ! is_post_type_hierarchical( $post_type ) || ! is_singular( $post_type ) ) {
					return false;
				}

				// Ancestors of the current page.
				$ancestors = get_post_ancestors( $post->ID );

				// Chosen parent/grandparents.
				$selected = wp_parse_id_list( $settings['selected'] );

				foreach ( $selected as $id ) {
					if ( in_array( $id, $ancestors ) ) {
						return true;
					}
				}

				break;
			case 'template':
				if ( is_page() && is_page_template( $settings['selected'] ) ) {
					return true;
				}
				break;
		}

		return false;
	}

	/**
	 * Checks if this is one of the selected taxonomy term.
	 *
	 * @param array $settings
	 *
	 * @return bool
	 */
	public static function taxonomy( $settings = array() ) {

		$target = explode( '_', $settings['target'] );

		// Remove the tax_ prefix.
		array_shift( $target );

		// Assign the last key as the modifier _all, _selected
		$modifier = array_pop( $target );

		// Whatever is left is the taxonomy.
		$taxonomy = implode( '_', $target );

		if ( $taxonomy == 'category' ) {
			return self::category( $settings );
		} elseif ( $taxonomy == 'post_tag' ) {
			return self::post_tag( $settings );
		}

		switch ( $modifier ) {
			case 'all':
				if ( is_tax( $taxonomy ) ) {
					return true;
				}
				break;

			case 'ID':
			case 'selected':
				if ( is_tax( $taxonomy, wp_parse_id_list( $settings['selected'] ) ) ) {
					return true;
				}
				break;
		}

		return false;
	}

	/**
	 * Checks if this is one of the selected categories.
	 *
	 * @param array $settings
	 *
	 * @return bool
	 */
	public static function category( $settings = array() ) {

		$target = explode( '_', $settings['target'] );

		// Assign the last key as the modifier _all, _selected
		$modifier = array_pop( $target );

		switch ( $modifier ) {
			case 'all':
				if ( is_category() ) {
					return true;
				}
				break;

			case 'selected':
				if ( is_category( wp_parse_id_list( $settings['selected'] ) ) ) {
					return true;
				}
				break;
		}

		return false;
	}

	/**
	 * Checks if this is one of the selected tags.
	 *
	 * @param array $settings
	 *
	 * @return bool
	 */
	public static function post_tag( $settings = array() ) {

		$target = explode( '_', $settings['target'] );

		// Assign the last key as the modifier _all, _selected
		$modifier = array_pop( $target );

		switch ( $modifier ) {
			case 'all':
				if ( is_tag() ) {
					return true;
				}
				break;

			case 'selected':
				if ( is_tag( wp_parse_id_list( $settings['selected'] ) ) ) {
					return true;
				}
				break;
		}

		return false;
	}

	/**
	 * Checks if the post_type has the selected categories.
	 *
	 * @param array $settings
	 *
	 * @return bool
	 */
	public static function post_type_tax( $settings = array() ) {

		$target = explode( '_w_', $settings['target'] );

		// First key is the post type.
		$post_type = array_shift( $target );

		// Last Key is the taxonomy
		$taxonomy = array_pop( $target );

		if ( $taxonomy == 'category' ) {
			return self::post_type_category( $settings );
		} elseif ( $taxonomy == 'post_tag' ) {
			return self::post_type_tag( $settings );
		}

		if ( is_singular( $post_type ) && has_term( wp_parse_id_list( $settings['selected'] ), $taxonomy ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if the post_type has the selected categories.
	 *
	 * @param array $settings
	 *
	 * @return bool
	 */
	public static function post_type_category( $settings = array() ) {

		$target = explode( '_w_', $settings['target'] );

		// First key is the post type.
		$post_type = array_shift( $target );

		if ( is_singular( $post_type ) && has_category( wp_parse_id_list( $settings['selected'] ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks is a post_type has the selected tags.
	 *
	 * @param array $settings
	 *
	 * @return bool
	 */
	public static function post_type_tag( $settings = array() ) {

		$target = explode( '_w_', $settings['target'] );

		// First key is the post type.
		$post_type = array_shift( $target );

		if ( is_singular( $post_type ) && has_tag( wp_parse_id_list( $settings['selected'] ) ) ) {
			return true;
		}

		return false;
	}

}