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
						case 'values':
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
}