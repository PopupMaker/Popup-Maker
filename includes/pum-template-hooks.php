<?php

/**
 * @since 1.4 hooks & filters
 */
add_filter( 'pum_popup_content', array( $GLOBALS['wp_embed'], 'run_shortcode' ), 8 );
add_filter( 'pum_popup_content', array( $GLOBALS['wp_embed'], 'autoembed' ), 8 );
add_filter( 'pum_popup_content', 'wptexturize', 10 );
add_filter( 'pum_popup_content', 'convert_smilies', 10 );
add_filter( 'pum_popup_content', 'convert_chars', 10 );
add_filter( 'pum_popup_content', 'wpautop', 10 );
add_filter( 'pum_popup_content', 'shortcode_unautop', 10 );
add_filter( 'pum_popup_content', 'prepend_attachment', 10 );
add_filter( 'pum_popup_content', 'force_balance_tags', 10 );
add_filter( 'pum_popup_content', 'do_shortcode', 11 );
add_filter( 'pum_popup_content', 'capital_P_dangit', 11 );
