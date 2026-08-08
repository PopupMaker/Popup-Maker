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

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : ?>
			<?php
			the_post();
			\PopupMaker\set_current_popup( $popup );
			pum_template_part( 'popup' );
			?>
		<?php endwhile; ?>
	<?php endif; ?>

	<?php
	\PopupMaker\set_current_popup( $previous_popup );
	wp_footer();
	?>
</body>
</html>
