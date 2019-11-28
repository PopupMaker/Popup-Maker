<?php

/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class PUM_Upgrades
 */
class PUM_Admin_Upgrades {

    /**
     * @var PUM_Admin_Upgrades The one true PUM_Admin_Upgrades
     */
    private static $instance;

    /**
     * @var $upgrade_args
     */
    public $upgrade_args = array();

    public $page = null;

    public $doing_upgrades = false;

    public $required_cap = 'manage_options';

    public $current_routine = null;

    public $next_routine = null;

    /**
     * Initialize the actions needed to process upgrades.
     */
    public static function instance() {
        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof PUM_Admin_Upgrades ) ) {
            self::$instance = new PUM_Admin_Upgrades;
            self::$instance->init();
        }

        return self::$instance;
    }

    /**
     * Initialize the actions needed to process upgrades.
     */
    public function init() {

        $this->update_plugin_version();

        $this->required_cap = apply_filters( 'pum_upgrade_required_cap', 'manage_options' );

        // bail if this plugin data doesn't need updating
        if ( pum_get_db_ver() >= Popup_Maker::$DB_VER ) {
            return;
        }

        add_action( 'admin_menu', array( $this, 'register_pages' ) );
        add_action( 'network_admin_menu', array( $this, 'register_pages' ) );

        add_action( 'admin_init', array( $this, 'process_upgrade_args' ) );

        add_action( 'wp_ajax_pum_trigger_upgrades', array( $this, 'trigger_upgrades' ) );
        add_action( 'admin_notices', array( $this, 'show_upgrade_notices' ) );
    }

    public function update_plugin_version() {

        $current_ver = get_option( 'pum_ver', false );

        if ( ! $current_ver ) {

            $deprecated_ver = get_site_option( 'popmake_version', false );

            $current_ver = $deprecated_ver ? $deprecated_ver : Popup_Maker::$VER;
            add_option( 'pum_ver', Popup_Maker::$VER );

        }

        if ( version_compare( $current_ver, Popup_Maker::$VER, '<' ) ) {
            // Save Upgraded From option
            update_option( 'pum_ver_upgraded_from', $current_ver );
            update_option( 'pum_ver', Popup_Maker::$VER );
        }

    }

    /**
     * Registers the pum-upgrades admin page.
     */
    public function register_pages() {
        global $pum_upgrades_page;

        $parent = null;

        /*
        if ( function_exists( 'is_network_admin' ) && is_network_admin() ) {
            add_menu_page(
                __( 'Popup Maker', 'popup-maker' ),
                __( 'Popup Maker', 'popup-maker' ),
                'manage_network_plugins',
                'popup-maker',
                '',
                'data:image/svg+xml;base64,' . base64_encode('<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid meet" viewBox="0 0 16 16" width="16" height="16"><defs><path d="M0.14 9.69C0.99 13.84 4.65 14.46 6.18 14.29C7.71 14.12 9.36 13.37 10.42 12.42C11.48 11.47 12.18 10.98 13.3 10.13C14.42 9.29 15.28 8.24 15.57 7.78C15.86 7.32 16.37 5.41 15.57 3.71C14.76 2.02 12.92 1.69 11.88 1.7C10.84 1.72 5.83 2.42 4.19 2.76C2.54 3.1 -0.71 5.54 0.14 9.69Z" id="d2cKlvMGiG"></path><clipPath id="clipe1dqYdHPtO"><use xlink:href="#d2cKlvMGiG" opacity="1"></use></clipPath><path d="M9.35 8.59C9.35 10.57 7.82 12.19 5.95 12.19C4.07 12.19 2.54 10.57 2.54 8.59C2.54 6.6 4.07 4.99 5.95 4.99C7.82 4.99 9.35 6.6 9.35 8.59Z" id="cdfsLVOC"></path><clipPath id="clipb7EwfuaVEB"><use xlink:href="#cdfsLVOC" opacity="1"></use></clipPath><path d="M9.04 9.39L8.35 10.9L9.11 11.66C9.12 11.66 9.12 11.66 9.12 11.66C10.01 10.71 10.41 9.4 10.19 8.11C10.19 8.09 10.18 8.04 10.16 7.95L9.11 8.01L9.04 9.39Z" id="apNChgYiM"></path><path d="M3.74 11.08L2.82 9.69L1.78 9.94C1.78 9.94 1.78 9.94 1.78 9.94C2.1 11.2 3 12.24 4.2 12.74C4.23 12.75 4.28 12.77 4.36 12.8L4.87 11.89L3.74 11.08Z" id="a767A2LuY"></path><path d="M4.96 5.33L6.62 5.17L6.89 4.14C6.89 4.14 6.89 4.14 6.89 4.14C5.62 3.83 4.29 4.14 3.28 4.96C3.26 4.97 3.22 5.01 3.15 5.07L3.72 5.95L4.96 5.33Z" id="bhrbq8GS0"></path><path d="M14.12 5.67C14.12 6.78 13.27 7.68 12.23 7.68C11.19 7.68 10.34 6.78 10.34 5.67C10.34 4.57 11.19 3.67 12.23 3.67C13.27 3.67 14.12 4.57 14.12 5.67Z" id="d2hpPQXk8"></path><clipPath id="clipaHD8R803"><use xlink:href="#d2hpPQXk8" opacity="1"></use></clipPath><path d="M12.51 2.83L13.24 3.05L13.02 4.27L12.02 3.98L12.51 2.83Z" id="d2kQRXgvan"></path><path d="M14.65 4.57L14.87 5.29L13.73 5.78L13.42 4.79L14.65 4.57Z" id="e4K79bJBs4"></path><path d="M14.34 7.35L13.77 7.85L12.85 7.02L13.62 6.33L14.34 7.35Z" id="aedMpv11P"></path><path d="M11.95 8.41L11.2 8.27L11.3 7.03L12.32 7.22L11.95 8.41Z" id="gurBgUNT"></path><path d="M9.69 6.69L9.58 5.95L10.78 5.62L10.95 6.64L9.69 6.69Z" id="baWqWWOLp"></path><path d="M10.05 3.9L10.62 3.4L11.54 4.24L10.76 4.93L10.05 3.9Z" id="aEHM8bjkQ"></path></defs><g><g><g><g clip-path="url(#clipe1dqYdHPtO)"><use xlink:href="#d2cKlvMGiG" opacity="1" fill-opacity="0" stroke="black" stroke-width="1.2" stroke-opacity="1"></use></g></g><g><g><g clip-path="url(#clipb7EwfuaVEB)"><use xlink:href="#cdfsLVOC" opacity="1" fill-opacity="0" stroke="black" stroke-width="1.6" stroke-opacity="1"></use></g></g><g><use xlink:href="#apNChgYiM" opacity="1" fill="black" fill-opacity="1"></use><g><use xlink:href="#apNChgYiM" opacity="1" fill-opacity="0" stroke="black" stroke-width="1" stroke-opacity="1"></use></g></g><g><use xlink:href="#a767A2LuY" opacity="1" fill="black" fill-opacity="1"></use><g><use xlink:href="#a767A2LuY" opacity="1" fill-opacity="0" stroke="black" stroke-width="1" stroke-opacity="1"></use></g></g><g><use xlink:href="#bhrbq8GS0" opacity="1" fill="black" fill-opacity="1"></use><g><use xlink:href="#bhrbq8GS0" opacity="1" fill-opacity="0" stroke="black" stroke-width="1" stroke-opacity="1"></use></g></g></g><g><g><g clip-path="url(#clipaHD8R803)"><use xlink:href="#d2hpPQXk8" opacity="1" fill-opacity="0" stroke="black" stroke-width="2.8" stroke-opacity="1"></use></g></g><g><use xlink:href="#d2kQRXgvan" opacity="1" fill="black" fill-opacity="1"></use></g><g><use xlink:href="#e4K79bJBs4" opacity="1" fill="black" fill-opacity="1"></use></g><g><use xlink:href="#aedMpv11P" opacity="1" fill="black" fill-opacity="1"></use></g><g><use xlink:href="#gurBgUNT" opacity="1" fill="black" fill-opacity="1"></use></g><g><use xlink:href="#baWqWWOLp" opacity="1" fill="black" fill-opacity="1"></use></g><g><use xlink:href="#aEHM8bjkQ" opacity="1" fill="black" fill-opacity="1"></use></g></g></g></g></svg>')
            );
            $parent = 'popup-maker';
        }
        */

        $this->page = add_submenu_page(
            $parent,
            __( 'Popup Maker Upgrades', 'popup-maker' ),
            __( 'Popup Maker Upgrades', 'popup-maker' ),
            $this->required_cap,
            'pum-upgrades',
            array( $this, 'upgrades_screen' )
        );

        $pum_upgrades_page = $this->page;
    }

    /**
     * Process upgrade args.
     */
    public function process_upgrade_args() {

        $page = isset( $_GET['page'] ) ? $_GET['page'] : '';

        if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX && $_REQUEST['action'] == 'pum_trigger_upgrades' ) && $page != 'pum-upgrades' ) {
            return;
        }

        $this->doing_upgrades = true;

        $action    = isset( $_REQUEST['pum-upgrade'] ) ? sanitize_text_field( $_REQUEST['pum-upgrade'] ) : $this->get_pum_db_ver() + 1;
        $step      = isset( $_REQUEST['step'] ) ? absint( $_REQUEST['step'] ) : 1;
        $total     = isset( $_REQUEST['total'] ) ? absint( $_REQUEST['total'] ) : false;
        $custom    = isset( $_REQUEST['custom'] ) ? absint( $_REQUEST['custom'] ) : 0;
        $number    = isset( $_REQUEST['number'] ) ? absint( $_REQUEST['number'] ) : 100;
        $completed = isset( $_REQUEST['completed'] ) ? absint( $_REQUEST['completed'] ) : false;
        $steps     = ceil( $total / $number );

        if ( $step > $steps ) {
            // Prevent a weird case where the estimate was off. Usually only a couple.
            $steps = $step;
        }

        $this->upgrade_args = array(
                'page'        => 'pum-upgrades',
                'pum-upgrade' => $action,
                'step'        => $step,
                'total'       => $total,
                'custom'      => $custom,
                'steps'       => $steps,
                'number'      => $number,
                'completed'   => $completed,
        );
        update_option( 'pum_doing_upgrade', $this->upgrade_args );

    }

    /**
     * Get upgrade arg.
     *
     * @param string $key
     *
     * @return bool|null
     */
    public function set_arg( $key, $value = null ) {

        $this->upgrade_args[ $key ] = $value;
        if ( $key == 'number' || $key == 'total' ) {
            $this->upgrade_args['steps'] = ceil( $this->upgrade_args['total'] / $this->upgrade_args['number'] );
        }
        if ( $this->upgrade_args['step'] > $this->upgrade_args['steps'] ) {
            // Prevent a weird case where the estimate was off. Usually only a couple.
            $this->upgrade_args['steps'] = $this->upgrade_args['step'];
        } elseif ( $this->upgrade_args['step'] * $this->upgrade_args['steps'] ) {
            update_option( 'pum_doing_upgrade', $this->upgrade_args );
        }

    }

    /**
     * Get upgrade arg.
     *
     * @param string $key
     *
     * @return bool|null
     */
    public function get_arg( $key = null ) {

        if ( ! $key ) {
            return null;
        }

        if ( ! isset( $this->upgrade_args[ $key ] ) ) {
            return false;
        }

        return $this->upgrade_args[ $key ];

    }

    public function get_args() {
        return $this->upgrade_args;
    }

    public function doing_upgrades() {
        return $this->doing_upgrades;
    }

    /**
     * Display Upgrade Notices
     *
     * @return void
     */
    public function show_upgrade_notices() {

        $screen = get_current_screen();

        if ( $screen->id == $this->page ) {
            return; // Don't show notices on the upgrades page
        }

        if ( ! $this->has_upgrades() ) {
            return;
        }

        // Sequential Orders was the first stepped upgrade, so check if we have a stalled upgrade
        $resume_upgrade = $this->maybe_resume_upgrade();

        if ( ! empty( $resume_upgrade ) ) {

            $resume_url = add_query_arg( $resume_upgrade, admin_url( 'index.php' ) );
            printf(
                    '<div class="error"><p>' . __( 'Popup Maker needs to complete a database upgrade that was previously started, click <a href="%s">here</a> to resume the upgrade.', 'popup-maker' ) . '</p></div>',
                    esc_url( $resume_url )
            );

        } else {

            printf(
                    '<div class="error"><p><strong>%s:</strong> <span class="dashicons dashicons-warning" style="color: #dc3232;"></span> %s %s %s</p></div>',
                    __( 'Popup Maker', 'popup-maker' ),
                    __( 'Important', 'popup-maker' ),
                    __( 'Database upgrades required.', 'popup-maker' ),
                    sprintf(
                            __( 'Please click %shere%s to complete these changes now.', 'popup-maker' ),
                            '<a href="' . esc_url( admin_url( 'options.php?page=pum-upgrades' ) ) . '">',
                            '</a>'
                    )
            );

        }

    }

    /**
     * Triggers all upgrade functions
     *
     * This function is usually triggered via AJAX
     *
     * @return void
     */
    public function trigger_upgrades() {

        if ( ! current_user_can( $this->required_cap ) ) {
            wp_die( __( 'You do not have permission to do upgrades', 'popup-maker' ), __( 'Error', 'popup-maker' ), array( 'response' => 403 ) );
        }

        $deprecated_ver = get_site_option( 'popmake_version', false );
        $current_ver    = get_option( 'pum_ver', $deprecated_ver );

        // Save Upgraded From option
        if ( $current_ver ) {
            update_option( 'pum_ver_upgraded_from', $current_ver );
        }

        update_option( 'pum_ver', Popup_Maker::$VER );

        // Process DB Upgrades
        $this->process_upgrades();

        if ( DOING_AJAX ) {
            echo wp_json_encode( array(
                    'complete'  => true,
                    'status'    => sprintf(
                            '<strong>%s</strong><br/>%s',
                            __( 'Upgrades have been completed successfully.', 'popup-maker' ),
                            sprintf( 'You will automatically be redirected in %s seconds', '<span id="pum-countdown">5</span>' )
                    ),
                    'redirect'  => admin_url( 'edit.php?post_type=popup' ),
                    'countdown' => 5000,
            ) ); // Let AJAX know that the upgrade is complete
            exit;
        }
    }

    /**
     * Updates the pum_db_ver to the passed $version.
     *
     * If no $version is passed a default value will be established.
     *
     * @param null $version
     */
    public function set_pum_db_ver( $version = null ) {

        if ( $version ) {
            $version = preg_replace( '/[^0-9.].*/', '', $version );
            update_option( 'pum_db_ver', $version );

            return;
        }

        $upgraded_from = get_option( 'pum_ver_upgraded_from', false );

        // this is the current database schema version number
        $current_db_ver = pum_get_db_ver();

        // If no current db version, but prior install detected, set db version correctly.
        if ( ! $current_db_ver ) {
            if ( $upgraded_from ) {
                if ( version_compare( $upgraded_from, '1.3.0', '<' ) ) {
                    $current_db_ver = 1;
                } else {
                    $current_db_ver = 2;
                }
            } else {
                $current_db_ver = Popup_Maker::$DB_VER;
            }
            add_option( 'pum_db_ver', $current_db_ver );
        }

    }

    /**
     * Gets the pum_db_ver or sets and returns the correct one.
     *
     * @see PUM_Utils_Upgrades::set_pum_db_ver()
     *
     * return $pum_db_ver
     */
    public function get_pum_db_ver() {

    	static $pum_db_ver;

    	if ( ! isset( $pum_db_ver ) ) {
		    // this is the current database schema version number
		    $pum_db_ver = pum_get_db_ver();
	    }

        if ( ! $pum_db_ver ) {
            $this->set_pum_db_ver();
            $pum_db_ver = pum_get_db_ver();
        }

        return preg_replace( '/[^0-9.].*/', '', $pum_db_ver );
    }

    /**
     * Process upgrades in a stepped succession.
     *
     * Starts with the current version and loops until reaching the target version.
     */
    public function process_upgrades() {

        // this is the target version that we need to reach
        $target_db_ver = Popup_Maker::$DB_VER;

        // this is the current database schema version number
        $current_db_ver = $this->get_pum_db_ver();

        // Run upgrade routine until target version reached.
        while ( $current_db_ver < $target_db_ver ) {

            // increment the current db_ver by one
            $current_db_ver ++;

            $this->current_routine = $current_db_ver;

            $this->next_routine = $current_db_ver == $target_db_ver ? null : $current_db_ver + 1;

            if ( file_exists( POPMAKE_DIR . "includes/admin/upgrades/class-pum-admin-upgrade-routine-{$current_db_ver}.php" ) ) {

                require_once POPMAKE_DIR . "includes/admin/upgrades/class-pum-admin-upgrade-routine-{$current_db_ver}.php";

                $func = "PUM_Admin_Upgrade_Routine_{$current_db_ver}::run";
                if ( is_callable( $func ) ) {
                    call_user_func( $func );
                }

            }

        }

    }

    public function current_routine() {
        return $this->current_routine;
    }

    public function next_routine() {
        return $this->next_routine;
    }

    /**
     * Process upgrades in a stepped succession.
     *
     * Starts with the current version and loops until reaching the target version.
     */
    public function get_upgrades() {

        // this is the target version that we need to reach
        $target_db_ver = Popup_Maker::$DB_VER;

        // this is the current database schema version number
        $current_db_ver = $this->get_pum_db_ver();

        $upgrades = array();

        // Run upgrade routine until target version reached.
        while ( $current_db_ver < $target_db_ver ) {

            // increment the current db_ver by one
            $current_db_ver ++;

            if ( file_exists( POPMAKE_DIR . "includes/admin/upgrades/class-pum-admin-upgrade-routine-{$current_db_ver}.php" ) ) {

                require_once POPMAKE_DIR . "includes/admin/upgrades/class-pum-admin-upgrade-routine-{$current_db_ver}.php";

                $func = "PUM_Admin_Upgrade_Routine_{$current_db_ver}::description";
                if ( is_callable( $func ) ) {
                    $upgrades[ $current_db_ver ] = call_user_func( $func );
                }

            }

        }

        return $upgrades;
    }

    public function get_upgrade( $version = null ) {
        $upgrades = $this->get_upgrades();
        if ( isset ( $upgrades[ $version ] ) ) {
            return $upgrades[ $version ];
        } else {
            return false;
        }
    }

    /**
     * Returns true if there are unprocessed upgrades.
     *
     * @return bool
     */
    public function has_upgrades() {
        return boolval( count( $this->get_upgrades() ) );
    }

    /**
     * For use when doing 'stepped' upgrade routines, to see if we need to start somewhere in the middle
     *
     * @return mixed   When nothing to resume returns false, otherwise starts the upgrade where it left off
     */
    public function maybe_resume_upgrade() {

        $doing_upgrade = get_option( 'pum_doing_upgrade', false );

        if ( empty( $doing_upgrade ) ) {
            return false;
        }

        return $doing_upgrade;

    }

    /**
     * Adds an upgrade action to the completed upgrades array
     *
     * @param  string $upgrade_action The action to add to the competed upgrades array
     *
     * @return bool If the function was successfully added
     */
    public function set_upgrade_complete( $upgrade_action = '' ) {

        if ( empty( $upgrade_action ) ) {
            return false;
        }

        $completed_upgrades   = $this->get_completed_upgrades();
        $completed_upgrades[] = $upgrade_action;

        // Remove any blanks, and only show uniques
        $completed_upgrades = array_unique( array_values( $completed_upgrades ) );

        return update_option( 'pum_completed_upgrades', $completed_upgrades );
    }

    /**
     * Check if the upgrade routine has been run for a specific action
     *
     * @param  string $upgrade_action The upgrade action to check completion for
     *
     * @return bool                   If the action has been added to the copmleted actions array
     */
    public function has_upgrade_completed( $upgrade_action = '' ) {

        if ( empty( $upgrade_action ) ) {
            return false;
        }

        $completed_upgrades = $this->get_completed_upgrades();

        return in_array( $upgrade_action, $completed_upgrades );

    }

    /**
     * Get's the array of completed upgrade actions
     *
     * @return array The array of completed upgrades
     */
    public function get_completed_upgrades() {

        $completed_upgrades = get_option( 'pum_completed_upgrades' );

        if ( false === $completed_upgrades ) {
            $completed_upgrades = array();
        }

        return $completed_upgrades;

    }

    public function step_up() {
        $step = $this->upgrade_args['step'];
        if ( $step >= $this->upgrade_args['steps'] ) {
            $this->upgrade_args['step'] = $this->upgrade_args['steps'];

            return false;
        }
        $this->upgrade_args['step'] ++;

        return true;
    }

    /**
     * Renders the upgrades screen.
     */
    public function upgrades_screen() { ?>
        <div class="wrap">
            <h2>
                <?php _e( 'Popup Maker - Upgrades', 'popup-maker' ); ?>
                <img src="<?php echo POPMAKE_URL . '/assets/images/admin/loading.gif'; ?>" id="pum-upgrade-loader"/>
            </h2>

            <style>
                #pum-upgrade-status {
                    max-height: 300px;
                    background: #fff;
                    box-shadow: inset 0 1px 1px rgba(0, 0, 0, .5);
                    overflow-y: scroll;
                    text-overflow: ellipsis;
                    padding: 0 1.5em;
                }
            </style>
            <p>
                <?php _e( 'The upgrade process has started, please be patient. This could take several minutes. You will be automatically redirected when the upgrade is finished.', 'popup-maker' ); ?>
            </p>
            <div id="pum-upgrade-status"></div>
            <script type="text/javascript">
                (function ($, document, undefined) {
                    var $loader = $('#pum-upgrade-loader').hide(),
                            $status_box = $('#pum-upgrade-status'),
                            $timer,
                            timer = 500;

                    function update_status(message) {
                        $('<p>')
                                .html(message)
                                .appendTo($status_box);

                        $status_box.animate({
                            scrollTop: $status_box.get(0).scrollHeight
                        }, {
                            duration: 'slow',
                            queue: false
                        });
                    }

                    function countdown(timer, callback) {
                        var time_left = timer - 1000;
                        if (time_left >= 0) {
                            setTimeout(function () {
                                $timer.text(time_left / 1000);
                                countdown(time_left, callback);
                            }, 1000);
                        } else {
                            callback();
                        }
                    }

                    function next_step(args) {

                        $loader.show();

                        if (args === undefined) {
                            args = {};
                        }

                        $.ajax({
                                    url: ajaxurl,
                                    data: $.extend({action: 'pum_trigger_upgrades'}, args),
                                    type: 'GET',
                                    dataType: 'json'
                                })
                                .done(function (response) {

                                    if (response.status !== undefined) {
                                        update_status(response.status);
                                    }

                                    if (response.complete !== undefined) {
                                        $loader.hide();
                                    } else if (response.next !== undefined && typeof response.next === 'object') {
                                        next_step(response.next);
                                    }

                                    if (response.redirect !== undefined) {
                                        if (response.countdown === undefined) {
                                            setTimeout(function () {
                                                document.location.href = response.redirect;
                                            }, timer);
                                        } else {
                                            $timer = $('#pum-countdown');
                                            countdown(response.countdown, function () {
                                                document.location.href = response.redirect;
                                            });
                                        }
                                    }
                                })
                                .fail(function () {
                                    update_status("<?php _e( 'Upgrade failed, please try again.', 'popup-maker' ); ?>");
                                });
                    }

                    $(document).ready(function () {
                        // Trigger upgrades on page load
                        next_step(<?php echo wp_json_encode( $this->get_args() ); ?>);
                        update_status('<?php printf( '<strong>%s</strong>', $this->get_upgrade( $this->get_arg( 'pum-upgrade' ) ) ); ?>');
                    });
                }(jQuery, document));
            </script>

        </div>
        <?php
    }

}

PUM_Admin_Upgrades::instance();
