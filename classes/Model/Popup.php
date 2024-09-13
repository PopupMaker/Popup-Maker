<?php
/**
 * Model for Popup
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Model_Popup
 *
 * @since 1.4
 */
class PUM_Model_Popup extends PUM_Abstract_Model_Post {

	/**
	 * Post Type.
	 *
	 * @var string
	 */
	protected $required_post_type = 'popup';

	/**
	 * Filtered array of conditions.
	 *
	 * @var array
	 */
	public $conditions_filtered = [];

	/**
	 * Old content caching, don't use.
	 *
	 * @var string
	 *
	 * @deprecated  1.8.0 Was used in PUM ALM extension, needs time to get those changes published.
	 * @toberemoved v1.9.0
	 */
	public $content;

	/**
	 * Currently being passively migrated.
	 *
	 * @var bool
	 */
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

	// TODO Remove these once no longer needed.

	/**
	 * Don't use!
	 *
	 * @var array
	 * @deprecated 1.7.0
	 */
	public $display;

	/**
	 * Don't use!
	 *
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
	 * Get popup meta.
	 *
	 * @param string $key Meta key.
	 * @param bool   $single Get single only or multiple values.
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
		/**
		 * Do not use!
		 *
		 * @deprecated 1.8.0
		 */
		$this->content = $this->post_content;

		return apply_filters( 'pum_popup_content', $this->post_content, $this->ID );
	}

	/**
	 * Returns array of all popup settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		if ( ! isset( $this->settings ) ) {
			// This hack is here to allow creating popups on the fly without saved meta.
			$settings = isset( $this->settings ) ? $this->settings : $this->get_meta( 'popup_settings' );

			if ( ! is_array( $settings ) ) {
				$settings = [];
			}

			// Review: the above should be removed and replaced with a hooked filter here to supply defaults when $settings === false.
			$this->settings = apply_filters( 'pum_popup_settings', $settings, $this->ID );
		}

		return $this->settings;
	}

	/**
	 * Returns a specific popup setting with optional default value when not found.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default_value Default value if not set.
	 *
	 * @return bool|mixed
	 */
	public function get_setting( $key, $default_value = false ) {
		$settings = $this->get_settings();

		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default_value;
	}

	/**
	 * Update popup setting.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $value New value.
	 *
	 * @return bool|int
	 */
	public function update_setting( $key, $value ) {
		// TODO Once fields have been merged into the model itself, add automatic validation here.
		$new_settings = [ $key => $value ];

		return $this->update_settings( $new_settings, true );
	}

	/**
	 * Update multiple settings at once.
	 *
	 * @param array $new_settings Array of new setting key=>value pairs.
	 * @param bool  $merge Wheher to merge values or replace them.
	 *
	 * @return bool|int
	 */
	public function update_settings( $new_settings = [], $merge = true ) {
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

			if ( false === $field ) {
				if ( isset( $value ) ) {
					// This is a value set programatically, not by a defined field. ex theme_slug.
					$settings[ $key ] = $value;
				}
				continue;
			}

			if ( $field['private'] ) {
				unset( $settings[ $key ] );
			} elseif ( 'checkbox' === $field['type'] ) {
				$settings[ $key ] = (bool) $value;
			}
		}

		$settings['id']   = $this->ID;
		$settings['slug'] = $this->post_name;

		// Pass conditions only if there are JS conditions.
		if ( $this->has_conditions( [ 'js_only' => true ] ) ) {
			$settings['conditions'] = $this->get_parsed_js_conditions();
		}

		return apply_filters( 'pum_popup_get_public_settings', $settings, $this );
	}

	/**
	 * Preprocess PHP conditions in order for more accurate JS handling.
	 *
	 * @return array Array of conditions, whith PHP conditions replaced with boolean values.
	 */
	public function get_parsed_js_conditions() {
		$parsed_conditions = $this->get_conditions();

		foreach ( $parsed_conditions as $group_index => $conditions ) {
			foreach ( $conditions as $index => $condition ) {

				// Check each non js condition, replace it with true/false depending on its result.
				if ( ! $this->is_js_condition( $condition ) ) {
					$return = false;

					if ( ! $condition['not_operand'] && $this->check_condition( $condition ) ) {
						$return = true;
					} elseif ( $condition['not_operand'] && ! $this->check_condition( $condition ) ) {
						$return = true;
					}

					$parsed_conditions[ $group_index ][ $index ] = $return;
				}
			}
		}

		return $parsed_conditions;
	}

	/**
	 * Check if a given condition is JS based.
	 *
	 * @param array $condition Condition to check.
	 *
	 * @return bool
	 */
	public function is_js_condition( $condition = [] ) {
		$condition_args = PUM_Conditions::instance()->get_condition( $condition['target'] );

		if ( ! $condition_args ) {
			return false;
		}

		// Bail early with true for conditions that will be processed in JavaScript later.
		return true === $condition_args['advanced'] || empty( $condition_args['callback'] );
	}

	/**
	 * Get popup cookies.
	 *
	 * @return array
	 */
	public function get_cookies() {
		return apply_filters( 'pum_popup_get_cookies', $this->get_setting( 'cookies', [] ), $this->ID );
	}

	/**
	 * Check if popup has cookie by event.
	 *
	 * @param string $event Event to check for cookie on.
	 *
	 * @return bool
	 */
	public function has_cookie( $event ) {
		foreach ( (array) $this->get_cookies() as $cookie ) {
			if ( $cookie['event'] === $event ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get popup triggers.
	 *
	 * @return array
	 */
	public function get_triggers() {
		$triggers = $this->get_setting( 'triggers', [] );

		// Automatically add click trigger when on the front end.
		if ( ! is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			$has_click_trigger = false;

			foreach ( $triggers as $trigger ) {
				if ( 'click_open' === $trigger['type'] ) {
					$has_click_trigger = true;
				}
			}

			if ( ! $has_click_trigger && apply_filters( 'pum_add_default_click_trigger', true, $this->ID ) ) {
				$triggers[] = [
					'type'     => 'click_open',
					'settings' => [
						'extra_selectors' => '',
						'cookie_name'     => null,
					],
				];
			}
		}

		return apply_filters( 'pum_popup_get_triggers', $triggers, $this->ID );
	}

	/**
	 * Check if popup has trigger of type.
	 *
	 * @param string $type Popup trigger type to check for.
	 *
	 * @return bool
	 */
	public function has_trigger( $type ) {
		$triggers = $this->get_triggers();

		foreach ( $triggers as $trigger ) {
			if ( $trigger['type'] === $type ) {
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
	 * Array of deprecated settings groups.
	 *
	 * @var array
	 */
	public $dep_groups = [];

	/**
	 * Retrieve settings in the form of deprecated grouped arrays.
	 *
	 * @deprecated
	 *
	 * @param string $group Old group to fetch settings for.
	 * @param string $key Setting key to retrieve.
	 *
	 * @return mixed
	 */
	protected function dep_get_settings_group( $group, $key = null ) {
		if ( $this->mock ) {
			return [];
		}

		if ( ! isset( $this->dep_groups[ $group ] ) ) {
			/**
			 * Remap old meta settings to new settings location for v1.7. This acts as a passive migration when needed.
			 */
			$remapped_keys = $this->remapped_meta_settings_keys( $group );

			// This will only return data from extensions as core data has been migrated already.
			$group_values = $this->get_meta( "popup_$group" );

			if ( ! $group_values || ! is_array( $group_values ) ) {
				$group_values = [];
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

			$this->dep_groups[ $group ] = $group_values;
		}

		$values = apply_filters( "pum_popup_get_$group", $this->dep_groups[ $group ], $this->ID );

		if ( ! $key ) {
			return $values;
		}

		$value = isset( $values[ $key ] ) ? $values[ $key ] : null;

		if ( ! isset( $value ) ) {
			$value = $this->get_meta( "popup_{$group}_{$key}" );
		}

		return apply_filters( "pum_popup_get_{$group}_" . $key, $value, $this->ID );
	}

	/**
	 * Get list of remappings for old data.
	 *
	 * @param string $group Group to get values for.
	 *
	 * @return array|mixed
	 */
	public function remapped_meta_settings_keys( $group ) {
		$remapped_meta_settings_keys = [
			'display' => [
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
			],
			'close'   => [
				'text'          => 'close_text',
				'button_delay'  => 'close_button_delay',
				'overlay_click' => 'close_on_overlay_click',
				'esc_press'     => 'close_on_esc_press',
				'f4_press'      => 'close_on_f4_press',
			],
		];

		return isset( $remapped_meta_settings_keys[ $group ] ) ? $remapped_meta_settings_keys[ $group ] : [];
	}

	/**
	 * Returns all or single display settings.
	 *
	 * @deprecated 1.7.0 Use get_setting instead.
	 *
	 * @param string|null $key Settings -> Display key to get.
	 *
	 * @return mixed
	 */
	public function get_display( $key = null ) {
		$display = $this->dep_get_settings_group( 'display', $key );

		foreach (
			[
				'responsive_min_width',
				'responsive_max_width',
				'custom_width',
				'custom_height',
			] as $key => $value
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
	 * @param string|null $key Settings key to get.
	 *
	 * @return mixed
	 */
	public function get_close( $key = null ) {
		return $this->dep_get_settings_group( 'close', $key );
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
		$classes = [
			'overlay'   => [
				'pum',
				'pum-overlay',
				'pum-theme-' . $this->get_theme_id(),
				'pum-theme-' . $this->get_theme_slug(),
				'popmake-overlay', // Backward Compatibility.
			],
			'container' => [
				'pum-container',
				'popmake', // Backward Compatibility.
				'theme-' . $this->get_theme_id(), // Backward Compatibility.
			],
			'title'     => [
				'pum-title',
				'popmake-title', // Backward Compatibility.
			],
			'content'   => [
				'pum-content',
				'popmake-content', // Backward Compatibility.
			],
			'close'     => [
				'pum-close',
				'popmake-close', // Backward Compatibility.
			],
		];

		$size = $this->get_setting( 'size', 'medium' );

		if ( in_array( $size, [ 'nano', 'micro', 'tiny', 'small', 'medium', 'normal', 'large', 'xlarge' ], true ) ) {
			$classes['container'] = array_merge(
				$classes['container'],
				[
					'pum-responsive',
					'pum-responsive-' . $size,
					'responsive', // Backward Compatibility.
					'size-' . $size, // Backward Compatibility.
				]
			);
		} elseif ( 'custom' === $size ) {
			$classes['container'][] = 'size-custom'; // Backward Compatibility.
		}

		if ( ! $this->get_setting( 'custom_height_auto' ) && $this->get_setting( 'scrollable_content' ) ) {
			$classes['container'] = array_merge(
				$classes['container'],
				[
					'pum-scrollable',
					'scrollable', // Backward Compatibility.
				]
			);
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
			if ( ! in_array( $trigger['type'], $classes['overlay'], true ) ) {
				$classes['overlay'][] = $trigger['type'];
			}
		}

		if ( is_singular( 'popup' ) ) {
			$classes['overlay'][] = 'pum-preview';
		}

		$classes = apply_filters( 'pum_popup_classes', $classes, $this->ID );

		if ( ! isset( $classes[ $element ] ) ) {
			$classes[ $element ] = [];
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

		$data_attr = [
			'id'              => $this->ID,
			'slug'            => $this->post_name,
			'theme_id'        => $this->get_theme_id(),
			'cookies'         => $this->get_cookies(),
			'triggers'        => $this->get_triggers(),
			'mobile_disabled' => $this->mobile_disabled() ? true : null,
			'tablet_disabled' => $this->tablet_disabled() ? true : null,
			'meta'            => [
				'display'    => $this->get_display(),
				'close'      => $this->get_close(),
				// Added here for backward compatibility in extensions.
				'click_open' => popmake_get_popup_meta( 'click_open', $this->ID ),
			],
		];

		// Pass conditions only if there are JS conditions.
		if ( $this->has_conditions( [ 'js_only' => true ] ) ) {
			$data_attr['conditions'] = $this->get_parsed_js_conditions();
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
	 * Placeholder in a series of changes to officially remove condition filtering.
	 * This is a temporary method to allow for backwards compatibility.
	 *
	 * @since 1.16.13
	 *
	 * @param array $filters Array of condition filters.
	 *
	 * @return array
	 */
	public function get_conditions_with_filters( $filters = [
		'string'  => false,
		'string2' => true,
	] ) {

		$js_only  = isset( $filters['js_only'] ) && $filters['js_only'];
		$php_only = isset( $filters['php_only'] ) && $filters['php_only'];

		$conditions = $this->get_setting( 'conditions', [] );
		// Sanity Check on the values not operand value.
		foreach ( $conditions as $group_key => $group ) {
			foreach ( $group as $key => $condition ) {
				if (
					( $js_only && ! $this->is_js_condition( $condition ) ) ||
					( $php_only && $this->is_js_condition( $condition ) )
				) {
					unset( $conditions[ $group_key ][ $key ] );
					continue;
				}
			}

			if ( empty( $conditions[ $group_key ] ) ) {
				unset( $conditions[ $group_key ] );
				continue;
			}
		}

		return $conditions;
	}

	/**
	 * Get the popups conditions.
	 *
	 * @param boolean|string[] $filters Array of condition filters.
	 *
	 * @return array
	 */
	public function get_conditions( $filters = false ) {

		// Backwards compatibility for old filters.
		$conditions = false === $filters ? $this->get_setting( 'conditions', [] ) : $this->get_conditions_with_filters( $filters );

		foreach ( $conditions as $group_key => $group ) {
			foreach ( $group as $key => $condition ) {
				$conditions[ $group_key ][ $key ] = $this->parse_condition( $condition );
			}

			if ( ! empty( $conditions[ $group_key ] ) ) {
				// Renumber each subarray.
				$conditions[ $group_key ] = array_values( $conditions[ $group_key ] );
			}
		}

		// Renumber top arrays.
		$conditions = array_values( $conditions );

		return apply_filters( 'pum_popup_get_conditions', $conditions, $this->ID, $filters );
	}

	/**
	 * Return a flattened list of conditions (no groups).
	 *
	 * @since 1.16.13
	 *
	 * @param boolean|string[] $filters Array of condition filters.
	 *
	 * @return array
	 */
	public function get_conditions_list( $filters = false ) {
		$conditions = $this->get_conditions( $filters );

		$conditions_list = [];

		foreach ( $conditions as $group ) {
			foreach ( $group as $condition ) {
				$conditions_list[] = $condition;
			}
		}

		return $conditions_list;
	}

	/**
	 * Ensures condition data integrity.
	 *
	 * @param array $condition Condition.
	 *
	 * @return array
	 */
	public function parse_condition( $condition ) {
		$condition = wp_parse_args(
			$condition,
			[
				'target'      => '',
				'not_operand' => false,
				'settings'    => [],
			]
		);

		$condition['not_operand'] = (bool) $condition['not_operand'];

		/** Backward compatibility layer */
		foreach ( $condition['settings'] as $key => $value ) {
			$condition[ $key ] = $value;
		}

		// The not operand value is missing, set it to false.
		return $condition;
	}

	/**
	 * Checks if this popup has any conditions.
	 *
	 * @param false|string[] $filters Array of filters to use.
	 *
	 * @return bool
	 */
	public function has_conditions( $filters = false ) {
		return (bool) count( $this->get_conditions( $filters ) );
	}

	/**
	 * Checks if the popup has a specific condition.
	 *
	 * Generally used for conditional asset loading.
	 *
	 * @param string[]|string $conditions Array of condition to check for.
	 *
	 * @return bool
	 */
	public function has_condition( $conditions ) {

		if ( ! $this->has_conditions() ) {
			return false;
		}

		$found = false;

		if ( ! is_array( $conditions ) ) {
			$conditions = [ $conditions ];
		}

		foreach ( $this->get_conditions() as $group ) {
			foreach ( $group as $condition ) {
				if ( in_array( $condition['target'], $conditions, true ) ) {
					$found = true;
				}
			}
		}

		return (bool) $found;
	}

	/**
	 * Retrieves the 'enabled' meta key and returns true if popup is enabled
	 *
	 * @since 1.12
	 * @return bool True if enabled
	 */
	public function is_enabled() {
		$enabled = $this->get_meta( 'enabled' );

		// Post ID not valid.
		if ( false === $enabled ) {
			return false;
		}

		// If the key is missing...
		if ( '' === $enabled ) {
			// Set it to enabled.
			$enabled = 1;
			$this->update_meta( 'enabled', $enabled );
		} else {
			// Else, load it in.
			$enabled = intval( $enabled );
			if ( ! in_array( $enabled, [ 0, 1 ], true ) ) {
				$enabled = 1;
			}
		}
		if ( 1 === $enabled ) {
			return true;
		} else {
			return false;
		}
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
			// Published/private.
		}

		// If popup is not enabled, this popup is not loadable.
		if ( ! $this->is_enabled() ) {
			return false;
		}

		if ( $this->has_conditions() ) {

			// All Groups Must Return True. Break if any is false and set $loadable to false.
			foreach ( $this->get_conditions() as $group => $conditions ) {

				// Groups are false until a condition proves true.
				$group_check = false;

				// At least one group condition must be true. Break this loop if any condition is true.
				foreach ( $conditions as $condition ) {

					// If this is JS condition, popup must load to check it later. Group can't be known false til then.
					if ( $this->is_js_condition( $condition ) ) {
						$group_check = true;
						break;
					}

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
					break;
				}
			}
		}

		return apply_filters( 'pum_popup_is_loadable', $loadable, $this->ID );
	}

	/**
	 * Check an individual condition with settings.
	 *
	 * @param array $condition Condition to check.
	 *
	 * @return bool
	 */
	public function check_condition( $condition = [] ) {
		$condition_args = PUM_Conditions::instance()->get_condition( $condition['target'] );

		if ( ! $condition_args ) {
			return false;
		}

		// Bail early with true for conditions that will be processed in JavaScript later.
		if ( $this->is_js_condition( $condition ) ) {
			return true;
		}

		$condition['settings'] = isset( $condition['settings'] ) && is_array( $condition['settings'] ) ? $condition['settings'] : [];

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
	 * @param string $event Event nme.
	 * @param string $which Which stats to get.
	 *
	 * @return int
	 */
	public function get_event_count( $event = 'open', $which = 'current' ) {
		switch ( $which ) {
			case 'current':
				$current = $this->get_meta( "popup_{$event}_count" );

				// Save future queries by inserting a valid count.
				if ( false === $current || ! is_numeric( $current ) ) {
					$current = 0;
					$this->update_meta( "popup_{$event}_count", $current );
				}

				return absint( $current );
			case 'total':
				$total = $this->get_meta( "popup_{$event}_count_total" );

				// Save future queries by inserting a valid count.
				if ( false === $total || ! is_numeric( $total ) ) {
					$total = 0;
					$this->update_meta( "popup_{$event}_count_total", $total );
				}

				return absint( $total );
		}

		return 0;
	}

	/**
	 * Increase popup event counts.
	 *
	 * @param string $event Evet to increase count for.
	 */
	public function increase_event_count( $event = 'open' ) {
		/**
		 * This section simply ensures that all keys exist before the below query runs. This should only ever cause extra queries once per popup, usually in the admin.
		 */
		$keys = PUM_Analytics::event_keys( $event );

		// Set the current count.
		$current = $this->get_event_count( $event );
		if ( ! $current ) {
			$current = 0;
		}

		++$current;

		// Set the total count since creation.
		$total = $this->get_event_count( $event, 'total' );
		if ( ! $total ) {
			$total = 0;
		}

		++$total;

		$this->update_meta( 'popup_' . $keys[0] . '_count', absint( $current ) );
		$this->update_meta( 'popup_' . $keys[0] . '_count_total', absint( $total ) );
		$this->update_meta( 'popup_last_' . $keys[1], time() );

		$site_total = get_option( 'pum_total_' . $keys[0] . '_count', 0 );
		++$site_total;
		update_option( 'pum_total_' . $keys[0] . '_count', $site_total );

		// If is multisite add this blogs total to the site totals.
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$network_total = get_site_option( 'pum_site_total_' . $keys[0] . '_count', false );
			$network_total = ! $network_total ? $site_total : $network_total + 1;
			update_site_option( 'pum_site_total_' . $keys[0] . '_count', $network_total );
		}
	}

	/**
	 * Set event default values.
	 *
	 * @param string $event Event name.
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
		add_post_meta(
			$this->ID,
			'popup_count_reset',
			[
				'timestamp'   => time(),
				'opens'       => absint( $this->get_event_count( 'open', 'current' ) ),
				'conversions' => absint( $this->get_event_count( 'conversion', 'current' ) ),
			]
		);

		foreach ( [ 'open', 'conversion' ] as $event ) {
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

		if ( empty( $resets ) ) {
			// No results found.
			return false;
		}

		if ( ! empty( $resets['timestamp'] ) ) {
			// Looks like the result is already the last one, return it.
			return $resets;
		}

		if ( count( $resets ) === 1 ) {
			// Looks like we only got one result, return it.
			return $resets[0];
		}

		usort( $resets, [ $this, 'compare_resets' ] );

		return $resets[0];
	}

	/**
	 * Array comparison callback function comparing timestamps.
	 *
	 * @param array $a Array with `timestamp` key for comparison.
	 * @param array $b Array with `timestamp` key for comparison.
	 *
	 * @return bool
	 */
	public function compare_resets( $a, $b ) {
		$a = (float) $a['timestamp'];
		$b = (float) $b['timestamp'];

		// TODO Replace this with PHP 7.4 `<=>` operator once we drop support for PHP 5.6.
		// return (float) $a['timestamp'] <=> (float) $b['timestamp'];

		if ( $a < $b ) {
			return -1;
		} elseif ( $a > $b ) {
			return 1;
		} else {
			return 0;
		}
	}

	/**
	 * Setup this popup when instantiated.
	 *
	 * @param WP_Post $post WP_Post object.
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

		for ( $i = $this->data_version; $this->data_version < $this->model_version; $i++ ) {
			do_action_ref_array( 'pum_popup_passive_migration_' . $this->data_version, [ &$this ] );
			++$this->data_version;

			/**
			 * Update the popups data version.
			 */
			$this->update_meta( 'data_version', $this->data_version );
		}

		do_action_ref_array( 'pum_popup_passive_migration', [ &$this, $this->data_version ] );

		$this->doing_passive_migration = false;
	}

	/**
	 * Save unsaved data.
	 *
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
	 * Get instance of popup model.
	 *
	 * @deprecated 1.8.0 Only here to prevent possible errors.
	 *
	 * @param int  $id Popup ID.
	 * @param bool $force Force load.
	 *
	 * @return PUM_Model_Popup
	 */
	public static function instance( $id, $force = false ) {
		return pum_get_popup( $id );
	}
}
