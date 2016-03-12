var PUMTabs;
(function ($, document, undefined) {
    "use strict";
    PUMTabs = {
        init: function () {
            $('.pum-tabs-container').filter(':not(.initialized)').each(function () {
                var $this = $(this),
                    first_tab = $this.find('.tab:first');

                if ($this.hasClass('vertical-tabs')) {
                    $this.css({
                        minHeight: $this.find('.tabs').eq(0).outerHeight(true)
                    });
                }

                $this.find('.active').removeClass('active');
                first_tab.addClass('active');
                $(first_tab.find('a').attr('href')).addClass('active');
                $this.addClass('initialized');
            });
        }
    };

    $(document)
        .on('pum_init', PUMTabs.init)
        .on('click', '.pum-tabs-container .tab', function (e) {
            var $this = $(this),
                tab_group = $this.parents('.pum-tabs-container:first'),
                link = $this.find('a').attr('href');

            tab_group.find('.active').removeClass('active');

            $this.addClass('active');
            $(link).addClass('active');

            e.preventDefault();
        });
}(jQuery, document));