<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'popmake_example_popup_content', 'popmake_default_example_popup_content', 1 );
function popmake_default_example_popup_content() {
	echo popmake_get_default_example_popup_content();
}

function popmake_get_default_example_popup_content() {
	return '<p>Suspendisse ipsum eros, tincidunt sed commodo ut, viverra vitae ipsum. Etiam non porta neque. Pellentesque nulla elit, aliquam in ullamcorper at, bibendum sed eros. Morbi non sapien tellus, ac vestibulum eros. In hac habitasse platea dictumst. Nulla vestibulum, diam vel porttitor placerat, eros tortor ultrices lectus, eget faucibus arcu justo eget massa. Maecenas id tellus vitae justo posuere hendrerit aliquet ut dolor.</p>';
}