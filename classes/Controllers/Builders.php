<?php
/**
 * Page builder coordinator.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Controllers;

use PopupMaker\Base\PageBuilder;
use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates the WordPress lifecycle shared by page builder integrations.
 *
 * @since 1.25.0
 */
class Builders extends Controller {

	/**
	 * Available builders, keyed by class name.
	 *
	 * @var array<string,PageBuilder>
	 */
	private $builders = [];

	/**
	 * Cached document owners, including unowned popup IDs.
	 *
	 * @var array<int,PageBuilder|null>
	 */
	private $owners = [];

	/**
	 * Cached authorized builder request.
	 *
	 * @var array{builder:PageBuilder,popup_id:int}|null
	 */
	private $edit_request;

	/**
	 * Whether the builder request has been resolved.
	 *
	 * @var bool
	 */
	private $edit_request_resolved = false;

	/**
	 * Whether shared hooks have been registered.
	 *
	 * @var bool
	 */
	private $pipeline_initialized = false;

	/**
	 * Initialize builder discovery.
	 *
	 * @return void
	 */
	public function init() {
		// Plugin builders are ready after plugins_loaded; theme builders appear
		// after setup. The init pass covers builders that finish booting there.
		add_action( 'plugins_loaded', [ $this, 'boot_builders' ], 20 );
		add_action( 'after_setup_theme', [ $this, 'boot_builders' ], 20 );
		add_action( 'init', [ $this, 'boot_builders' ], 20 );

		$this->boot_builders();
	}

	/**
	 * Detected builder classes bundled with the plugin.
	 *
	 * Class constants resolve to strings without autoloading. An adapter is only
	 * loaded and constructed after its builder's cheap runtime signal appears.
	 *
	 * @return class-string<PageBuilder>[]
	 */
	protected function detected_builder_classes() {
		if ( ! defined( 'ELEMENTOR_VERSION' ) && ! did_action( 'elementor/loaded' ) ) {
			return [];
		}

		return [ \PopupMaker\Builders\Elementor::class ];
	}

	/**
	 * Construct a detected builder.
	 *
	 * @param class-string<PageBuilder> $builder_class Builder class.
	 *
	 * @return PageBuilder
	 */
	protected function instantiate_builder( $builder_class ) {
		return new $builder_class( $this->container );
	}

	/**
	 * Register builders whose APIs are now available.
	 *
	 * @return void
	 */
	public function boot_builders() {
		foreach ( $this->detected_builder_classes() as $builder_class ) {
			if (
				! is_string( $builder_class ) ||
				isset( $this->builders[ $builder_class ] ) ||
				! is_subclass_of( $builder_class, PageBuilder::class )
			) {
				continue;
			}

			$builder = $this->instantiate_builder( $builder_class );

			if ( ! $builder instanceof PageBuilder || ! $builder->is_available() ) {
				continue;
			}

			$class = get_class( $builder );

			if ( isset( $this->builders[ $class ] ) ) {
				continue;
			}

			$builder->register_hooks();
			$this->builders[ $class ]    = $builder;
			$this->owners                = [];
			$this->edit_request          = null;
			$this->edit_request_resolved = false;
		}

		if ( $this->builders ) {
			$this->register_pipeline_hooks();
		}
	}

	/**
	 * Register the shared pipeline once a builder is available.
	 *
	 * @return void
	 */
	private function register_pipeline_hooks() {
		if ( $this->pipeline_initialized ) {
			return;
		}

		$this->pipeline_initialized = true;

		add_filter( 'request', [ $this, 'allow_builder_request' ] );
		add_filter( 'body_class', [ $this, 'filter_canvas_body_classes' ] );
		add_filter( 'the_title', [ $this, 'suppress_canvas_title' ], PHP_INT_MAX, 2 );
		add_filter( 'the_content', [ $this, 'suppress_canvas_content' ], PHP_INT_MAX );
		add_filter( 'pum_popup_is_loadable', [ $this, 'is_canvas_popup_loadable' ], 1001, 2 );
		add_filter( 'pum_popup_data_attr', [ $this, 'filter_canvas_data_attr' ], 1001, 2 );
		add_filter( 'pum_popup_get_public_settings', [ $this, 'filter_canvas_settings' ], 1001, 2 );
		add_filter( 'pum_popup_close_button_attributes', [ $this, 'filter_canvas_close_attributes' ], 1001, 2 );
		add_filter( 'pum_popup_content', [ $this, 'render_popup_content' ], 1000, 2 );

		// Draft canvases are absent from Popup Maker's normal preload query.
		add_action( 'wp_enqueue_scripts', [ $this, 'preload_canvas_popup' ], 11 );
	}

	/**
	 * Get the authorized native builder request.
	 *
	 * @return array{builder:PageBuilder,popup_id:int}|null
	 */
	protected function get_edit_request() {
		if ( $this->edit_request_resolved ) {
			return $this->edit_request;
		}

		$this->edit_request_resolved = true;
		$this->edit_request          = null;

		foreach ( $this->builders as $builder ) {
			$popup_id = absint( $builder->get_requested_popup_id() );

			if (
				! $popup_id ||
				'popup' !== get_post_type( $popup_id ) ||
				! is_user_logged_in() ||
				! current_user_can( 'edit_post', $popup_id )
			) {
				continue;
			}

			$this->edit_request = [
				'builder'  => $builder,
				'popup_id' => $popup_id,
			];

			break;
		}

		return $this->edit_request;
	}

	/**
	 * Get the popup ID owned by the authorized builder request.
	 *
	 * @return int
	 */
	public function get_edit_popup_id() {
		$request = $this->get_edit_request();

		return $request ? $request['popup_id'] : 0;
	}

	/**
	 * Get the popup ID rendered by the native editor canvas.
	 *
	 * @return int
	 */
	public function get_canvas_popup_id() {
		$request = $this->get_edit_request();

		if ( ! $request || ! $request['builder']->is_canvas_request() ) {
			return 0;
		}

		$popup_id = $request['popup_id'];

		return $popup_id && is_singular( 'popup' ) && absint( get_queried_object_id() ) === $popup_id
			? $popup_id
			: 0;
	}

	/**
	 * Restore an authorized builder request to the private popup post type.
	 *
	 * @param mixed $query_vars Parsed query variables.
	 *
	 * @return mixed
	 */
	public function allow_builder_request( $query_vars ) {
		if ( ! is_array( $query_vars ) ) {
			return $query_vars;
		}

		$popup_id = $this->get_edit_popup_id();

		if ( ! $popup_id ) {
			return $query_vars;
		}

		$query_vars['p']         = $popup_id;
		$query_vars['post_type'] = 'popup';
		$status                  = get_post_status( $popup_id );

		if ( $status && ! in_array( $status, [ 'publish', 'private' ], true ) ) {
			$query_vars['post_status'] = $status;
		}

		return $query_vars;
	}

	/**
	 * Mark the active theme document as a builder canvas.
	 *
	 * @param mixed $classes Body classes.
	 *
	 * @return mixed
	 */
	public function filter_canvas_body_classes( $classes ) {
		if ( ! is_array( $classes ) || ! $this->get_canvas_popup_id() ) {
			return $classes;
		}

		$classes[] = 'pum-builder-preview';

		return array_values( array_unique( $classes ) );
	}

	/**
	 * Prevent the theme loop from rendering a second copy of the document.
	 *
	 * Popup Maker renders the editable document once, inside the popup, through
	 * its normal footer renderer. The active theme still supplies the page shell.
	 *
	 * @param mixed $content Post content.
	 *
	 * @return mixed
	 */
	public function suppress_canvas_content( $content ) {
		return $this->get_canvas_popup_id() && in_the_loop() && is_main_query() ? '' : $content;
	}

	/**
	 * Prevent the theme loop from rendering the popup title behind the canvas.
	 *
	 * @param mixed $title   Post title.
	 * @param mixed $post_id Post ID.
	 *
	 * @return mixed
	 */
	public function suppress_canvas_title( $title, $post_id = 0 ) {
		$canvas_id = $this->get_canvas_popup_id();

		return $canvas_id && absint( $post_id ) === $canvas_id && in_the_loop() && is_main_query() ? '' : $title;
	}

	/**
	 * Load only the popup being edited on a native builder canvas.
	 *
	 * @param bool $loadable Whether the popup is loadable.
	 * @param int  $popup_id Popup ID.
	 *
	 * @return bool
	 */
	public function is_canvas_popup_loadable( $loadable, $popup_id ) {
		$canvas_id = $this->get_canvas_popup_id();

		return $canvas_id ? absint( $popup_id ) === $canvas_id : $loadable;
	}

	/**
	 * Remove live triggers from legacy canvas data.
	 *
	 * @param mixed $data_attr Popup data attributes.
	 * @param int   $popup_id Popup ID.
	 *
	 * @return mixed
	 */
	public function filter_canvas_data_attr( $data_attr, $popup_id ) {
		if ( is_array( $data_attr ) && absint( $popup_id ) === $this->get_canvas_popup_id() ) {
			$data_attr['triggers'] = [];
		}

		return $data_attr;
	}

	/**
	 * Remove live triggers from modern canvas settings.
	 *
	 * @param mixed $settings Popup public settings.
	 * @param mixed $popup Popup model.
	 *
	 * @return mixed
	 */
	public function filter_canvas_settings( $settings, $popup ) {
		if (
			is_array( $settings ) &&
			is_object( $popup ) &&
			isset( $popup->ID ) &&
			absint( $popup->ID ) === $this->get_canvas_popup_id()
		) {
			$settings['triggers'] = [];
		}

		return $settings;
	}

	/**
	 * Keep the canvas close button visible but inert.
	 *
	 * @param mixed $attributes Close button attributes.
	 * @param mixed $popup      Popup model.
	 *
	 * @return mixed
	 */
	public function filter_canvas_close_attributes( $attributes, $popup ) {
		if (
			! is_array( $attributes ) ||
			! is_object( $popup ) ||
			! isset( $popup->ID ) ||
			absint( $popup->ID ) !== $this->get_canvas_popup_id()
		) {
			return $attributes;
		}

		$style = isset( $attributes['style'] ) && is_scalar( $attributes['style'] )
			? rtrim( (string) $attributes['style'], '; ' ) . ';'
			: '';

		$attributes['aria-disabled'] = 'true';
		$attributes['tabindex']      = '-1';
		$attributes['style']         = $style . 'pointer-events:none';

		return $attributes;
	}

	/**
	 * Preload the editor popup and its canvas behavior.
	 *
	 * @return void
	 */
	public function preload_canvas_popup() {
		$popup_id = $this->get_canvas_popup_id();

		if ( ! $popup_id ) {
			return;
		}

		$popup  = $this->container->get( 'popups' )->get_by_id( $popup_id );
		$popups = $this->container->get_controller( 'Frontend\Popups' );

		if (
			! pum_is_popup( $popup ) ||
			$popup_id !== $popup->ID ||
			! $popups instanceof \PopupMaker\Controllers\Frontend\Popups
		) {
			return;
		}

		$popups->preload_popup( $popup );
		wp_enqueue_style( 'popup-maker-builder-preview' );
		wp_enqueue_script( 'popup-maker-builder-preview' );
	}

	/**
	 * Render popup content through its owning builder.
	 *
	 * @param mixed $content Popup post content.
	 * @param mixed $popup_id Popup ID.
	 *
	 * @return mixed
	 */
	public function render_popup_content( $content, $popup_id = 0 ) {
		if (
			! is_string( $content ) ||
			! is_numeric( $popup_id ) ||
			( is_admin() && ! wp_doing_ajax() )
		) {
			return $content;
		}

		$popup_id = absint( $popup_id );

		if ( ! $popup_id || 'popup' !== get_post_type( $popup_id ) ) {
			return $content;
		}

		$builder = $this->owner_for( $popup_id );

		if ( ! $builder ) {
			return $content;
		}

		$request          = $this->get_edit_request();
		$is_editor_canvas = $request &&
			$request['builder'] === $builder &&
			$request['popup_id'] === $popup_id &&
			$this->get_canvas_popup_id() === $popup_id;
		$rendered         = $builder->render_document( $popup_id, $is_editor_canvas );

		return is_string( $rendered ) ? $rendered : $content;
	}

	/**
	 * Find and cache the builder that owns a popup document.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return PageBuilder|null
	 */
	protected function owner_for( $popup_id ) {
		$popup_id = absint( $popup_id );

		if ( array_key_exists( $popup_id, $this->owners ) ) {
			return $this->owners[ $popup_id ];
		}

		$this->owners[ $popup_id ] = null;

		foreach ( $this->builders as $builder ) {
			if ( $builder->owns_document( $popup_id ) ) {
				$this->owners[ $popup_id ] = $builder;
				break;
			}
		}

		return $this->owners[ $popup_id ];
	}
}
