var pum_debug_mode = false,
    pum_debug;
(function ($, pum_vars) {

    pum_vars = window.pum_vars || {
        debug_mode: false
    };

    pum_debug_mode = pum_vars.debug_mode !== undefined && pum_vars.debug_mode;

    // Force Debug Mode when the ?pum_debug query arg is present.
    if (!pum_debug_mode && window.location.href.indexOf('pum_debug') !== -1) {
        pum_debug_mode = true;
    }

    if (pum_debug_mode) {

        var inited = false,
            current_popup_event = false,
            vars = window.pum_debug_vars || {
                'debug_mode_enabled': 'Popup Maker: Debug Mode Enabled',
                'debug_started_at': 'Debug started at:',
                'debug_more_info': 'For more information on how to use this information visit https://docs.wppopupmaker.com/?utm_medium=js-debug-info&utm_campaign=ContextualHelp&utm_source=browser-console&utm_content=more-info',
                'global_info': 'Global Information',
                'localized_vars': 'Localized variables',
                'popups_initializing': 'Popups Initializing',
                'popups_initialized': 'Popups Initialized',
                'single_popup_label': 'Popup: #',
                'theme_id': 'Theme ID: ',
                'label_method_call': 'Method Call:',
                'label_method_args': 'Method Arguments:',
                'label_popup_settings': 'Settings',
                'label_triggers': 'Triggers',
                'label_cookies': 'Cookies',
                'label_delay': 'Delay:',
                'label_conditions': 'Conditions',
                'label_cookie': 'Cookie:',
                'label_settings': 'Settings:',
                'label_selector': 'Selector:',
                'label_mobile_disabled': 'Mobile Disabled:',
                'label_tablet_disabled': 'Tablet Disabled:',
                'label_event': 'Event: %s',
                'triggers': [],
                'cookies': []
            };

        pum_debug = {
            odump: function (o) {
                return $.extend({}, o);
            },
            logo: function () {
                console.log("" +
                    " -------------------------------------------------------------" + '\n' +
                    "|  ____                           __  __       _              |" + '\n' +
                    "| |  _ \\ ___  _ __  _   _ _ __   |  \\/  | __ _| | _____ _ __  |" + '\n' +
                    "| | |_) / _ \\| '_ \\| | | | '_ \\  | |\\/| |/ _` | |/ / _ \\ '__| |" + '\n' +
                    "| |  __/ (_) | |_) | |_| | |_) | | |  | | (_| |   <  __/ |    |" + '\n' +
                    "| |_|   \\___/| .__/ \\__,_| .__/  |_|  |_|\\__,_|_|\\_\\___|_|    |" + '\n' +
                    "|            |_|         |_|                                  |" + '\n' +
                    " -------------------------------------------------------------"
                );
            },
            initialize: function () {
                inited = true;

                // Clear Console
                //console.clear();

                // Render Logo
                pum_debug.logo();

                console.debug(vars.debug_mode_enabled);
                console.log(vars.debug_started_at, new Date());
                console.info(vars.debug_more_info);

                // Global Info Divider
                pum_debug.divider(vars.global_info);

                // Localized Variables
                console.groupCollapsed(vars.localized_vars);
                console.log('pum_vars:', pum_debug.odump(pum_vars));
                $(document).trigger('pum_debug_initialize_localized_vars');
                console.groupEnd();

                // Trigger to add more debug info from extensions.
                $(document).trigger('pum_debug_initialize');
            },
            popup_event_header: function ($popup) {
                var settings = $popup.popmake('getSettings');


                if (current_popup_event === settings.id) {
                    return;
                }

                current_popup_event = settings.id;
                pum_debug.divider(vars.single_popup_label + settings.id + ' - ' + settings.slug);
            },
            divider: function (heading) {
                var totalWidth = 62,
                    extraSpace = 62,
                    padding = 0,
                    line = " " + new Array(totalWidth + 1).join("-") + " ";

                if (typeof heading === 'string') {
                    extraSpace = totalWidth - heading.length;
                    padding = {
                        left: Math.floor(extraSpace / 2),
                        right: Math.floor(extraSpace / 2)
                    };

                    if (padding.left + padding.right === extraSpace - 1) {
                        padding.right++;
                    }

                    padding.left = new Array(padding.left + 1).join(" ");
                    padding.right = new Array(padding.right + 1).join(" ");

                    console.log("" +
                        line + '\n' +
                        "|" + padding.left + heading + padding.right + "|" + '\n' +
                        line
                    );
                } else {
                    console.log(line);
                }
            },
            click_trigger: function ($popup, trigger_settings) {
                var settings = $popup.popmake('getSettings'),
                    trigger_selectors = [
                        '.popmake-' + settings.id,
                        '.popmake-' + decodeURIComponent(settings.slug),
                        'a[href$="#popmake-' + settings.id + '"]'
                    ],
                    trigger_selector;

                if (trigger_settings.extra_selectors && trigger_settings.extra_selectors !== '') {
                    trigger_selectors.push(trigger_settings.extra_selectors);
                }

                trigger_selectors = pum.hooks.applyFilters('pum.trigger.click_open.selectors', trigger_selectors, trigger_settings, $popup);

                trigger_selector = trigger_selectors.join(', ');

                console.log(vars.label_selector, trigger_selector);
            },
            trigger: function ($popup, trigger) {
                if (typeof vars.triggers[trigger.type] === 'string') {

                    console.groupCollapsed(vars.triggers[trigger.type]);

                    switch (trigger.type) {
                    case 'auto_open':
                        console.log(vars.label_delay, trigger.settings.delay);
                        console.log(vars.label_cookie, trigger.settings.cookie_name);
                        break;
                    case 'click_open':
                        pum_debug.click_trigger($popup, trigger.settings);
                        console.log(vars.label_cookie, trigger.settings.cookie_name);
                        break;
                    }

                    $(document).trigger('pum_debug_render_trigger', $popup, trigger);

                    console.groupEnd();
                }
            },
            cookie: function ($popup, cookie) {
                if (typeof vars.cookies[cookie.event] === 'string') {
                    console.groupCollapsed(vars.cookies[cookie.event]);

                    switch (cookie.event) {
                    case 'on_popup_open':
                    case 'on_popup_close':
                    case 'manual':
                    case 'ninja_form_success':
                        console.log(vars.label_cookie, pum_debug.odump(cookie.settings));
                        break;
                    }

                    $(document).trigger('pum_debug_render_trigger', $popup, cookie);

                    console.groupEnd();
                }
            }
        };

        $(document)
            .on('pumInit', '.pum', function () {
                var $popup = PUM.getPopup($(this)),
                    settings = $popup.popmake('getSettings'),
                    triggers = settings.triggers || [],
                    cookies = settings.cookies || [],
                    conditions = settings.conditions || [],
                    i = 0;

                if (!inited) {
                    pum_debug.initialize();
                    pum_debug.divider(vars.popups_initializing);
                }

                console.groupCollapsed(vars.single_popup_label + settings.id + ' - ' + settings.slug);

                // Popup Theme ID
                console.log(vars.theme_id, settings.theme_id);

                // Triggers
                if (triggers.length) {
                    console.groupCollapsed(vars.label_triggers);
                    for (i = 0; triggers.length > i; i++) {
                        pum_debug.trigger($popup, triggers[i]);
                    }
                    console.groupEnd();
                }

                // Cookies
                if (cookies.length) {
                    console.groupCollapsed(vars.label_cookies);
                    for (i = 0; cookies.length > i; i += 1) {
                        pum_debug.cookie($popup, cookies[i]);
                    }
                    console.groupEnd();
                }

                // Conditions
                if (conditions.length) {
                    console.groupCollapsed(vars.label_conditions);
                    console.log(conditions);
                    console.groupEnd();
                }

                console.groupCollapsed(vars.label_popup_settings);


                // Mobile Disabled.
                console.log(vars.label_mobile_disabled, settings.disable_on_mobile !== false);

                // Tablet Disabled.
                console.log(vars.label_tablet_disabled, settings.disable_on_tablet !== false);

                // Settings.
                console.log(vars.label_display_settings, pum_debug.odump(settings));

                // Trigger to add more debug info from extensions.
                $popup.trigger('pum_debug_popup_settings');

                console.groupEnd();

                console.groupEnd();

            })
            .on('pumBeforeOpen', '.pum', function () {
                var $popup = PUM.getPopup($(this)),
                    $last_trigger = $.fn.popmake.last_open_trigger;

                pum_debug.popup_event_header($popup);

                console.groupCollapsed(vars.label_event.replace('%s', 'pumBeforeOpen'));

                try {
                    $last_trigger = $($.fn.popmake.last_open_trigger);
                    $last_trigger = $last_trigger.length ? $last_trigger : $.fn.popmake.last_open_trigger.toString();
                } catch (error) {
                    $last_trigger = "";
                } finally {
                    console.log(vars.label_triggers, [$last_trigger]);
                }

                console.groupEnd();
            })
            .on('pumOpenPrevented', '.pum', function () {
                var $popup = PUM.getPopup($(this));

                pum_debug.popup_event_header($popup);

                console.groupCollapsed(vars.label_event.replace('%s', 'pumOpenPrevented'));

                console.groupEnd();
            })
            .on('pumAfterOpen', '.pum', function () {
                var $popup = PUM.getPopup($(this));

                pum_debug.popup_event_header($popup);

                console.groupCollapsed(vars.label_event.replace('%s', 'pumAfterOpen'));

                console.groupEnd();
            })
            .on('pumSetupClose', '.pum', function () {
                var $popup = PUM.getPopup($(this));

                pum_debug.popup_event_header($popup);

                console.groupCollapsed(vars.label_event.replace('%s', 'pumSetupClose'));

                console.groupEnd();
            })
            .on('pumClosePrevented', '.pum', function () {
                var $popup = PUM.getPopup($(this));

                pum_debug.popup_event_header($popup);

                console.groupCollapsed(vars.label_event.replace('%s', 'pumClosePrevented'));

                console.groupEnd();
            })
            .on('pumBeforeClose', '.pum', function () {
                var $popup = PUM.getPopup($(this));

                pum_debug.popup_event_header($popup);

                console.groupCollapsed(vars.label_event.replace('%s', 'pumBeforeClose'));

                console.groupEnd();
            })
            .on('pumAfterClose', '.pum', function () {
                var $popup = PUM.getPopup($(this));

                pum_debug.popup_event_header($popup);

                console.groupCollapsed(vars.label_event.replace('%s', 'pumAfterClose'));

                console.groupEnd();
            })
            .on('pumBeforeReposition', '.pum', function () {
                var $popup = PUM.getPopup($(this));

                pum_debug.popup_event_header($popup);

                console.groupCollapsed(vars.label_event.replace('%s', 'pumBeforeReposition'));

                console.groupEnd();
            })
            .on('pumAfterReposition', '.pum', function () {
                var $popup = PUM.getPopup($(this));

                pum_debug.popup_event_header($popup);

                console.groupCollapsed(vars.label_event.replace('%s', 'pumAfterReposition'));

                console.groupEnd();
            })
            .on('pumCheckingCondition', '.pum', function (event, result, condition) {
                var $popup = PUM.getPopup($(this));

                pum_debug.popup_event_header($popup);

                console.groupCollapsed(vars.label_event.replace('%s', 'pumCheckingCondition'));

                console.log((condition.not_operand ? '(!) ' : '') + condition.target + ': ' + result, condition);

                console.groupEnd();
            });
    }

}(jQuery));