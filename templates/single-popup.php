<?php
/**
 * Single Popup Template.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

$is_builder_preview = (bool) apply_filters( 'popup_maker/is_builder_preview', false );

// Preserve the legacy Bricks template behavior.
// Keep it until Bricks adopts the shared builder preview controller.
if ( ! $is_builder_preview ) {
	get_header();
	get_footer();
	return;
}

$popup_id = absint( get_queried_object_id() );
$popup    = pum_get_popup( $popup_id );

if ( ! pum_is_popup( $popup ) ) {
	return;
}

$previous_popup = \PopupMaker\get_current_popup();
$has_title      = '' !== $popup->get_title();
$show_close     = $popup->show_close_button();
$body_classes   = [ 'pum-builder-preview' ];

if ( $popup->get_setting( 'overlay_disabled', false ) ) {
	$body_classes[] = 'pum-builder-preview-overlay-disabled';
}

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
	<style id="pum-builder-preview-styles">
		html,
		body.pum-builder-preview {
			min-height: 100%;
		}

		body.pum-builder-preview {
			margin: 0;
			min-height: 100vh;
			overflow: auto;
		}

		body.pum-builder-preview.pum-builder-preview-overlay-disabled {
			background: transparent !important;
		}

		body.pum-builder-preview > .pum-builder-preview-popup {
			display: block !important;
			opacity: 1 !important;
			visibility: visible !important;
		}

		body.pum-builder-preview .pum-container {
			display: block !important;
			opacity: 1 !important;
			visibility: visible !important;
		}

		body.pum-builder-preview .pum-close[aria-disabled="true"] {
			display: block !important;
			pointer-events: none !important;
		}
	</style>
</head>
<?php
/**
 * A DOM-owning builder can emit its own body tag and wrapper through the standard
 * body and footer hooks. The shared template remains unaware of builder markup.
 */
if ( ! apply_filters( 'popup_maker/builder_canvas_body_tag', false, $body_classes ) ) {
	?>
	<body <?php body_class( $body_classes ); ?>>
	<?php
}

wp_body_open();
?>
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : ?>
			<?php
			the_post();
			\PopupMaker\set_current_popup( $popup );
			?>
			<div
				id="pum-<?php pum_popup_ID(); ?>"
				role="dialog"
				aria-modal="false"
				<?php if ( $has_title ) : ?>
					aria-labelledby="pum_popup_title_<?php pum_popup_ID(); ?>"
				<?php else : ?>
					aria-label="<?php esc_attr_e( 'Popup preview', 'popup-maker' ); ?>"
				<?php endif; ?>
				class="<?php pum_popup_classes(); ?> pum-builder-preview-popup"
				<?php pum_popup_data_attr(); ?>
			>
				<div id="popmake-<?php pum_popup_ID(); ?>" class="<?php pum_popup_classes( null, 'container' ); ?>">
					<?php do_action( 'pum_popup_before_title' ); ?>
					<?php do_action( 'popmake_popup_before_inner' ); // Backward compatibility. ?>

					<?php if ( $has_title ) : ?>
						<div id="pum_popup_title_<?php pum_popup_ID(); ?>" class="<?php pum_popup_classes( null, 'title' ); ?>">
							<?php pum_popup_title(); ?>
						</div>
					<?php endif; ?>

					<?php do_action( 'pum_popup_before_content' ); ?>
					<div class="<?php pum_popup_classes( null, 'content' ); ?>" <?php pum_popup_content_tabindex_attr(); ?>>
						<?php pum_popup_content(); ?>
					</div>
					<?php do_action( 'pum_popup_after_content' ); ?>
					<?php do_action( 'popmake_popup_after_inner' ); // Backward compatibility. ?>

					<?php if ( $show_close ) : ?>
						<button type="button" class="<?php pum_popup_classes( null, 'close' ); ?>" aria-label="<?php esc_attr_e( 'Close', 'popup-maker' ); ?>" aria-disabled="true" tabindex="-1">
							<?php pum_popup_close_text(); ?>
						</button>
					<?php endif; ?>
				</div>
			</div>
		<?php endwhile; ?>
	<?php endif; ?>

	<?php
	\PopupMaker\set_current_popup( $previous_popup );
	wp_footer();
	?>
	<script id="pum-builder-preview-script">
		( function ( $, PUM ) {
			'use strict';

			if ( ! $ || ! PUM || 'function' !== typeof PUM.getPopup ) {
				return;
			}

			var popupId = <?php echo absint( $popup_id ); ?>,
				$popup = PUM.getPopup( popupId ),
				$container,
				resizeObserver;

			function constrainPopupToVisibleViewport() {
				var inset = 10,
					bounds = {
						left: inset,
						top: $( 'body' ).hasClass( 'admin-bar' ) ? 42 : inset,
						right: window.innerWidth - inset,
						bottom: window.innerHeight - inset
					},
					frameRect,
					containerRect,
					offsetLeft,
					offsetTop;

				if ( ! $container || ! $container.length ) {
					return;
				}

				if ( window.frameElement && window.parent !== window ) {
					try {
						frameRect = window.frameElement.getBoundingClientRect();
						bounds.left = Math.max( bounds.left, inset - frameRect.left );
						bounds.top = Math.max( bounds.top, inset - frameRect.top );
						bounds.right = Math.min(
							bounds.right,
							window.parent.innerWidth - frameRect.left - inset
						);
						bounds.bottom = Math.min(
							bounds.bottom,
							window.parent.innerHeight - frameRect.top - inset
						);
					} catch ( error ) {
						// Keep the iframe's own viewport bounds across origins.
					}
				}

				if ( bounds.right <= bounds.left || bounds.bottom <= bounds.top ) {
					return;
				}

				containerRect = $container[ 0 ].getBoundingClientRect();
				offsetLeft = containerRect.width <= bounds.right - bounds.left
					? Math.max(
						bounds.left - containerRect.left,
						Math.min( 0, bounds.right - containerRect.right )
					)
					: Math.max( 0, bounds.left - containerRect.left );
				offsetTop = containerRect.height <= bounds.bottom - bounds.top
					? Math.max(
						bounds.top - containerRect.top,
						Math.min( 0, bounds.bottom - containerRect.bottom )
					)
					: Math.max( 0, bounds.top - containerRect.top );

				$container.css( {
					left: ( parseFloat( $container.css( 'left' ) ) || 0 ) + offsetLeft,
					top: ( parseFloat( $container.css( 'top' ) ) || 0 ) + offsetTop
				} );
			}

			function repositionPopup() {
				if ( ! $popup || ! $popup.length || ! $popup.closest( 'body' ).length ) {
					return;
				}

				$popup.popmake( 'reposition' );
				$popup.find( '.pum-close' ).attr( {
					'aria-disabled': 'true',
					tabindex: '-1'
				} );

				$( document ).trigger( 'pumBuilderPreviewReposition', [ $popup, popupId ] );
				constrainPopupToVisibleViewport();
			}

			if ( $popup && $popup.length ) {
				$popup.on( 'pumBeforeClose.pumBuilderPreview', function () {
					$popup.addClass( 'preventClose' );
				} );
				$popup.on( 'pumAfterOpen.pumBuilderPreview', repositionPopup );

				$container = $popup.find( '.pum-container' );

				if ( 'ResizeObserver' in window && $container.length ) {
					resizeObserver = new ResizeObserver( repositionPopup );
					resizeObserver.observe( $container[ 0 ] );
				}
			}

			if ( PUM.initialized ) {
				repositionPopup();
			} else {
				$( document ).one( 'pumInitialized.pumBuilderPreview', repositionPopup );
			}

			$( window ).on( 'resize.pumBuilderPreview', repositionPopup );
		} )( window.jQuery, window.PUM );
	</script>
</body>
</html>
