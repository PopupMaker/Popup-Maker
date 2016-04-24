<?php
/**
 * Fields
 *
 * @package     PUM
 * @subpackage  Classes/Admin/Popups/PUM_Popup_Analytics_Metabox
 * @copyright   Copyright (c) 2016, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class PUM_Popup_Analytics_Metabox
 *
 * @since 1.4
 */
class PUM_Popup_Analytics_Metabox {

    /**
     * Initialize the needed actions & filters.
     */
    public static function init() {
        add_action( 'add_meta_boxes', array( __CLASS__, 'register_metabox' ) );
        //add_action( 'print_media_templates', array( __CLASS__, 'media_templates' ) );
        add_action( 'pum_save_popup', array( __CLASS__, 'save_popup' ) );
    }

    /**
     * Register the metabox for popup post type.
     *
     * @return void
     */
    public static function register_metabox() {
        add_meta_box( 'pum_popup_analytics', __( 'Analytics', 'popup-maker' ), array( __CLASS__, 'render_metabox' ), 'popup', 'side', 'high' );
    }

    /**
     * Display Metabox
     *
     * @return void
     */
    public static function render_metabox() {
        global $post;

        $popup = new PUM_Popup( $post->ID ); ?>
        <div id="pum-popup-analytics" class="pum-meta-box">

        <?php do_action( 'pum_popup_analytics_metabox_before', $post->ID ); ?>

        <div id="pum-popup-analytics" class="pum-popup-analytics">

            <table class="form-table">
                <tbody>
                <tr>
                    <td><?php _e( 'Opens', 'popup-maker' ); ?></td>
                    <td><?php echo $popup->get_open_count( 'current' ); ?></td>
                </tr>
                <tr class="separator">
                    <td colspan="2">
                        <label>
                            <input type="checkbox" name="popup_reset_open_count" id="popup_reset_open_count" value="1"/>
                            <?php _e( 'Reset Open Count', 'popup-maker' ); ?>
                        </label>
                        <?php if ( ( $reset = $popup->get_last_open_count_reset() ) ) : ?><br/>
                            <small>
                                <strong><?php _e( 'Last Reset', 'popup-maker' ); ?>:</strong> <?php echo date( 'm-d-Y H:i', $reset['timestamp'] ); ?>
                                <br/>
                                <strong><?php _e( 'Previous Opens', 'popup-maker' ); ?>:</strong> <?php echo $reset['count']; ?>
                                <br/>
                                <strong><?php _e( 'Lifetime Opens', 'popup-maker' ); ?>:</strong> <?php echo $popup->get_open_count( 'total' ); ?>
                            </small>
                        <?php endif; ?>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <?php do_action( 'pum_popup_analytics_metabox_after', $post->ID ); ?>
        </div><?php
    }

    /**
     * @param $post_id
     */
    public static function save_popup( $post_id ) {
        if ( isset( $_POST['popup_reset_open_count'] ) ) {

            /**
             * Reset popup open count, per user request.
             */
            $popup = new PUM_Popup( $post_id );
            $popup->reset_open_count();

        }
    }

    /**
     *
     */
    public static function media_templates() {

    }

}

PUM_Popup_Analytics_Metabox::init();
