function pumSelected(val1, val2, print) {
    "use strict";

    var selected = false;
    if (typeof val1 === 'object' && typeof val2 === 'string' && jQuery.inArray(val2, val1) !== -1) {
        selected = true;
    } else if (typeof val2 === 'object' && typeof val1 === 'string' && jQuery.inArray(val1, val2) !== -1) {
        selected = true;
    } else if (val1 === val2) {
        selected = true;
    }

    if (selected && print !== undefined && print) {
        return ' selected="selected"';
    }
    return selected;
}
