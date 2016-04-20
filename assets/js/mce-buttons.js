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
                    var values = {};
                    if (args.has_content) {
                        values._inner_content = editor.selection.getContent();
                    }
                    wpmce[tag].openModal(editor, values);
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

}(jQuery, tinymce || {}, wp.mce || {}));