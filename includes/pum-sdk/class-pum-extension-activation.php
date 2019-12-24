<?php
/**
 * Activation handler
 *
 * @package     PUM\SDK\ActivationHandler
 * @since       1.0.0
 * @copyright	Copyright (c) 2019, Code Atlantic LLC
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


/**
 * Popup Maker Extension Activation Handler Class
 *
 * @since       1.0.0
 */
class PUM_Extension_Activation {

    public $plugin_name, $plugin_path, $plugin_file, $has_popmake, $popmake_base;

    /**
     * Setup the activation class
     *
     * @access      public
     * @since       1.0.0
     *
     * @param $plugin_path
     * @param $plugin_file
     */
    public function __construct( $plugin_path, $plugin_file ) {
        // We need plugin.php!
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        $plugins = get_plugins();

        // Set plugin directory
        $plugin_path = array_filter( explode( '/', $plugin_path ) );
        $this->plugin_path = end( $plugin_path );

        // Set plugin file
        $this->plugin_file = $plugin_file;

        // Set plugin name
        if ( isset( $plugins[ $this->plugin_path . '/' . $this->plugin_file ]['Name'] ) ) {
            $this->plugin_name = str_replace( 'Popup Maker - ', '', $plugins[ $this->plugin_path . '/' . $this->plugin_file ]['Name'] );
        } else {
            $this->plugin_name = __( 'This plugin', 'popup-maker' );
        }

        // Is Popup Maker installed?
        foreach( $plugins as $plugin_path => $plugin ) {
            if( $plugin['Name'] == 'Popup Maker' ) {
                $this->has_popmake = true;
                $this->popmake_base = $plugin_path;
                break;
            }
        }
    }


    /**
     * Process plugin deactivation
     *
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function run() {
        // Display notice
        add_action( 'admin_notices', array( $this, 'missing_popmake_notice' ) );
    }


    /**
     * Display notice if Popup Maker isn't installed
     */
    public function missing_popmake_notice() {
        if( $this->has_popmake ) {
            $url  = esc_url( wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . $this->popmake_base ), 'activate-plugin_' . $this->popmake_base ) );
            $link = '<a href="' . $url . '">' . __( 'activate it', 'popmake-extension-activation' ) . '</a>';
        } else {
            $url  = esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=popup-maker' ), 'install-plugin_popup-maker' ) );
            $link = '<a href="' . $url . '">' . __( 'install it', 'popmake-extension-activation' ) . '</a>';
        }
        
        echo '<div class="error"><p>' . $this->plugin_name . sprintf( __( ' requires Popup Maker! Please %s to continue!', 'popmake-extension-activation' ), $link ) . '</p></div>';
    }
}
