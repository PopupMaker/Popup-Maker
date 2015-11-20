/**
 * Initialize Popup Maker.
 * Version 1.4.0
 */
(function ($) {
    "use strict";

    $('.popmake').css({visibility: "visible"}).hide();

    $(document).ready(function () {
        $('.popmake').popmake();

            /* Commented out so that the cookie parts can be converted later.
            .each(function () {
                var $this = $(this),
                    settings = $this.data('popmake'),
                    auto_open = settings.meta.auto_open,
                    cookie_name = "popmake-auto-open-" + settings.id,
                    noCookieCheck;




                if (auto_open !== undefined && auto_open.enabled) {

                    if (auto_open.cookie_key !== undefined && auto_open.cookie_key !== '') {
                        cookie_name = cookie_name + "-" + auto_open.cookie_key;
                    }

                    noCookieCheck = function () {
                        return $.pm_cookie(cookie_name) === undefined;
                    };

                    $this.on('popmakeSetCookie.auto-open', function () {
                        if (auto_open.cookie_time !== '' && noCookieCheck()) {
                            $.pm_cookie(
                                cookie_name,
                                true,
                                auto_open.session_cookie ? null : auto_open.cookie_time,
                                auto_open.cookie_path
                            );
                        }
                    });

                    switch (auto_open.cookie_trigger) {
                        case "open":
                            $this.on('popmakeAfterOpen', function () {
                                $this.trigger('popmakeSetCookie');
                            });
                            break;
                        case "close":
                            $this.on('popmakeBeforeClose', function () {
                                $this.trigger('popmakeSetCookie');
                            });
                            break;
                    }

                    setTimeout(function () {
                        if (noCookieCheck()) {
                            if (!$this.hasClass('active')) {
                                $.fn.popmake.last_open_trigger = 'Auto Open Popups ID-' + settings.id;
                                $this.popmake('open');
                            }
                        }
                    }, auto_open.delay);
                }
            });
         */
    });
}(jQuery));