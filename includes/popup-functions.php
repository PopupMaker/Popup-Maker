<?php
/**
 * Popup Functions
 *
 * @package		POPMAKE
 * @subpackage  Functions
 * @copyright   Copyright (c) 2014, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


function popmake_get_the_popup_classes( $popup_id = null ) {
	if( !$popup_id ) $popup_id = get_the_ID();
	return implode( ' ', apply_filters( 'popmake_get_the_popup_classes', array( 'popmake' ), $popup_id ) );
}


function popmake_the_popup_classes( $popup_id = null ) {
	esc_attr_e( popmake_get_the_popup_classes( $popup_id ) );
}


function popmake_add_popup_size_classes( $classes, $popup_id ) {
	$popup_size = popmake_get_popup_display( $popup_id, 'size' );
	if( in_array( $popup_size, array('nano','micro','tiny','small','medium','normal','large','xlarge') ) ) {
		$classes[] = 'responsive';
		$classes[] = 'size-' . $popup_size;
	}
	elseif($popup_size == 'custom') {
		$classes[] = 'size-custom';
	}

	if(!popmake_get_popup_display( $popup_id, 'custom_height_auto' ) && popmake_get_popup_display( $popup_id, 'scrollable_content' )) {
		$classes[] = 'scrollable';
	}

	return $classes;
}
add_filter('popmake_get_the_popup_classes', 'popmake_add_popup_size_classes', 5, 2);


function popmake_get_the_popup_data_attr( $popup_id = null ) {
	if( !$popup_id ) $popup_id = get_the_ID();
	$post = get_post( $popup_id );
	$data_attr = array(
		'id'   => $popup_id,
		'slug' => $post->post_name,
		'meta' => array(
			'display'    => popmake_get_popup_display( $popup_id ),
			'close'      => popmake_get_popup_close( $popup_id ),
			'click_open' => popmake_get_popup_click_open( $popup_id )
		)
	);
	return apply_filters('popmake_get_the_popup_data_attr', $data_attr, $popup_id );
}


function popmake_the_popup_data_attr( $popup_id = null ) {
	echo 'data-popmake="'. esc_attr( json_encode( popmake_get_the_popup_data_attr( $popup_id ) ) ) .'"';
}



/**
 * Returns the meta group of a popup or value if key is set.
 *
 * @since 1.0
 * @param int $popup_id ID number of the popup to retrieve a overlay meta for
 * @return mixed array|string
 */
function popmake_get_popup_meta_group( $group, $popup_id = NULL, $key = NULL ) {
	global $pagenow;
	if(!$popup_id) $popup_id = get_the_ID();

	$post_meta = get_post_custom( $popup_id );
	$default_check_key = 'popup_defaults_set';
	if(!in_array($group, array('close','display','targeting_condition'))) {
		$default_check_key = "popup_{$group}_defaults_set";
	}

	$group_values = array_key_exists($default_check_key, $post_meta) ? array() : apply_filters("popmake_popup_{$group}_defaults", array());
	foreach($post_meta as $meta_key => $value) {
		if(strpos($meta_key, "popup_{$group}_") !== false) {
			$new_key = str_replace("popup_{$group}_", '', $meta_key);
			if(count($value) == 1)
				$group_values[$new_key] = $value[0];
			else
				$group_values[$new_key] = $value;
		}
	}
	if($key) {
		$key = str_replace('.', '_', $key);
		if(!isset($group_values[$key])) {
			return false;
		}
		$value = $group_values[$key];
		return apply_filters( "popmake_get_popup_{$group}_$key", $value, $popup_id );
	}
	else {
		return apply_filters( "popmake_get_popup_{$group}", $group_values, $popup_id );
	}
}


/**
 * Returns the load settings meta of a popup.
 *
 * @since 1.0
 * @param int $popup_id ID number of the popup to retrieve a overlay meta for
 * @return mixed array|string of the popup load settings meta 
 */
function popmake_get_popup_targeting_condition( $popup_id = NULL, $key = NULL ) {
	return popmake_get_popup_meta_group( 'targeting_condition', $popup_id, $key );
}

function popmake_get_popup_targeting_condition_includes( $popup_id, $post_type = NULL ) {
	$post_meta = get_post_custom_keys( $popup_id );
	$includes = array();
	if(!empty($post_meta)) {
		foreach( $post_meta as $meta_key ) {
			if(strpos($meta_key, 'popup_targeting_condition_on_') !== false) {
				$id = intval( substr( strrchr( $meta_key, "_" ), 1 ) );

				if($id > 0) {
					$remove = strrchr( $meta_key  , strrchr( $meta_key, "_" ));
					$name = str_replace( 'popup_targeting_condition_on_', "",  str_replace( $remove, "", $meta_key ) );
					
					$includes[$name][] = intval( $id );
				}
			}
		}
	}
	if($post_type) {
		if(!isset($includes[$post_type]) || empty($includes[$post_type])) {
			$includes[$post_type] = array();
		}
		return $includes[$post_type];
	}
	return $includes;
}

function popmake_get_popup_targeting_condition_excludes( $popup_id, $post_type = NULL ) {
	$post_meta = get_post_custom_keys( $popup_id );
	$excludes = array();
	if(!empty($post_meta)) {
		foreach( $post_meta as $meta_key ) {
			if(strpos($meta_key, 'popup_targeting_condition_exclude_on_') !== false) {
				$id = intval( substr( strrchr( $meta_key, "_" ), 1 ) );

				if($id > 0) {
					$remove = strrchr( $meta_key  , strrchr( $meta_key, "_" ));
					$name = str_replace( 'popup_targeting_condition_exclude_on_', "",  str_replace( $remove, "", $meta_key ) );
					
					$excludes[$name][] = intval( $id );
				}
			}
		}
	}
	if($post_type) {
		if(!isset($excludes[$post_type]) || empty($excludes[$post_type])) {
			$excludes[$post_type] = array();
		}
		return $excludes[$post_type];
	}
	return $excludes;
}


/**
 * Returns the title of a popup.
 *
 * @since 1.0
 * @param int $popup_id ID number of the popup to retrieve a title for
 * @return mixed string|int
 */
function popmake_get_the_popup_title( $popup_id = NULL ) {
	if( !$popup_id ) $popup_id = get_the_ID();
	$title = get_post_meta( $popup_id, 'popup_title', true );
	return apply_filters( 'popmake_get_the_popup_title', $title, $popup_id );
}


function popmake_the_popup_title( $popup_id = NULL ) {
	echo esc_html( popmake_get_the_popup_title( $popup_id ) );
}


function popmake_get_the_popup_content( $popup_id = NULL ) {
	if( !$popup_id ) $popup_id = get_the_ID();
	return apply_filters( 'popmake_get_the_popup_content', get_the_content( $popup_id ), $popup_id );
}


function popmake_apply_the_content( $content ) {
	return apply_filters( 'the_content', $content );
}
add_filter('popmake_get_the_popup_content', 'popmake_apply_the_content', 100);


function popmake_the_popup_content( $popup_id = NULL ) {
	echo popmake_get_the_popup_content( $popup_id );
}


/**
 * Returns the display meta of a popup.
 *
 * @since 1.0
 * @param int $popup_id ID number of the popup to retrieve a display meta for
 * @return mixed array|string of the popup display meta 
 */
function popmake_get_popup_display( $popup_id = NULL, $key = NULL ) {
	return popmake_get_popup_meta_group( 'display', $popup_id, $key );
}


/**
 * Returns the close meta of a popup.
 *
 * @since 1.0
 * @param int $popup_id ID number of the popup to retrieve a close meta for
 * @return mixed array|string of the popup close meta 
 */
function popmake_get_popup_close( $popup_id = NULL, $key = NULL ) {
	return popmake_get_popup_meta_group( 'close', $popup_id, $key );
}


/**
 * Returns the click_open meta of a popup.
 *
 * @since 1.0
 * @param int $popup_id ID number of the popup to retrieve a click_open meta for
 * @return mixed array|string of the popup click_open meta
 */
function popmake_get_popup_click_open( $popup_id = NULL, $key = NULL ) {
	return popmake_get_popup_meta_group( 'click_open', $popup_id, $key );
}



function popmake_popup_content_container( $content ) {
	global $post;
	if ($post->post_type == 'popup') {
		$content = '<div class="popmake-content">' . $content;
		$content .= '</div>';
		$content .= '<a class="popmake-close">'. apply_filters( 'popmake_popup_default_close_text', __( '&#215;', 'popup-maker'), $post->ID ) .'</a>';
	}
	return $content;
}
add_filter('the_content', 'popmake_popup_content_container', 10000);



function popmake_popup_is_loadable( $popup_id ) {
	global $post, $wp_query;
	
	$conditions = popmake_get_popup_targeting_condition( $popup_id );

	$return = false;

	/**
	 * on_home
	 * 
	 * on_entire_site
	 *
	 * on_entire_site
	 * exclude_on_home
	 */
	if( is_home() && ( array_key_exists('on_home', $conditions) || ( array_key_exists('on_entire_site', $conditions) && !array_key_exists('exclude_on_home', $conditions) ) ) ) {
		$return = true;
	}

	/*
		on_pages

		on_pages
		on_specific_pages
		on_page_235

		on_entire_site
		exclude_on_pages

		on_entire_site
		exclude_on_pages
		

		on_entire_site
		exclude_on_specific_pages
		exclude_on_page_235
	*/
	elseif( is_page() ) {

		// Load on all pages
		if( array_key_exists('on_pages', $conditions) && !array_key_exists('on_specific_pages', $conditions) ) {
			$return = true;
		}
		// Load on specific pages
		if( array_key_exists('on_specific_pages', $conditions) && array_key_exists('on_page_' . $post->ID, $conditions) ) {
			$return = true;
		}
		// Load on entire site not excluding all pages.
		if( array_key_exists('on_entire_site', $conditions) && !array_key_exists('exclude_on_pages', $conditions) ) {
			$return = true;
		}
		// Load on entire site not excluding specific pages.
		if( array_key_exists('on_entire_site', $conditions) && array_key_exists('exclude_on_specific_pages', $conditions) && !array_key_exists('exclude_on_page_' . $post->ID, $conditions) ) {
			$return = true;
		}

	}
	elseif( is_category() ) {
		$category_id = $wp_query->get_queried_object_id();

		// Load on all categories
		if( array_key_exists('on_categories', $conditions) && !array_key_exists('on_specific_categories', $conditions) ) {
			$return = true;
		}
		// Load on specific categories
		if( array_key_exists('on_specific_categories', $conditions) && array_key_exists('on_category_' . $category_id, $conditions) ) {
			$return = true;
		}
		// Load on entire site not excluding all categories.
		if( array_key_exists('on_entire_site', $conditions) && !array_key_exists('exclude_on_categories', $conditions) ) {
			$return = true;
		}
		// Load on entire site not excluding specific categories.
		if( array_key_exists('on_entire_site', $conditions) && array_key_exists('exclude_on_specific_categories', $conditions) && !array_key_exists('exclude_on_category_' . $category_id, $conditions) ) {
			$return = true;
		}

	}
	elseif( is_tag() ) {

		// Load on all tags
		if( array_key_exists('on_tags', $conditions) && !array_key_exists('on_specific_tags', $conditions) ) {
			$return = true;
		}
		// Load on specific tags
		if( array_key_exists('on_specific_tags', $conditions) && array_key_exists('on_post_tag_' . $category_id, $conditions) ) {
			$return = true;
		}
		// Load on entire site not excluding all tags.
		if( array_key_exists('on_entire_site', $conditions) && !array_key_exists('exclude_on_tags', $conditions) ) {
			$return = true;
		}
		// Load on entire site not excluding specific tags.
		if( array_key_exists('on_entire_site', $conditions) && array_key_exists('exclude_on_specific_tags', $conditions) && !array_key_exists('exclude_on_post_tag_' . $category_id, $conditions) ) {
			$return = true;
		}

	}
	elseif( is_single() && $post->post_type == 'post' ) {

		// Load on all pages
		if( array_key_exists('on_posts', $conditions) && !array_key_exists('on_specific_posts', $conditions) ) {
			$return = true;
		}
		// Load on specific pages
		if( array_key_exists('on_specific_posts', $conditions) && array_key_exists('on_post_' . $post->ID, $conditions) ) {
			$return = true;
		}
		// Load on entire site not excluding all pages.
		if( array_key_exists('on_entire_site', $conditions) && !array_key_exists('exclude_on_posts', $conditions) ) {
			$return = true;
		}
		// Load on entire site not excluding specific pages.
		if( array_key_exists('on_entire_site', $conditions) && array_key_exists('exclude_on_specific_posts', $conditions) && !array_key_exists('exclude_on_post_' . $post->ID, $conditions) ) {
			$return = true;
		}

	}
	// Add support for custom post types
	elseif( is_single() && $post->post_type != 'post' ) {
		$pt = $post->post_type;
		// Load on all pages
		if( array_key_exists("on_{$pt}s", $conditions) && !array_key_exists("on_specific_{$pt}s", $conditions) ) {
			$return = true;
		}
		// Load on specific pages
		if( array_key_exists("on_specific_{$pt}s", $conditions) && array_key_exists("on_{$pt}_" . $post->ID, $conditions) ) {
			$return = true;
		}
		// Load on entire site not excluding all pages.
		if( array_key_exists("on_entire_site", $conditions) && !array_key_exists("exclude_on_{$pt}s", $conditions) ) {
			$return = true;
		}
		// Load on entire site not excluding specific pages.
		if( array_key_exists("on_entire_site", $conditions) && array_key_exists("exclude_on_specific_{$pt}s", $conditions) && !array_key_exists("exclude_on_{$pt}_" . $post->ID, $conditions) ) {
			$return = true;
		}

	}
	elseif( is_tax() ) {
		$term_id = $wp_query->get_queried_object_id();
	}

	// An Archive is a Category, Tag, Author or a Date based pages.
	elseif( is_archive() ) {}

	return apply_filters('popmake_popup_is_loadable', $return, $popup_id);
}


function get_all_popups() {
	$query = new WP_Query( array(
		'post_type' => 'popup',
		'posts_per_page' => -1
	) );
    return $query;
}
