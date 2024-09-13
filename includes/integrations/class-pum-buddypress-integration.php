<?php
/**
 * Integrations for buddypress
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_BuddyPress_Integration
 */
class PUM_BuddyPress_Integration {

	/**
	 *
	 */
	public static function init() {
		add_filter( 'pum_registered_conditions', [ __CLASS__, 'registered_conditions' ] );
		add_filter( 'pum_condition_sort_order', [ __CLASS__, 'condition_sort_order' ] );
	}

	/**
	 * @param array $conditions
	 *
	 * @return array
	 */
	public static function registered_conditions( $conditions = [] ) {

		$conditions = array_merge(
			$conditions,
			[
				// Add Additional Conditions
				'is_buddypress'           => [
					'group'    => __( 'BuddyPress', 'buddypress' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					'name'     => __( 'BP: Is a BuddyPress Page', 'popup-maker' ),
					'callback' => 'is_buddypress',
				],

				'bp_is_user'              => [
					'group'    => __( 'BuddyPress', 'buddypress' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					'name'     => __( 'BP: Is User Page', 'popup-maker' ),
					'callback' => 'bp_is_user',
				],

				'bp_is_group'             => [
					'group'    => __( 'BuddyPress', 'buddypress' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					'name'     => __( 'BP: Is Group Page', 'popup-maker' ),
					'callback' => 'bp_is_group',
				],

				'bp_is_user_messages'     => [
					'group'    => __( 'BuddyPress', 'buddypress' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					'name'     => __( 'BP: Is User Messages Page', 'popup-maker' ),
					'callback' => 'bp_is_user_messages',
				],

				'bp_is_activation_page'   => [
					'group'    => __( 'BuddyPress', 'buddypress' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					'name'     => __( 'BP: Is Activation Page', 'popup-maker' ),
					'callback' => 'bp_is_activation_page',
				],

				'bp_is_register_page'     => [
					'group'    => __( 'BuddyPress', 'buddypress' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					'name'     => __( 'BP: Is Register Page', 'popup-maker' ),
					'callback' => 'bp_is_register_page',
				],

				'bp_is_item_admin'        => [
					'group'    => __( 'BuddyPress', 'buddypress' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					'name'     => __( 'BP: Is Item Admin', 'popup-maker' ),
					'callback' => 'bp_is_item_admin',
				],

				'bp_is_item_mod'          => [
					'group'    => __( 'BuddyPress', 'buddypress' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					'name'     => __( 'BP: Is Item Mod', 'popup-maker' ),
					'callback' => 'bp_is_item_mod',
				],

				'bp_is_directory'         => [
					'group'    => __( 'BuddyPress', 'buddypress' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					'name'     => __( 'BP: Is Directory', 'popup-maker' ),
					'callback' => 'bp_is_directory',
				],
				'bp_is_current_component' => [
					'group'    => __( 'BuddyPress', 'buddypress' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					'name'     => __( 'BP: Is Current Component', 'popup-maker' ),
					'fields'   => [
						'selected' => [
							'type'     => 'select',
							'multiple' => true,
							'as_array' => true,
							'select2'  => true,
							'options'  => self::component_option_list(),
							'label'    => __( 'Which components?', 'popup-maker' ),
						],
					],
					'callback' => [ __CLASS__, 'bp_is_current_component' ],
				],

				'bp_is_current_action'    => [
					'group'    => __( 'BuddyPress', 'buddypress' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					'name'     => __( 'BP: Is Current Action', 'popup-maker' ),
					'fields'   => [
						'selected' => [
							'type'  => 'text',
							'label' => __( 'Which actions?', 'popup-maker' ),
						],
					],
					'callback' => [ __CLASS__, 'bp_is_current_action' ],
				],

				'bp_is_action_variable'   => [
					'group'    => __( 'BuddyPress', 'buddypress' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					'name'     => __( 'BP: Is Action Variable', 'popup-maker' ),
					'fields'   => [
						'selected' => [
							'type'  => 'text',
							'label' => __( 'Which action variables?', 'popup-maker' ),
						],
					],
					'callback' => [ __CLASS__, 'bp_is_action_variable' ],
				],

			]
		);

		return $conditions;
	}

	/**
	 * @return array
	 */
	public static function component_option_list() {
		global $bp;

		$components = [];

		foreach ( $bp->active_components as $component => $key ) {
			$components[ $component ] = ucfirst( $component );
		}

		return $components;
	}

	/**
	 * Checks if the current page is the selected bp components.
	 *
	 * @param array $settings
	 *
	 * @return bool
	 */
	public static function bp_is_current_component( $settings = [] ) {
		global $bp;

		if ( empty( $settings['selected'] ) ) {
			return false;
		}

		if ( ! is_array( $settings['selected'] ) ) {
			$settings['selected'] = [ $settings['selected'] ];
		}

		$found = false;

		foreach ( $settings['selected'] as $component ) {
			if ( ! array_key_exists( $component, $bp->active_components ) ) {
				continue;
			}

			if ( bp_is_current_component( $component ) ) {
				$found = true;
			}
		}

		return $found;
	}

	/**
	 * Checks if the current page is the selected bp action.
	 *
	 * @param array $settings
	 *
	 * @return bool
	 */
	public static function bp_is_current_action( $settings = [] ) {

		if ( empty( $settings['selected'] ) ) {
			return false;
		}

		if ( ! is_array( $settings['selected'] ) ) {
			$settings['selected'] = array_map( 'trim', explode( ',', $settings['selected'] ) );
		}

		$found = false;

		foreach ( $settings['selected'] as $action ) {
			if ( bp_is_current_action( $action ) ) {
				$found = true;
			}
		}

		return $found;
	}


	/**
	 * Checks if the current page is the selected bp action variable.
	 *
	 * @param array $settings
	 *
	 * @return bool
	 */
	public static function bp_is_action_variable( $settings = [] ) {

		if ( empty( $settings['selected'] ) ) {
			return false;
		}

		if ( ! is_array( $settings['selected'] ) ) {
			$settings['selected'] = array_map( 'trim', explode( ',', $settings['selected'] ) );
		}

		$found = false;

		foreach ( $settings['selected'] as $variable ) {
			if ( bp_is_action_variable( $variable ) ) {
				$found = true;
			}
		}

		return $found;
	}

	/**
	 * @param array $order
	 *
	 * @return array
	 */
	public static function condition_sort_order( $order = [] ) {
		$order[ __( 'BuddyPress', 'buddypress' ) ] = 5.756; // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

		return $order;
	}
}
