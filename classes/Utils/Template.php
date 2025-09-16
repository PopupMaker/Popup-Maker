<?php
/**
 * Template Utility
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Utils_Template
 */
class PUM_Utils_Template {

	/**
	 * Get template search paths with priorities.
	 *
	 * @return array<int, string> Priority-indexed array of template directory paths
	 */
	public static function paths() {
		$template_dir = apply_filters( 'pum_template_path', 'popup-maker' );

		$old_template_dir = apply_filters( 'popmake_templates_dir', 'popmake_templates' );

		$file_paths = apply_filters(
			'pum_template_paths',
			[
				1   => trailingslashit( get_stylesheet_directory() ) . $template_dir,
				2   => trailingslashit( get_stylesheet_directory() ) . $old_template_dir,
				10  => trailingslashit( get_template_directory() ) . $template_dir,
				11  => trailingslashit( get_template_directory() ) . $old_template_dir,
				100 => Popup_Maker::$DIR . 'templates',
			]
		);

		/* @deprecated 1.8.9 */
		$file_paths = apply_filters( 'popmake_template_paths', $file_paths );

		// sort the file paths based on priority
		ksort( $file_paths, SORT_NUMERIC );

		return array_map( 'trailingslashit', $file_paths );
	}

	/**
	 * Locate a template and return the path for inclusion.
	 *
	 * This is the load order:
	 *
	 *        yourtheme        /    $template_path    /    $template_name
	 *        yourtheme        /    $template_name
	 *        $default_path    /    $template_name
	 *
	 * @access   public
	 *
	 * @param string|string[] $template_names Template name(s) to locate
	 * @param bool            $load Whether to load the template immediately
	 * @param bool            $use_require_once Whether to use require_once when loading
	 *
	 * @return string|false Path to located template file, or false if not found
	 * @internal param string $template_path (default: '')
	 * @internal param string $default_path (default: '')
	 */
	public static function locate( $template_names, $load = false, $use_require_once = true ) {
		// No file found yet
		$located = false;

		$template_name = '';

		// Try to find a template file
		foreach ( (array) $template_names as $template_name ) {

			// Continue if template is empty
			if ( empty( $template_name ) ) {
				continue;
			}

			// Trim off any slashes from the template name
			$template_name = ltrim( $template_name, '/' );

			// try locating this template file by looping through the template paths
			foreach ( self::paths() as $template_path ) {
				if ( file_exists( $template_path . $template_name ) ) {
					$located = $template_path . $template_name;
					break;
				}
			}

			if ( $located ) {
				break;
			}
		}

		// Return what we found
		$located = apply_filters( 'pum_locate_template', $located, $template_name );

		if ( ( true === $load ) && ! empty( $located ) ) {
			load_template( $located, $use_require_once );
		}

		return $located;
	}

	/**
	 * Locate a template part (for templates like the topic-loops).
	 *
	 * Popup_Maker::$DEBUG will prevent overrides in themes from taking priority.
	 *
	 * @param string      $slug Template slug
	 * @param string|null $name Template name variation
	 * @param bool        $load Whether to load the template immediately
	 *
	 * @return string|false Path to located template part, or false if not found
	 */
	public static function locate_part( $slug, $name = null, $load = false ) {
		/** @var string[] $templates */
		$templates = [];
		if ( $name ) {
			// slug-name.php
			$templates[] = "{$slug}-{$name}.php";
		}

		// slug.php
		$templates[] = "{$slug}.php";

		// Allow template parts to be filtered
		$templates = apply_filters( 'pum_locate_template_part', $templates, $slug, $name );

		/* @deprecated 1.8.0 */
		$templates = apply_filters( 'popmake_get_template_part', $templates, $slug, $name );

		// Return the part that is found
		return self::locate( $templates, $load, false );
	}

	/**
	 * Render file with extracted arguments.
	 *
	 * @param string|false         $template Path to template file or false if not found
	 * @param array<string, mixed> $args Variables to extract into template scope
	 * @return void
	 */
	public static function render( $template, $args = [] ) {

		if ( ! $template || ! file_exists( $template ) ) {
			_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', esc_html( $template ?: 'unknown' ) ), '1.0.0' );

			return;
		}

		if ( $args ) {
			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
			extract( $args );
		}

		include $template;
	}

	/**
	 * Render a template part in $slug-$name.php fashion.
	 *
	 * Allows passing arguments that will be globally accessible in the template.
	 *
	 * @param string               $slug Template slug
	 * @param string|null          $name Template name variation
	 * @param array<string, mixed> $args Variables to extract into template scope
	 * @return void
	 */
	public static function part( $slug, $name = null, $args = [] ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo self::get_part( $slug, $name, $args );
	}

	/**
	 * Get a template part in $slug-$name.php fashion.
	 *
	 * Allows passing arguments that will be globally accessible in the template.
	 *
	 * @param string               $slug Template slug
	 * @param string|null          $name Template name variation
	 * @param array<string, mixed> $args Variables to extract into template scope
	 *
	 * @return string Rendered template content (empty string if template not found)
	 */
	public static function get_part( $slug, $name = null, $args = [] ) {

		$template = self::locate_part( $slug, $name );

		ob_start();

		do_action( 'pum_before_template_part', $template, $slug, $name, $args );

		/* @deprecated 1.8.0 */
		do_action( 'get_template_part_' . $slug, $slug, $name );

		if ( $template ) {
			self::render( $template, $args );
		}

		do_action( 'pum_after_template_part', $template, $slug, $name, $args );

		$content = ob_get_clean();
		return false !== $content ? $content : '';
	}

	/**
	 * Gets the rendered contents of the specified template file.
	 *
	 * @param string               $template_name Template file name to locate and render
	 * @param array<string, mixed> $args Variables to extract into template scope
	 *
	 * @return string Rendered template content (empty string if template not found)
	 */
	public static function get( $template_name, $args = [] ) {
		$template = self::locate( $template_name );

		// Allow 3rd party plugin filter template file from their plugin.
		$template = apply_filters( 'pum_get_template', $template, $template_name, $args );

		ob_start();

		do_action( 'pum_before_template', $template_name, $template, $args );

		if ( $template ) {
			self::render( $template, $args );
		}

		do_action( 'pum_after_template', $template_name, $template, $args );

		$content = ob_get_clean();
		return false !== $content ? $content : '';
	}

	/**
	 * Get other templates (e.g. popup content) passing attributes and including the file.
	 *
	 * @deprecated  public
	 *
	 * @param string               $template_name Template file name with extension: file-name.php
	 * @param array<string, mixed> $args Template variables (default: array())
	 * @return void
	 */
	public static function load( $template_name, $args = [] ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo self::get( $template_name, $args );
	}
}
