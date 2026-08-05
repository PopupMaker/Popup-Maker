<?php
/**
 * Single Popup Template.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

$is_builder_preview = (bool) apply_filters( 'popup_maker/is_builder_preview', false );

// Preserve the legacy Bricks template behavior until it adopts the shared
// builder preview controller.
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

		body.pum-builder-preview > .pum-builder-preview-popup {
			display: flex !important;
			align-items: flex-start;
			justify-content: center;
			position: relative !important;
			inset: auto !important;
			min-height: 100vh;
			width: 100% !important;
			padding: 100px 2.5vw 3em;
			opacity: 1 !important;
			visibility: visible !important;
			overflow: visible;
		}

		body.pum-builder-preview .pum-container {
			display: block !important;
			position: relative !important;
			top: auto !important;
			left: auto !important;
			margin-left: 0 !important;
			margin-bottom: 0;
			opacity: 1 !important;
			visibility: visible !important;
		}
	</style>
</head>
<body <?php body_class( 'pum-builder-preview' ); ?>>
	<?php wp_body_open(); ?>
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : ?>
			<?php
			the_post();
			\PopupMaker\set_current_popup( $popup );
			$has_title = pum_get_popup_title() !== '';
			?>
			<div
				id="pum-<?php pum_popup_ID(); ?>"
				role="dialog"
				aria-modal="false"
				<?php if ( $has_title ) : ?>
					aria-labelledby="pum_popup_title_<?php pum_popup_ID(); ?>"
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
						<?php the_content(); ?>
					</div>
					<?php do_action( 'pum_popup_after_content' ); ?>
					<?php do_action( 'popmake_popup_after_inner' ); // Backward compatibility. ?>

					<?php if ( pum_show_close_button() ) : ?>
						<button type="button" class="<?php pum_popup_classes( null, 'close' ); ?>" aria-label="<?php esc_attr_e( 'Close', 'popup-maker' ); ?>">
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
</body>
</html>
