/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/
(function ($) {
    "use strict";
    var tabs = {
        init: function () {
            $('.pum-tabs-container').filter(':not(.pum-tabs-initialized)').each(function () {
                var $this = $(this).addClass('pum-tabs-initialized'),
                    $tabList = $this.find('> ul.tabs'),
                    $firstTab = $tabList.find('> li:first'),
                    forceMinHeight = $this.data('min-height');

                if ($this.hasClass('vertical-tabs')) {
                    var minHeight = forceMinHeight && forceMinHeight > 0 ? forceMinHeight : $tabList.eq(0).outerHeight(true);

                    $this.css({
                        minHeight: minHeight + 'px'
                    });

                    if ($this.parent().innerHeight < minHeight) {
                        $this.parent().css({
                            minHeight: minHeight + 'px'
                        });
                    }
                }

                // Trigger first tab.
                $firstTab.trigger('click');
            });
        }
    };

    // Import this module.
    window.PUM_Admin = window.PUM_Admin || {};
    window.PUM_Admin.tabs = tabs;

    $(document)
        .on('pum_init', PUM_Admin.tabs.init)
        .on('click', '.pum-tabs-initialized li.tab', function (e) {
            var $this = $(this),
                $container = $this.parents('.pum-tabs-container:first'),
                $tabs = $container.find('> ul.tabs > li.tab'),
                $tab_contents = $container.find('> div.tab-content'),
                link = $this.find('a').attr('href');

            $tabs.removeClass('active');
            $tab_contents.removeClass('active');

            $this.addClass('active');
            $container.find('> div.tab-content' + link).addClass('active');

            e.preventDefault();
        });
}(jQuery));