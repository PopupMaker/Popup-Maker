<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class PUM_Privacy
 */
class PUM_Privacy {

	public static function init() {
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ), 10 );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_erasers' ), 10 );
		add_action( 'admin_init', array( __CLASS__, 'privacy_policy_content' ), 20 );
	}

	/**
	 * Add the suggested privacy policy text to the policy postbox.
	 */
	public static function privacy_policy_content() {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			$content = self::default_privacy_policy_content( true );
			wp_add_privacy_policy_content( __( 'Popup Maker', 'popup-maker' ), $content );
		}
	}

	/**
	 * Return the default suggested privacy policy content.
	 *
	 * @param bool $descr Whether to include the descriptions under the section headings. Default false.
	 *
	 * @return string The default policy content.
	 */
	public static function default_privacy_policy_content( $descr = false ) {
		$suggested_text = $descr ? '<strong class="privacy-policy-tutorial">' . __( 'Suggested text:', 'popup-maker' ) . ' </strong>' : '';
		ob_start();
		?>
		<div class="pum-suggested-text">
			<p class="privacy-policy-tutorial"><?php _e( 'Hello,', 'popup-maker' ); ?></p> <p class="privacy-policy-tutorial"><?php _e( 'This information serves as a guide on what sections need to be modified due to usage of Popup Maker and its extensions.', 'popup-maker' ); ?></p>
			<p class="privacy-policy-tutorial"><?php _e( 'You should include the information below in the correct sections of you privacy policy.', 'popup-maker' ); ?></p>
			<p class="privacy-policy-tutorial"><strong> <?php _e( 'Disclaimer:', 'popup-maker' ); ?></strong> <?php _e( 'This information is only for guidance and not to be considered as legal advice.', 'popup-maker' ); ?></p>
			<p class="privacy-policy-tutorial"><strong> <?php _e( 'Note:', 'popup-maker' ); ?></strong> <?php _e( 'Some of the information below is dynamically generated, such as cookies. If you add or change popups you will see those additions or changes below and will need to update your policy accordingly.', 'popup-maker' ); ?></p>

			<h2><?php _e( 'What personal data we collect and why we collect it', 'popup-maker' ); ?></h2>

			<h3><?php _e( 'Subscription forms', 'popup-maker' ); ?></h3>
			<p class="privacy-policy-tutorial"><?php _e( 'Popup Maker subscription forms are not enabled by default.', 'popup-maker' ); ?></p>
			<p class="privacy-policy-tutorial"><?php _e( 'If you have used them in your popups to collect email subscribers, use this subsection to note what personal data is captured when someone submits a subscription form, and how long you keep it.', 'popup-maker' ); ?></p>
			<p class="privacy-policy-tutorial"><?php _e( 'For example, you may note that you keep form submissions for ongoing marketing purposes.', 'popup-maker' ); ?></p>
			<p><?php echo $suggested_text . __( 'If you submit a subscription form on our site you will be opting in for us to save your name, email address and other relevant information.', 'popup-maker' ); ?></p>
			<p><?php _e( 'These subscriptions are used to notify you about related content, discounts & other special offers.', 'popup-maker' ); ?></p> <p><?php _e( 'You can opt our or unsubscribe at any time in the future by clicking link in the bottom of any email.', 'popup-maker' ); ?></p>

			<h3><?php _e( 'Cookies', 'popup-maker' ); ?></h3>
			<p class="privacy-policy-tutorial"><?php _e( 'Popup Maker uses cookies for most popups. The primary function is to prevent your users from being annoyed by seeing the same popup repeatedly.', 'popup-maker' ); ?></p>
			<p class="privacy-policy-tutorial"><?php _e( 'This may result in cookies being saved for an extended period of time. These are non-tracking cookies used only by our popups.', 'popup-maker' ); ?></p>

			<?php
			$cookies = self::get_all_cookies();
			if ( ! empty( $cookies ) ) : ?>
				<p class="privacy-policy-tutorial"><?php _e( 'Below is a list of all cookies currently registered within your popup settings. These are here for you to disclose if you are so required.', 'popup-maker' ); ?></p>
				<table class="wp-list-table" style="width: 100%;">
					<thead>
					<tr>
						<th align="left"><?php _e( 'Cookie Name', 'popup-maker' ); ?></th>
						<th align="left"><?php _e( 'Usage', 'popup-maker' ); ?></th>
						<th align="left"><?php _e( 'Time', 'popup-maker' ); ?></th>
					</tr>
					</thead>
					<tbody style="border: 1px solid;"><?php
					foreach ( $cookies as $cookie ) {
						if ( ! is_array( $cookie ) ) {
							continue;
						}

						$cookie = wp_parse_args( $cookie, array(
							'name'  => '',
							'label' => '',
							'time'  => '',
						) );

						printf( '<tr><td style="border-top: 1px dashed;">%s</td><td style="border-top: 1px dashed;">%s</td><td style="border-top: 1px dashed;">%s</td></tr>', $cookie['name'], $cookie['label'], $cookie['time'] );
					}
					?>
					</tbody>
				</table>
			<?php
			endif; ?>

			<p><?php echo $suggested_text . __( 'We use anonymous cookies to prevent users from seeing the same popup repetitively in an attempt to make our users experience more pleasant while still delivering time sensitive messaging.', 'popup-maker' ); ?></p>

			<h3><?php _e( 'Analytics', 'popup-maker' ); ?></h3>
			<p class="privacy-policy-tutorial"><?php _e( 'Popup Maker anonymously tracks popup views and conversions.', 'popup-maker' ); ?></p>

			<h2><?php _e( 'How long we retain your data', 'popup-maker' ); ?></h2>
			<p><?php _e( 'Subscriber information is retained in the local database indefinitely for analytic tracking purposes and for future export.', 'popup-maker' ); ?></p>
			<p><?php _e( 'Data will be exported or removed upon users request via the existing Exporter or Eraser.', 'popup-maker' ); ?></p> <p><?php _e( 'If syncing data to a 3rd party service (for example Mailchimp), data is retained there until unsubscribed or deleted.', 'popup-maker' ); ?></p>

			<h2><?php _e( 'Where we send your data', 'popup-maker' ); ?></h2>
			<p><?php _e( 'Popup Maker does not send any user data outside of your site by default.', 'popup-maker' ); ?></p>
			<p><?php _e( 'If you have extended our subscription forms to send data to a 3rd party service such as Mailchimp, user info may be passed to these external services. These services may be located abroad.', 'popup-maker' ); ?></p>
		</div>

		<?php
		$content = ob_get_clean();

		/**
		 * Filters the default content suggested for inclusion in a privacy policy.
		 *
		 * @param $content string The default policy content.
		 */
		return apply_filters( 'pum_get_default_privacy_policy_content', $content );

	}

	/**
	 * Register exporter for Popup Maker Optin Form Subscriber Data.
	 *
	 * @see https://github.com/allendav/wp-privacy-requests/blob/master/EXPORT.md
	 *
	 * @param $exporters
	 *
	 * @return array
	 */
	public static function register_exporter( $exporters ) {
		$exporters[] = array(
			'exporter_friendly_name' => __( 'Popup Maker Subscribe Form' ),
			'callback'               => array( __CLASS__, 'exporter' ),
		);

		return $exporters;
	}

	/**
	 * Register erasers for Popup Maker Optin Form Subscriber Data.
	 *
	 * @see https://github.com/allendav/wp-privacy-requests/blob/master/EXPORT.md
	 *
	 * @param $exporters
	 *
	 * @return array
	 */
	public static function register_erasers( $exporters ) {
		$exporters[] = array(
			'eraser_friendly_name' => __( 'Popup Maker Subscribe Form' ),
			'callback'             => array( __CLASS__, 'exporter' ),
		);

		return $exporters;
	}

	/**
	 * Exporter for Popup Maker Optin Form Subscriber Data.
	 *
	 * @see https://github.com/allendav/wp-privacy-requests/blob/master/EXPORT.md
	 *
	 * @param     $email_address
	 * @param int $page
	 *
	 * @return array
	 */
	public static function exporter( $email_address, $page = 1 ) {
		$number = 500; // Limit us to avoid timing out
		$page   = (int) $page;

		$export_items = array();
		$subscribers  = PUM_DB_Subscribers::instance()->query( array(
			's'       => $email_address,
			'page'    => $page,
			'limit'   => $number,
			'orderby' => 'ID',
			'order'   => 'ASC',
		), 'ARRAY_A' );

		foreach ( (array) $subscribers as $subscriber ) {
			if ( $subscriber['email'] == $email_address ) {
				// Most item IDs should look like postType-postID
				// If you don't have a post, comment or other ID to work with,
				// use a unique value to avoid having this item's export
				// combined in the final report with other items of the same id
				$item_id = "pum-subscriber-{$subscriber['ID']}";

				// Core group IDs include 'comments', 'posts', etc.
				// But you can add your own group IDs as needed
				$group_id = 'pum-subscribers';

				// Optional group label. Core provides these for core groups.
				// If you define your own group, the first exporter to
				// include a label will be used as the group label in the
				// final exported report
				$group_label = __( 'Subscriber Data' );

				// Plugins can add as many items in the item data array as they want

				$data = array();

				foreach ( $subscriber as $field_key => $field_value ) {
					switch ( $field_key ) {
						case 'ID':
							$data[] = array(
								'name'  => __( 'ID', 'popup-maker' ),
								'value' => $field_value,
							);
							break;
						case 'email':
							$data[] = array(
								'name'  => __( 'Email', 'popup-maker' ),
								'value' => $field_value,
							);
							break;
						case 'name':
							$data[] = array(
								'name'  => __( 'Name', 'popup-maker' ),
								'value' => $field_value,
							);
							break;
						case 'fname':
							$data[] = array(
								'name'  => __( 'First Name', 'popup-maker' ),
								'value' => $field_value,
							);
							break;
						case 'lname':
							$data[] = array(
								'name'  => __( 'Last Name', 'popup-maker' ),
								'value' => $field_value,
							);
							break;
						case 'consent':
							$data[] = array(
								'name'  => __( 'Provided Consent', 'popup-maker' ),
								'value' => ucfirst( $field_value ),
							);
							break;
						case 'values':
						case 'consent_args':
							$values = maybe_unserialize( $field_value );

							foreach ( (array) $values as $key => $value ) {

								// Empty values don't need to be rendered.
								if ( empty( $value ) ) {
									continue;
								}

								$label = '';

								switch ( $key ) {
									case 'provider':
										$providers = PUM_Newsletter_Providers::instance()->get_providers();

										if ( ! empty( $providers[ $value ] ) ) {
											$label = $providers[ $value ]->name;
										}
										break;
									case 'required':
										$label = __( 'Consent Required', 'popup-maker' );
										break;
									case 'text':
										$label = __( 'Consent Text', 'popup-maker' );
										break;
									case 'name':
									case 'lname':
									case 'email':
									case 'fname':
									case 'list_id':
									case 'popup_id':
									case 'email_hash':
									case 'pum_form_popup_id':
									case 'mc_args':
										// Leave these values out.
										break;
								}

								$label = apply_filters( 'pum_privacy_subscriber_value_label', $label, $key, $value );

								if ( ! empty( $label ) ) {
									$data[] = array(
										'name'  => $label,
										'value' => $value,
									);
								}
							}

							break;
						case 'created':
							$data[] = array(
								'name'  => __( 'Date Subscribed', 'popup-maker' ),
								'value' => $field_value,
							);
							break;
					}
				}

				$export_items[] = array(
					'group_id'    => $group_id,
					'group_label' => $group_label,
					'item_id'     => $item_id,
					'data'        => $data,
				);
			}

		}

		// Tell core if we have more comments to work on still
		$done = count( $subscribers ) < $number;

		return array(
			'data' => $export_items,
			'done' => $done,
		);
	}


	/**
	 * Eraser for Popup Maker Optin Form Subscriber Data.
	 *
	 * @see https://github.com/allendav/wp-privacy-requests/blob/master/EXPORT.md
	 *
	 * @param     $email_address
	 * @param int $page
	 *
	 * @return array
	 */
	public static function eraser( $email_address, $page = 1 ) {
		if ( empty( $email_address ) ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}

		$messages       = array();
		$items_removed  = false;
		$items_retained = false;

		$number = 500; // Limit us to avoid timing out
		$page   = (int) $page;

		$subscribers = PUM_DB_Subscribers::instance()->query( array(
			's'       => $email_address,
			'page'    => $page,
			'limit'   => $number,
			'orderby' => 'ID',
			'order'   => 'ASC',
		), 'ARRAY_A' );

		foreach ( (array) $subscribers as $subscriber ) {
			if ( $subscriber['email'] == $email_address ) {

				// Data should not be deleted if the user was left subscribed to a service provider.
				$unsubscribed = apply_filters( 'pum_privacy_eraser_subscriber_was_unsubscribed', true, $email_address, $subscriber );

				if ( $unsubscribed ) {

					$deleted = PUM_DB_Subscribers::instance()->delete( $subscriber['ID'] );

					if ( $deleted ) {
						$items_removed = true;
					} else {
						$items_retained = true;
						$messages[]     = __( 'Subscription information was not removed. A database error may have occurred during deletion.', 'popup-maker' );
					}
				} else {
					$items_retained = true;
					$messages[]     = __( 'Subscription information was not removed. This may occur when no immediate confirmation is received during our attempt to unsubscribe you from our mailing list.', 'popup-maker' );
				}

			}

		}

		// Tell core if we have more comments to work on still
		$done = count( $subscribers ) < $number;

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => $messages,
			'done'           => $done,
		);
	}

	/**
	 * @return array
	 */
	public static function get_all_cookies() {
		$popups  = PUM_Popups::get_all();
		$cookies = array();

		if ( $popups->have_posts() ) {
			while ( $popups->have_posts() ) : $popups->next_post();
				// Set this popup as the global $current.
				PUM_Site_Popups::current_popup( $popups->post );

				$popup = pum_get_popup( $popups->post->ID );

				if ( ! pum_is_popup( $popup ) ) {
					continue;
				}

				$pcookies = $popup->get_setting( 'cookies', array() );

				if ( ! empty( $pcookies ) ) {
					foreach ( $pcookies as $cookie ) {
						if ( ! empty ( $cookie['settings']['name'] ) ) {
							$current_time = 0;
							if ( ! empty( $cookies[ $cookie['settings']['name'] ] ) ) {
								$current_time = strtotime( '+' . $cookies[ $cookie['settings']['name'] ]['time'] );
							}

							if ( empty( $cookies[ $cookie['settings']['name'] ] ) ) {
								$cookies[ $cookie['settings']['name'] ] = array(
									'label' => __( 'Cookie used to prevent popup from displaying repeatedly.', 'popup-maker' ),
									'name'  => $cookie['settings']['name'],
									'time'  => $cookie['settings']['time'],
								);
							}

							$new_time = strtotime( '+' . $cookie['settings']['time'] );
							if ( $new_time > $current_time ) {
								$cookies[ $cookie['settings']['name'] ]['time'] = $cookie['settings']['time'];
							}
						}
					}
				}
			endwhile;

			// Clear the global $current.
			PUM_Site_Popups::current_popup( null );
		}

		return apply_filters( 'pum_privacy_get_all_cookies', $cookies );
	}
}