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
		$context = [
			'product'  => 'core',
			'name'     => __( 'Popup Maker', 'popup-maker' ),
			'licensed' => false,
		];

		/**
		 * Filter the product acknowledged by review requests.
		 *
		 * @param array{product:string,name:string,licensed:bool} $context Product context.
		 */
		$context = apply_filters( 'pum_reviews_product_context', $context );

		if ( ! is_array( $context ) ) {
			return [
				'product'  => 'core',
				'name'     => __( 'Popup Maker', 'popup-maker' ),
				'licensed' => false,
			];
		}

		$product = isset( $context['product'] ) ? sanitize_key( (string) $context['product'] ) : 'core';
		if ( '' === $product ) {
			$product = 'core';
		}

		return [
			'product'  => $product,
			'name'     => ! empty( $context['name'] ) ? sanitize_text_field( (string) $context['name'] ) : __( 'Popup Maker', 'popup-maker' ),
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
		$destinations = apply_filters( 'pum_reviews_destinations', $destinations, $context );

		return is_array( $destinations ) ? $destinations : [];
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
		static $printed = false;

		if ( $printed || self::hide_notices() ) {
			return;
		}

		$printed              = true;
		$track_review_actions = self::should_track_review_actions();
		$product_context      = self::get_product_context();
		?>
		<script type="text/javascript">
			window.pum_review_nonce = '<?php echo esc_html( wp_create_nonce( 'pum_review_action' ) ); ?>';
			window.pum_review_ajax_url = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
			<?php if ( $track_review_actions ) : ?>
			window.pum_review_api_url = '<?php echo esc_attr( self::$api_url ); ?>';
			window.pum_review_uuid = '<?php echo esc_attr( wp_hash( home_url() . '-' . get_current_user_id() ) ); ?>';
			<?php endif; ?>
			window.pum_review_trigger = {
				group: '<?php echo esc_attr( self::get_trigger_group() ); ?>',
				code: '<?php echo esc_attr( self::get_trigger_code() ); ?>',
				pri: '<?php echo esc_attr( self::get_current_trigger( 'pri' ) ); ?>'
			};
			window.pum_review_context = {
				product: '<?php echo esc_attr( $product_context['product'] ); ?>',
				attempt: <?php echo (int) self::attempt_count(); ?>,
				needsImpression: <?php echo self::needs_impression() ? 'true' : 'false'; ?>
			};
		</script>
		<?php
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
	 * @param string          $reason        Action taken by the user.
	 * @param string|false    $trigger_group Trigger group key.
	 * @param string|false    $trigger_code  Trigger code.
	 * @param int|string|bool $trigger_pri   Trigger priority.
	 * @return bool
	 */
	public static function record_action( $reason = 'maybe_later', $trigger_group = false, $trigger_code = false, $trigger_pri = false ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		$reason        = sanitize_key( is_string( $reason ) ? $reason : 'maybe_later' );
		$trigger_group = $trigger_group ?: self::get_trigger_group();
		$trigger_code  = $trigger_code ?: self::get_trigger_code();
		$trigger_pri   = false !== $trigger_pri ? (int) $trigger_pri : (int) self::get_current_trigger( 'pri' );
		$timestamp     = current_time( 'mysql' );

		if ( ! $trigger_group || ! $trigger_code ) {
			return false;
		}

		if ( 0 === strpos( $reason, 'shown_' ) ) {
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
	 * @return bool Whether this was a new attempt.
	 */
	public static function record_presentation() {
		$user_id = get_current_user_id();
		$group   = self::get_trigger_group();
		$code    = self::get_trigger_code();

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
				'trigger_priority' => (int) self::get_current_trigger( 'pri' ),
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
		$group = self::get_trigger_group();
		$code  = self::get_trigger_code();
		$last  = get_user_meta( get_current_user_id(), '_pum_reviews_last_impression', true );

		if ( ! $group || ! $code ) {
			return false;
		}

		return ! is_array( $last ) || ! isset( $last['trigger_group'], $last['trigger_code'] ) || $group !== $last['trigger_group'] || $code !== $last['trigger_code'];
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
		return max( 0, (int) apply_filters( 'pum_reviews_max_attempts', 0 ) );
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

		$args = wp_parse_args(
			$_REQUEST,
			[
				'group'  => self::get_trigger_group(),
				'code'   => self::get_trigger_code(),
				'pri'    => self::get_current_trigger( 'pri' ),
				'reason' => 'maybe_later',
			]
		);

		try {
			$recorded = self::record_action(
				$args['reason'],
				sanitize_key( (string) $args['group'] ),
				sanitize_key( (string) $args['code'] ),
				(int) $args['pri']
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
	 * @return int|string
	 */
	public static function get_trigger_group() {
		static $selected;

		if ( ! isset( $selected ) ) {
			$dismissed_triggers = self::dismissed_triggers();

			$triggers = self::triggers();

			foreach ( $triggers as $g => $group ) {
				foreach ( $group['triggers'] as $t => $trigger ) {
					if ( ! in_array( false, $trigger['conditions'], true ) && ( empty( $dismissed_triggers[ $g ] ) || $dismissed_triggers[ $g ] < $trigger['pri'] ) ) {
						$selected = $g;
						break;
					}
				}

				if ( isset( $selected ) ) {
					break;
				}
			}
		}

		return $selected;
	}

	/**
	 * @return int|string
	 */
	public static function get_trigger_code() {
		static $selected;

		if ( ! isset( $selected ) ) {
			$dismissed_triggers = self::dismissed_triggers();

			foreach ( self::triggers() as $g => $group ) {
				foreach ( $group['triggers'] as $t => $trigger ) {
					if ( ! in_array( false, $trigger['conditions'], true ) && ( empty( $dismissed_triggers[ $g ] ) || $dismissed_triggers[ $g ] < $trigger['pri'] ) ) {
						$selected = $t;
						break;
					}
				}

				if ( isset( $selected ) ) {
					break;
				}
			}
		}

		return $selected;
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
		$expiration = apply_filters( 'pum_reviews_legacy_dismissal_expiration', '1 year' );
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
		static $triggers;

		if ( ! isset( $triggers ) ) {
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
		}

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
	 * @return string
	 */
	public static function render_review_actions() {
		ob_start();
		?>
		<ul>
			<?php foreach ( self::get_review_destinations() as $destination ) : ?>
				<?php
				if ( ! is_array( $destination ) || empty( $destination['url'] ) || empty( $destination['label'] ) ) {
					continue;
				}

				$reason = ! empty( $destination['reason'] ) ? sanitize_key( (string) $destination['reason'] ) : 'am_now_core';
				?>
				<li>
					<a class="pum-dismiss" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( (string) $destination['url'] ); ?>" data-reason="<?php echo esc_attr( $reason ); ?>">
						<?php if ( ! empty( $destination['primary'] ) ) : ?>
							<strong><?php echo esc_html( (string) $destination['label'] ); ?></strong>
						<?php else : ?>
							<?php echo esc_html( (string) $destination['label'] ); ?>
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
		self::clear_generic_panel_dismissal();

		if ( self::hide_notices() ) {
			return $alerts;
		}

		add_action( 'admin_footer', [ __CLASS__, 'print_review_request_vars' ] );

		self::record_presentation();

		$trigger         = self::get_current_trigger();
		$product_context = self::get_product_context();

		$track_review_actions = self::should_track_review_actions();
		$uuid                 = $track_review_actions ? wp_hash( home_url() . '-' . get_current_user_id() ) : '';

		ob_start();

		?>

		<script type="text/javascript">
			window.pum_review_nonce = '<?php echo esc_html( wp_create_nonce( 'pum_review_action' ) ); ?>';
			window.pum_review_ajax_url = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
			<?php if ( $track_review_actions ) : ?>
			window.pum_review_api_url = '<?php echo esc_attr( self::$api_url ); ?>';
			window.pum_review_uuid = '<?php echo esc_attr( $uuid ); ?>';
			<?php endif; ?>
			window.pum_review_trigger = {
				group: '<?php echo esc_attr( self::get_trigger_group() ); ?>',
				code: '<?php echo esc_attr( self::get_trigger_code() ); ?>',
				pri: '<?php echo esc_attr( self::get_current_trigger( 'pri' ) ); ?>'
			};
			window.pum_review_context = {
				product: '<?php echo esc_attr( $product_context['product'] ); ?>',
				attempt: <?php echo (int) self::attempt_count(); ?>,
				needsImpression: <?php echo self::needs_impression() ? 'true' : 'false'; ?>
			};
		</script>

		<?php echo self::render_review_actions(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the renderer. ?>

		<?php

		$html = ob_get_clean();

		$alerts[] = [
			'code'           => 'review_request',
			/* translators: %s: Popup Maker product name. */
			'title'          => '⭐ ' . sprintf( __( 'Is %s helping you grow?', 'popup-maker' ), $product_context['name'] ),
			'message'        => '<strong>' . esc_html( $trigger['message'] ) . '</strong>',
			'html'           => $html,
			'type'           => 'success',
			'category'       => 'recommendation',
			'dismiss_action' => 'maybe_later',
			'display_inline' => true,
		];

		return $alerts;
	}


	/**
	 * Render admin notices if available.
	 *
	 * @deprecated 1.8.0
	 */
	public static function admin_notices() {
		if ( self::hide_notices() ) {
			return;
		}

		self::record_presentation();

		$group           = self::get_trigger_group();
		$code            = self::get_trigger_code();
		$pri             = self::get_current_trigger( 'pri' );
		$trigger         = self::get_current_trigger();
		$product_context = self::get_product_context();

		$track_review_actions = self::should_track_review_actions();
		$uuid                 = $track_review_actions ? wp_hash( home_url() . '-' . get_current_user_id() ) : '';

		?>

		<script type="text/javascript">
			(function ($) {
				var trigger = {
					group: '<?php echo esc_attr( $group ); ?>',
					code: '<?php echo esc_attr( $code ); ?>',
					pri: '<?php echo esc_attr( $pri ); ?>'
				},
					reviewContext = {
						product: '<?php echo esc_attr( $product_context['product'] ); ?>',
						attempt: <?php echo (int) self::attempt_count(); ?>,
						needsImpression: <?php echo self::needs_impression() ? 'true' : 'false'; ?>
					};

				function track(reason) {
					<?php if ( $track_review_actions && ! empty( self::$api_url ) ) : ?>
					$.ajax({
						method: "POST",
						dataType: "json",
						url: '<?php echo esc_attr( self::$api_url ); ?>',
						data: {
							trigger_group: trigger.group,
							trigger_code: trigger.code,
							reason: reason,
							product: reviewContext.product,
							attempt: reviewContext.attempt,
							uuid: '<?php echo esc_attr( $uuid ); ?>'
						}
					});
					<?php endif; ?>
				}

				function record(reason) {
					return $.ajax({
						method: "POST",
						dataType: "json",
						url: ajaxurl,
						data: {
							action: 'pum_review_action',
							nonce: '<?php echo esc_attr( wp_create_nonce( 'pum_review_action' ) ); ?>',
							group: trigger.group,
							code: trigger.code,
							pri: trigger.pri,
							reason: reason
						}
					});
				}

				function dismiss(reason) {
					record(reason);
					track(reason);
					document.dispatchEvent(new CustomEvent('pumReviewRequestDismissed'));
				}

				if (reviewContext.needsImpression) {
					reviewContext.needsImpression = false;
					record('shown_' + reviewContext.product).done(function () {
						track('shown_' + reviewContext.product);
					});
				}

				$(document)
					.on('click', '.pum-notice .pum-dismiss', function (event) {
						var $this = $(this),
							reason = $this.data('reason'),
							notice = $this.parents('.pum-notice');

						notice.fadeTo(100, 0, function () {
							notice.slideUp(100, function () {
								notice.remove();
							});
						});

						dismiss(reason);
					})
					.ready(function () {
						setTimeout(function () {
							$('.pum-notice button.notice-dismiss').click(function (event) {
								dismiss('maybe_later');
							});
						}, 1000);
					});
			}(jQuery));
		</script>

		<style>
			.pum-notice p {
				margin-bottom: 0;
			}

			.pum-notice img.logo {
				float: right;
				margin-left: 10px;
				width: 128px;
				padding: 0.25em;
				border: 1px solid #ccc;
			}
		</style>

		<div class="notice notice-success is-dismissible pum-notice">

			<p>
				<img class="logo" src="<?php echo esc_attr( POPMAKE_URL ); ?>/assets/images/mark.svg" />
				<strong>
					<?php echo esc_html( $trigger['message'] ); ?>
				</strong>
			</p>
			<?php echo self::render_review_actions(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the renderer. ?>

		</div>

		<?php
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
