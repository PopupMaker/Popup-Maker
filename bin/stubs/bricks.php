<?php
/**
 * Static analysis stubs for the Bricks theme.
 *
 * Mirrors the Bricks 1.12.4 API surface Popup Maker calls. Bricks ships as a
 * theme, so these symbols never exist during analysis.
 *
 * @package PopupMaker
 */

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound, Universal.Namespaces.DisallowCurlyBraceSyntax.Forbidden
// phpcs:disable Universal.Namespaces.DisallowDeclarationWithoutName.Forbidden, Universal.Namespaces.OneDeclarationPerFile.MultipleFound
// phpcs:disable Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound

namespace {
	define( 'BRICKS_VERSION', '1.12.4' );
	define( 'BRICKS_DB_GLOBAL_SETTINGS', 'bricks_global_settings' );
	define( 'BRICKS_BUILDER_PARAM', 'bricks' );
	define( 'BRICKS_BUILDER_IFRAME_PARAM', 'brickspreview' );
}

namespace Bricks {
	/**
	 * Helper class for Bricks Builder functionality.
	 */
	class Helpers {

		/**
		 * Get the editor mode for a post.
		 *
		 * @param int $post_id The post ID.
		 * @return string|false The editor mode or false if not using Bricks.
		 */
		public static function get_editor_mode( $post_id ) {
			return false;
		}

		/**
		 * Get Bricks data for a post.
		 *
		 * @param int    $post_id The post ID.
		 * @param string $type    The data type to retrieve.
		 * @return array<int,mixed>|false The Bricks data or false if none exists.
		 */
		public static function get_bricks_data( $post_id, $type ) {
			return false;
		}

		/**
		 * Whether the post type is supported by the builder.
		 *
		 * @param int $post_id The post ID.
		 * @return bool
		 */
		public static function is_post_type_supported( $post_id = 0 ) {
			return false;
		}

		/**
		 * Get the builder edit link for a post.
		 *
		 * @param int $post_id The post ID.
		 * @return string
		 */
		public static function get_builder_edit_link( $post_id = 0 ) {
			return '';
		}

		/**
		 * Get template settings for a template.
		 *
		 * @param int $post_id The post ID.
		 * @return array<string,mixed>
		 */
		public static function get_template_settings( $post_id ) {
			return [];
		}
	}

	/**
	 * Frontend rendering class for Bricks Builder.
	 */
	class Frontend {

		/**
		 * Flat element map for the tree currently rendering.
		 *
		 * @var array<string,mixed>
		 */
		public static $elements = [];

		/**
		 * Content area currently rendering.
		 *
		 * @var string
		 */
		public static $area = 'content';

		/**
		 * Render Bricks content wrapped in a content tag.
		 *
		 * @param array<int,mixed>    $bricks_data      The Bricks data to render.
		 * @param array<string,mixed> $attributes       Wrapper attributes.
		 * @param string              $html_after_begin Markup inserted after the opening tag.
		 * @param string              $html_before_end  Markup inserted before the closing tag.
		 * @param string              $tag              Wrapper tag name.
		 * @return void
		 */
		public static function render_content( $bricks_data = [], $attributes = [], $html_after_begin = '', $html_before_end = '', $tag = 'main' ) {
		}

		/**
		 * Render Bricks element data without a wrapper.
		 *
		 * Returns the markup rather than echoing it, unlike render_content().
		 *
		 * @param array<int,mixed> $elements The elements to render.
		 * @param string           $area     The content area being rendered.
		 * @return string Rendered markup.
		 */
		public static function render_data( $elements = [], $area = 'content' ) {
			return '';
		}
	}

	/**
	 * Database interaction class for Bricks Builder.
	 */
	class Database {

		/**
		 * Global Bricks settings.
		 *
		 * @var array<string,mixed>
		 */
		public static $global_settings = [];

		/**
		 * Active templates for the current request.
		 *
		 * @var array<string,mixed>
		 */
		public static $active_templates = [];

		/**
		 * Get template data from the database.
		 *
		 * @param string $type The template data type to retrieve.
		 * @return array<int,mixed>|false The template data or false if none exists.
		 */
		public static function get_template_data( $type ) {
			return false;
		}

		/**
		 * Get element data for a post.
		 *
		 * @param int    $post_id      The post ID.
		 * @param string $content_area The content area to retrieve.
		 * @return array<int,mixed>
		 */
		public static function get_data( $post_id = 0, $content_area = '' ) {
			return [];
		}

		/**
		 * Get a global Bricks setting.
		 *
		 * @param string $key     The setting key.
		 * @param mixed  $default The default value.
		 * @return mixed
		 */
		public static function get_setting( $key, $default = false ) {
			return $default;
		}
	}

	/**
	 * Asset generation class for Bricks Builder.
	 */
	class Assets {

		/**
		 * Accumulated inline CSS keyed by CSS type.
		 *
		 * @var array<string,string>
		 */
		public static $inline_css = [];

		/**
		 * Post IDs whose Bricks page settings CSS should be generated.
		 *
		 * @var array<int,int>
		 */
		public static $page_settings_post_ids = [];

		/**
		 * Generate CSS for a set of elements into the inline CSS accumulator.
		 *
		 * @param array<int,mixed> $elements The elements to generate CSS for.
		 * @param string           $css_type The CSS type key to accumulate into.
		 * @return void
		 */
		public static function generate_css_from_elements( $elements, $css_type ) {
		}

		/**
		 * Generate all inline CSS for a post.
		 *
		 * @param int $post_id The post ID.
		 * @return string
		 */
		public static function generate_inline_css( $post_id = 0 ) {
			return '';
		}

		/**
		 * Generate page-settings CSS for the collected post IDs.
		 *
		 * @return string|null
		 */
		public static function generate_inline_css_page_settings() {
			return '';
		}

		/**
		 * Get one page-settings script group for the collected post IDs.
		 *
		 * @param string $script_key Page-settings script key.
		 * @return string|null
		 */
		public static function get_page_settings_scripts( $script_key = '' ) {
			return '';
		}

		/**
		 * Minify a CSS string.
		 *
		 * @param string $css The CSS to minify.
		 * @return string
		 */
		public static function minify_css( $css ) {
			return $css;
		}
	}

	/**
	 * Theme styles management class for Bricks Builder.
	 */
	class Theme_Styles {

		/**
		 * Set the active style for a post.
		 *
		 * @param int $post_id The post ID.
		 * @return void
		 */
		public static function set_active_style( $post_id ) {
		}
	}
}
