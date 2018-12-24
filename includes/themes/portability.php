<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

function pum_install_theme( $name, $settings = null, $extra_meta = array() ) {
	if ( ! isset( $settings ) ) {
		$settings = PUM_Admin_Themes::defaults();
	}

	$new_theme_id = @wp_insert_post( array(
		'post_title'     => $name,
		'post_author'    => get_current_user_id(),
		'post_status'    => 'publish',
		'post_type'      => 'popup_theme',
		'comment_status' => 'closed',
		'meta_input'     => array_merge( (array) $extra_meta, array(
			'popup_theme_settings' => $settings,
		) ),
	) );

	pum_force_theme_css_refresh();

	return $new_theme_id;

}

function pum_import_theme_from_repo( $hash ) {
	$theme_data = array(
		'name'            => __( 'Imported Theme', 'popup-maker' ),
		'settings'        => PUM_Admin_Themes::defaults(),
		'original_author' => 'Daniel',
	);

	return pum_intall_theme( $theme_data['name'], $theme_data['settings'], array(
		'_pum_theme_repo_hash'   => $hash,
		'_pum_theme_repo_author' => $theme_data['original_author'],
	) );
}

/**
 * Installs a default theme and returns the new theme ID.
 *
 * @since 1.8.0
 *
 * @return int|\WP_Error
 */
function pum_install_default_theme() {
	return pum_install_theme( __( 'Default Theme', 'popup-maker' ), null, array(
		'_pum_built_in'      => 'default-theme',
		'_pum_default_theme' => true,
	) );
}
