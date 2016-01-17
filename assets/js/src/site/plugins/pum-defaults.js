/**
 * Defines the core $.popmake defaults.
 * Version 1.4.0
 */
(function ($) {
    "use strict";

    $.fn.popmake.defaults = {
        meta: {
            display: {
                stackable: 0,
                overlay_disabled: 0,
                size: 'medium',
                responsive_max_width: '',
                responsive_max_width_unit: '%',
                responsive_min_width: '',
                responsive_min_width_unit: '%',
                custom_width: '',
                custom_width_unit: '%',
                custom_height: '',
                custom_height_unit: 'em',
                custom_height_auto: 0,
                location: 'center top',
                position_top: 100,
                position_left: 0,
                position_bottom: 0,
                position_right: 0,
                position_fixed: 0,
                animation_type: 'fade',
                animation_speed: 350,
                animation_origin: 'center top'
            },
            close: {
                overlay_click: 0,
                esc_press: 0,
                f4_press: 0
            }
        },
        // TODO Remove these once extensions have all been updated.
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

}(jQuery));