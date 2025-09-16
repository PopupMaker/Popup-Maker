<?php
/**
 * Model for Call To Action
 *
 * @author    Code Atlantic
 * @package   PopupMaker
 * @copyright (c) 2024, Code Atlantic LLC.
 */

namespace PopupMaker\Models;

use WP_Post;
use PopupMaker\Base\Model\Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class CallToAction
 *
 * @since 1.21.0
 */
class CallToAction extends Post {

	/**
	 * Current model version.
	 *
	 * @var int
	 */
	const MODEL_VERSION = 1;

	/**
	 * Call To Action UUID.
	 *
	 * @var string
	 */
	protected $uuid;

	/**
	 * Call To Action description.
	 *
	 * @var string|null
	 */
	protected $description;

	/**
	 * Call To Action Settings.
	 *
	 * @var array<string,mixed>
	 */
	protected $settings;

	/**
	 * Build a call to action.
	 *
	 * @param \WP_Post|array<string,mixed> $cta Call To Action data.
	 */
	public function __construct( $cta ) {
		parent::__construct( $cta );

		$custom_properties = [
			'uuid'        => null,
			'description' => null,
			'settings'    => [],
		];

		foreach ( $custom_properties as $key => $value ) {
			$this->$key = $value;
		}

		/**
		 * Call To Action settings.
		 *
		 * @var array<string,mixed>|false $settings
		 */
		$settings = get_post_meta( $this->ID, 'cta_settings', true );

		if ( empty( $settings ) ) {
			$settings = \PopupMaker\get_default_call_to_action_settings();
		}

		$this->settings = $settings;

		$this->data_version = get_post_meta( $cta->ID, 'data_version', true );

		if ( ! $this->data_version ) {
			$this->data_version = self::MODEL_VERSION;
			update_post_meta( $cta->ID, 'data_version', self::MODEL_VERSION );
		}
	}

	/**
	 * Get the call to action UUID.
	 *
	 * @return string
	 */
	public function get_uuid() {
		if ( isset( $this->uuid ) ) {
			return $this->uuid;
		}

		// Get or generate UUID
		$uuid = get_post_meta( $this->ID, 'cta_uuid', true );

		if ( empty( $uuid ) ) {
			$uuid = \PopupMaker\generate_unique_cta_uuid( $this->ID );
			update_post_meta( $this->ID, 'cta_uuid', $uuid );
		}

		/**
		 * Filter the call to action UUID.
		 *
		 * @param string $uuid Call to action UUID.
		 * @param int $call_to_action_id Call to action ID.
		 *
		 * @return string
		 */
		$this->uuid = apply_filters( 'popup_maker/get_call_to_action_uuid', $uuid, $this->ID );

		return $this->uuid;
	}

	/**
	 * Get the action type class for this call to action.
	 *
	 * @return \PopupMaker\Base\CallToAction|false
	 */
	public function get_action_type_handler() {
		$cta_type         = $this->get_setting( 'type', 'link' );
		$cta_type_handler = \PopupMaker\plugin( 'cta_types' )->get( $cta_type );

		if ( ! $cta_type_handler instanceof \PopupMaker\Base\CallToAction ) {
			return false;
		}

		return $cta_type_handler;
	}

	/**
	 * Get the call to action settings array.
	 *
	 * @return array<string,mixed>
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Get public call to action settings array.
	 *
	 * @return array<string,mixed>
	 */
	public function get_public_settings() {
		return [];
	}

	/**
	 * Get a call to action setting.
	 *
	 * Settings are stored in JS based camelCase. But WP prefers snake_case.
	 *
	 * This method supports camelCase based dot.notation, as well as snake_case.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default_value Default value.
	 *
	 * @return mixed|false
	 */
	public function get_setting( $key, $default_value = false ) {
		if ( isset( $this->settings[ $key ] ) ) {
			$value = $this->settings[ $key ];
		} else {
			// Support camelCase, snake_case, and dot.notation.
			// Check for camelKeys & dot.notation.
			$value = \PopupMaker\fetch_key_from_array( $key, $this->settings, 'camelCase' );

			if ( null === $value ) {
				if ( null === $value ) {
					$value = $default_value;
				}
			}
		}

		/**
		 * Filter the option.
		 *
		 * @param mixed $value Option value.
		 * @param string $key Option key.
		 * @param mixed $default_value Default value.
		 * @param int $call to action_id Restriction ID.
		 *
		 * @return mixed
		 */
		return apply_filters( 'popup_maker/get_call_to_action_setting', $value, $key, $default_value, $this->ID );
	}

	/**
	 * Get the description for this call to action.
	 *
	 * @return string
	 */
	public function get_description() {
		if ( ! isset( $this->description ) ) {
			$this->description = get_the_excerpt( $this->ID );

			if ( empty( $this->description ) ) {
				$this->description = __( 'This content is restricted.', 'popup-maker' );
			}
		}

		return $this->description;
	}

	/**
	 * Generate a call to action URL.
	 *
	 * @param string              $base_url Base URL.
	 * @param array<string,mixed> $extra_args Extra arguments.
	 *
	 * @return string
	 */
	public function generate_url( $base_url = '', $extra_args = [] ) {
		$args = wp_parse_args( $extra_args, [
			'cta' => $this->get_uuid(),
		] );

		return \add_query_arg( $args, $base_url );
	}

	/**
	 * Convert this call to action to an array.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array() {
		$settings = $this->get_settings();

		return array_merge( [
			'id'          => $this->ID,
			'slug'        => $this->slug,
			'title'       => $this->title,
			'description' => $this->get_description(),
			'status'      => $this->status,
		], $settings );
	}

	/**
	 * Get a CTA's event count.
	 *
	 * @param string $event Event name.
	 * @param string $which Which stats to get.
	 *
	 * @return int
	 */
	public function get_event_count( $event = 'conversion', $which = 'current' ) {
		switch ( $which ) {
			case 'current':
				$current = $this->get_meta( "cta_{$event}_count" );

				// Save future queries by inserting a valid count.
				if ( false === $current || ! is_numeric( $current ) ) {
					$current = 0;
					$this->update_meta( "cta_{$event}_count", $current );
				}

				return absint( $current );
			case 'total':
				$total = $this->get_meta( "cta_{$event}_count_total" );

				// Save future queries by inserting a valid count.
				if ( false === $total || ! is_numeric( $total ) ) {
					$total = 0;
					$this->update_meta( "cta_{$event}_count_total", $total );
				}

				return absint( $total );
		}

		return 0;
	}

	/**
	 * Track a conversion for this CTA based on CTA event args.
	 *
	 * @param array<string,mixed> $extra_args Extra arguments.
	 *
	 * @return void
	 */
	public function track_conversion( $extra_args = [] ) {
		$extra_args = wp_parse_args( $extra_args, [
			'notrack' => false,
		] );

		if ( $extra_args['notrack'] ) {
			/**
			 * Fires when a CTA triggers core to track a conversion for a popup.
			 *
			 * @param \PopupMaker\Models\CallToAction $cta The CTA object.
			 * @param array<string,mixed> $extra_args {
			 *     @type bool   $notrack Whether to not track the conversion.
			 * }
			 */
			do_action( 'popup_maker/cta_conversion_notrack', $this, $extra_args );
			return;
		}

		$this->increase_event_count( 'conversion' );

		/**
		 * Fires when a CTA triggers core to track a conversion for a popup.
		 *
		 * @param \PopupMaker\Models\CallToAction $cta The CTA object.
		 * @param array<string,mixed> $extra_args {
		 *     @type bool   $notrack Whether to not track the conversion.
		 * }
		 */
		do_action( 'popup_maker/cta_conversion', $this, $extra_args );
	}

	/**
	 * Increase a CTA's event count.
	 *
	 * @param string $event Event name.
	 *
	 * @return bool
	 */
	public function increase_event_count( $event = 'conversion' ) {
		$current = $this->get_event_count( $event, 'current' );
		$total   = $this->get_event_count( $event, 'total' );

		$this->update_meta( "cta_{$event}_count", $current + 1 );
		$this->update_meta( "cta_{$event}_count_total", $total + 1 );

		return true;
	}
}
