<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Popmake_Woocommerce_Integration {
	public function __construct() {
        if ( function_exists( 'WC' ) || class_exists( 'WooCommerce' ) ) {
            add_action( 'popmake_before_post_type_targeting_conditions', array( $this, 'targeting_conditions' ) );
            add_filter( 'popmake_supported_post_types', array( $this, 'post_types' ) );
            add_filter( 'popmake_supported_taxonomies', array( $this, 'taxonomies' ) );
            add_filter( 'popmake_popup_meta_fields', array( $this, 'popup_meta_fields' ) );
            add_filter( 'popmake_popup_is_loadable', array( $this, 'popup_is_loadable' ), 10, 4 );
        }
	}

	public function targeting_conditions( $targeting_condition ) { ?>
		<div id="targeting_condition-on_woocommerce" class="targeting_condition form-table">
			<input type="checkbox"
			       id="popup_targeting_condition_on_woocommerce"
			       name="popup_targeting_condition_on_woocommerce"
			       value="true"
				<?php if ( ! empty( $targeting_condition['on_woocommerce'] ) ) {
					echo 'checked="checked" ';
				} ?>
				/>
			<label for="popup_targeting_condition_on_woocommerce"><?php _e( 'On All WooCommerce', 'popup-maker' ); ?></label>

			<div class="options">
				<?php do_action( "popmake_popup_targeting_condition_on_woocommerce_options", $targeting_condition ); ?>
			</div>
		</div>
		<div id="targeting_condition-exclude_on_woocommerce" class="targeting_condition form-table">
			<input type="checkbox"
			       id="popup_targeting_condition_exclude_on_woocommerce"
			       name="popup_targeting_condition_exclude_on_woocommerce"
			       value="true"
				<?php if ( ! empty( $targeting_condition['exclude_on_woocommerce'] ) ) {
					echo 'checked="checked" ';
				} ?>
				/>
			<label for="popup_targeting_condition_exclude_on_woocommerce"><?php _e( 'Exclude on All WooCommerce', 'popup-maker' ); ?></label>

			<div class="options">
				<?php do_action( "popmake_popup_targeting_condition_exclude_on_woocommerce_options", $targeting_condition ); ?>
			</div>
		</div>
		<div id="targeting_condition-on_shop" class="targeting_condition form-table">
			<input type="checkbox"
			       id="popup_targeting_condition_on_shop"
			       name="popup_targeting_condition_on_shop"
			       value="true"
				<?php if ( ! empty( $targeting_condition['on_shop'] ) ) {
					echo 'checked="checked" ';
				} ?>
				/>
			<label for="popup_targeting_condition_on_shop"><?php _e( 'On Shop Page', 'popup-maker' ); ?></label>

			<div class="options">
				<?php do_action( "popmake_popup_targeting_condition_on_shop_options", $targeting_condition ); ?>
			</div>
		</div>
		<div id="targeting_condition-exclude_on_shop" class="targeting_condition form-table">
		<input type="checkbox"
		       id="popup_targeting_condition_exclude_on_shop"
		       name="popup_targeting_condition_exclude_on_shop"
		       value="true"
			<?php if ( ! empty( $targeting_condition['exclude_on_shop'] ) ) {
				echo 'checked="checked" ';
			} ?>
			/>
		<label for="popup_targeting_condition_exclude_on_shop"><?php _e( 'Exclude on Shop Page', 'popup-maker' ); ?></label>

		<div class="options">
			<?php do_action( "popmake_popup_targeting_condition_exclude_on_shop_options", $targeting_condition ); ?>
		</div>
		</div><?php
	}

	public function post_types( $post_types ) {
		return array_merge( $post_types, array(
			'product'
		) );
	}

	public function taxonomies( $taxonomies ) {
		return array_merge( $taxonomies, array(
			'product_cat',
			'product_tag'
		) );
	}

	public function popup_meta_fields( $fields ) {
		return array_merge( $fields, array(
			'popup_targeting_condition_on_woocommerce',
			'popup_targeting_condition_exclude_on_woocommerce',
			'popup_targeting_condition_on_shop',
			'popup_targeting_condition_exclude_on_shop',
		) );
	}

	public function popup_is_loadable( $is_loadable, $popup_id, $conditions = array(), $sitewide = false ) {

		/**
		 * WooCommerce Page Checks
		 */
		if ( is_woocommerce() ) {
			if ( ! $sitewide && array_key_exists( 'on_woocommerce', $conditions ) ) {
				$is_loadable = true;
			} elseif ( $sitewide && array_key_exists( 'exclude_on_woocommerce', $conditions ) ) {
				$is_loadable = false;
			}
		}

		/**
		 * Shop Page Checks
		 */
		if ( is_shop() ) {
			if ( ! $sitewide && array_key_exists( 'on_shop', $conditions ) ) {
				$is_loadable = true;
			} elseif ( $sitewide && array_key_exists( 'exclude_on_shop', $conditions ) ) {
				$is_loadable = false;
			}
		}

		return $is_loadable;
	}

}

function popmake_deprecated_woocommerce_support() {
    new Popmake_Woocommerce_Integration();
}

add_action( 'init', 'popmake_deprecated_woocommerce_support' );

