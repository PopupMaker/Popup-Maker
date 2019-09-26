<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Model_Popup
 *
 * @since 1.4
 */
class PUM_Model_Popup extends PUM_Abstract_Model_Post {

	/** @var string */
	protected $required_post_type = 'popup';

	/** @var array */
	public $conditions_filtered = array();

	/**
	 * @var string
	 *
	 * @deprecated  1.8.0 Was used in PUM ALM extension, needs time to get those changes published.
	 * @toberemoved v1.9.0
	 */
	public $content;

	/** @var bool */
	public $doing_passive_migration = false;

	/**
	 * The current model version.
	 *
	 * 1 - v1.0.0
	 * 2 - v1.4.0
	 * 3 - v1.7.0
	 *
	 * @var int
	 */
	public $model_version = 3;

	/**
	 * The version of the data currently stored for the current item.
	 *
	 * 1 - v1.0.0
	 * 2 - v1.4.0
	 * 3 - v1.7.0
	 *
	 * @var int
	 */
	public $data_version;

	# TODO Remove these once no longer needed.

	/**
	 * @var array
	 * @deprecated 1.7.0
	 */
	public $display;

	/**
	 * @var array
	 * @deprecated 1.7.0
	 */
	public $close;

	/**
	 * Used to hackishly insert settings for generated popups not stored in DB. (Shortcodes).
	 *
	 * @var array
	 * @since 1.8.0
	 */
	public $settings = null;

	/**
	 * Used to hackishly insert title for generated popups not stored in DB. (Shortcodes).
	 *
	 * @var string
	 * @since 1.8.0
	 */
	public $title = null;

	/**
	 * Used to hackishly change the model to prevent queries. (Shortcodes).
	 *
	 * @var string
	 * @since 1.8.0
	 */
	public $mock = false;

	/**
	 * @param      $key
	 * @param bool $single
	 *
	 * @return mixed|false
	 */
	public function get_meta( $key, $single = true ) {
		if ( $this->mock ) {
			return false;
		}

		return parent::get_meta( $key, $single );
	}

	/**
	 * Returns the title of a popup.
	 *
	 * @uses filter `pum_popup_get_title`
	 *
	 * @return string
	 */
	public function get_title() {
		$title = isset( $this->title ) ? $this->title : $this->get_meta( 'popup_title' );

		return (string) apply_filters( 'pum_popup_get_title', (string) $title, $this->ID );
	}

	/**
	 * Returns the content of a popup.
	 *
	 * @uses filter `pum_popup_content`
	 *
	 * @return string
	 */
	public function get_content() {
		/** @deprecated 1.8.0 */
		$this->content = $this->post_content;

		return apply_filters( 'pum_popup_content', $this->post_content, $this->ID );
	}

	/**
	 * Returns array of all popup settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		// This hack is here to allow creating popups on the fly without saved meta.
		$settings = isset( $this->settings ) ? $this->settings : $this->get_meta( 'popup_settings' );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		// Review: the above should be removed and replaced with a hooked filter here to supply defaults when $settings === false.
		return apply_filters( 'pum_popup_settings', $settings, $this->ID );
	}

	/**
	 * Returns a specific popup setting with optional default value when not found.
	 *
	 * @param      $key
	 * @param bool $default
	 *
	 * @return bool|mixed
	 */
	public function get_setting( $key, $default = false ) {
		$settings = $this->get_settings();

		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return bool|int
	 */
	public function update_setting( $key, $value ) {
		// TODO Once fields have been merged into the model itself, add automatic validation here.
		$new_settings = array( $key => $value );

		return $this->update_settings( $new_settings, true );
	}

	/**
	 * @param array $new_settings
	 * @param bool  $merge
	 *
	 * @return bool|int
	 */
	public function update_settings( $new_settings = array(), $merge = true ) {
		$settings = $this->get_settings();

		// TODO Once fields have been merged into the model itself, add automatic validation here.
		if ( $merge ) {
			foreach ( $new_settings as $key => $value ) {
				$settings[ $key ] = $value;
			}
		} else {
			$settings = $new_settings;
		}

		if ( empty( $settings['theme_id'] ) ) {
			$settings['theme_id'] = pum_get_default_theme_id();
		}

		if ( empty( $settings['theme_slug'] ) ) {
			$settings['theme_slug'] = get_post_field( 'post_name', $settings['theme_id'] );
		}

		return $this->update_meta( 'popup_settings', $settings );
	}

	/**
	 * Returns cleansed public settings for a popup.
	 *
	 * @return array
	 */
	public function get_public_settings() {
		$settings = wp_parse_args( $this->get_settings(), PUM_Admin_Popups::defaults() );

		foreach ( $settings as $key => $value ) {
			$field = PUM_Admin_Popups::get_field( $key );

			if ( $field['private'] ) {
				unset( $settings[ $key ] );
			} elseif ( $field['type'] == 'checkbox' ) {
				$settings[ $key ] = (bool) $value;
			}
		}

		$settings['id']   = $this->ID;
		$settings['slug'] = $this->post_name;

		$filters = array( 'js_only' => true );

		if ( $this->has_conditions( $filters ) ) {
			$settings['conditions'] = $this->get_conditions( $filters );
		}

		return apply_filters( 'pum_popup_get_public_settings', $settings, $this );
	}

	/**
	 * @return array
	 */
	public function get_cookies() {
		return apply_filters( 'pum_popup_get_cookies', $this->get_setting( 'cookies', array() ), $this->ID );
	}

	/**
	 * @param $event
	 *
	 * @return bool
	 */
	public function has_cookie( $event ) {
		foreach ( (array) $this->get_cookies() as $cookie ) {
			if ( $cookie['event'] == $event ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function get_triggers() {
		$triggers = $this->get_setting( 'triggers', array() );

		// Automatically add click trigger when on the front end.
		if ( ! is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			$has_click_trigger = false;

			foreach ( $triggers as $trigger ) {
				if ( $trigger['type'] == 'click_open' ) {
					$has_click_trigger = true;
				}
			}

			if ( ! $has_click_trigger && apply_filters( 'pum_add_default_click_trigger', true, $this->ID ) ) {
				$triggers[] = array(
					'type'     => 'click_open',
					'settings' => array(
						'extra_selectors' => '',
						'cookie_name'     => null,
					),
				);
			}
		}

		return apply_filters( 'pum_popup_get_triggers', $triggers, $this->ID );
	}

	/**
	 * @param $type
	 *
	 * @return bool
	 */
	public function has_trigger( $type ) {
		$triggers = $this->get_triggers();

		foreach ( $triggers as $trigger ) {
			if ( $trigger['type'] == $type ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns this popups theme id or the default id.
	 *
	 * @uses filter `pum_popup_get_theme_id`
	 *
	 * @return int $theme_id
	 */
	public function get_theme_id() {
		// TODO replace usage of popmake_get_default_popup_theme.
		$theme_id = $this->get_setting( 'theme_id', pum_get_default_theme_id() );

		return (int) apply_filters( 'pum_popup_get_theme_id', $theme_id, $this->ID );
	}

	/**
	 * Retrieve settings in the form of deprecated grouped arrays.
	 *
	 * @param      $group
	 * @param null $key
	 *
	 * @return mixed
	 */
	protected function _dep_get_settings_group( $group, $key = null ) {
		if ( $this->mock ) {
			return array();
		}

		if ( ! $this->$group ) {
			/**
			 * Remap old meta settings to new settings location for v1.7. This acts as a passive migration when needed.
			 */
			$remapped_keys = $this->remapped_meta_settings_keys( $group );

			// This will only return data from extensions as core data has been migrated already.
			$group_values = $this->get_meta( "popup_$group" );

			if ( ! $group_values || ! is_array( $group_values ) ) {
				$group_values = array();
			}

			// Data manipulation begins here. We don't want any of this saved, only returned for backward compatibility.
			foreach ( $remapped_keys as $old_key => $new_key ) {
				$group_values[ $old_key ] = $this->get_setting( $new_key );
			}

			$deprecated_values = popmake_get_popup_meta_group( $group, $this->ID );

			if ( ! empty( $deprecated_values ) ) {
				foreach ( $deprecated_values as $old_key => $value ) {

					if ( ! isset( $group_values[ $old_key ] ) ) {
						$group_values[ $old_key ] = $value;
					}

				}
			}


			$this->$group = $group_values;
		}

		$values = apply_filters( "pum_popup_get_$group", $this->$group, $this->ID );

		if ( ! $key ) {
			return $values;
		}

		$value = isset ( $values[ $key ] ) ? $values[ $key ] : null;

		if ( ! isset( $value ) ) {
			$value = $this->get_meta( "popup_{$group}_{$key}" );
		}

		return apply_filters( "pum_popup_get_{$group}_" . $key, $value, $this->ID );
	}

	/**
	 * @param $group
	 *
	 * @return array|mixed
	 */
	public function remapped_meta_settings_keys( $group ) {
		$remapped_meta_settings_keys = array(
			'display' => array(
				'stackable'                 => 'stackable',
				'overlay_disabled'          => 'overlay_disabled',
				'scrollable_content'        => 'scrollable_content',
				'disable_reposition'        => 'disable_reposition',
				'size'                      => 'size',
				'responsive_min_width'      => 'responsive_min_width',
				'responsive_min_width_unit' => 'responsive_min_width_unit',
				'responsive_max_width'      => 'responsive_max_width',
				'responsive_max_width_unit' => 'responsive_max_width_unit',
				'custom_width'              => 'custom_width',
				'custom_width_unit'         => 'custom_width_unit',
				'custom_height'             => 'custom_height',
				'custom_height_unit'        => 'custom_height_unit',
				'custom_height_auto'        => 'custom_height_auto',
				'location'                  => 'location',
				'position_from_trigger'     => 'position_from_trigger',
				'position_top'              => 'position_top',
				'position_left'             => 'position_left',
				'position_bottom'           => 'position_bottom',
				'position_right'            => 'position_right',
				'position_fixed'            => 'position_fixed',
				'animation_type'            => 'animation_type',
				'animation_speed'           => 'animation_speed',
				'animation_origin'          => 'animation_origin',
				'overlay_zindex'            => 'overlay_zindex',
				'zindex'                    => 'zindex',
			),
			'close'   => array(
				'text'          => 'close_text',
				'button_delay'  => 'close_button_delay',
				'overlay_click' => 'close_on_overlay_click',
				'esc_press'     => 'close_on_esc_press',
				'f4_press'      => 'close_on_f4_press',
			),
		);

		return isset( $remapped_meta_settings_keys[ $group ] ) ? $remapped_meta_settings_keys[ $group ] : array();


	}

	/**
	 * Returns all or single display settings.
	 *
	 * @deprecated 1.7.0 Use get_setting instead.
	 *
	 * @param string|null $key
	 *
	 * @return mixed
	 */
	public function get_display( $key = null ) {
		$display = $this->_dep_get_settings_group( 'display', $key );

		foreach (
			array(
				'responsive_min_width',
				'responsive_max_width',
				'custom_width',
				'custom_height',
			) as $key => $value
		) {
			$temp = isset( $display[ $key ] ) ? $display[ $key ] : false;

			if ( $temp && is_string( $temp ) ) {
				$display[ $key ]           = preg_replace( '/\D/', '', $temp );
				$display[ $key . '_unit' ] = str_replace( $display[ $key ], '', $temp );
			}
		}

		return $display;
	}

	/**
	 * Returns all or single close settings.
	 *
	 * @deprecated 1.7.0 Use get_setting instead.
	 *
	 * @param string|null $key
	 *
	 * @return mixed
	 */
	public function get_close( $key = null ) {
		return $this->_dep_get_settings_group( 'close', $key );
	}

	/**
	 * Returns the slug for a theme. Used for CSS classes.
	 *
	 * @return string
	 */
	private function get_theme_slug() {
		$theme_slug = $this->get_setting( 'theme_slug' );

		if ( false === $theme_slug ) {
			$theme_slug = get_post_field( 'post_name', $this->get_theme_id() );
			$this->update_setting( 'theme_slug', $theme_slug );
		}

		return $theme_slug;
	}

	/**
	 * Returns array of classes for this popup.
	 *
	 * @param string $element The key or html element identifier.
	 *
	 * @return array $classes
	 */
	public function get_classes( $element = 'overlay' ) {
		$classes = array(
			'overlay'   => array(
				'pum',
				'pum-overlay',
				'pum-theme-' . $this->get_theme_id(),
				'pum-theme-' . $this->get_theme_slug(),
				'popmake-overlay', // Backward Compatibility
			),
			'container' => array(
				'pum-container',
				'popmake', // Backward Compatibility
				'theme-' . $this->get_theme_id(), // Backward Compatibility
			),
			'title'     => array(
				'pum-title',
				'popmake-title', // Backward Compatibility
			),
			'content'   => array(
				'pum-content',
				'popmake-content', // Backward Compatibility
			),
			'close'     => array(
				'pum-close',
				'popmake-close' // Backward Compatibility
			),
		);

		$size = $this->get_setting( 'size', 'medium' );

		if ( in_array( $size, array( 'nano', 'micro', 'tiny', 'small', 'medium', 'normal', 'large', 'xlarge' ) ) ) {
			$classes['container'] = array_merge( $classes['container'], array(
				'pum-responsive',
				'pum-responsive-' . $size,
				'responsive', // Backward Compatibility
				'size-' . $size, // Backward Compatibility
			) );
		} elseif ( $size == 'custom' ) {
			$classes['container'][] = 'size-custom'; // Backward Compatibility
		}

		if ( ! $this->get_setting( 'custom_height_auto' ) && $this->get_setting( 'scrollable_content' ) ) {
			$classes['container'] = array_merge( $classes['container'], array(
				'pum-scrollable',
				'scrollable', // Backward Compatibility
			) );
		}

		if ( $this->get_setting( 'position_fixed' ) ) {
			$classes['container'][] = 'pum-position-fixed';
		}

		if ( $this->get_setting( 'overlay_disabled' ) ) {
			$classes['overlay'][] = 'pum-overlay-disabled';
		}

		if ( $this->get_setting( 'disable_accessibility' ) ) {
			$classes['overlay'][] = 'pum-accessibility-disabled';
		}

		if ( $this->get_setting( 'close_on_overlay_click' ) ) {
			$classes['overlay'][] = 'pum-click-to-close';
		}

		// Add a class for each trigger type.
		foreach ( $this->get_triggers() as $trigger ) {
			if ( ! in_array( $trigger['type'], $classes['overlay'] ) ) {
				$classes['overlay'][] = $trigger['type'];
			}
		}

		if ( is_singular( 'popup' ) ) {
			$classes['overlay'][] = 'pum-preview';
		}


		$classes = apply_filters( 'pum_popup_classes', $classes, $this->ID );

		if ( ! isset( $classes[ $element ] ) ) {
			$classes[ $element ] = array();
		}

		return apply_filters( "pum_popup_{$element}_classes", $classes[ $element ], $this->ID );
	}

	/**
	 * Returns array for data attribute of this popup.
	 *
	 * @deprecated 1.8.0
	 *
	 * @return array|bool
	 */
	public function get_data_attr() {
		if ( $this->mock ) {
			return false;
		}

		$data_attr = array(
			'id'              => $this->ID,
			'slug'            => $this->post_name,
			'theme_id'        => $this->get_theme_id(),
			'cookies'         => $this->get_cookies(),
			'triggers'        => $this->get_triggers(),
			'mobile_disabled' => $this->mobile_disabled() ? true : null,
			'tablet_disabled' => $this->tablet_disabled() ? true : null,
			'meta'            => array(
				'display'    => $this->get_display(),
				'close'      => $this->get_close(),
				// Added here for backward compatibility in extensions.
				'click_open' => popmake_get_popup_meta( 'click_open', $this->ID ),
			),
		);

		$filters = array( 'js_only' => true );

		if ( $this->has_conditions( $filters ) ) {
			$data_attr['conditions'] = $this->get_conditions( $filters );
		}

		return apply_filters( 'pum_popup_data_attr', $data_attr, $this->ID );
	}

	/**
	 * Returns the close button text.
	 *
	 * @return string
	 */
	public function close_text() {
		$text       = $this->get_setting( 'close_text', '&#215;' );
		$theme_text = pum_get_theme_close_text( $this->get_theme_id() );

		if ( empty( $text ) && ! empty( $theme_text ) ) {
			$text = $theme_text;
		}

		return apply_filters( 'pum_popup_close_text', $text, $this->ID );
	}

	/**
	 * Returns true if the close button should be rendered.
	 *
	 * @uses apply_filters `pum_popup_show_close_button`
	 *
	 * @return bool
	 */
	public function show_close_button() {
		return (bool) apply_filters( 'pum_popup_show_close_button', true, $this->ID );
	}

	/**
	 * Get the popups conditions.
	 *
	 * @param array $filters
	 *
	 * @return array
	 */
	public function get_conditions( $filters = array() ) {

		$filters = wp_parse_args( $filters, array(
			'php_only' => null,
			'js_only'  => null,
		) );

		$cache_key = hash( 'md5', json_encode( $filters ) );

		// Check if these exclusion filters have already been applied and prevent extra processing.
		$conditions = isset( $this->conditions_filtered[ $cache_key ] ) ? $this->conditions_filtered[ $cache_key ] : false;

		if ( ! $conditions ) {
			$conditions = $this->get_setting( 'conditions', array() );
			// Sanity Check on the values not operand value.
			foreach ( $conditions as $group_key => $group ) {

				foreach ( $group as $key => $condition ) {

					if ( $this->exclude_condition( $condition, $filters ) ) {
						unset( $conditions[ $group_key ][ $key ] );
						if ( empty( $conditions[ $group_key ] ) ) {
							unset( $conditions[ $group_key ] );
							break;
						}
						continue;
					}

					$conditions[ $group_key ][ $key ] = $this->parse_condition( $condition );
				}

				if ( ! empty( $conditions[ $group_key ] ) ) {
					// Renumber each subarray.
					$conditions[ $group_key ] = array_values( $conditions[ $group_key ] );
				}
			}

			// Renumber top arrays.
			$conditions = array_values( $conditions );

			$this->conditions_filtered[ $cache_key ] = $conditions;
		}

		return apply_filters( 'pum_popup_get_conditions', $conditions, $this->ID, $filters );
	}

	/**
	 * Ensures condition data integrity.
	 *
	 * @param $condition
	 *
	 * @return array
	 */
	public function parse_condition( $condition ) {
		$condition = wp_parse_args( $condition, array(
			'target'      => '',
			'not_operand' => false,
			'settings'    => array(),
		) );

		$condition['not_operand'] = (bool) $condition['not_operand'];

		/** Backward compatibility layer */
		foreach ( $condition['settings'] as $key => $value ) {
			$condition[ $key ] = $value;
		}

		// The not operand value is missing, set it to false.
		return $condition;
	}

	/**
	 * @param       $condition
	 * @param array $filters
	 *
	 * @return bool
	 */
	public function exclude_condition( $condition, $filters = array() ) {

		$exclude = false;

		// The condition target doesn't exist. Lets ignore this condition.
		if ( empty( $condition['target'] ) ) {
			return true;
		}

		$condition_args = PUM_Conditions::instance()->get_condition( $condition['target'] );

		// The condition target doesn't exist. Lets ignore this condition.
		if ( ! $condition_args ) {
			return true;
		}

		if ( $filters['js_only'] && $condition_args['advanced'] != true ) {
			return true;
		} elseif ( $filters['php_only'] && $condition_args['advanced'] != false ) {
			return true;
		}

		return $exclude;
	}

	/**
	 * Checks if this popup has any conditions.
	 *
	 * @param array $filters
	 *
	 * @return bool
	 */
	public function has_conditions( $filters = array() ) {
		return (bool) count( $this->get_conditions( $filters ) );
	}

	/**
	 * Checks if the popup has a specific condition.
	 *
	 * Generally used for conditional asset loading.
	 *
	 * @param array|string $conditions
	 *
	 * @return bool
	 */
	public function has_condition( $conditions ) {

		if ( ! $this->has_conditions() ) {
			return false;
		}

		$found = false;

		if ( ! is_array( $conditions ) ) {
			$conditions = array( $conditions );
		}

		foreach ( $this->get_conditions() as $group => $conds ) {

			foreach ( $conds as $condition ) {

				if ( in_array( $condition['target'], $conditions ) ) {
					$found = true;
				}

			}

		}

		return (bool) $found;
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

		$filters = array( 'php_only' => true );

		if ( $this->has_conditions( $filters ) ) {

			// All Groups Must Return True. Break if any is false and set $loadable to false.
			foreach ( $this->get_conditions( $filters ) as $group => $conditions ) {

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

	/**
	 * Check an individual condition with settings.
	 *
	 * @param array $condition
	 *
	 * @return bool
	 */
	public function check_condition( $condition = array() ) {
		$condition_args = PUM_Conditions::instance()->get_condition( $condition['target'] );

		if ( ! $condition_args ) {
			return false;
		}

		$condition['settings'] = isset( $condition['settings'] ) && is_array( $condition['settings'] ) ? $condition['settings'] : array();

		return (bool) call_user_func( $condition_args['callback'], $condition, $this );
	}

	/**
	 * Check if mobile was disabled
	 *
	 * @return bool
	 */
	public function mobile_disabled() {
		return (bool) apply_filters( 'pum_popup_mobile_disabled', $this->get_setting( 'disable_on_mobile' ), $this->ID );
	}

	/**
	 * Check if tablet was disabled
	 *
	 * @return bool
	 */
	public function tablet_disabled() {
		return (bool) apply_filters( 'pum_popup_tablet_disabled', (bool) $this->get_setting( 'disable_on_tablet' ), $this->ID );
	}

	/**
	 * Get a popups event count.
	 *
	 * @param string $event
	 * @param string $which
	 *
	 * @return int
	 */
	public function get_event_count( $event = 'open', $which = 'current' ) {

		$keys = PUM_Analytics::event_keys( $event );

		switch ( $which ) {
			case 'current' :
				$current = $this->get_meta( 'popup_' . $keys[0] . '_count' );

				// Save future queries by inserting a valid count.
				if ( $current === false || ! is_numeric( $current ) ) {
					$current = 0;
					$this->update_meta( 'popup_' . $keys[0] . '_count', $current );
				}

				return absint( $current );
			case 'total'   :
				$total = $this->get_meta( 'popup_' . $keys[0] . '_count_total' );

				// Save future queries by inserting a valid count.
				if ( $total === false || ! is_numeric( $total ) ) {
					$total = 0;
					$this->update_meta( 'popup_' . $keys[0] . '_count_total', $total );
				}

				return absint( $total );
		}

		return 0;
	}

	/**
	 * Increase popup event counts.
	 *
	 * @param string $event
	 */
	public function increase_event_count( $event = 'open' ) {

		/**
		 * This section simply ensures that all keys exist before the below query runs. This should only ever cause extra queries once per popup, usually in the admin.
		 */
		//$this->set_event_defaults( $event );

		$keys = PUM_Analytics::event_keys( $event );

		// Set the current count
		$current = $this->get_event_count( $event );
		if ( ! $current ) {
			$current = 0;
		};
		$current = $current + 1;

		// Set the total count since creation.
		$total = $this->get_event_count( $event, 'total' );
		if ( ! $total ) {
			$total = 0;
		}
		$total = $total + 1;

		$this->update_meta( 'popup_' . $keys[0] . '_count', absint( $current ) );
		$this->update_meta( 'popup_' . $keys[0] . '_count_total', absint( $total ) );
		$this->update_meta( 'popup_last_' . $keys[1], current_time( 'timestamp', 0 ) );

		$site_total = get_option( 'pum_total_' . $keys[0] . '_count', 0 );
		$site_total ++;
		update_option( 'pum_total_' . $keys[0] . '_count', $site_total );

		// If is multisite add this blogs total to the site totals.
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$network_total = get_site_option( 'pum_site_total_' . $keys[0] . '_count', false );
			$network_total = ! $network_total ? $site_total : $network_total + 1;
			update_site_option( 'pum_site_total_' . $keys[0] . '_count', $network_total );
		}
	}

	/**
	 * @param $event
	 */
	public function set_event_defaults( $event ) {
		$this->get_event_count( $event );
		$this->get_event_count( $event, 'total' );

		$keys = PUM_Analytics::event_keys( $event );
		$last = $this->get_meta( 'popup_last_' . $keys[1] );

		if ( empty( $last ) || ! is_numeric( $last ) ) {
			$this->update_meta( 'popup_last_' . $keys[1], 0 );
		}
	}

	/**
	 * Log and reset popup open count to 0.
	 */
	public function reset_counts() {
		// Log the reset time and count.
		add_post_meta( $this->ID, 'popup_count_reset', array(
			'timestamp'   => current_time( 'timestamp', 0 ),
			'opens'       => absint( $this->get_event_count( 'open', 'current' ) ),
			'conversions' => absint( $this->get_event_count( 'conversion', 'current' ) ),
		) );

		foreach ( array( 'open', 'conversion' ) as $event ) {
			$keys = PUM_Analytics::event_keys( $event );
			$this->update_meta( 'popup_' . $keys[0] . '_count', 0 );
			$this->update_meta( 'popup_last_' . $keys[1], 0 );
		}
	}

	/**
	 * Returns the last reset information.
	 *
	 * @return mixed
	 */
	public function get_last_count_reset() {
		$resets = $this->get_meta( 'popup_count_reset', false );

		if ( empty ( $resets ) ) {
			// No results found.
			return false;
		}

		if ( ! empty( $resets['timestamp'] ) ) {
			// Looks like the result is already the last one, return it.
			return $resets;
		}

		if ( count( $resets ) == 1 ) {
			// Looks like we only got one result, return it.
			return $resets[0];
		}

		usort( $resets, array( $this, "compare_resets" ) );

		return $resets[0];
	}

	/**
	 * @param $a
	 * @param $b
	 *
	 * @return bool
	 */
	public function compare_resets( $a, $b ) {
		return ( float ) $a['timestamp'] < ( float ) $b['timestamp'];
	}

	/**
	 * @param $post WP_Post
	 */
	public function setup( $post ) {
		parent::setup( $post );

		if ( ! $this->is_valid() ) {
			return;
		}

		// REVIEW Does this need to be here or somewhere else like get_meta/get_setting?
		if ( ! isset( $this->data_version ) ) {
			$this->data_version = (int) $this->get_meta( 'data_version' );

			if ( ! $this->data_version ) {
				$theme            = $this->get_meta( 'popup_theme' );
				$display_settings = $this->get_meta( 'popup_display' );

				// If there are existing settings set the data version to 2 so they can be updated.
				// Otherwise set to the current version as this is a new popup.
				$is_v2              = ( ! empty( $display_settings ) && is_array( $display_settings ) ) || $theme > 0;
				$this->data_version = $is_v2 ? 2 : $this->model_version;

				$this->update_meta( 'data_version', $this->data_version );
			}
		}

		if ( $this->data_version < $this->model_version && pum_passive_popup_upgrades_enabled() ) {
			/**
			 * Process passive settings migration as each popup is loaded. The will only run each migration routine once for each popup.
			 */
			$this->passive_migration();
		}
	}

	/**
	 * Allows for passive migration routines based on the current data version.
	 */
	public function passive_migration() {
		$this->doing_passive_migration = true;

		for ( $i = $this->data_version; $this->data_version < $this->model_version; $i ++ ) {
			do_action_ref_array( 'pum_popup_passive_migration_' . $this->data_version, array( &$this ) );
			$this->data_version ++;

			/**
			 * Update the popups data version.
			 */
			$this->update_meta( 'data_version', $this->data_version );
		}

		do_action_ref_array( 'pum_popup_passive_migration', array( &$this, $this->data_version ) );

		$this->doing_passive_migration = false;
	}

	/**
	 * @deprecated 1.7.0 Still used in several extension migration routines, so needs to stay for now.
	 */
	public function save() {
		try {
			pum()->popups->update_item( $this->ID, $this->to_array() );
		} catch ( Exception $e ) {
			return;
		}
	}

	/**
	 * @deprecated 1.8.0 Only here to prevent possible errors.
	 *
	 * @param      $id
	 * @param bool $force
	 *
	 * @return \PUM_Model_Popup
	 */
	public static function instance( $id, $force = false ) {
		return pum_get_popup( $id );
	}
}

