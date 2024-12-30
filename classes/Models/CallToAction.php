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
 * @since X.X.X
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
		$settings = get_post_meta( $this->id, 'cta_settings', true );

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
		$uuid = get_post_meta( $this->id, 'cta_uuid', true );

		if ( empty( $uuid ) ) {
			$uuid = \PopupMaker\generate_unique_cta_uuid( $this->id );
			update_post_meta( $this->id, 'cta_uuid', $uuid );
		}

		/**
		 * Filter the call to action UUID.
		 *
		 * @param string $uuid Call to action UUID.
		 * @param int $call_to_action_id Call to action ID.
		 *
		 * @return string
		 */
		$this->uuid = apply_filters( 'popup_maker/get_call_to_action_uuid', $uuid, $this->id );

		return $this->uuid;
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
		// Support camelCase, snake_case, and dot.notation.
		// Check for camelKeys & dot.notation.
		$value = \PopupMaker\fetch_key_from_array( $key, $this->settings, 'camelCase' );

		if ( null === $value ) {
			$value = $default_value;
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
		return apply_filters( 'popup_maker/get_call_to_action_setting', $value, $key, $default_value, $this->id );
	}

	/**
	 * Get the description for this call to action.
	 *
	 * @return string
	 */
	public function get_description() {
		if ( ! isset( $this->description ) ) {
			$this->description = get_the_excerpt( $this->id );

			if ( empty( $this->description ) ) {
				$this->description = __( 'This content is restricted.', 'popup-maker' );
			}
		}

		return $this->description;
	}

	/**
	 * Generate a call to action URL.
	 *
	 * @param int $pid Popup (post) ID (optional). The associated pid for tracking purposes.
	 *
	 * @return string
	 */
	public function generate_url( $pid = null ) {
		return \add_query_arg( [
			'cta'  => true,
			'pid'  => $pid,
			'uuid' => $this->get_uuid(),
		], home_url() );
	}
	/**
	 * Convert this call to action to an array.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array() {
		$settings = $this->get_settings();

		return array_merge( [
			'id'          => $this->id,
			'slug'        => $this->slug,
			'title'       => $this->title,
			'description' => $this->get_description(),
			'status'      => $this->status,
		], $settings );
	}
}
