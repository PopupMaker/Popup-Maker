<?php
/*******************************************************************************
 * Copyright (c) 2020, Code Atlantic LLC.
 ******************************************************************************/

class PUM_Integration_Builder_BeaverBuilder extends PUM_Abstract_Integration {

	/**
	 * @var string
	 */
	public $key = 'beaverbuilder';

	/**
	 * @var string
	 */
	public $type = 'builder';

	/**
	 * @return string
	 */
	public function label() {
		return 'Beaver Builder';
	}

	/**
	 * @return bool
	 */
	public function enabled() {
		return defined( 'FL_BUILDER_VERSION' );
	}

	public function __construct() {
		if ( ! $this->enabled() ) {
			return;
		}

		add_filter( 'fl_builder_post_types', [ $this, 'builder_post_types' ] );
		add_filter( 'fl_builder_admin_settings_post_types', [ $this, 'builder_admin_settings_post_types' ] );
		add_filter( 'popmake_popup_post_type_args', [ $this, 'popup_post_type_args' ] );

		add_action( 'init', [ $this, 'init' ] );
	}

	public function init() {
		if ( $this->is_editing_popup() ) {
			$popup_id = absint( $_GET['p'] );

			if ( ! current_user_can( 'edit_post', $popup_id ) ) {
				return;
			}

			add_filter( 'pum_popup_content', function ( $content ) {
				// Hack
				global $wp_query;
				$wp_query->in_the_loop = true;
				return FLBuilder::render_content( $content );
			} );

			add_filter( 'pum_popup_is_loadable', [ $this, 'popup_is_loadable' ], 10, 2 );
			add_filter( 'pum_popup_settings', [ $this, 'popup_settings' ], 10, 2 );

			/* Filter the single_template with our custom function*/
			add_filter( 'single_template', [ $this, 'custom_template' ] );
			add_filter( 'pum_get_popup_id', [ $this, 'get_popup_id' ] );

			remove_action( 'wp_footer', [ 'PUM_Site_Popups', 'render_popups' ] );
		} else {
			add_action( 'wp_footer' ,function () {
				add_filter( 'pum_popup_content', function ( $content, $popup_id ) {
					if ( FLBuilderModel::is_builder_enabled( $popup_id ) ) {
						ob_start();
						FLBuilder::render_content_by_id( $popup_id );
						$content = ob_get_clean();
					}

					return $content;
				}, 10, 2 );
			}, -1 );

		}
	}

	/**
	 * @param array $post_types
	 *
	 * @return array
	 */
	public function builder_admin_settings_post_types( $post_types ) {
		$post_types['popup'] = get_post_type_object( 'popup' );

		return $post_types;
	}

	/**
	 * @param array $post_types
	 *
	 * @return array
	 */
	public function builder_post_types( $post_types ) {
		$post_types[] = 'popup';

		return $post_types;
	}

	/**
	 * @param null|int $popup_id
	 *
	 * @return bool
	 */
	public function is_editing_popup( $popup_id = null ) {
		// If not using builder, not editing a popup, or no post ID set, return false.
		if ( ! isset( $_GET['fl_builder'] ) || ! isset( $_GET['post_type'] ) || $_GET['post_type'] !== 'popup' || ! isset( $_GET['p'] ) ) {
			return false;
		}

		// If the $popup_id is set, compare it to the current $p ID.
		if ( isset( $popup_id ) ) {
			// Check user can edit this popup.
			return $popup_id === absint( $_GET['p'] );
		}

		return true;
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public function popup_post_type_args( $args ) {
		if ( $this->is_editing_popup() ) {
			$args['publicly_queryable'] = true;
		}

		return $args;
	}

	/**
	 * @param $loadable
	 * @param $popup_id
	 *
	 * @return bool
	 */
	public function popup_is_loadable( $loadable, $popup_id ) {
		// If we are not editing anything, return normally.
		if ( ! $this->is_editing_popup() ) {
			return $loadable;
		}

		// If this popup isn't being edited, disable it.
		return $this->is_editing_popup( $popup_id );
	}

	/**
	 * @param $settings
	 * @param $popup_id
	 *
	 * @return mixed
	 */
	public function popup_settings( $settings, $popup_id ) {
		// If we are not editing this popup, return normally.
		if ( ! $this->is_editing_popup( $popup_id ) ) {
			return $settings;
		}

		$settings['zindex'] = 1;

		return $settings;
	}


	/**
	 * @param $single
	 *
	 * @return string
	 */
	public function custom_template( $single ) {
		global $post;

		/* Checks for single template by post type */
		if ( $post->post_type == 'popup' ) {
			remove_filter( 'the_content', 'FLBuilder::render_content' );

			if ( file_exists( Popup_Maker::$DIR . '/templates/single-popup.php' ) ) {
				return Popup_Maker::$DIR . '/templates/single-popup.php';
			}
		}

		return $single;

	}

	/**
	 * @param $popup_id
	 *
	 * @return int
	 */
	public function get_popup_id( $popup_id ) {
		if ( ! $this->is_editing_popup() ) {
			return $popup_id;
		}

		return isset( $_GET['p'] ) ? absint( $_GET['p'] ) : 0;
	}


}
