<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Get a templates part in $slug-$name.php fashion.
 *
 * Allows passing arguments that will be globally accessible in the templates.
 *
 * @param string $slug
 * @param string $name
 * @param array $args
 *
 * @return string
 */
function pum_get_template_part( $slug, $name = null, $args = null ) {
	return PUM_Utils_Template::get_part( $slug, $name, $args );
}


/**
 * Render a templates part in $slug-$name.php fashion.
 *
 * Allows passing arguments that will be globally accessible in the templates.
 *
 * @param string $slug
 * @param string $name
 * @param array $args
 */
function pum_template_part( $slug, $name = null, $args = array() ) {
	echo pum_get_template_part( $slug, $name, $args );
}

/**
 * Gets the rendered contents of the specified templates file.
 *
 * @param $template_name
 * @param array $args
 *
 * @return string
 */
function pum_get_template( $template_name, $args = array() ) {
	return PUM_Utils_Template::get( $template_name, $args );
}

/**
 * Get other templates (e.g. product attributes) passing attributes and including the file.
 *
 * @deprecated Likely a better way @see pum_template_part()
 *
 * @param string $template_name Template file name with extension: file-name.php
 * @param array $args (default: array())
 */
function pum_load_template( $template_name, $args = array() ) {
	echo pum_get_template( $template_name, $args );
}