<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the popup id.
 *
 * @param int $popup_id
 *
 * @return int
 */
function pum_get_popup_id( $popup_id = 0 ) {
	if ( ! empty( $popup_id ) && is_numeric( $popup_id ) ) {
		$_popup_id = $popup_id;
	} elseif ( is_object( PUM_Site_Popups::$current ) && is_numeric( PUM_Site_Popups::$current->ID ) ) {
		$_popup_id = PUM_Site_Popups::$current->ID;
	} else {
		$_popup_id = 0;
	}

	return (int) apply_filters( 'pum_get_popup_id', (int) $_popup_id, $popup_id );
}

/**
 * Get a popup model instance.
 *
 * @since 1.7.0
 *
 * @param int $popup_id
 * @param bool $force Clears cached instance and refreshes.
 *
 * @return PUM_Model_Popup|false
 */
function pum_get_popup( $popup_id = 0, $force = false ) {
	return PUM_Model_Popup::instance( pum_get_popup_id( $popup_id ), $force );
}

/**
 * Checks if the $popup is valid.
 *
 * @param mixed|PUM_Model_Popup $popup
 *
 * @return bool
 */
function pum_is_popup( $popup ) {
	return is_object( $popup ) && is_numeric( $popup->ID ) && $popup->is_valid();
}

#region Deprecated & Soon to Be Deprecated Functions

/**
 * @param $popup_id
 *
 * @return array|null|WP_Post
 */
function popmake_get_popup( $popup_id ) {
	if ( ! $popup_id ) {
		$popup_id = popmake_get_the_popup_ID();
	}

	return get_post( $popup_id );
}

/**
 * @return int
 */
function popmake_get_the_popup_ID() {
	global $popup;

	return $popup ? $popup->ID : 0;
}

/**
 *
 */
function popmake_the_popup_ID() {
	echo popmake_get_the_popup_ID();
}

/**
 * @return int
 */
function get_the_popup_ID() {
	return popmake_get_the_popup_ID();
}

/**
 * @deprecated 1.4 Use the PUM_Popup class instead.
 *
 * @param int $popup_id
 *
 * @return mixed|void
 */
function popmake_get_the_popup_theme( $popup_id = null ) {
	if ( ! $popup_id ) {
		$popup_id = popmake_get_the_popup_ID();
	}
	$theme = get_post_meta( $popup_id, 'popup_theme', true );
	if ( empty( $theme ) ) {
		$theme = popmake_get_default_popup_theme();
	}

	return apply_filters( 'popmake_get_the_popup_theme', $theme, $popup_id );
}

/**
 * @deprecated 1.4 Use pum_popup_theme_id instead.
 * @param int $popup_id
 */
function popmake_the_popup_theme( $popup_id = null ) {
	echo popmake_get_the_popup_theme( $popup_id );
}

/**
 * @deprecated 1.4 Use the PUM_Popup class instead.
 *
 * @param int $popup_id
 *
 * @return string
 */
function popmake_get_the_popup_classes( $popup_id = null ) {
	if ( ! $popup_id ) {
		$popup_id = popmake_get_the_popup_ID();
	}
	$theme_id = popmake_get_the_popup_theme( $popup_id );

	return implode( ' ', apply_filters( 'popmake_get_the_popup_classes', array(
		'popmake',
		'theme-' . $theme_id
	), $popup_id ) );
}

/**
 * @deprecated 1.4 Use pum_popup_classes instead.
 * @param int $popup_id
 */
function popmake_the_popup_classes( $popup_id = null ) {
	esc_attr_e( popmake_get_the_popup_classes( $popup_id ) );
}


/**
 * @deprecated 1.4 Built into the PUM_Popup class instead.
 *
 * @param array $classes
 * @param int   $popup_id
 *
 * @return array
 */
function popmake_add_popup_size_classes( $classes, $popup_id ) {
	$popup_size = popmake_get_popup_display( $popup_id, 'size' );
	if ( in_array( $popup_size, array( 'nano', 'micro', 'tiny', 'small', 'medium', 'normal', 'large', 'xlarge' ) ) ) {
		$classes[] = 'responsive';
		$classes[] = 'size-' . $popup_size;
	} elseif ( $popup_size == 'custom' ) {
		$classes[] = 'size-custom';
	}

	if ( ! popmake_get_popup_display( $popup_id, 'custom_height_auto' ) && popmake_get_popup_display( $popup_id, 'scrollable_content' ) ) {
		$classes[] = 'scrollable';
	}

	return $classes;
}

/**
 * @deprecated 1.4 Use the PUM_Popup class instead.
 *
 * @param int $popup_id
 *
 * @return array
 */
function popmake_get_the_popup_data_attr( $popup_id = null ) {
	if ( ! $popup_id ) {
		$popup_id = popmake_get_the_popup_ID();
	}
	$post      = get_post( $popup_id );
	$data_attr = array(
		'id'       => $popup_id,
		'slug'     => $post->post_name,
		'theme_id' => popmake_get_the_popup_theme( $popup_id ),
		'cookies'  => pum_get_popup_cookies( $popup_id ),
		'triggers' => pum_get_popup_triggers( $popup_id ),
		'meta'     => array(
			'display'    => popmake_get_popup_display( $popup_id ),
			'close'      => popmake_get_popup_close( $popup_id ),
			'click_open' => popmake_get_popup_click_open( $popup_id ),
		)
	);
	if ( popmake_get_popup_auto_open( $popup_id, 'enabled' ) ) {
		$data_attr['meta']['auto_open'] = popmake_get_popup_auto_open( $popup_id );
	}
	if ( popmake_get_popup_admin_debug( $popup_id, 'enabled' ) ) {
		$data_attr['meta']['admin_debug'] = popmake_get_popup_admin_debug( $popup_id );
	}

	return apply_filters( 'popmake_get_the_popup_data_attr', $data_attr, $popup_id );
}

/**
 * @param $data_attr
 *
 * @return mixed
 */
function popmake_clean_popup_data_attr( $data_attr ) {

	$display = $data_attr['meta']['display'];

	if ( ! in_array( $display['size'], array(
		'nano',
		'micro',
		'tiny',
		'small',
		'medium',
		'normal',
		'large',
		'xlarge'
	) )
	) {
		unset( $display['responsive_max_width'], $display['responsive_max_width_unit'], $display['responsive_min_width'], $display['responsive_min_width_unit'] );
	} else if ( $display['size'] != 'custom' ) {
		unset( $display['custom_height'], $display['custom_height_auto'], $display['custom_height_unit'], $display['custom_width'], $display['custom_width_unit'] );
	}

	if ( empty( $display['responsive_max_width'] ) ) {
		unset( $display['responsive_max_width'], $display['responsive_max_width_unit'] );
	}
	if ( empty( $display['responsive_min_width'] ) ) {
		unset( $display['responsive_min_width'], $display['responsive_min_width_unit'] );
	}
	if ( strpos( $display['location'], 'left' ) === false ) {
		unset( $display['position_left'] );
	}
	if ( strpos( $display['location'], 'right' ) === false ) {
		unset( $display['position_right'] );
	}
	if ( strpos( $display['location'], 'top' ) === false ) {
		unset( $display['position_top'] );
	}
	if ( strpos( $display['location'], 'bottom' ) === false ) {
		unset( $display['position_bottom'] );
	}

	$data_attr['meta']['display'] = $display;

	if ( $data_attr['meta']['click_open']['extra_selectors'] == '' ) {
		unset( $data_attr['meta']['click_open']['extra_selectors'] );
	}

	if ( $data_attr['meta']['close']['text'] == '' ) {
		unset( $data_attr['meta']['close']['text'] );
	}

	if ( $data_attr['meta']['close']['button_delay'] == '' ) {
		unset( $data_attr['meta']['close']['button_delay'] );
	}

	foreach ( $data_attr['meta'] as $key => $opts ) {
		if ( empty ( $opts ) ) {
			unset( $data_attr['meta'][ $key ] );
		}
	}

	return $data_attr;
}

//add_filter( 'popmake_get_the_popup_data_attr', 'popmake_clean_popup_data_attr' );

/**
 * @deprecated 1.4 Use pum_popup_data_attr instead.
 * @param int $popup_id
 */
function popmake_the_popup_data_attr( $popup_id = null ) {
	echo 'data-popmake="' . esc_attr( wp_json_encode( popmake_get_the_popup_data_attr( $popup_id ) ) ) . '"';
}

/**
 * Returns the meta group of a popup or value if key is set.
 *
 * @since 1.3.0
 * @deprecated 1.4
 *
 * @param $group
 * @param int $popup_id ID number of the popup to retrieve a overlay meta for
 * @param null $key
 * @param null $default
 *
 * @return mixed array|string
 */
function popmake_get_popup_meta( $group, $popup_id = null, $key = null, $default = null ) {
	if ( ! $popup_id ) {
		$popup_id = popmake_get_the_popup_ID();
	}

	$values = get_post_meta( $popup_id, "popup_{$group}", true );

	if ( ! $values ) {
		$defaults = apply_filters( "popmake_popup_{$group}_defaults", array() );
		$values = array_merge( $defaults, popmake_get_popup_meta_group( $group, $popup_id ) );
	} else {
		$values = array_merge( popmake_get_popup_meta_group( $group, $popup_id ), $values );
	}

	if ( $key ) {

		// Check for dot notation key value.
		$test  = uniqid();
		$value = popmake_resolve( $values, $key, $test );
		if ( $value == $test ) {

			$key = str_replace( '.', '_', $key );

			if ( ! isset( $values[ $key ] ) ) {
				$value = $default;
			} else {
				$value = $values[ $key ];
			}

		}

		return apply_filters( "popmake_get_popup_{$group}_$key", $value, $popup_id );
	} else {
		return apply_filters( "popmake_get_popup_{$group}", $values, $popup_id );
	}
}

/**
 * Returns the meta group of a popup or value if key is set.
 *
 * @since 1.0
 * @deprecated 1.3.0
 *
 * @param int $popup_id ID number of the popup to retrieve a overlay meta for
 *
 * @return mixed array|string
 */
function popmake_get_popup_meta_group( $group, $popup_id = null, $key = null, $default = null ) {
	if ( ! $popup_id || $group === 'secure_logout') {
		$popup_id = popmake_get_the_popup_ID();
	}

	$post_meta         = get_post_custom( $popup_id );

	if ( ! is_array( $post_meta ) ) {
		$post_meta = array();
	}

	$default_check_key = 'popup_defaults_set';
	if ( ! in_array( $group, array( 'auto_open', 'close', 'display', 'targeting_condition' ) ) ) {
		$default_check_key = "popup_{$group}_defaults_set";
	}

	$group_values = array_key_exists( $default_check_key, $post_meta ) ? array() : apply_filters( "popmake_popup_{$group}_defaults", array() );
	foreach ( $post_meta as $meta_key => $value ) {
		if ( strpos( $meta_key, "popup_{$group}_" ) !== false ) {
			$new_key = str_replace( "popup_{$group}_", '', $meta_key );
			if ( count( $value ) == 1 ) {
				$group_values[ $new_key ] = $value[0];
			} else {
				$group_values[ $new_key ] = $value;
			}
		}
	}
	if ( $key ) {
		$key = str_replace( '.', '_', $key );
		if ( ! isset( $group_values[ $key ] ) ) {
			$value = $default;
		} else {
			$value = $group_values[ $key ];
		}

		return apply_filters( "popmake_get_popup_{$group}_$key", $value, $popup_id );
	} else {
		return apply_filters( "popmake_get_popup_{$group}", $group_values, $popup_id );
	}
}

/**
 * Returns the load settings meta of a popup.
 *
 * @since 1.0
 * @deprecated 1.4
 *
 * @param int $popup_id ID number of the popup to retrieve a overlay meta for
 *
 * @return mixed array|string of the popup load settings meta
 */
function popmake_get_popup_targeting_condition( $popup_id = null, $key = null ) {
	return popmake_get_popup_meta_group( 'targeting_condition', $popup_id, $key );
}

/**
 *
 * @since 1.0
 * @deprecated 1.4
 *
 * @param      $popup_id
 * @param null $post_type
 *
 * @return array
 */
function popmake_get_popup_targeting_condition_includes( $popup_id, $post_type = null ) {
	$post_meta = get_post_custom_keys( $popup_id );
	$includes  = array();
	if ( ! empty( $post_meta ) ) {
		foreach ( $post_meta as $meta_key ) {
			if ( strpos( $meta_key, 'popup_targeting_condition_on_' ) !== false ) {
				$id = intval( substr( strrchr( $meta_key, "_" ), 1 ) );

				if ( $id > 0 ) {
					$remove = strrchr( $meta_key, strrchr( $meta_key, "_" ) );
					$name   = str_replace( 'popup_targeting_condition_on_', "", str_replace( $remove, "", $meta_key ) );

					$includes[ $name ][] = intval( $id );
				}
			}
		}
	}
	if ( $post_type ) {
		if ( ! isset( $includes[ $post_type ] ) || empty( $includes[ $post_type ] ) ) {
			$includes[ $post_type ] = array();
		}

		return $includes[ $post_type ];
	}

	return $includes;
}

/**
 * @param      $popup_id
 * @param null $post_type
 *
 * @return array
 */
function popmake_get_popup_targeting_condition_excludes( $popup_id, $post_type = null ) {
	$post_meta = get_post_custom_keys( $popup_id );
	$excludes  = array();
	if ( ! empty( $post_meta ) ) {
		foreach ( $post_meta as $meta_key ) {
			if ( strpos( $meta_key, 'popup_targeting_condition_exclude_on_' ) !== false ) {
				$id = intval( substr( strrchr( $meta_key, "_" ), 1 ) );

				if ( $id > 0 ) {
					$remove = strrchr( $meta_key, strrchr( $meta_key, "_" ) );
					$name   = str_replace( 'popup_targeting_condition_exclude_on_', "", str_replace( $remove, "", $meta_key ) );

					$excludes[ $name ][] = intval( $id );
				}
			}
		}
	}
	if ( $post_type ) {
		if ( ! isset( $excludes[ $post_type ] ) || empty( $excludes[ $post_type ] ) ) {
			$excludes[ $post_type ] = array();
		}

		return $excludes[ $post_type ];
	}

	return $excludes;
}

/**
 * Returns the title of a popup.
 *
 * @since 1.0
 * @deprecated 1.4 Use the PUM_Popup class instead.
 *
 * @param int $popup_id ID number of the popup to retrieve a title for
 *
 * @return mixed string|int
 */
function popmake_get_the_popup_title( $popup_id = null ) {
	if ( ! $popup_id ) {
		$popup_id = popmake_get_the_popup_ID();
	}
	$title = get_post_meta( $popup_id, 'popup_title', true );

	return apply_filters( 'popmake_get_the_popup_title', $title, $popup_id );
}

/**
 * @deprecated 1.4 Use pum_popup_title instead.
 * @param int $popup_id
 */
function popmake_the_popup_title( $popup_id = null ) {
	echo esc_html( popmake_get_the_popup_title( $popup_id ) );
}

/**
 * @deprecated 1.4 Use the PUM_Popup class instead.
 *
 * @param int $popup_id
 *
 * @return mixed|void
 */
function popmake_get_the_popup_content( $popup_id = null ) {
	if ( ! $popup_id ) {
		$popup_id = popmake_get_the_popup_ID();
	}
	$popup = popmake_get_popup( $popup_id );

	return apply_filters( 'the_popup_content', $popup->post_content, $popup_id );
}

/**
 * @deprecated 1.4 Use pum_popup_content instead.
 * @param int $popup_id
 */
function popmake_the_popup_content( $popup_id = null ) {
	echo popmake_get_the_popup_content( $popup_id );
}

/**
 * Returns the display meta of a popup.
 *
 * @since 1.0
 * @deprecated 1.4
 *
 * @param int $popup_id ID number of the popup to retrieve a display meta for
 *
 * @return mixed array|string of the popup display meta
 */
function popmake_get_popup_display( $popup_id = null, $key = null, $default = null ) {
	return pum_popup( $popup_id )->get_display( $key );
	//return popmake_get_popup_meta( 'display', $popup_id, $key, $default );
}

/**
 * Returns the close meta of a popup.
 *
 * @since 1.0
 * @deprecated 1.4 Use PUM_Popup class instead
 *
 * @param int $popup_id ID number of the popup to retrieve a close meta for
 *
 * @return mixed array|string of the popup close meta
 */
function popmake_get_popup_close( $popup_id = null, $key = null, $default = null ) {
	return pum_popup( $popup_id )->get_close( $key );
	//return popmake_get_popup_meta( 'close', $popup_id, $key, $default );
}

/**
 * Returns the click_open meta of a popup.
 *
 * @since 1.0
 * @deprecated 1.4
 *
 * @param int $popup_id ID number of the popup to retrieve a click_open meta for
 * @param null $key
 * @param null $default
 *
 * @return mixed array|string of the popup click_open meta
 */
function popmake_get_popup_click_open( $popup_id = null, $key = null, $default = null ) {
	return popmake_get_popup_meta( 'click_open', $popup_id, $key, $default );
}

/**
 * Returns the auto open meta of a popup.
 *
 * @since 1.1.0
 * @deprecated 1.4
 *
 * @param int $popup_id ID number of the popup to retrieve a auto open meta for
 *
 * @return mixed array|string of the popup auto open meta
 */
function popmake_get_popup_auto_open( $popup_id = null, $key = null, $default = null ) {
	return popmake_get_popup_meta( 'auto_open', $popup_id, $key, $default );
}

/**
 * Returns the auto open meta of a popup.
 *
 * @since 1.1.8
 * @deprecated 1.4
 *
 * @param int $popup_id ID number of the popup to retrieve a admin debug meta for
 * @param null $key
 * @param null $default
 *
 * @return mixed array|string of the popup admin debug meta
 */
function popmake_get_popup_admin_debug( $popup_id = null, $key = null, $default = null ) {
	if ( ! current_user_can( 'edit_post', $popup_id ) ) {
		return null;
	}

	return popmake_get_popup_meta( 'admin_debug', $popup_id, $key, $default );
}

/**
 * todo replace this with customizable templates.
 *
 * @param $content
 * @param $popup_id
 *
 * @return string
 */
function popmake_popup_content_container( $content, $popup_id ) {
	$popup = popmake_get_popup( $popup_id );
	if ( $popup->post_type == 'popup' ) {
		$content = '<div class="popmake-content">' . $content;
		$content .= '</div>';
		if ( apply_filters( 'popmake_show_close_button', true, $popup_id ) ) {
			$content .= '<span class="popmake-close">' . apply_filters( 'popmake_popup_default_close_text', '&#215;', $popup_id ) . '</span>';
		}
	}

	return $content;
}

/**
 * @deprecated 1.4 use PUM_Popup get_close_text method.
 *
 * @param $text
 * @param $popup_id
 *
 * @return mixed
 */
function popmake_popup_close_text( $text, $popup_id ) {
	$theme_text = get_post_meta( popmake_get_the_popup_theme( $popup_id ), 'popup_theme_close_text', true );
	if ( $theme_text && $theme_text != '' ) {
		$text = $theme_text;
	}

	$popup_close_text = popmake_get_popup_close( $popup_id, 'text' );
	if ( $popup_close_text && $popup_close_text != '' ) {
		$text = $popup_close_text;
	}

	return $text;
}
add_filter( 'popmake_popup_default_close_text', 'popmake_popup_close_text', 10, 2 );


/**
 * @param $popup_id
 *
 * @return mixed|void
 */
function popmake_popup_is_loadable( $popup_id ) {
	global $post, $wp_query;

	$conditions  = popmake_get_popup_targeting_condition( $popup_id );
	$sitewide    = false;
	$is_loadable = false;

	if ( array_key_exists( 'on_entire_site', $conditions ) ) {
		$sitewide    = true;
		$is_loadable = true;
	}
	/**
	 * Front Page Checks
	 */
	if ( is_front_page() ) {
		if ( ! $sitewide && array_key_exists( 'on_home', $conditions ) ) {
			$is_loadable = true;
		} elseif ( $sitewide && array_key_exists( 'exclude_on_home', $conditions ) ) {
			$is_loadable = false;
		}
	}
	/**
	 * Blog Index Page Checks
	 */
	if ( is_home() ) {
		if ( ! $sitewide && array_key_exists( 'on_blog', $conditions ) ) {
			$is_loadable = true;
		} elseif ( $sitewide && array_key_exists( 'exclude_on_blog', $conditions ) ) {
			$is_loadable = false;
		}
	} /**
	 * Page Checks
	 */
	elseif ( is_page() ) {
		if ( ! $sitewide ) {
			// Load on all pages
			if ( array_key_exists( 'on_pages', $conditions ) && ! array_key_exists( 'on_specific_pages', $conditions ) ) {
				$is_loadable = true;
			} // Load on specific pages
			elseif ( array_key_exists( 'on_specific_pages', $conditions ) && array_key_exists( 'on_page_' . $post->ID, $conditions ) ) {
				$is_loadable = true;
			}
		} else {
			// Exclude on all pages.
			if ( array_key_exists( 'exclude_on_pages', $conditions ) && ! array_key_exists( 'exclude_on_specific_pages', $conditions ) ) {
				$is_loadable = false;
			} // Exclude on specific pages.
			elseif ( array_key_exists( 'exclude_on_specific_pages', $conditions ) && array_key_exists( 'exclude_on_page_' . $post->ID, $conditions ) ) {
				$is_loadable = false;
			}
		}
	} /**
	 * Post Checks
	 */
	elseif ( is_single() && $post->post_type == 'post' ) {
		if ( ! $sitewide ) {
			// Load on all posts`1
			if ( array_key_exists( 'on_posts', $conditions ) && ! array_key_exists( 'on_specific_posts', $conditions ) ) {
				$is_loadable = true;
			} // Load on specific posts
			elseif ( array_key_exists( 'on_specific_posts', $conditions ) && array_key_exists( 'on_post_' . $post->ID, $conditions ) ) {
				$is_loadable = true;
			}
		} else {
			// Exclude on all posts.
			if ( array_key_exists( 'exclude_on_posts', $conditions ) && ! array_key_exists( 'exclude_on_specific_posts', $conditions ) ) {
				$is_loadable = false;
			} // Exclude on specific posts.
			elseif ( array_key_exists( 'exclude_on_specific_posts', $conditions ) && array_key_exists( 'exclude_on_post_' . $post->ID, $conditions ) ) {
				$is_loadable = false;
			}
		}
	} /**
	 * Category Checks
	 */
	elseif ( is_category() ) {
		$category_id = $wp_query->get_queried_object_id();
		if ( ! $sitewide ) {
			// Load on all categories
			if ( array_key_exists( 'on_categorys', $conditions ) && ! array_key_exists( 'on_specific_categorys', $conditions ) ) {
				$is_loadable = true;
			} // Load on specific categories
			elseif ( array_key_exists( 'on_specific_categorys', $conditions ) && array_key_exists( 'on_category_' . $category_id, $conditions ) ) {
				$is_loadable = true;
			}
		} else {
			// Exclude on all categories.
			if ( array_key_exists( 'exclude_on_categorys', $conditions ) && ! array_key_exists( 'exclude_on_specific_categorys', $conditions ) ) {
				$is_loadable = false;
			} // Exclude on specific categories.
			elseif ( array_key_exists( 'exclude_on_specific_categorys', $conditions ) && array_key_exists( 'exclude_on_category_' . $category_id, $conditions ) ) {
				$is_loadable = false;
			}
		}
	} /**
	 * Tag Checks
	 */
	elseif ( is_tag() ) {
		$tag_id = $wp_query->get_queried_object_id();
		if ( ! $sitewide ) {
			// Load on all tags
			if ( array_key_exists( 'on_tags', $conditions ) && ! array_key_exists( 'on_specific_tags', $conditions ) ) {
				$is_loadable = true;
			} // Load on specific tags
			elseif ( array_key_exists( 'on_specific_tags', $conditions ) && array_key_exists( 'on_tag_' . $tag_id, $conditions ) ) {
				$is_loadable = true;
			}
		} else {
			// Exclude on all tags.
			if ( array_key_exists( 'exclude_on_tags', $conditions ) && ! array_key_exists( 'exclude_on_specific_tags', $conditions ) ) {
				$is_loadable = false;
			} // Exclude on specific tags.
			elseif ( array_key_exists( 'exclude_on_specific_tags', $conditions ) && array_key_exists( 'exclude_on_tag_' . $tag_id, $conditions ) ) {
				$is_loadable = false;
			}
		}
	} /**
	 * Custom Post Type Checks
	 * Add support for custom post types
	 */
	elseif ( is_single() && ! in_array( $post->post_type, array( 'post', 'page' ) ) ) {
		$pt = $post->post_type;

		if ( ! $sitewide ) {
			// Load on all post type items
			if ( array_key_exists( "on_{$pt}s", $conditions ) && ! array_key_exists( "on_specific_{$pt}s", $conditions ) ) {
				$is_loadable = true;
			} // Load on specific post type items
			elseif ( array_key_exists( "on_specific_{$pt}s", $conditions ) && array_key_exists( "on_{$pt}_" . $post->ID, $conditions ) ) {
				$is_loadable = true;
			}
		} else {
			// Exclude on all post type items.
			if ( array_key_exists( "exclude_on_{$pt}s", $conditions ) && ! array_key_exists( "exclude_on_specific_{$pt}s", $conditions ) ) {
				$is_loadable = false;
			} // Exclude on specific post type items.
			elseif ( array_key_exists( "exclude_on_specific_{$pt}s", $conditions ) && array_key_exists( "exclude_on_{$pt}_" . $post->ID, $conditions ) ) {
				$is_loadable = false;
			}
		}
	} /**
	 * Custom Taxonomy Checks
	 * Add support for custom taxonomies
	 */
	elseif ( is_tax() ) {
		$term_id = $wp_query->get_queried_object_id();
		$tax     = get_query_var( 'taxonomy' );
		if ( ! $sitewide ) {
			// Load on all custom tax terms.
			if ( array_key_exists( "on_{$tax}s", $conditions ) && ! array_key_exists( "on_specific_{$tax}s", $conditions ) ) {
				$is_loadable = true;
			} // Load on specific custom tax terms.
			elseif ( array_key_exists( "on_specific_{$tax}s", $conditions ) && array_key_exists( "on_{$tax}_" . $term_id, $conditions ) ) {
				$is_loadable = true;
			}
		} else {
			// Exclude on all custom tax terms.
			if ( array_key_exists( "exclude_on_{$tax}s", $conditions ) && ! array_key_exists( "exclude_on_specific_{$tax}s", $conditions ) ) {
				$is_loadable = false;
			} // Exclude on specific custom tax terms.
			elseif ( array_key_exists( "exclude_on_specific_{$tax}s", $conditions ) && array_key_exists( "exclude_on_{$tax}_" . $term_id, $conditions ) ) {
				$is_loadable = false;
			}
		}
	}
	/**
	 * Search Checks
	 */
	if ( is_search() ) {
		if ( ! $sitewide && array_key_exists( 'on_search', $conditions ) ) {
			$is_loadable = true;
		} elseif ( $sitewide && array_key_exists( 'exclude_on_search', $conditions ) ) {
			$is_loadable = false;
		}
	}
	/**
	 * 404 Page Checks
	 */
	if ( is_404() ) {
		if ( ! $sitewide && array_key_exists( 'on_404', $conditions ) ) {
			$is_loadable = true;
		} elseif ( $sitewide && array_key_exists( 'exclude_on_404', $conditions ) ) {
			$is_loadable = false;
		}
	}

	/*
		// An Archive is a Category, Tag, Author or a Date based pages.
		elseif( is_archive() ) {
			if( array_key_exists("on_entire_site", $conditions)) {
				$is_loadable = true;
			}
		}
	*/

	return apply_filters( 'popmake_popup_is_loadable', $is_loadable, $popup_id, $conditions, $sitewide );
}


/**
 * @return WP_Query
 */
function get_all_popups() {
	$query = PUM_Popups::get_all();

	return $query;
}


#endregion