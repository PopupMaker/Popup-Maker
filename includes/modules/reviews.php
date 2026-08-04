<?php
/**
 * Modules for reviews
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Modules_Reviews
 *
 * This class adds a review request system for your plugin or theme to the WP dashboard.
 */
class PUM_Modules_Reviews {

	/**
	 * Core review destination.
	 *
	 * @var string
	 */
	const CORE_REVIEW_URL = 'https://wordpress.org/support/plugin/popup-maker/reviews/#new-post';

	/**
	 * Review API endpoint.
	 *
	 * @var string
	 */
	public static $api_url = 'https://api.wppopupmaker.com/wp-json/pmapi/v1/review_action';

	/**
	 * Cached trigger definitions for the current request.
	 *
	 * @var array|null
	 */
	protected static $triggers_cache;

	/**
	 * Cached selected trigger group for the current request.
	 *
	 * @var int|string|false
	 */
	protected static $selected_trigger_group = false;

	/**
	 * Whether the selected trigger has been resolved.
	 *
	 * @var bool
	 */
	protected static $selected_trigger_resolved = false;

	/**
	 * Cached selected trigger code for the current request.
	 *
	 * @var int|string|false
	 */
	protected static $selected_trigger_code = false;

	/**
	 * Whether the review configuration has been printed.
	 *
	 * @var bool
	 */
	protected static $printed_review_vars = false;

	/**
	 * Whether review interaction metrics may be sent remotely.
	 *
	 * Review dismissal state is always stored locally. Remote interaction
	 * metrics follow the same explicit opt-in as the core telemetry service.
	 *
	 * @return bool
	 */
	public static function should_track_review_actions() {
		return class_exists( 'PUM_Telemetry' ) && PUM_Telemetry::has_opted_in();
	}

	/**
	 * Get the product this review request should acknowledge.
	 *
	 * Core owns only its WordPress.org request. Products may provide their own
	 * context through the filter when they also provide a valid destination.
	 *
	 * @return array{product:string,name:string,licensed:bool}
	 */
	public static function get_product_context() {
		$defaults = [
			'product'  => 'core',
			'name'     => __( 'Popup Maker', 'popup-maker' ),
			'licensed' => false,
		];

		/**
		 * Filter the product acknowledged by review requests.
		 *
		 * @param array{product:string,name:string,licensed:bool} $context Product context.
		 */
		$context = apply_filters( 'popup_maker/reviews/product_context', $defaults );

		if ( ! is_array( $context ) ) {
			return $defaults;
		}

		$product = isset( $context['product'] ) && is_scalar( $context['product'] ) ? sanitize_key( (string) $context['product'] ) : $defaults['product'];
		if ( '' === $product ) {
			$product = $defaults['product'];
		}

		return [
			'product'  => $product,
			'name'     => isset( $context['name'] ) && is_scalar( $context['name'] ) && '' !== (string) $context['name'] ? sanitize_text_field( (string) $context['name'] ) : $defaults['name'],
			'licensed' => ! empty( $context['licensed'] ),
		];
	}

	/**
	 * Get the review and feedback destinations for the current product.
	 *
	 * @return array<string,array{label:string,url:string,reason:string,primary:bool}>
	 */
	public static function get_review_destinations() {
		$context      = self::get_product_context();
		$destinations = [
			'core' => [
				'label'   => __( 'Leave a 5-star review', 'popup-maker' ),
				'url'     => self::CORE_REVIEW_URL,
				'reason'  => 'am_now_core',
				'primary' => true,
			],
		];

		/**
		 * Filter review destinations and their interaction reason keys.
		 *
		 * @param array<string,array{label:string,url:string,reason:string,primary:bool}> $destinations Review destinations.
		 * @param array{product:string,name:string,licensed:bool}                       $context      Product context.
		 */
		$destinations = apply_filters( 'popup_maker/reviews/destinations', $destinations, $context );

		return self::normalize_review_destinations( $destinations );
	}

	/**
	 * Normalize filtered review destinations for rendering and authorization.
	 *
	 * @param mixed $destinations Filtered review destinations.
	 * @return array<string|int,array{label:string,url:string,reason:string,primary:bool}>
	 */
	protected static function normalize_review_destinations( $destinations ) {
		if ( ! is_array( $destinations ) ) {
			return [];
		}

		$normalized = [];

		foreach ( $destinations as $key => $destination ) {
			if ( ! is_array( $destination ) || ! isset( $destination['url'], $destination['label'], $destination['reason'] ) || ! is_scalar( $destination['url'] ) || ! is_scalar( $destination['label'] ) || ! is_scalar( $destination['reason'] ) ) {
				continue;
			}

			$url    = esc_url_raw( trim( (string) $destination['url'] ), [ 'http', 'https' ] );
			$label  = sanitize_text_field( (string) $destination['label'] );
			$reason = sanitize_key( (string) $destination['reason'] );
			$scheme = wp_parse_url( $url, PHP_URL_SCHEME );

			if ( '' === $url || ! in_array( $scheme, [ 'http', 'https' ], true ) || '' === $label || '' === $reason ) {
				continue;
			}

			$normalized[ $key ] = [
				'label'   => $label,
				'url'     => $url,
				'reason'  => $reason,
				'primary' => ! empty( $destination['primary'] ),
			];
		}

		return $normalized;
	}

	/**
	 * Remove the permanent dismissal written by the notification panel.
	 *
	 * Before review notices declared their own close action, the panel stored
	 * review_request in the generic permanent-dismissal bucket. Legacy review
	 * alerts never used that bucket, so the entry is safe to remove while
	 * preserving every other dismissed alert.
	 *
	 * @return void
	 */
	public static function clear_generic_panel_dismissal() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		if ( get_user_meta( $user_id, '_pum_reviews_generic_dismissal_migrated', true ) ) {
			return;
		}

		update_user_meta( $user_id, '_pum_reviews_generic_dismissal_migrated', current_time( 'mysql' ) );

		$dismissed_alerts = get_user_meta( $user_id, '_pum_dismissed_alerts', true );
		if ( ! is_array( $dismissed_alerts ) || ! array_key_exists( 'review_request', $dismissed_alerts ) ) {
			return;
		}

		unset( $dismissed_alerts['review_request'] );

		if ( empty( $dismissed_alerts ) ) {
			delete_user_meta( $user_id, '_pum_dismissed_alerts' );
		} else {
			update_user_meta( $user_id, '_pum_dismissed_alerts', $dismissed_alerts );
		}
	}

	/**
	 *
	 */
	public static function init() {
		add_filter( 'pum_alert_list', [ __CLASS__, 'review_alert' ] );
		add_action( 'wp_ajax_pum_review_action', [ __CLASS__, 'ajax_handler' ] );
		// Bridge the modern notification panel's dismiss flow into the
		// legacy review trigger state. Without this, dismissing the
		// review notice through the panel records nothing on this side
		// and the notice reappears on the next page load.
		add_action( 'pum_alert_dismissed', [ __CLASS__, 'on_panel_dismiss' ], 10, 2 );
	}

	/**
	 * Print review request vars for JS consumers.
	 */
	public static function print_review_request_vars() {
		if ( self::$printed_review_vars || self::hide_notices() ) {
			return;
		}

		self::$printed_review_vars = true;
		$track_review_actions      = self::should_track_review_actions();
		$product_context           = self::get_product_context();
		$vars                      = [
			'nonce'    => wp_create_nonce( 'pum_review_action' ),
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'api_url'  => $track_review_actions ? self::$api_url : '',
			'uuid'     => $track_review_actions ? wp_hash( home_url() . '-' . get_current_user_id() ) : '',
			'trigger'  => [
				'group' => (string) self::get_trigger_group(),
				'code'  => (string) self::get_trigger_code(),
				'pri'   => (int) self::get_current_trigger( 'pri' ),
			],
			'context'  => [
				'product'         => $product_context['product'],
				'attempt'         => self::attempt_count() + ( self::needs_impression() ? 1 : 0 ),
				'needsImpression' => self::needs_impression(),
			],
		];
		?>
		<script type="text/javascript">
			(function (config) {
				window.pum_review_nonce = config.nonce;
				window.pum_review_ajax_url = config.ajax_url;
				window.pum_review_trigger = config.trigger;
				window.pum_review_context = config.context;

				if (config.api_url) {
					window.pum_review_api_url = config.api_url;
					window.pum_review_uuid = config.uuid;
				}
			}(<?php echo wp_json_encode( $vars, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON is encoded for a script context. ?>));
		</script>
		<?php
	}

	/**
	 * Reset request-local caches.
	 *
	 * @internal Used by tests that change trigger inputs in one PHP process.
	 * @return void
	 */
	public static function reset_runtime_cache() {
		self::$triggers_cache            = null;
		self::$selected_trigger_group    = false;
		self::$selected_trigger_code     = false;
		self::$selected_trigger_resolved = false;
		self::$printed_review_vars       = false;
	}

	/**
	 * Map panel dismiss reasons onto legacy review state.
	 *
	 * @param string $code   Alert code that was dismissed.
	 * @param string $reason Action / reason key.
	 * @return void
	 */
	public static function on_panel_dismiss( $code, $reason = '' ) {
		if ( 'review_request' !== $code ) {
			return;
		}

		self::record_action( $reason ?: 'maybe_later' );
	}

	/**
	 * Record a review request action in the provider-owned state.
	 *
	 * @param string       $reason        Action taken by the user.
	 * @param string|false $trigger_group Trigger group key.
	 * @param string|false $trigger_code  Trigger code.
	 * @return bool
	 */
	public static function record_action( $reason = 'maybe_later', $trigger_group = false, $trigger_code = false ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		$reason = sanitize_key( is_string( $reason ) ? $reason : 'maybe_later' );

		if ( false === $trigger_group ) {
			$trigger_group = sanitize_key( (string) self::get_trigger_group() );
		} elseif ( is_scalar( $trigger_group ) && '' !== (string) $trigger_group ) {
			$trigger_group = sanitize_key( (string) $trigger_group );
		} else {
			return false;
		}

		if ( false === $trigger_code ) {
			$trigger_code = sanitize_key( (string) self::get_trigger_code() );
		} elseif ( is_scalar( $trigger_code ) && '' !== (string) $trigger_code ) {
			$trigger_code = sanitize_key( (string) $trigger_code );
		} else {
			return false;
		}

		$timestamp = current_time( 'mysql' );

		if ( ! $trigger_group || ! $trigger_code ) {
			return false;
		}

		$trigger = self::triggers( $trigger_group, $trigger_code );
		if ( ! is_array( $trigger ) || ! isset( $trigger['pri'] ) || ! is_numeric( $trigger['pri'] ) ) {
			return false;
		}

		$trigger_pri = (int) $trigger['pri'];

		self::record_presentation( $trigger_group, $trigger_code, $trigger_pri );

		if ( 0 === strpos( $reason, 'shown_' ) ) {
			$last_impression = get_user_meta( $user_id, '_pum_reviews_last_impression', true );
			if ( is_array( $last_impression ) && isset( $last_impression['trigger_group'], $last_impression['trigger_code'] ) && sanitize_key( (string) $last_impression['trigger_group'] ) === $trigger_group && sanitize_key( (string) $last_impression['trigger_code'] ) === $trigger_code ) {
				return true;
			}

			update_user_meta(
				$user_id,
				'_pum_reviews_last_impression',
				[
					'reason'        => $reason,
					'product'       => self::get_product_context()['product'],
					'attempt'       => self::attempt_count(),
					'trigger_group' => sanitize_key( (string) $trigger_group ),
					'trigger_code'  => sanitize_key( (string) $trigger_code ),
					'timestamp'     => $timestamp,
				]
			);
			update_user_meta( $user_id, '_pum_reviews_impression_count', (int) get_user_meta( $user_id, '_pum_reviews_impression_count', true ) + 1 );
			self::increment_action_count( $reason );

			return true;
		}

		$dismissed_triggers                   = self::dismissed_triggers();
		$dismissed_triggers[ $trigger_group ] = $trigger_pri;
		update_user_meta( $user_id, '_pum_reviews_dismissed_triggers', $dismissed_triggers );
		update_user_meta( $user_id, '_pum_reviews_last_dismissed', $timestamp );
		update_user_meta(
			$user_id,
			'_pum_reviews_last_action',
			[
				'reason'           => $reason,
				'product'          => self::get_product_context()['product'],
				'attempt'          => self::attempt_count(),
				'trigger_group'    => sanitize_key( (string) $trigger_group ),
				'trigger_code'     => sanitize_key( (string) $trigger_code ),
				'trigger_priority' => $trigger_pri,
				'timestamp'        => $timestamp,
			]
		);

		self::increment_action_count( $reason );

		if ( 0 === strpos( $reason, 'am_now' ) ) {
			update_user_meta( $user_id, '_pum_reviews_last_review_click', $timestamp );
		} elseif ( in_array( $reason, [ 'already_did', 'never' ], true ) ) {
			self::already_did( true );
		}

		return true;
	}

	/**
	 * Increment a local review-request action metric.
	 *
	 * @param string $reason Action reason key.
	 * @return void
	 */
	protected static function increment_action_count( $reason ) {
		$user_id       = get_current_user_id();
		$action_counts = get_user_meta( $user_id, '_pum_reviews_action_counts', true );
		$action_counts = is_array( $action_counts ) ? $action_counts : [];

		$action_counts[ $reason ] = isset( $action_counts[ $reason ] ) ? (int) $action_counts[ $reason ] + 1 : 1;
		update_user_meta( $user_id, '_pum_reviews_action_counts', $action_counts );
	}

	/**
	 * Record the first presentation of a distinct eligible trigger.
	 *
	 * Rendering both the inline alert and notification panel still counts as one
	 * attempt. Reloading the same trigger also does not inflate the count.
	 *
	 * @param string|false    $trigger_group Trigger group key.
	 * @param string|false    $trigger_code  Trigger code.
	 * @param int|string|bool $trigger_pri   Trigger priority.
	 * @return bool Whether this was a new attempt.
	 */
	public static function record_presentation( $trigger_group = false, $trigger_code = false, $trigger_pri = false ) {
		$user_id = get_current_user_id();
		$group   = is_scalar( $trigger_group ) && '' !== (string) $trigger_group ? sanitize_key( (string) $trigger_group ) : self::get_trigger_group();
		$code    = is_scalar( $trigger_code ) && '' !== (string) $trigger_code ? sanitize_key( (string) $trigger_code ) : self::get_trigger_code();
		$pri     = false !== $trigger_pri ? (int) $trigger_pri : (int) self::get_current_trigger( 'pri' );

		if ( ! $user_id || ! $group || ! $code ) {
			return false;
		}

		$last = get_user_meta( $user_id, '_pum_reviews_last_presented', true );
		if ( is_array( $last ) && isset( $last['trigger_group'], $last['trigger_code'] ) && $group === $last['trigger_group'] && $code === $last['trigger_code'] ) {
			return false;
		}

		$attempt = self::attempt_count() + 1;
		update_user_meta( $user_id, '_pum_reviews_attempt_count', $attempt );
		update_user_meta(
			$user_id,
			'_pum_reviews_last_presented',
			[
				'product'          => self::get_product_context()['product'],
				'attempt'          => $attempt,
				'trigger_group'    => sanitize_key( (string) $group ),
				'trigger_code'     => sanitize_key( (string) $code ),
				'trigger_priority' => $pri,
				'timestamp'        => current_time( 'mysql' ),
			]
		);

		return true;
	}

	/**
	 * Get the number of distinct review requests presented to the user.
	 *
	 * @return int
	 */
	public static function attempt_count() {
		return max( 0, (int) get_user_meta( get_current_user_id(), '_pum_reviews_attempt_count', true ) );
	}

	/**
	 * Whether the currently selected trigger still needs a visible impression.
	 *
	 * @return bool
	 */
	public static function needs_impression() {
		$group = sanitize_key( (string) self::get_trigger_group() );
		$code  = sanitize_key( (string) self::get_trigger_code() );
		$last  = get_user_meta( get_current_user_id(), '_pum_reviews_last_impression', true );

		if ( ! $group || ! $code ) {
			return false;
		}

		return ! is_array( $last ) || ! isset( $last['trigger_group'], $last['trigger_code'] ) || sanitize_key( (string) $last['trigger_group'] ) !== $group || sanitize_key( (string) $last['trigger_code'] ) !== $code;
	}

	/**
	 * Get the configured request cap.
	 *
	 * Zero means unlimited. Core intentionally defaults to unlimited because a
	 * request is only eligible after a new usage or age milestone and the shared
	 * cooldown has elapsed.
	 *
	 * @return int
	 */
	public static function max_attempts() {
		/**
		 * Filter the maximum number of distinct review request attempts.
		 *
		 * @param int $max_attempts Zero for unlimited.
		 */
		return max( 0, (int) apply_filters( 'popup_maker/reviews/max_attempts', 0 ) );
	}

	/**
	 * Get the install date for comparisons. Sets the date to now if none is found.
	 *
	 * @return false|string
	 */
	public static function installed_on() {
		$installed_on = get_option( 'pum_reviews_installed_on', false );

		if ( ! $installed_on ) {
			$installed_on = current_time( 'mysql' );
			update_option( 'pum_reviews_installed_on', $installed_on );
		}

		return $installed_on;
	}

	/**
	 *
	 */
	public static function ajax_handler() {
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['nonce'] ) ), 'pum_review_action' ) ) {
			wp_send_json_error();
		}

		if ( ! current_user_can( \PopupMaker\plugin()->get_permission( 'edit_popups' ) ) ) {
			wp_send_json_error();
		}

		$args = wp_parse_args(
			$_REQUEST,
			[
				'group'  => self::get_trigger_group(),
				'code'   => self::get_trigger_code(),
				'pri'    => self::get_current_trigger( 'pri' ),
				'reason' => 'maybe_later',
			]
		);

		$group  = isset( $args['group'] ) && is_scalar( $args['group'] ) ? sanitize_key( wp_unslash( (string) $args['group'] ) ) : '';
		$code   = isset( $args['code'] ) && is_scalar( $args['code'] ) ? sanitize_key( wp_unslash( (string) $args['code'] ) ) : '';
		$reason = isset( $args['reason'] ) && is_scalar( $args['reason'] ) ? sanitize_key( wp_unslash( (string) $args['reason'] ) ) : 'maybe_later';

		if ( '' === $group || '' === $code ) {
			wp_send_json_error();
		}

		try {
			$recorded = self::record_action(
				$reason,
				$group,
				$code
			);

			if ( ! $recorded ) {
				wp_send_json_error();
			}

			wp_send_json_success();
		} catch ( Exception $e ) {
			wp_send_json_error( $e );
		}
	}

	/**
	 * @return int|string|false
	 */
	public static function get_trigger_group() {
		self::resolve_selected_trigger();

		return self::$selected_trigger_group;
	}

	/**
	 * @return int|string|false
	 */
	public static function get_trigger_code() {
		self::resolve_selected_trigger();

		return self::$selected_trigger_code;
	}

	/**
	 * Resolve and cache the highest-priority eligible trigger.
	 *
	 * @return void
	 */
	protected static function resolve_selected_trigger() {
		if ( self::$selected_trigger_resolved ) {
			return;
		}

		self::$selected_trigger_resolved = true;
		$dismissed_triggers              = self::dismissed_triggers();

		foreach ( self::triggers() as $group_code => $group ) {
			foreach ( $group['triggers'] as $trigger_code => $trigger ) {
				if ( ! in_array( false, $trigger['conditions'], true ) && ( empty( $dismissed_triggers[ $group_code ] ) || $dismissed_triggers[ $group_code ] < $trigger['pri'] ) ) {
					self::$selected_trigger_group = $group_code;
					self::$selected_trigger_code  = $trigger_code;

					return;
				}
			}
		}
	}

	/**
	 * @param string|null $key
	 *
	 * @return bool|mixed|void
	 */
	public static function get_current_trigger( $key = null ) {
		$group = self::get_trigger_group();
		$code  = self::get_trigger_code();

		if ( ! $group || ! $code ) {
			return false;
		}

		$trigger = self::triggers( $group, $code );

		return empty( $key ) ? $trigger : ( isset( $trigger[ $key ] ) ? $trigger[ $key ] : false );
	}

	/**
	 * Returns an array of dismissed trigger groups.
	 *
	 * Array contains the group key and highest priority trigger that has been shown previously for each group.
	 *
	 * $return = array(
	 *   'group1' => 20
	 * );
	 *
	 * @return array|mixed
	 */
	public static function dismissed_triggers() {
		$user_id = get_current_user_id();

		$dismissed_triggers = get_user_meta( $user_id, '_pum_reviews_dismissed_triggers', true );

		if ( ! $dismissed_triggers ) {
			$dismissed_triggers = [];
		}

		return $dismissed_triggers;
	}

	/**
	 * Returns true if the user has opted to never see this again. Or sets the option.
	 *
	 * @param bool $set If set this will mark the user as having opted to never see this again.
	 *
	 * @return bool
	 */
	public static function already_did( $set = false ) {
		$user_id = get_current_user_id();

		if ( $set ) {
			update_user_meta( $user_id, '_pum_reviews_already_did', true );

			return true;
		}

		return (bool) get_user_meta( $user_id, '_pum_reviews_already_did', true );
	}

	/**
	 * Recover stale legacy permanent dismissals.
	 *
	 * Older releases made the first review-link click indistinguishable from an
	 * explicit "already reviewed" response. New actions carry their reason, so
	 * this migration only resets legacy records without structured action data.
	 *
	 * @return bool Whether a legacy permanent dismissal was reset.
	 */
	public static function reset_stale_legacy_dismissal() {
		$user_id = get_current_user_id();
		if ( ! $user_id || ! self::already_did() ) {
			return false;
		}

		$last_action = get_user_meta( $user_id, '_pum_reviews_last_action', true );
		if ( is_array( $last_action ) && ! empty( $last_action['reason'] ) ) {
			return false;
		}

		$last_dismissed = self::last_dismissed();
		if ( ! $last_dismissed ) {
			return false;
		}

		/**
		 * Filter how old an ambiguous legacy permanent dismissal must be before reset.
		 *
		 * Return an empty value to disable the migration.
		 *
		 * @param string $expiration Relative date expression.
		 */
		$expiration = apply_filters( 'popup_maker/reviews/legacy_dismissal_expiration', '1 year' );
		if ( ! is_string( $expiration ) || '' === trim( $expiration ) ) {
			return false;
		}

		$expires_at = strtotime( $last_dismissed . ' +' . ltrim( trim( $expiration ), '+' ) );
		if ( false === $expires_at || $expires_at > time() ) {
			return false;
		}

		delete_user_meta( $user_id, '_pum_reviews_already_did' );
		update_user_meta( $user_id, '_pum_reviews_legacy_dismissal_reset', current_time( 'mysql' ) );

		return true;
	}

	/**
	 * Build the geometric popup-view milestones reached by the site.
	 *
	 * The 50/100/500/1,000 pattern continues without a terminal threshold, so
	 * high-volume sites remain eligible for genuinely new milestones.
	 *
	 * @param int $total_open_count Total recorded popup views.
	 * @return array<int>
	 */
	public static function open_count_thresholds( $total_open_count ) {
		$total_open_count = max( 0, (int) $total_open_count );
		$thresholds       = [];
		$num              = 50;
		$multiplier       = 2;

		do {
			$thresholds[] = $num;

			if ( $num > (int) floor( PHP_INT_MAX / $multiplier ) ) {
				break;
			}

			$num       *= $multiplier;
			$multiplier = 2 === $multiplier ? 5 : 2;
		} while ( $num <= $total_open_count );

		return $thresholds;
	}

	/**
	 * Gets a list of triggers.
	 *
	 * @param string|null $group
	 * @param string|null $code
	 *
	 * @return bool|mixed
	 */
	public static function triggers( $group = null, $code = null ) {
		if ( ! isset( self::$triggers_cache ) ) {
			$product_context = self::get_product_context();
			/* translators: 1: Popup Maker product name, 2: amount of time. */
			$time_message = __( 'You\'ve been using %1$s for %2$s. If it has helped you engage visitors, grow your audience, or generate meaningful conversions, would you take a moment to leave us a 5-star review?', 'popup-maker' );
			$triggers     = [
				'time_installed' => [
					'triggers' => [
						'one_week'     => [
							'message'    => sprintf( $time_message, $product_context['name'], __( '1 week', 'popup-maker' ) ),
							'conditions' => [
								strtotime( self::installed_on() . ' +1 week' ) < time(),
							],
							'pri'        => 10,
						],
						'one_month'    => [
							'message'    => sprintf( $time_message, $product_context['name'], __( '1 month', 'popup-maker' ) ),
							'conditions' => [
								strtotime( self::installed_on() . ' +1 month' ) < time(),
							],
							'pri'        => 20,
						],
						'three_months' => [
							'message'    => sprintf( $time_message, $product_context['name'], __( '3 months', 'popup-maker' ) ),
							'conditions' => [
								strtotime( self::installed_on() . ' +3 months' ) < time(),
							],
							'pri'        => 30,
						],

					],
					'pri'      => 10,
				],
				'open_count'     => [
					'triggers' => [],
					'pri'      => 50,
				],
			];

			$pri = 10;
			/* translators: 1: Popup Maker product name, 2: number of popup views. */
			$open_message     = __( '%1$s has now delivered %2$s popup views on your site. If those popups have helped you achieve meaningful results, would you take a moment to leave us a 5-star review?', 'popup-maker' );
			$total_open_count = max( 0, (int) get_option( 'pum_total_open_count', 0 ) );

			foreach ( self::open_count_thresholds( $total_open_count ) as $num ) {
				$triggers['open_count']['triggers'][ $num . '_opens' ] = [
					'message'    => sprintf( $open_message, $product_context['name'], number_format_i18n( $num ) ),
					'conditions' => [
						$total_open_count >= $num,
					],
					'pri'        => $pri,
				];

				$pri += 10;
			}

			$triggers = apply_filters( 'pum_reviews_triggers', $triggers );

			// Sort Groups
			uasort( $triggers, [ __CLASS__, 'rsort_by_priority' ] );

			// Sort each groups triggers.
			foreach ( $triggers as $k => $v ) {
				uasort( $triggers[ $k ]['triggers'], [ __CLASS__, 'rsort_by_priority' ] );
			}

			self::$triggers_cache = $triggers;
		}

		$triggers = self::$triggers_cache;

		if ( isset( $group ) ) {
			if ( ! isset( $triggers[ $group ] ) ) {
				return false;
			}

			if ( ! isset( $code ) ) {
				return $triggers[ $group ];
			} else {
				return isset( $triggers[ $group ]['triggers'][ $code ] ) ? $triggers[ $group ]['triggers'][ $code ] : false;
			}
		}

		return $triggers;
	}

	/**
	 * Render the shared review request actions.
	 *
	 * @param array<string,array{label:string,url:string,reason:string,primary:bool}>|null $destinations Review destinations.
	 * @return string
	 */
	public static function render_review_actions( $destinations = null ) {
		$destinations = null === $destinations ? self::get_review_destinations() : self::normalize_review_destinations( $destinations );

		ob_start();
		?>
		<ul>
			<?php foreach ( $destinations as $destination ) : ?>
				<li>
					<a class="pum-dismiss" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $destination['url'] ); ?>" data-reason="<?php echo esc_attr( $destination['reason'] ); ?>">
						<?php if ( ! empty( $destination['primary'] ) ) : ?>
							<strong><?php echo esc_html( $destination['label'] ); ?></strong>
						<?php else : ?>
							<?php echo esc_html( $destination['label'] ); ?>
						<?php endif; ?>
					</a>
				</li>
			<?php endforeach; ?>
			<li>
				<a href="#" class="pum-dismiss" data-reason="maybe_later">
					<?php esc_html_e( 'Remind me later', 'popup-maker' ); ?>
				</a>
			</li>
			<li>
				<a href="#" class="pum-dismiss" data-reason="already_did">
					<?php esc_html_e( 'I’ve already left a review', 'popup-maker' ); ?>
				</a>
			</li>
		</ul>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Register alert when review request is available.
	 *
	 * @param array $alerts
	 *
	 * @return array
	 */
	public static function review_alert( $alerts = [] ) {
		if ( self::hide_notices() ) {
			return $alerts;
		}

		self::clear_generic_panel_dismissal();

		add_action( 'admin_footer', [ __CLASS__, 'print_review_request_vars' ] );

		$trigger         = self::get_current_trigger();
		$product_context = self::get_product_context();
		$destinations    = self::get_review_destinations();
		$allowed_actions = array_merge( [ 'maybe_later', 'already_did' ], wp_list_pluck( $destinations, 'reason' ) );

		$html = self::render_review_actions( $destinations );

		$alerts[] = [
			'code'            => 'review_request',
			/* translators: %s: Popup Maker product name. */
			'title'           => '⭐ ' . sprintf( __( 'Is %s helping you grow?', 'popup-maker' ), $product_context['name'] ),
			'message'         => '<strong>' . esc_html( $trigger['message'] ) . '</strong>',
			'html'            => $html,
			'type'            => 'success',
			'category'        => 'recommendation',
			'dismiss_action'  => 'maybe_later',
			'allowed_actions' => array_values( array_unique( $allowed_actions ) ),
			'display_inline'  => true,
		];

		return $alerts;
	}

	/**
	 * Checks if notices should be shown.
	 *
	 * @return bool
	 */
	public static function hide_notices() {
		self::reset_stale_legacy_dismissal();

		$trigger_code = self::get_trigger_code();
		$max_attempts = self::max_attempts();

		$conditions = [
			self::already_did(),
			self::last_dismissed() && strtotime( self::last_dismissed() . ' +2 weeks' ) > time(),
			empty( $trigger_code ),
			$max_attempts > 0 && self::attempt_count() >= $max_attempts,
		];

		return in_array( true, $conditions, true );
	}

	/**
	 * Gets the last dismissed date.
	 *
	 * @return false|string
	 */
	public static function last_dismissed() {
		$user_id = get_current_user_id();

		return get_user_meta( $user_id, '_pum_reviews_last_dismissed', true );
	}

	/**
	 * Sort array by priority value
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 */
	public static function sort_by_priority( $a, $b ) {
		if ( ! isset( $a['pri'] ) || ! isset( $b['pri'] ) || $a['pri'] === $b['pri'] ) {
			return 0;
		}

		return ( $a['pri'] < $b['pri'] ) ? - 1 : 1;
	}

	/**
	 * Sort array in reverse by priority value
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 */
	public static function rsort_by_priority( $a, $b ) {
		if ( ! isset( $a['pri'] ) || ! isset( $b['pri'] ) || $a['pri'] === $b['pri'] ) {
			return 0;
		}

		return ( $a['pri'] < $b['pri'] ) ? 1 : - 1;
	}
}

PUM_Modules_Reviews::init();
