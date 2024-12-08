<?php
/**
 * Function loader - Legacy
 * - loaded on init of legacy Popup_Maker instance.
 *
 * File loader guidelines:
 *
 * This file is loaded and will hanlde the loading of other function files.
 * - Newer files will be namespaced under PopupMaker\ namespace.
 * - Older functions will be prefixed as well as:
 *   - Deprecated & moved to the -deprecated directory, merged fewer files.
 *   - Legacy functions will be moved to -legacy directory.
 *
 * @since X.X.X
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

defined( 'ABSPATH' ) || exit;

/** Loads most of our core functions */
require_once __DIR__ . '/functions.php';

/** Deprecated functionality */
require_once __DIR__ . '/functions-backcompat.php';
require_once __DIR__ . '/functions-deprecated.php';
require_once __DIR__ . '/deprecated-classes.php';
require_once __DIR__ . '/deprecated-filters.php';
require_once __DIR__ . '/integrations.php';

// Old Stuff.
require_once __DIR__ . '/defaults.php';
require_once __DIR__ . '/input-options.php';

require_once __DIR__ . '/importer/easy-modal-v2.php';

// Phasing Out
require_once __DIR__ . '/class-popmake-fields.php';
require_once __DIR__ . '/class-popmake-popup-fields.php';

/**
 * v1.4 Additions
 */
require_once __DIR__ . '/class-pum-fields.php';
require_once __DIR__ . '/class-pum-form.php';

// Modules
require_once __DIR__ . '/modules/menus.php';
require_once __DIR__ . '/modules/reviews.php';

require_once __DIR__ . '/pum-install-functions.php';
