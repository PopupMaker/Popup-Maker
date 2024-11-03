<?php

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound, Universal.Namespaces.DisallowCurlyBraceSyntax.Forbidden

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
		 * @return array<string,mixed>|false The Bricks data or false if none exists.
		 */
		public static function get_bricks_data( $post_id, $type ) {
			return false;
		}
	}

	/**
	 * Frontend rendering class for Bricks Builder.
	 */
	class Frontend {

		/**
		 * Render Bricks content.
		 *
		 * @param array<string,mixed> $data The Bricks data to render.
		 * @return void
		 */
		public static function render_content( $data ) {
		}
	}

	/**
	 * Database interaction class for Bricks Builder.
	 */
	class Database {

		/**
		 * Get template data from the database.
		 *
		 * @param string $type The template data type to retrieve.
		 * @return array<string,mixed>|false The template data or false if none exists.
		 */
		public static function get_template_data( $type ) {
			return false;
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
