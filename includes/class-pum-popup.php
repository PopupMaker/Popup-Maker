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

		/**
		 * @var string
		 */
		protected $required_post_type = 'popup';

		/**
		 * @var null
		 */
		protected $cookies = null;

		/**
		 * @var null
		 */
		protected $triggers = null;

		/**
		 * @var null
		 */
		protected $conditions = null;

		/**
		 * @var null
		 */
		protected $display = null;

		/**
		 * @var null
		 */
		protected $close = null;

		/**
		 * @var null
		 */
		protected $theme_id = null;

		/**
		 * @var null
		 */
		protected $data_attr = null;

		/**
		 * @var null
		 */
		protected $title = null;

		/**
		 * @var null
		 */
		protected $content = null;

		/**
		 * If no id is passed this will check for the current global id.
		 *
		 * @todo replace usage of popmake_get_the_popup_ID.
		 *
		 * @uses function `popmake_get_the_popup_ID`
		 *
		 * @param bool $_id
		 * @param array $_args
		 *
		 * return boolean $valid
		 */
		public function __construct( $_id = null, $_args = array() ) {
			if ( ! $_id ) {
				$_id = popmake_get_the_popup_ID();
			}

			return parent::__construct( $_id, $_args );
		}

		/**
		 * Returns the title of a popup.
		 *
		 * @uses filter `popmake_get_the_popup_title`
		 * @uses filter `pum_popup_get_title`
		 *
		 * @return string
		 */
		public function get_title() {
			if ( ! $this->title ) {
				$title = get_post_meta( $this->ID, 'popup_title', true );

				// Deprecated
				$this->title = apply_filters( 'popmake_get_the_popup_title', $title, $this->ID );
			}

			return apply_filters( 'pum_popup_get_title', $this->title, $this->ID );
		}

		/**
		 * Returns the content of a popup.
		 *
		 * @uses filter `the_popup_content`
		 * @uses filter `pum_popup_content`
		 *
		 * @return string
		 */
		public function get_content() {
			if ( ! $this->content ) {
				// Deprecated Filter
				$this->content = apply_filters( 'the_popup_content', $this->post_content, $this->ID );
			}

			return $this->content = apply_filters( 'pum_popup_content', $this->content, $this->ID );
		}

		/**
		 * Returns this popups theme id or the default id.
		 *
		 * @todo replace usage of popmake_get_default_popup_theme.
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

				// Deprecated filter
				$this->theme_id = apply_filters( 'popmake_get_the_popup_theme', $theme_id, $this->ID );
			}

			return (int) apply_filters( 'pum_popup_get_theme_id', $this->theme_id, $this->ID );
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

			// Add a class for each trigger type.
			foreach ( $this->get_triggers() as $trigger => $trigger_settings ) {
				if ( ! in_array( $trigger, $classes ) ) {
					$classes[] = $trigger;
				}
			}

			// Deprecated
			$classes = apply_filters( 'popmake_get_the_popup_classes', $classes, $this->ID );

			return apply_filters( 'pum_popup_get_classes', $classes, $this->ID );
		}

		/**
		 * @return mixed|void
		 */
		function get_cookies() {
			if ( ! $this->cookies ) {
				$this->cookies = get_post_meta( $this->ID, 'popup_cookies', true );

				if ( ! $this->cookies ) {
					$this->cookies = array();
				}
			}

			return apply_filters( 'pum_popup_get_cookies', $this->cookies, $this->ID );
		}

		/**
		 * @return mixed|void
		 */
		function get_triggers() {
			if ( ! $this->triggers ) {
				$this->triggers = get_post_meta( $this->ID, 'popup_triggers', true );

				if ( ! $this->triggers ) {
					$this->triggers = array();
				}
			}

			return apply_filters( 'pum_popup_get_triggers', $this->triggers, $this->ID );
		}

		/**
		 * Returns all or single display settings.
		 *
		 * @param null $key
		 *
		 * @return mixed
		 */
		function get_display( $key = null ) {
			if ( ! $this->display ) {
				$display_values = get_post_meta( $this->ID, 'popup_display', true );

				if ( ! $display_values ) {
					$display_values = apply_filters( "pum_popup_display_defaults", array() );
				}

				$this->display = $display_values;
			}

			$values = apply_filters( 'pum_popup_get_display', $this->display, $this->ID );

			if ( ! $key ) {
				return $values;
			}

			$value = isset ( $values[ $key ] ) ? $values[ $key ] : null;

			return apply_filters( 'pum_popup_get_display_' . $key, $value, $this->ID );
		}

		/**
		 * Returns all or single close settings.
		 *
		 * @param null $key
		 *
		 * @return mixed
		 */
		function get_close( $key = null ) {
			if ( ! $this->close ) {
				$close_values = get_post_meta( $this->ID, 'popup_close', true );

				if ( ! $close_values ) {
					$close_values = apply_filters( "pum_popup_close_defaults", array() );
				}

				$this->close = $close_values;
			}

			$values = apply_filters( 'pum_popup_get_close', $this->close, $this->ID );

			if ( ! $key ) {
				return $values;
			}

			$value = isset ( $values[ $key ] ) ? $values[ $key ] : null;

			return apply_filters( 'pum_popup_get_close_' . $key, $value, $this->ID );

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
						'display'    => $this->get_display(),
						'close'      => $this->get_close(),
						'click_open' => popmake_get_popup_click_open( $this->ID ),
					)
				);

				// Deprecated
				$this->data_attr = apply_filters( 'popmake_get_the_popup_data_attr', $data_attr, $this->ID );
			}

			return apply_filters( 'pum_popup_get_data_attr', $this->data_attr, $this->ID );
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

			// Check to see if popup has close text to over ride default.
			$popup_close_text = $this->get_close( 'text' );
			if ( $popup_close_text && $popup_close_text != '' ) {
				$text = $popup_close_text;
			}

			return apply_filters( 'pum_popup_close_text', $text, $this->ID );
		}


		/**
		 * Returns whether or not the popup is visible in the loop.
		 *
		 * @todo this function will be rebuilt as part of another issue anyways so this is currently a placeholder.
		 *
		 * @return bool
		 */
		public function is_loadable() {
			$loadable = true;

			if ( ! $this->ID ) {
				$loadable = false;
				// Published/private
			}

			return apply_filters( 'pum_popup_is_loadable', $loadable, $this->ID );
		}


	}

}
