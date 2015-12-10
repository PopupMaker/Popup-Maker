<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PUM_Popup' ) ) {

	/**
	 * Class PUM_Popup
	 *
	 * @since 1.4.0
	 */
	class PUM_Popup extends PUM_Post {

		protected $required_post_type = 'popup';

		private $_cookies = null;
		private $_triggers = null;
		private $_conditions = null;

		private $theme_id = null;
		private $data_attr = null;
		private $title = null;
		private $content = null;

		/**
		 * If no id is passed this will check for the current global id.
		 *
		 * @todo replace usage of popmake_get_the_popup_ID.
		 *
		 * @uses function `popmake_get_the_popup_ID`
		 *
		 * @param bool $_id
		 * @param array $_args
		 */
		public function __construct( $_id = false, $_args = array() ) {
			if ( ! $_id ) {
				$id = popmake_get_the_popup_ID();
				if ( ! $id ) {
					return false;
				}
				$_id = $id;
			}
			parent::__construct( $_id, $_args );
		}

		/**
		 * Returns the title of a popup.
		 *
		 * @uses deprecated filter `popmake_get_the_popup_title`
		 * @uses filter `pum_popup_get_title`
		 *
		 * @return string
		 */
		public function get_title() {
			if ( ! $this->title ) {
				$title = get_post_meta( $this->ID, 'popup_title', true );

				// Deprecated
				$title = apply_filters( 'popmake_get_the_popup_title', $title, $this->ID );

				$this->title = apply_filters( 'pum_popup_get_title', $title, $this->ID );
			}

			return $this->title;
		}

		/**
		 * Returns the content of a popup.
		 *
		 * todo incorporate the
		 *
		 * @uses deprecated filter `the_popup_content`
		 * @uses filter `pum_popup_content`
		 *
		 * @return string
		 */
		public function get_content() {
			if ( ! $this->content ) {
				// Deprecated Filter
				$content = apply_filters( 'the_popup_content', $this->post_content, $this->ID );

				$this->content = apply_filters( 'pum_popup_content', $content, $this->ID );
			}

			return $this->content;
		}

		/**
		 * Returns this popups theme id or the default id.
		 *
		 * todo replace usage of popmake_get_default_popup_theme.
		 *
		 * @uses filter `popmake_get_the_popup_theme`
		 * @uses filter `pum_popup_get_theme_id`
		 *
		 * @return int $theme_id
		 */
		public function get_theme_id() {
			if ( ! $this->theme_id ) {
				$theme_id = get_post_meta( $this->ID, 'popup_theme', true );

				if ( ! $theme_id ) {
					$theme_id = popmake_get_default_popup_theme();
				}

				$this->theme_id = $theme_id;
			}

			// Deprecated
			$theme_id = apply_filters( 'popmake_get_the_popup_theme', $this->theme_id, $this->ID );

			return (int) apply_filters( 'pum_popup_get_theme_id', $theme_id, $this->ID );
		}

		/**
		 * Returns array of classes for this popup.
		 *
		 * @todo integrate popmake_add_popup_size_classes into this method.
		 *
		 * @uses deprecated filter `popmake_get_the_popup_classes`
		 * @uses filter `pum_popup_get_classes`
		 *
		 * @return array $classes
		 */
		function get_classes() {
			$classes = array(
				'popmake',
				'theme-' . $this->get_theme_id()
			);

			// Deprecated
			$classes = apply_filters( 'popmake_get_the_popup_classes', $classes, $this->ID );

			return apply_filters( 'pum_popup_get_classes', $classes, $this->ID );
		}

		function get_cookies() {}
		function get_triggers() {}

		/**
		 * Returns all or single display settings.
		 *
		 * @param null $key
		 *
		 * @return bool|mixed|WP_Error
		 */
		function get_display( $key = null ) {
			if ( ! $this->display ) {
				$this->display = get_post_meta( $this->ID, 'popup_display', true );
			}
			if ( ! $key ) {
				return $this->display;
			}

			if ( isset ( $this->display[ $key ] ) ) {
				return $this->display[ $key ];
			}

			return false;
		}

		/**
		 * Returns all or single close settings.
		 *
		 * @param null $key
		 *
		 * @return bool|mixed|WP_Error
		 */
		function get_close( $key = null ) {
			if ( ! $this->close ) {
				$values = get_post_meta( $this->ID, 'popup_close', true );


				if ( ! $values ) {
					$values = apply_filters( "pum_popup_close_defaults", array() );
				}

				$this->close = $values;
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

				return apply_filters( "pum_popup_close_$key", $value, $this->ID );
			} else {
				return apply_filters( "popmake_get_popup_{$group}", $values, $popup_id );
			}




			if ( ! $key ) {
				return $this->close;
			}

			if ( isset ( $this->close[ $key ] ) ) {
				return $this->close[ $key ];
			}

			return false;
		}

		/**
		 * Returns array for data attribute of this popup.
		 *
		 * @todo integrate popmake_clean_popup_data_attr
		 *
		 * @uses deprecated filter `popmake_get_the_popup_data_attr`
		 * @uses filter `pum_popup_get_data_attr`
		 *
		 * @return array
		 */
		function get_data_attr() {
			if ( ! $this->data_attr ) {

				$data_attr = array(
					'id'       => $this->ID,
					'slug'     => $this->post_name,
					'theme_id' => $this->get_theme_id(),
					'cookies'  => $this->get_cookies(),
					'triggers' => $this->get_triggers(),
					'meta'     => array(
						'display'    => popmake_get_popup_display( $this->ID ),
						'close'      => popmake_get_popup_close( $this->ID ),
						'click_open' => popmake_get_popup_click_open( $this->ID ),
					)
				);

				// Deprecated
				$data_attr = apply_filters( 'popmake_get_the_popup_data_attr', $data_attr, $this->ID );

				$this->data_attr = apply_filters( 'pum_popup_get_data_attr', $data_attr, $this->ID );
			}

			return $this->data_attr;
		}

		/**
		 * Returns the close button text.
		 * @return mixed|void
		 */
		public function close_text() {
			$text = __( '&#215;', 'popup-maker' );

			/** @deprecated */
			$text = apply_filters( 'popmake_popup_default_close_text', $text, $this->ID );

			// todo replace this with PUM_Theme class in the future.
			$theme_text = get_post_meta( $this->get_theme_id(), 'popup_theme_close_text', true );
			if ( $theme_text && $theme_text != '' ) {
				$text = $theme_text;
			}

			// todo replace this with PUM_Popup close settings.
			$popup_close_text = popmake_get_popup_close( $popup_id, 'text' );
			if ( $popup_close_text && $popup_close_text != '' ) {
				$text = $popup_close_text;
			}


			return apply_filters( 'pum_popup_close_text', $text, $this->ID );
		}


		public function get_preview_url() {
			$args = apply_filters( 'pum_popup_preview_url_args', array(
				'printable' => true,
			),$this );
			return get_permalink( $this->ID ) . '?' . http_build_query( $args );
		}

		/**
		 * Returns whether or not the popup is visible in the loop.
		 *
		 * @return bool
		 */
		public function is_loadable() {
			if ( ! $this->ID ) {
				$loadable = false;
				// Published/private
			} elseif ( $this->post_status !== 'publish' && ! current_user_can( 'edit_post', $this->id ) ) {
				$loadable = false;

				// visibility setting
			} elseif ( 'hidden' === $this->visibility ) {
				$loadable = false;
			} elseif ( 'visible' === $this->visibility ) {
				$loadable = true;

				// Visibility in loop
			} elseif ( is_search() ) {
				$loadable = 'search' === $this->visibility;
			} else {
				$loadable = true;
			}

			return apply_filters( 'pum_popup_is_loadable', $loadable, $this->ID );
		}


	}

}
