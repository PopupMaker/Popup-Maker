/**
 * Defines the core $.popmake defaults.
 * Version 1.4
 */
(function ($, document, undefined) {
    "use strict";

    $.fn.popmake.defaults = {
        id: null,
        slug: "",
        theme_id: null,
        cookies: [],
        triggers: [],
        conditions: [],
        mobile_disabled: null,
        tablet_disabled: null,
        custom_height_auto: false,
        scrollable_content: false,
        position_from_trigger: false,
        position_fixed: false,
        overlay_disabled: false,
        stackable: false,
        disable_reposition: false,
        close_on_overlay_click: false,
		close_on_form_submission: false,
		close_on_form_submission_delay: 0,
        close_on_esc_press: false,
        close_on_f4_press: false,
        disable_on_mobile: false,
        disable_on_tablet: false,
        size: "medium",
        responsive_min_width: "0%",
        responsive_max_width: "100%",
        custom_width: "640px",
        custom_height: "380px",
        animation_type: "fade",
        animation_speed: "350",
        animation_origin: "center top",
        location: "center top",
        position_top: "100",
        position_bottom: "0",
        position_left: "0",
        position_right: "0",
        zindex: "1999999999",
        close_button_delay: "0",
        // TODO Remove these once extensions have all been updated.
        meta: {
            display: {
                stackable: false,
                overlay_disabled: false,
                size: "medium",
                responsive_max_width: "100",
                responsive_max_width_unit: '%',
                responsive_min_width: "0",
                responsive_min_width_unit: '%',
                custom_width: "640",
                custom_width_unit: 'px',
                custom_height: "380",
                custom_height_unit: 'px',
                custom_height_auto: false,
                location: "center top",
                position_top: 100,
                position_left: 0,
                position_bottom: 0,
                position_right: 0,
                position_fixed: false,
                animation_type: 'fade',
                animation_speed: 350,
                animation_origin: 'center top',
                scrollable_content: false,
                disable_reposition: false,
                position_from_trigger: false,
                overlay_zindex: false,
                zindex: "1999999999"
            },
            close: {
                overlay_click: false,
                esc_press: false,
                f4_press: false,
                text: "",
                button_delay: 0
            },
            click_open: []
        },
        container: {
            active_class: 'active',
            attr: {
                class: "popmake"
            }
        },
        title: {
            attr: {
                class: "popmake-title"
            }
        },
        content: {
            attr: {
                class: "popmake-content"
            }
        },
        close: {
            close_speed: 0,
            attr: {
                class: "popmake-close"
            }
        },
        overlay: {
            attr: {
                id: "popmake-overlay",
                class: "popmake-overlay"
            }
        }
    };

}(jQuery, document));
