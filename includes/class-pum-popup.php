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
		 * @param string $element The key or html element identifier.
		 *
		 * @return array $classes
		 */
		function get_classes( $element = 'overlay' ) {
			$classes = array(
				'overlay' => array(
					'pum',
					'pum-overlay',
					'pum-theme-' . $this->get_theme_id(),
					'popmake-overlay', // Backward Compatibility
				),
				'container' => array(
					'pum-container',
					'popmake', // Backward Compatibility
					'theme-' . $this->get_theme_id(), // Backward Compatibility
				),
				'title' => array(
					'pum-title',
					'popmake-title', // Backward Compatibility
				),
				'content' => array(
					'pum-content',
					'popmake-content', // Backward Compatibility
				),
				'close' => array(
					'pum-close',
					'popmake-close' // Backward Compatibility
				),
			);


			$size = $this->get_display( 'size' );
			if ( in_array( $size, array( 'nano', 'micro', 'tiny', 'small', 'medium', 'normal', 'large', 'xlarge' ) ) ) {
				$classes['container'][] = 'pum-responsive';
				$classes['container'][] = 'pum-responsive-' . $size;
				$classes['container'][] = 'responsive'; // Backward Compatibility
				$classes['container'][] = 'size-' . $size; // Backward Compatibility
			} elseif ( $size == 'custom' ) {
				$classes['container'][] = 'size-custom'; // Backward Compatibility
			}

			if ( ! $this->get_display( 'custom_height_auto' ) && $this->get_display( 'scrollable_content' ) ) {
				$classes['container'][] = 'pum-scrollable';
				$classes['container'][] = 'scrollable'; // Backward Compatibility
			}

			if ( $this->get_display( 'position_fixed' ) ) {
				$classes['container'][] = 'pum-position-fixed';
			}

			// Add a class for each trigger type.
			foreach ( $this->get_triggers() as $trigger => $trigger_settings ) {
				if ( ! in_array( $trigger, $classes['overlay'] ) ) {
					$classes['overlay'][] = $trigger;
				}
			}

			if ( is_singular( 'popup' ) ) {
				$classes['overlay'][] = 'pum-preview';
			}

			// Deprecated: applies old classes to the new overlay
			if ( $element == 'container' ) {
				$classes['container'] = apply_filters( 'popmake_get_the_popup_classes', $classes['container'], $this->ID );
			}

			$classes = apply_filters( 'pum_popup_get_classes', $classes, $this->ID );

			return apply_filters( "pum_popup_get_{$element}_classes", $classes[ $element ], $this->ID );
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
		 * @return mixed|void
		 */
		function get_conditions() {
			if ( ! $this->conditions ) {
				$this->conditions = get_post_meta( $this->ID, 'popup_conditions', true );

				if ( ! $this->conditions ) {
					$this->conditions = array();
				}
			}

			return apply_filters( 'pum_popup_get_conditions', $this->conditions, $this->ID );
		}

		/**
		 * @return mixed|void
		 */
		function has_conditions() {
			return boolval( count ( $this->get_conditions() ) );
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
		 * Returns true if the close button should be rendered.
		 *
		 * @uses apply_filters `popmake_show_close_button`
		 * @uses apply_filters `pum_popup_show_close_button`
		 *
		 * @return bool
		 */
		public function show_close_button() {
			// Deprecated filter.
			$show = apply_filters( 'popmake_show_close_button', true, $this->ID );

			return boolval( apply_filters( 'pum_popup_show_close_button', $show, $this->ID ) );
		}


		/**
		 * Returns whether or not the popup is visible in the loop.
		 *
		 * @return bool
		 */
		public function is_loadable() {

			// Loadable defaults to true if no conditions. Making the popup available everywhere.
			$loadable = true;

			if ( ! $this->ID ) {
				return false;
				// Published/private
			}

			if ( $this->has_conditions() ) {

				// All Groups Must Return True. Break if any is false and set $loadable to false.
				foreach ( $this->get_conditions() as $group => $conditions ) {

					// Groups are false until a condition proves true.
					$group_check = false;

					// At least one group condition must be true. Break this loop if any condition is true.
					foreach ( $conditions as $condition ) {

						// If any condition passes, set $group_check true and break.
						if ( ! $condition['not_operand'] && $this->check_condition( $condition ) ) {
							$group_check = true;
							break;
						} elseif ( $condition['not_operand'] && ! $this->check_condition( $condition ) ) {
							$group_check = true;
							break;
						}

					}

					// If any group of conditions doesn't pass, popup is not loadable.
					if ( ! $group_check ) {
						$loadable = false;
					}

				}

			}

			return apply_filters( 'pum_popup_is_loadable', $loadable, $this->ID );
		}

		public function check_condition( $settings = array() ) {
			$condition = PUM_Conditions::instance()->get_condition( $settings['target'] );

			if ( ! $condition ) {
				return false;
			}

			return call_user_func( $condition->get_callback(), $settings, $this );
		}

	}

}
