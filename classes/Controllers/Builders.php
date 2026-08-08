<?php
/**
 * Page builder coordinator.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Controllers;

use PopupMaker\Interfaces\BuilderProvider;
use PopupMaker\Interfaces\BuilderProvider\EditsPopups;
use PopupMaker\Interfaces\BuilderProvider\LoadsDocumentAssets;
use PopupMaker\Interfaces\BuilderProvider\ProvidesPreviewUrl;
use PopupMaker\Interfaces\BuilderProvider\RendersDocuments;
use PopupMaker\Plugin\Controller;
use PopupMaker\Services\BuilderPreviewUrl;
use PopupMaker\Services\BuilderProviders;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the page builder request lifecycle.
 *
 * Providers answer questions and perform one operation each. This controller
 * decides when those operations happen:
 *
 * - authorizing a builder edit/canvas request and restoring the popup query;
 * - selecting the isolated popup canvas template;
 * - routing popup content through the owning builder;
 * - batching secondary-document assets and finalizing each builder at most
 *   once per boundary.
 *
 * Keeping batching here prevents multiple builder popups from repeating
 * one-time bootstraps: providers accumulate, the coordinator flushes.
 *
 * @since 1.25.0
 */
class Builders extends Controller {

	/**
	 * Provider registry.
	 *
	 * @var BuilderProviders
	 */
	private $providers;

	/**
	 * Provider keys whose runtime hooks have been registered.
	 *
	 * @var array<string,bool>
	 */
	private $registered = [];

	/**
	 * Provider keys with assets awaiting finalization.
	 *
	 * @var array<string,bool>
	 */
	private $pending = [];

	/**
	 * Popup IDs whose assets have already been collected this request.
	 *
	 * @var array<int,bool>
	 */
	private $collected = [];

	/**
	 * Initialize the coordinator.
	 *
	 * @return void
	 */
	public function init() {
		$this->providers = new BuilderProviders();

		foreach ( $this->default_providers() as $provider ) {
			$this->providers->register( $provider );
		}

		// Core initializes on `plugins_loaded:11`; Pro and add-ons load at 12/13.
		// Delay the extension point until their registration callbacks exist.
		if ( did_action( 'plugins_loaded' ) && ! doing_action( 'plugins_loaded' ) ) {
			$this->register_providers();
		} else {
			add_action( 'plugins_loaded', [ $this, 'register_providers' ], 20 );
		}

		// Theme builders are available after setup. Some plugin builders construct
		// their APIs on `init`, so retry there without double-hooking.
		add_action( 'after_setup_theme', [ $this, 'register_provider_hooks' ], 20 );
		add_action( 'init', [ $this, 'register_provider_hooks' ], 20 );

		// Restore the popup query for an authorized builder request. The popup
		// post type is intentionally not publicly queryable.
		add_filter( 'request', [ $this, 'allow_builder_request' ] );
		// Select the authorized popup canvas after builders replace the template.
		add_filter( 'template_include', [ $this, 'use_popup_canvas' ], PHP_INT_MAX );
		add_filter( 'popup_maker/is_builder_preview', [ $this, 'is_builder_canvas' ] );

		// Route popup content through whichever builder owns the document.
		add_filter( 'pum_popup_content', [ $this, 'render_popup_content' ], 1000, 2 );

		// Drafts are excluded from the normal preload query, but their canvas still
		// needs Popup Maker's CSS, JavaScript, and per-document builder assets.
		add_action( 'wp_enqueue_scripts', [ $this, 'preload_canvas_popup' ], 11 );

		/**
		 * Asset batch boundaries.
		 *
		 * Priority 12 sits after Popup Maker's own preload at 11, so popups
		 * discovered during preloading finalize before `wp_head()` output. The
		 * footer pass catches popups discovered later, such as a `popmake-123`
		 * click trigger found while filtering `the_content`.
		 */
		add_action( 'wp_enqueue_scripts', [ $this, 'flush_pending_assets' ], 12 );
		add_action( 'wp_footer', [ $this, 'flush_pending_assets_late' ], 0 );
	}

	/**
	 * Providers bundled with the plugin.
	 *
	 * @return BuilderProvider[]
	 */
	protected function default_providers() {
		return [
			new \PopupMaker\Builders\Elementor( $this->container ),
		];
	}

	/**
	 * Get the provider registry.
	 *
	 * @return BuilderProviders
	 */
	public function providers() {
		return $this->providers;
	}

	/**
	 * Register providers supplied by add-ons and their preview URL filters.
	 *
	 * @return void
	 */
	public function register_providers() {
		/**
		 * Register additional page builder providers.
		 *
		 * Mirrors the `pum_integrations` filter used by form providers.
		 *
		 * @param BuilderProviders $providers Provider registry.
		 */
		do_action( 'popup_maker/register_builder_providers', $this->providers );

		// Preview URL filters do not depend on a bootstrapped builder runtime.
		foreach ( $this->providers->all() as $provider ) {
			if ( $provider instanceof ProvidesPreviewUrl ) {
				$provider->register_preview_url();
			}
		}
	}

	/**
	 * Register hooks for every available provider.
	 *
	 * @return void
	 */
	public function register_provider_hooks() {
		foreach ( $this->providers->available() as $provider ) {
			if ( isset( $this->registered[ $provider->key() ] ) ) {
				continue;
			}

			$provider->register_hooks();

			$this->registered[ $provider->key() ] = true;
		}
	}

	/**
	 * Get the authorized builder edit request for this page load.
	 *
	 * Authorization lives here rather than in providers so every builder is
	 * held to the same checks: the target must be a popup, the user must be
	 * logged in, and they must be able to edit that specific popup.
	 *
	 * @return array{provider:BuilderProvider,popup_id:int}|null
	 */
	protected function get_edit_request() {
		$edit_request = null;

		/**
		 * Recognizing a builder request only reads query arguments, so every
		 * registered provider is asked — not just the available ones. A builder
		 * can be installed but not yet bootstrapped for the current request, and
		 * the popup query still has to be restored (and live triggers stripped)
		 * for the editor to work at all.
		 */
		foreach ( $this->providers->all() as $provider ) {
			$popup_id = absint( BuilderPreviewUrl::read_request( $provider->key() ) );

			if ( ! $popup_id && $provider instanceof EditsPopups ) {
				$popup_id = absint( $provider->get_requested_popup_id() );
			}

			if ( ! $popup_id ) {
				continue;
			}

			if ( 'popup' !== get_post_type( $popup_id ) ) {
				continue;
			}

			if ( ! is_user_logged_in() || ! current_user_can( 'edit_post', $popup_id ) ) {
				continue;
			}

			$edit_request = [
				'provider' => $provider,
				'popup_id' => $popup_id,
			];

			break;
		}

		return $edit_request;
	}

	/**
	 * Get the authorized popup ID for this builder request.
	 *
	 * @return int Popup ID, or 0 when the request is not an authorized builder request.
	 */
	public function get_edit_popup_id() {
		$request = $this->get_edit_request();

		return $request ? $request['popup_id'] : 0;
	}

	/**
	 * Get the popup ID when the isolated canvas should be rendered.
	 *
	 * The editor shell is left to the builder; only the canvas — and our own
	 * signed preview — gets Popup Maker's template.
	 *
	 * @return int Popup ID, or 0 when this is not a canvas request.
	 */
	public function get_canvas_popup_id() {
		$request = $this->get_edit_request();

		if ( ! $request ) {
			return 0;
		}

		$provider = $request['provider'];

		$is_signed_preview = (bool) BuilderPreviewUrl::read_request( $provider->key() );

		if ( ! $is_signed_preview && $provider instanceof EditsPopups && ! $provider->is_canvas_request() ) {
			return 0;
		}

		if ( ! is_singular( 'popup' ) || absint( get_queried_object_id() ) !== $request['popup_id'] ) {
			return 0;
		}

		return $request['popup_id'];
	}

	/**
	 * Restore the popup query for an authorized builder request.
	 *
	 * @param mixed $query_vars Parsed query variables.
	 *
	 * @return mixed Filtered query variables.
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

		/**
		 * Builders open unsaved popups, which are drafts. A front-end query
		 * returns only published and private posts, so an authorized editor
		 * would otherwise get a 404 for a popup they just created.
		 */
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
	 * @return string Filtered template path.
	 */
	public function use_popup_canvas( $template ) {
		if ( ! $this->get_edit_popup_id() ) {
			return $template;
		}

		/**
		 * Never render popups through the normal footer pass during a builder
		 * request.
		 *
		 * On the canvas the template renders the popup itself, so the footer pass
		 * would duplicate it. On the editor shell there is no popup to show at
		 * all: rendering one covers the entire builder with a fixed, full-screen
		 * overlay at Popup Maker's stacking order.
		 */
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
			pum_is_popup( $popup ) &&
			$popup_id === $popup->ID &&
			$popups instanceof \PopupMaker\Controllers\Frontend\Popups
		) {
			$popups->preload_popup( $popup );
		}
	}

	/**
	 * Render popup content through the builder that owns the document.
	 *
	 * @param mixed $content  Popup post content.
	 * @param mixed $popup_id Popup ID.
	 *
	 * @return mixed Builder markup, or the original content.
	 */
	public function render_popup_content( $content, $popup_id = 0 ) {
		if ( ! is_string( $content ) || ! is_numeric( $popup_id ) ) {
			return $content;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			return $content;
		}

		$popup_id = absint( $popup_id );

		if ( ! $popup_id || 'popup' !== get_post_type( $popup_id ) ) {
			return $content;
		}

		$provider = $this->find_document_provider( $popup_id );

		if ( $provider ) {
			$rendered = $provider->render_document( $popup_id );

			if ( is_string( $rendered ) ) {
				$content = $rendered;
			}
		}

		$asset_provider = $this->find_asset_provider( $popup_id );

		if ( $asset_provider ) {
			$this->collect_assets( $asset_provider, $popup_id );
		}

		return $content;
	}

	/**
	 * Find the provider whose builder owns a popup's document.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return RendersDocuments|null
	 */
	protected function find_document_provider( $popup_id ) {
		foreach ( $this->providers->supporting( RendersDocuments::class ) as $provider ) {
			if ( $provider->is_builder_document( $popup_id ) ) {
				return $provider;
			}
		}

		return null;
	}

	/**
	 * Find the provider whose builder owns a popup's document assets.
	 *
	 * Asset ownership is independent from document rendering. Builders that
	 * render through WordPress' existing content pipeline can still need a
	 * secondary document's assets collected.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return LoadsDocumentAssets|null
	 */
	protected function find_asset_provider( $popup_id ) {
		foreach ( $this->providers->supporting( LoadsDocumentAssets::class ) as $provider ) {
			if ( $provider->is_builder_document( $popup_id ) ) {
				return $provider;
			}
		}

		return null;
	}

	/**
	 * Collect one popup's assets and mark its provider for finalization.
	 *
	 * Deduplicated per popup, so a popup rendered more than once in a request
	 * only collects once.
	 *
	 * @param BuilderProvider $provider Provider that owns the document assets.
	 * @param int             $popup_id Popup ID.
	 *
	 * @return void
	 */
	protected function collect_assets( BuilderProvider $provider, $popup_id ) {
		if ( ! $provider instanceof LoadsDocumentAssets ) {
			return;
		}

		if ( isset( $this->collected[ $popup_id ] ) ) {
			return;
		}

		$this->collected[ $popup_id ] = true;

		$provider->collect_document_assets( $popup_id );

		$this->pending[ $provider->key() ] = true;
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
	 * Finalize each provider with pending assets exactly once.
	 *
	 * @param bool $after_head Whether head output has already been sent.
	 *
	 * @return void
	 */
	protected function flush( $after_head ) {
		if ( ! $this->pending ) {
			return;
		}

		foreach ( array_keys( $this->pending ) as $key ) {
			$provider = $this->providers->get( $key );

			if ( ! $provider instanceof LoadsDocumentAssets ) {
				unset( $this->pending[ $key ] );
				continue;
			}

			if ( $provider->finalize_document_assets( $after_head ) ) {
				unset( $this->pending[ $key ] );
			}
		}
	}
}
