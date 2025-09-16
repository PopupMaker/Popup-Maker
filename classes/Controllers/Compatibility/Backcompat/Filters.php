<?php
/**
 * Filters
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Controllers\Compatibility\Backcompat;

use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Class Filters
 *
 * @since 1.21.0
 */
class Filters extends Controller {

	/**
	 * Initialize admin controller.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'popup_maker/popup_post_type_args', [ $this, 'filter_popup_post_type_args' ] );
		add_filter( 'popup_maker/popup_theme_post_type_args', [ $this, 'filter_popup_theme_post_type_args' ] );
		add_filter( 'popup_maker/popup_category_tax_args', [ $this, 'filter_popup_category_tax_args' ] );
		add_filter( 'popup_maker/popup_tag_tax_args', [ $this, 'filter_popup_tag_tax_args' ] );
		add_filter( 'popup_maker/post_type_labels', [ $this, 'filter_post_type_labels' ], 10, 3 );
	}

	/**
	 * Filter popup post type args.
	 *
	 * @param array $popup_args Post type args.
	 *
	 * @return array
	 */
	public function filter_popup_post_type_args( $popup_args ) {
		if ( has_filter( 'popmake_popup_supports' ) ) {
			/**
			 * Filter: popmake_popup_supports
			 *
			 * @param array<string> $supports Popup supports.
			 *
			 * @deprecated 1.21.0
			 */
			$popup_args['supports'] = apply_filters( 'popmake_popup_supports', $popup_args['supports'] );
		}

		if ( has_filter( 'popmake_popup_post_type_args' ) ) {
			/**
			 * Filter: popmake_popup_post_type_args
			 *
			 * @param array<string,mixed> $popup_args Popup post type args.
			 *
			 * @deprecated 1.21.0
			 */
			$popup_args = apply_filters( 'popmake_popup_post_type_args', $popup_args );
		}

		if ( has_filter( 'pum_popup_post_type_args' ) ) {
			/**
			 * Filter: pum_popup_post_type_args
			 *
			 * @param array<string,mixed> $popup_args Popup post type args.
			 *
			 * @deprecated 1.21.0
			 */
			$popup_args = apply_filters( 'pum_popup_post_type_args', $popup_args );
		}

		return $popup_args;
	}

	/**
	 * Filter popup theme post type args.
	 *
	 * @param array $popup_theme_args Post type args.
	 *
	 * @return array
	 */
	public function filter_popup_theme_post_type_args( $popup_theme_args ) {
		if ( has_filter( 'popmake_popup_theme_labels' ) ) {
			/**
			 * Filter: popmake_popup_theme_labels
			 *
			 * @param array<string,mixed> $labels Popup theme labels.
			 *
			 * @deprecated 1.21.0
			 */
			$popup_theme_args['labels'] = apply_filters( 'popmake_popup_theme_labels', $popup_theme_args['labels'] );
		}

		if ( has_filter( 'popmake_popup_theme_supports' ) ) {
			/**
			 * Filter: popmake_popup_theme_supports
			 *
			 * @param array<string> $supports Popup theme supports.
			 *
			 * @deprecated 1.21.0
			 */
			$popup_theme_args['supports'] = apply_filters( 'popmake_popup_theme_supports', $popup_theme_args['supports'] );
		}

		if ( has_filter( 'popmake_popup_theme_post_type_args' ) ) {
			/**
			 * Filter: popmake_popup_theme_post_type_args
			 *
			 * @param array<string,mixed> $args Popup theme post type args.
			 *
			 * @deprecated 1.21.0
			 */
			$popup_theme_args = apply_filters( 'popmake_popup_theme_post_type_args', [] );
		}

		return $popup_theme_args;
	}

	/**
	 * Filter popup category tax args.
	 *
	 * @param array $popup_category_tax_args Tax args.
	 *
	 * @return array
	 */
	public function filter_popup_category_tax_args( $popup_category_tax_args ) {
		if ( has_filter( 'popmake_category_labels' ) ) {
			/**
			 * Filter: popmake_category_labels
			 *
			 * @param array<string,mixed> $labels Category labels.
			 *
			 * @deprecated 1.21.0
			 */
			$popup_category_tax_args['labels'] = apply_filters( 'popmake_category_labels', $popup_category_tax_args['labels'] );
		}

		if ( has_filter( 'popmake_category_args' ) ) {
			/**
			 * Filter: popmake_category_args
			 *
			 * @param array<string,mixed> $popup_category_tax_args Category args.
			 *
			 * @deprecated 1.21.0
			 */
			$popup_category_tax_args = apply_filters( 'popmake_category_args', $popup_category_tax_args );
		}

		return $popup_category_tax_args;
	}

	/**
	 * Filter popup tag tax args.
	 *
	 * @param array $popup_tag_tax_args Tax args.
	 *
	 * @return array
	 */
	public function filter_popup_tag_tax_args( $popup_tag_tax_args ) {
		if ( has_filter( 'popmake_tag_labels' ) ) {
			/**
			 * Filter: popmake_tag_labels
			 *
			 * @param array<string,mixed> $labels Tag labels.
			 *
			 * @deprecated 1.21.0
			 */
			$popup_tag_tax_args['labels'] = apply_filters( 'popmake_tag_labels', $popup_tag_tax_args['labels'] );
		}

		if ( has_filter( 'popmake_tag_args' ) ) {
			/**
			 * Filter: popmake_tag_args
			 *
			 * @param array<string,mixed> $popup_tag_tax_args Tag args.
			 *
			 * @deprecated 1.21.0
			 */
			$popup_tag_tax_args = apply_filters( 'popmake_tag_args', $popup_tag_tax_args );
		}

		return $popup_tag_tax_args;
	}

	/**
	 * Filter post type labels.
	 *
	 * @param array  $post_type_labels Post type labels.
	 * @param string $singular Singular label.
	 * @param string $plural Plural label.
	 *
	 * @return array
	 */
	public function filter_post_type_labels( $post_type_labels, $singular, $plural ) {
		if ( has_filter( 'popmake_post_type_labels' ) ) {
			/**
			 * Filter: popmake_post_type_labels
			 *
			 * @param array<string,mixed> $post_type_labels Post type labels.
			 * @param string $singular Singular label.
			 * @param string $plural Plural label.
			 *
			 * @deprecated 1.21.0
			 */
			$post_type_labels = apply_filters( 'popmake_post_type_labels', $post_type_labels, $singular, $plural );
		}

		return $post_type_labels;
	}
}
