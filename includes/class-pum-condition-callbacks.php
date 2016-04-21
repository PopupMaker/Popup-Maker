<?php

// All Conditions should return false by default and only true if 100% matched.
/**
 * Class PUM_Condition_Callbacks
 */
class PUM_Condition_Callbacks {

	/**
	 * @param array $settings
	 *
	 * @return bool
	 */
	public static function post_type( $settings = array() ) {
		global $post;

		$target = explode( '_', $settings['target'] );

		$post_type = $target[0];
		$modifier  = $target[1];

		switch ( $modifier ) {
			case 'all':
				if ( is_singular( $post_type ) ) {
					return true;
				}
				break;

			case 'selected':
				if ( is_singular( $post_type ) && in_array( $post->ID, wp_parse_id_list( $settings['selected'] ) ) ) {
					return true;
				}
				break;
		}

		return false;
	}

	/**
	 * @param array $settings
	 *
	 * @return bool
	 */
	public static function taxonomy( $settings = array() ) {

		if ( strpos( $settings['target'], 'tax_category' ) !== false ) {
			return self::category( $settings );
		} elseif ( strpos( $settings['target'], 'tax_post_tag' ) !== false ) {
			return self::post_tag( $settings );
		}

		$taxonomy = str_replace( array( 'tax_', '_all', '_selected' ), array( '', '', '' ), $settings['target'] );

		$modifier = str_replace( "tax_{$taxonomy}_", '', $settings['target'] );

		switch ( $modifier ) {
			case 'all':
				if ( is_tax( $taxonomy ) ) {
					return true;
				}
				break;

			case 'selected':
				if ( is_tax( $taxonomy, wp_parse_id_list( $settings['selected'] ) ) ) {
					return true;
				}
				break;
		}

		return false;
	}

	/**
	 * @param array $settings
	 *
	 * @return bool
	 */
	public static function post_tag( $settings = array() ) {

		$modifier = str_replace( 'tax_post_tag_', '', $settings['target'] );

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
	 * @param array $settings
	 *
	 * @return bool
	 */
	public static function category( $settings = array() ) {

		$modifier = str_replace( 'tax_category_', '', $settings['target'] );

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
	 * @param array $settings
	 *
	 * @return bool
	 */
	public static function post_type_tax( $settings = array() ) {

		if ( strpos( $settings['target'], '_w_category' ) !== false || strpos( $settings['target'], '_wp_category' ) !== false ) {
			return self::post_type_category( $settings );
		} elseif ( strpos( $settings['target'], '_w_post_tag' ) !== false || strpos( $settings['target'], '_wp_post_tag' ) !== false ) {
			return self::post_type_tag( $settings );
		}

		if ( strpos( $settings['target'], '_w_' ) ) {
			$target = explode( '_w_', $settings['target'] );
			$w_wo   = 'w';
		} elseif ( strpos( $settings['target'], '_wo_' ) ) {
			$target = explode( '_wo_', $settings['target'] );
			$w_wo   = 'wo';
		} else {
			return false;
		}

		$post_type = $target[0];
		$taxonomy  = $target[1];

		switch ( $w_wo ) {
			case 'w':
				if ( is_singular( $post_type ) && has_term( wp_parse_id_list( $settings['selected'] ), $taxonomy ) ) {
					return true;
				}
				break;

			case 'wo':
				if ( is_singular( $post_type ) && ! has_term( wp_parse_id_list( $settings['selected'] ), $taxonomy ) ) {
					return true;
				}
				break;
		}

		return false;
	}

	/**
	 * @param array $settings
	 *
	 * @return bool
	 */
	public static function post_type_category( $settings = array() ) {

		if ( strpos( $settings['target'], '_w_' ) ) {
			$target = explode( '_w_', $settings['target'] );
			$w_wo   = 'w';
		} elseif ( strpos( $settings['target'], '_wo_' ) ) {
			$target = explode( '_wo_', $settings['target'] );
			$w_wo   = 'wo';
		} else {
			return false;
		}

		$post_type = $target[0];

		switch ( $w_wo ) {
			case 'w':
				if ( is_singular( $post_type ) && has_category( wp_parse_id_list( $settings['selected'] ) ) ) {
					return true;
				}
				break;

			case 'wo':
				if ( is_singular( $post_type ) && ! has_category( wp_parse_id_list( $settings['selected'] ) ) ) {
					return true;
				}
				break;
		}

		return false;
	}

	/**
	 * @param array $settings
	 *
	 * @return bool
	 */
	public static function post_type_tag( $settings = array() ) {

		if ( strpos( $settings['target'], '_w_' ) ) {
			$target = explode( '_w_', $settings['target'] );
			$w_wo   = 'w';
		} elseif ( strpos( $settings['target'], '_wo_' ) ) {
			$target = explode( '_wo_', $settings['target'] );
			$w_wo   = 'wo';
		} else {
			return false;
		}

		$post_type = $target[0];

		switch ( $w_wo ) {
			case 'w':
				if ( is_singular( $post_type ) && has_tag( wp_parse_id_list( $settings['selected'] ) ) ) {
					return true;
				}
				break;

			case 'wo':
				if ( is_singular( $post_type ) && ! has_tag( wp_parse_id_list( $settings['selected'] ) ) ) {
					return true;
				}
				break;
		}

		return false;
	}

}