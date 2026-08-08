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
use PopupMaker\Services\BuilderPreviewUrl;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates Popup Maker's page builder lifecycle.
 *
 * Builder integrations own third-party APIs. This controller owns the shared
 * WordPress orchestration: request authorization, the isolated popup canvas,
 * document rendering, and batched secondary-document assets.
 *
 * @since 1.25.0
 */
class Builders extends Controller {

	/**
	 * Bundled builder integrations, keyed by builder.
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
	 * Builders with assets awaiting finalization.
	 *
	 * @var array<string,PageBuilder>
	 */
	private $pending = [];

	/**
	 * Popup IDs whose assets have already been collected.
	 *
	 * @var array<int,bool>
	 */
	private $collected = [];

	/**
	 * Cached authorized builder request.
	 *
	 * @var array{builder:PageBuilder,popup_id:int}|null
	 */
	private $edit_request;

	/**
	 * Whether the authorized builder request has been resolved.
	 *
	 * @var bool
	 */
	private $edit_request_resolved = false;

	/**
	 * Whether controller hooks have been registered.
	 *
	 * @var bool
	 */
	private $initialized = false;

	/**
	 * Initialize the coordinator.
	 *
	 * @return void
	 */
	public function init() {
		if ( $this->initialized ) {
			return;
		}

		$this->initialized = true;

		foreach ( $this->default_builders() as $builder ) {
			if ( ! $builder instanceof PageBuilder || ! $builder->key() ) {
				continue;
			}

			$this->builders[ $builder->key() ] = $builder;
		}

		// Keep ordinary sites free of builder request and rendering hooks.
		if ( ! $this->builders ) {
			return;
		}

		$this->boot_builders();

		// Theme builders appear after setup, while some plugin builders finish
		// constructing their APIs on init. PageBuilder::boot() is idempotent.
		add_action( 'after_setup_theme', [ $this, 'boot_builders' ], 20 );
		add_action( 'init', [ $this, 'boot_builders' ], 20 );

		add_filter( 'request', [ $this, 'allow_builder_request' ] );
		add_filter( 'template_include', [ $this, 'use_popup_canvas' ], PHP_INT_MAX );
		add_filter( 'popup_maker/is_builder_preview', [ $this, 'is_builder_canvas' ] );
		add_filter( 'body_class', [ $this, 'filter_canvas_body_classes' ] );
		add_filter( 'pum_popup_content', [ $this, 'render_popup_content' ], 1000, 2 );

		// Draft canvases are absent from Popup Maker's normal preload query.
		add_action( 'wp_enqueue_scripts', [ $this, 'preload_canvas_popup' ], 11 );

		// Finalize documents discovered during preload before wp_head(), then
		// catch documents discovered while rendering the page in the footer.
		add_action( 'wp_enqueue_scripts', [ $this, 'flush_pending_assets' ], 12 );
		add_action( 'wp_footer', [ $this, 'flush_pending_assets_late' ], 0 );
	}

	/**
	 * Builders bundled with the plugin and active for this request.
	 *
	 * Concrete integrations are added by the commits that support them. Keeping
	 * this as a plain list avoids a registry and does not load inactive adapters.
	 *
	 * @return PageBuilder[]
	 */
	protected function default_builders() {
		return [];
	}

	/**
	 * Boot every builder whose third-party APIs are available.
	 *
	 * @return void
	 */
	public function boot_builders() {
		$became_ready = false;

		foreach ( $this->builders as $builder ) {
			$was_ready = $builder->is_ready();

			if ( $builder->boot() && ! $was_ready ) {
				$became_ready = true;
			}
		}

		// A lookup made unusually early must not hide a builder that became ready
		// at after_setup_theme or init.
		if ( $became_ready ) {
			$this->owners = [];
		}
	}

	/**
	 * Get the authorized builder request for this page load.
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
			$popup_id = absint( BuilderPreviewUrl::read_request( $builder->key() ) );

			if ( ! $popup_id ) {
				$popup_id = absint( $builder->get_requested_popup_id() );
			}

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
	 * Get the authorized popup ID for this builder request.
	 *
	 * @return int
	 */
	public function get_edit_popup_id() {
		$request = $this->get_edit_request();

		return $request ? $request['popup_id'] : 0;
	}

	/**
	 * Get the popup ID when the isolated canvas should be rendered.
	 *
	 * @return int
	 */
	public function get_canvas_popup_id() {
		$request = $this->get_edit_request();

		if ( ! $request ) {
			return 0;
		}

		$builder           = $request['builder'];
		$is_signed_preview = (bool) BuilderPreviewUrl::read_request( $builder->key() );

		if ( ! $is_signed_preview && ! $builder->is_canvas_request() ) {
			return 0;
		}

		if ( ! is_singular( 'popup' ) || absint( get_queried_object_id() ) !== $request['popup_id'] ) {
			return 0;
		}

		return $request['popup_id'];
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

		$status = get_post_status( $popup_id );

		if ( $status && ! in_array( $status, [ 'publish', 'private' ], true ) ) {
			$query_vars['post_status'] = $status;
		}

		return $query_vars;
	}

	/**
	 * Select the isolated popup canvas template.
	 *
	 * @param string $template Selected template path.
	 *
	 * @return string
	 */
	public function use_popup_canvas( $template ) {
		if ( ! $this->get_edit_popup_id() ) {
			return $template;
		}

		// A builder shell must not be covered by Popup Maker's footer renderer;
		// the isolated canvas renders its one popup directly from the template.
		$popups = $this->container->get_controller( 'Frontend\Popups' );

		if ( $popups instanceof \PopupMaker\Controllers\Frontend\Popups ) {
			remove_action( 'wp_footer', [ $popups, 'render_popups' ] );
		}

		if ( ! $this->get_canvas_popup_id() ) {
			return $template;
		}

		$canvas = $this->container->get_path( 'templates/single-popup.php' );

		return file_exists( $canvas ) ? $canvas : $template;
	}

	/**
	 * Whether the current request renders the isolated builder canvas.
	 *
	 * @param bool $is_canvas Whether another integration claimed the request.
	 *
	 * @return bool
	 */
	public function is_builder_canvas( $is_canvas ) {
		return $is_canvas || (bool) $this->get_canvas_popup_id();
	}

	/**
	 * Add presentation classes to the isolated canvas.
	 *
	 * @param mixed $classes Body classes.
	 *
	 * @return mixed
	 */
	public function filter_canvas_body_classes( $classes ) {
		if ( ! is_array( $classes ) ) {
			return $classes;
		}

		$popup_id = $this->get_canvas_popup_id();

		if ( ! $popup_id ) {
			return $classes;
		}

		$classes[] = 'pum-builder-preview';
		$popup     = pum_get_popup( $popup_id );

		if ( pum_is_popup( $popup ) && $popup->get_setting( 'overlay_disabled', false ) ) {
			$classes[] = 'pum-builder-preview-overlay-disabled';
		}

		return array_values( array_unique( $classes ) );
	}

	/**
	 * Preload the isolated canvas popup before head assets are printed.
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
		wp_enqueue_style( 'pum-builder-preview' );
		wp_enqueue_script( 'pum-builder-preview' );
	}

	/**
	 * Render and collect assets through the builder that owns the popup.
	 *
	 * @param mixed $content  Popup post content.
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

		$rendered = $builder->render_document(
			$popup_id,
			$popup_id === $this->get_canvas_popup_id()
		);

		if ( is_string( $rendered ) ) {
			$content = $rendered;
		}

		$this->collect_assets( $builder, $popup_id );

		return $content;
	}

	/**
	 * Find and cache the one builder that owns a popup document.
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
			if ( ! $builder->boot() || ! $builder->owns_document( $popup_id ) ) {
				continue;
			}

			$this->owners[ $popup_id ] = $builder;
			break;
		}

		return $this->owners[ $popup_id ];
	}

	/**
	 * Collect one popup's assets once and schedule finalization when needed.
	 *
	 * @param PageBuilder $builder  Owning builder.
	 * @param int         $popup_id Popup ID.
	 *
	 * @return void
	 */
	protected function collect_assets( PageBuilder $builder, $popup_id ) {
		if ( isset( $this->collected[ $popup_id ] ) ) {
			return;
		}

		$this->collected[ $popup_id ] = true;

		if ( $builder->collect_document_assets( $popup_id ) ) {
			$this->pending[ $builder->key() ] = $builder;
		}
	}

	/**
	 * Finalize pending assets before head output.
	 *
	 * @return void
	 */
	public function flush_pending_assets() {
		$this->flush( false );
	}

	/**
	 * Finalize pending assets discovered after head output.
	 *
	 * @return void
	 */
	public function flush_pending_assets_late() {
		$this->flush( true );
	}

	/**
	 * Finalize each pending builder once per boundary.
	 *
	 * @param bool $after_head Whether wp_head() output has passed.
	 *
	 * @return void
	 */
	protected function flush( $after_head ) {
		foreach ( $this->pending as $key => $builder ) {
			if ( $builder->finalize_document_assets( $after_head ) ) {
				unset( $this->pending[ $key ] );
			}
		}
	}
}
