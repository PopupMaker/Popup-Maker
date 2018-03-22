(function ($, tinymce) {
    "use strict";

    // Failsafe in case variables were not properly declared on page.
    if (typeof pum_shortcode_ui_vars === 'undefined') {
        return;
    }

    tinymce.PluginManager.add('pum_shortcodes', function (editor) {
        var shortcodes = pum_shortcode_ui_vars.shortcodes || {},
            menuItems = [];

        _.each(shortcodes, function (args, tag) {
            menuItems.push({
                text: args.label,
                value: tag,
                onclick: function () {
                    var values = {},
                        shortcode,
                        text = "[" + tag + "]",
                        options = {};

                    if (args.has_content) {
                        text += editor.selection.getContent() + "[/" + tag + "]";
                    }

                    shortcode = wp.mce.views.get(tag);

                    options.text = text;
                    options.encodedText = encodeURIComponent(text);

                    shortcode = new shortcode(options);

                    shortcode.renderForm(values, function (content) {
                        send_to_editor(content);
                    });
                }
            });
        });

        editor.addButton('pum_shortcodes', {
            type: 'menubutton',
            icon: 'pum_shortcodes',
            tooltip: pum_shortcode_ui_vars.I10n.shortcode_ui_button_tooltip || '',
            menu: menuItems
        });
    });

}(jQuery, tinymce || {}));