(function ($, tinymce, wpmce) {
    "use strict";

    tinymce.PluginManager.add('pum_shortcodes', function (editor) {
        var shortcodes = pum_shortcode_ui.shortcodes || pum_admin.shortcode_ui.shortcodes || [],
            menuItems = [];

        $.each(shortcodes, function (tag, args) {

            menuItems.push({
                text: args.label,
                value: tag,
                onclick: function () {
                    var values = {},
                        shortcode,
                        text = "[" + tag + "]";

                    if (args.has_content) {
                        text += editor.selection.getContent() + "[/" + tag + "]";
                    }

                    shortcode = wp.mce.views.get(tag);

                    var encodedText = encodeURIComponent( text );

                    var options = _.extend( options || {}, {
                        text: text,
                        encodedText: encodedText
                    } );

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
            tooltip: pum_admin.I10n.shortcode_ui_button_tooltip || '',
            menu: menuItems
        });
    });

}(jQuery, tinymce || {}, wp.mce.pum_shortcodes || {}));