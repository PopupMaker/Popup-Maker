<?php
/**
 * Single-Popup Template
 *
 * This template is used when displaying individual popup posts.
 * It renders the popup using the standard popup.php template.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

get_header();

if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		// Render the popup using the popup.php template.
		if ( 'popup' === get_post_type() ) {
			pum_get_template_part( 'popup', get_the_ID() );
		}
	endwhile;
endif;

get_footer();
