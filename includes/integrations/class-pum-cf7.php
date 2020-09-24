<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_CF7_Integration
 */
class PUM_CF7_Integration {

	/**
	 * Initialize if CF7 is active.
	 */
	public static function init() {
		add_filter( 'pum_get_cookies', array( __CLASS__, 'register_cookies' ) );
		add_filter( 'wpcf7_editor_panels', array( __CLASS__, 'editor_panels' ) );
		add_action( 'wpcf7_after_save', array( __CLASS__, 'save' ) );
		add_filter( 'wpcf7_form_elements', array( __CLASS__, 'form_elements' ) );
		add_action( 'popmake_preload_popup', array( __CLASS__, 'preload' ) );
	}

	/**
	 * Check if the popups use CF7 Forms and force enqueue their assets.
	 *
	 * @param $popup_id
	 */
	public static function preload( $popup_id ) {
		$popup = pum_get_popup( $popup_id );

		if ( has_shortcode( $popup->post_content, 'contact-form-7' ) ) {
		    if ( defined( 'WPCF7_LOAD_JS' ) && ! WPCF7_LOAD_JS ) {
		        return;
            }

			if ( function_exists( 'wpcf7_enqueue_scripts' ) ) {
				wpcf7_enqueue_scripts();
			}

			if ( function_exists( 'wpcf7_enqueue_styles' ) ) {
				wpcf7_enqueue_styles();
			}
		}
	}

	/**
	 * Append a hidden meta html element with the forms popup settings.
	 *
	 * @param $elements
	 *
	 * @return string
	 */
	public static function form_elements( $elements ) {
		$form = wpcf7_get_current_contact_form();

		$settings = wp_json_encode( self::form_options( $form->id() ) );

		return $elements . "<input type='hidden' class='wpcf7-pum' value='$settings' />";
	}

	/**
	 * Get a specific forms options.
	 *
	 * @param $id
	 *
	 * @return array
	 */
	public static function form_options( $id ) {
		$settings = get_option( 'cf7_pum_' . $id, self::defaults() );

		return wp_parse_args( $settings, self::defaults() );
	}

	/**
	 * Get default values.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'closepopup'   => false,
			'closedelay'   => 0,
			'openpopup'    => false,
			'openpopup_id' => 0,
		);
	}

	/**
	 * Registers new cookie events.
	 *
	 * @param array $cookies
	 *
	 * @return array
	 */
	public static function register_cookies( $cookies = array() ) {
		$cookies['cf7_form_success'] = array(
			'labels' => array(
				'name' => __( 'Contact Form 7 Success (deprecated. Use Form Submission instead.)', 'popup-maker' ),
			),
			'fields' => pum_get_cookie_fields(),
		);

		return $cookies;
	}

	/**
	 * Register new CF7 form editor tab.
	 *
	 * @param array $panels
	 *
	 * @return array
	 */
	public static function editor_panels( $panels = array() ) {
		return array_merge( $panels, array(
			'popups' => array(
				'title'    => __( 'Popup Settings', 'popup-maker' ),
				'callback' => array( __CLASS__, 'editor_panel' ),
			),
		) );
	}

	/**
	 * Render the popup tab.
	 *
	 * @param object $args
	 */
	public static function editor_panel( $args ) {

		$settings = self::form_options( $args->id() ); ?>
        <h2><?php _e( 'Popup Settings', 'popup-maker' ); ?></h2>
        <p class="description"><?php _e( 'These settings control popups after successful form submissions.', 'popup-maker' ); ?></p>
        <table class="form-table">
            <tbody>
            <tr>
	            <th scope="row">
		            <label for="wpcf7-pum-closepopup"><?php _e( 'Close Popup', 'popup-maker' ); ?></label>
	            </th>
	            <td>
		            <input type="checkbox" id="wpcf7-pum-closepopup" name="wpcf7-pum[closepopup]" value="true" <?php checked( $settings['closepopup'], true ); ?> />
	            </td>
            </tr>
            <tr id="wpcf7-pum-closedelay-wrapper">
	            <th scope="row">
		            <label for="wpcf7-pum-closedelay"><?php _e( 'Delay', 'popup-maker' ); ?></label>
	            </th>
	            <td>
		            <?php if ( strlen( $settings['closedelay'] ) >= 3 ) {
			            $settings['closedelay'] = $settings['closedelay'] / 1000;
		            } ?>

		            <input type="number" id="wpcf7-pum-closedelay" min="0" step="1" name="wpcf7-pum[closedelay]" style="width: 100px;" value="<?php echo esc_attr( $settings['closedelay'] ); ?>" /><?php _e( 'seconds', 'popup-maker' ); ?>
	            </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wpcf7-pum-openpopup"><?php _e( 'Open Popup', 'popup-maker' ); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="wpcf7-pum-openpopup" name="wpcf7-pum[openpopup]" value="true" <?php checked( $settings['openpopup'], true ); ?> />
                </td>
            </tr>
            <tr id="wpcf7-pum-openpopup_id-wrapper">
                <th scope="row">
                    <label for="wpcf7-pum-openpopup_id"><?php _e( 'Popup', 'popup-maker' ); ?></label>
                </th>
                <td>
                    <select id="wpcf7-pum-openpopup_id" name="wpcf7-pum[openpopup_id]">
						<?php foreach ( self::get_popup_list() as $option ) { ?>
                            <option value="<?php echo esc_attr( $option['value'] ); ?>" <?php selected( $settings['openpopup_id'], $option['value'] ); ?>><?php echo $option['label']; ?></option>
						<?php } ?>
                    </select>
                </td>
            </tr>

            </tbody>
        </table>
        <script>
            (function ($) {
                var $open = $('#wpcf7-pum-openpopup'),
                    $close = $('#wpcf7-pum-closepopup'),
                    $popup_id_wrapper = $('#wpcf7-pum-openpopup_id-wrapper'),
                    $delay_wrapper = $('#wpcf7-pum-closedelay-wrapper');

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
		<?php
	}

	/**
	 * Get a list of popups for a select box.
	 *
	 * @return array
	 */
	public static function get_popup_list() {
		$popup_list = array(
			array(
				'value' => 0,
				'label' => __( 'Select a popup', 'popup-maker' ),
			),
		);

		$popups = get_posts( array(
			'post_type'      => 'popup',
			'post_status'    => array( 'publish' ),
			'posts_per_page' => - 1,
		) );

		foreach ( $popups as $popup ) {
			$popup_list[] = array(
				'value' => $popup->ID,
				'label' => $popup->post_title,
			);

		}

		return $popup_list;
	}

	/**
	 * Save form popup options.
	 *
	 * @param $args
	 */
	public static function save( $args ) {
		if ( ! empty( $_POST['wpcf7-pum'] ) ) {
			$settings = $_POST['wpcf7-pum'];

			// Sanitize values.
			$settings['openpopup']    = ! empty( $settings['openpopup'] );
			$settings['openpopup_id'] = ! empty( $settings['openpopup_id'] ) ? absint( $settings['openpopup_id'] ) : 0;
			$settings['closepopup']   = ! empty( $settings['closepopup'] );
			$settings['closedelay']   = ! empty( $settings['closedelay'] ) ? absint( $settings['closedelay'] ) : 0;

			update_option( 'cf7_pum_' . $args->id(), $settings );
		} else {
			delete_option( 'cf7_pum_' . $args->id() );
		}
	}
}
