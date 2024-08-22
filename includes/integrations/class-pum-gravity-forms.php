<?php
/**
 * Gravity-forms Integrations class
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

class PUM_Gravity_Forms_Integation {

	public static function init() {
		add_filter( 'gform_form_settings_menu', [ __CLASS__, 'settings_menu' ] );
		add_action( 'gform_form_settings_page_popup-maker', [ __CLASS__, 'render_settings_page' ] );
		add_filter( 'pum_get_cookies', [ __CLASS__, 'register_cookies' ] );
		add_filter( 'gform_get_form_filter', [ __CLASS__, 'get_form' ], 10, 2 );
		add_action( 'popmake_preload_popup', [ __CLASS__, 'preload' ] );
		add_action( 'popmake_popup_before_inner', [ __CLASS__, 'force_ajax' ] );
		add_action( 'popmake_popup_after_inner', [ __CLASS__, 'force_ajax' ] );
	}

	public static function force_ajax() {
		if ( current_action() === 'popmake_popup_before_inner' ) {
			add_filter( 'shortcode_atts_gravityforms', [ __CLASS__, 'gfrorms_shortcode_atts' ] );
		}
		if ( current_action() === 'popmake_popup_after_inner' ) {
			remove_filter( 'shortcode_atts_gravityforms', [ __CLASS__, 'gfrorms_shortcode_atts' ] );
		}
	}

	public static function gfrorms_shortcode_atts( $out ) {
		$out['ajax'] = 'true';

		return $out;
	}


	public static function preload( $popup_id ) {
		if ( function_exists( 'gravity_form_enqueue_scripts' ) ) {
			$popup = pum_get_popup( $popup_id );

			if ( has_shortcode( $popup->post_content, 'gravityform' ) ) {
				$regex = "/\[gravityform.*id=[\'\"]?([0-9]*)[\'\"]?.*/";
				$popup = get_post( $popup_id );
				preg_match_all( $regex, $popup->post_content, $matches );
				foreach ( $matches[1] as $form_id ) {
					add_filter( "gform_confirmation_anchor_{$form_id}", '__return_false' );
					gravity_form_enqueue_scripts( $form_id, true );
				}
			}
		}
	}


	public static function settings_menu( $setting_tabs ) {
		$setting_tabs['998.002'] = [
			'name'  => 'popup-maker',
			'label' => __( 'Popup Maker', 'popup-maker' ),
		];

		return $setting_tabs;
	}


	public static function get_form( $form_string, $form ) {
		$settings    = wp_json_encode( self::form_options( $form['id'] ) );
		$field       = "<input type='hidden' class='gforms-pum' value='$settings' />";
		$form_string = preg_replace( '/(<form.*>)/', "$1 \r\n " . $field, $form_string );

		return $form_string;
	}

	/**
	 * Get default values.
	 *
	 * @return array
	 */
	public static function defaults() {
		return [
			'closepopup'   => false,
			'closedelay'   => 0,
			'openpopup'    => false,
			'openpopup_id' => 0,
		];
	}

	/**
	 * Get a specific forms options.
	 *
	 * @param $id
	 *
	 * @return array
	 */
	public static function form_options( $id ) {
		$settings = get_option( 'gforms_pum_' . $id, self::defaults() );

		return wp_parse_args( $settings, self::defaults() );
	}

	/**
	 * Registers new cookie events.
	 *
	 * @param array $cookies
	 *
	 * @return array
	 */
	public static function register_cookies( $cookies = [] ) {
		$cookies['gforms_form_success'] = [
			'labels' => [
				'name' => __( 'Gravity Form Success (deprecated. Use Form Submission instead.)', 'popup-maker' ),
			],
			'fields' => pum_get_cookie_fields(),
		];

		return $cookies;
	}


	public static function render_settings_page() {
		$form_id = rgget( 'id' );

		self::save();

		$settings = self::form_options( $form_id );

		GFFormSettings::page_header( __( 'Popup Settings', 'popup-maker' ) );

		?>

		<div id="popup_settings-editor">

			<form id="popup_settings_edit_form" method="post">

				<table class="form-table gforms_form_settings">
					<tr>
						<th scope="row">
							<label for="gforms-pum-closepopup"><?php esc_html_e( 'Close Popup', 'popup-maker' ); ?></label>
						</th>
						<td>
							<input type="checkbox" id="gforms-pum-closepopup" name="gforms-pum[closepopup]" value="true" <?php checked( $settings['closepopup'], true ); ?> />
						</td>
					</tr>
					<tr id="gforms-pum-closedelay-wrapper">
						<th scope="row">
							<label for="gforms-pum-closedelay"><?php esc_html_e( 'Delay', 'popup-maker' ); ?></label>
						</th>
						<td>
							<?php
							if ( strlen( $settings['closedelay'] ) >= 3 ) {
								$settings['closedelay'] = $settings['closedelay'] / 1000;
							}
							?>

							<input type="number" id="gforms-pum-closedelay" min="0" step="1" name="gforms-pum[closedelay]" style="width: 100px;" value="<?php echo esc_attr( $settings['closedelay'] ); ?>" /><?php esc_html_e( 'seconds', 'popup-maker' ); ?>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="gforms-pum-openpopup"><?php esc_html_e( 'Open Popup', 'popup-maker' ); ?></label>
						</th>
						<td>
							<input type="checkbox" id="gforms-pum-openpopup" name="gforms-pum[openpopup]" value="true" <?php checked( $settings['openpopup'], true ); ?> />
						</td>
					</tr>
					<tr id="gforms-pum-openpopup_id-wrapper">
						<th scope="row">
							<label for="gforms-pum-openpopup_id"><?php esc_html_e( 'Popup', 'popup-maker' ); ?></label>
						</th>
						<td>
							<select id="gforms-pum-openpopup_id" name="gforms-pum[openpopup_id]">
								<?php foreach ( self::get_popup_list() as $option ) { ?>
									<option value="<?php echo esc_attr( $option['value'] ); ?>" <?php selected( $settings['openpopup_id'], $option['value'] ); ?>><?php echo esc_html( $option['label'] ); ?></option>
								<?php } ?>
							</select>
						</td>
					</tr>
				</table>

				<input type="hidden" id="form_id" name="form_id" value="<?php echo esc_attr( $form_id ); ?>" />

				<p class="submit">
					<input type="submit" name="save" value="<?php esc_attr_e( 'Save', 'popup-maker' ); ?>" class="button-primary">
				</p>

				<?php wp_nonce_field( 'gform_popup_settings_edit', 'gform_popup_settings_edit' ); ?>

			</form>
			<script type="text/javascript">
				(function ($) {
					var $open = $('#gforms-pum-openpopup'),
						$close = $('#gforms-pum-closepopup'),
						$popup_id_wrapper = $('#gforms-pum-openpopup_id-wrapper'),
						$delay_wrapper = $('#gforms-pum-closedelay-wrapper');

					function check_open() {
						if ($open.is(':checked')) {
							$popup_id_wrapper.show();
						} else {
							$popup_id_wrapper.hide();
						}
					}

					function check_close() {
						if ($close.is(':checked')) {
							$delay_wrapper.show();
						} else {
							$delay_wrapper.hide();
						}
					}

					check_open();
					check_close();

					$open.on('click', check_open);
					$close.on('click', check_close);
				}(jQuery));
			</script>

		</div> <!-- / popup-editor -->

		<?php

		GFFormSettings::page_footer();
	}


	/**
	 * Get a list of popups for a select box.
	 *
	 * @return array
	 */
	public static function get_popup_list() {
		$popup_list = [
			[
				'value' => '',
				'label' => __( 'Select a popup', 'popup-maker' ),
			],
		];

		$popups = get_posts(
			[
				'post_type'      => 'popup',
				'post_status'    => [ 'publish' ],
				'posts_per_page' => - 1,
			]
		);

		foreach ( $popups as $popup ) {
			$popup_list[] = [
				'value' => $popup->ID,
				'label' => $popup->post_title,
			];
		}

		return $popup_list;
	}

	/**
	 * Save form popup options.
	 */
	public static function save() {

		if ( empty( $_POST ) || ! check_admin_referer( 'gform_popup_settings_edit', 'gform_popup_settings_edit' ) ) {
			return;
		}

		$form_id = rgget( 'id' );

		if ( ! empty( $_POST['gforms-pum'] ) ) {
			$settings = sanitize_key( wp_unslash( $_POST['gforms-pum'] ) );

			// Sanitize values.
			$settings['openpopup']    = ! empty( $settings['openpopup'] );
			$settings['openpopup_id'] = ! empty( $settings['openpopup_id'] ) ? absint( $settings['openpopup_id'] ) : 0;
			$settings['closepopup']   = ! empty( $settings['closepopup'] );
			$settings['closedelay']   = ! empty( $settings['closedelay'] ) ? absint( $settings['closedelay'] ) : 0;

			update_option( 'gforms_pum_' . $form_id, $settings );
		} else {
			delete_option( 'gforms_pum_' . $form_id );
		}
	}
}

/**
 * Review
 *
 * This should be reviewed.
 *
 * add_action( 'gform_loaded', array( 'PUM_Gravity_Forms_Integration', 'load' ), 5 );
 *
 * class PUM_Gravity_Forms_Integration {
 *
 * public static function load() {
 * if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
 * return;
 * }
 * require_once 'gravity-forms/class-pum-gf-popup-addon.php';
 * GFAddOn::register( 'PUM_GF_Popup_Addon' );
 * }
 * }
 *
 * function pum_gf_addon() {
 * return PUM_GF_Popup_Addon::get_instance();
 * }
 */
