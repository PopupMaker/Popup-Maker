<?php
/**
 * Function loader - Legacy
 * - loaded on init of legacy Popup_Maker instance.
 *
 * File loader guidelines:
 *
 * This file is loaded and will handle the loading of other function files.
 * - Newer files will be namespaced under PopupMaker\ namespace.
 * - Older functions will be prefixed as well as:
 *   - Deprecated & moved to the -deprecated directory, merged fewer files.
 *   - Legacy functions will be moved to -legacy directory.
 *
 * @since 1.21.0
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

defined( 'ABSPATH' ) || exit;

/** Loads most of our core functions */

/** General Functions */
require_once 'functions/developers.php';
require_once 'functions/extensions.php';
require_once 'functions/general.php';
require_once 'functions/newsletter.php';

/** Utility Functions */
require_once 'functions/utils/cache.php';
require_once 'functions/utils/filesystem.php';
require_once 'functions/utils/format.php';
require_once 'functions/utils/options.php';
require_once 'functions/utils/template.php';
require_once 'functions/utils/upgrades.php';

/** Admin Functions */
require_once 'functions/admin/conditionals.php';
require_once 'functions/admin/general.php';

/** Popup functions */
require_once 'functions/popups/conditionals.php';
require_once 'functions/popups/deprecated.php';
require_once 'functions/popups/getters.php';
require_once 'functions/popups/migrations.php';
require_once 'functions/popups/queries.php';
require_once 'functions/popups/template.php';

/** Popup Theme functions */
require_once 'functions/themes/conditionals.php';
require_once 'functions/themes/deprecated.php';
require_once 'functions/themes/getters.php';
require_once 'functions/themes/migrations.php';
require_once 'functions/themes/portability.php';
require_once 'functions/themes/queries.php';
require_once 'functions/themes/template.php';

/** Deprecated functionality */
require_once __DIR__ . '/legacy/functions-backcompat.php';
require_once __DIR__ . '/legacy/functions-deprecated.php';
require_once __DIR__ . '/legacy/deprecated-classes.php';
require_once __DIR__ . '/legacy/deprecated-filters.php';
require_once __DIR__ . '/integrations.php';

// Old Stuff.
require_once __DIR__ . '/legacy/defaults.php';
require_once __DIR__ . '/legacy/input-options.php';

require_once __DIR__ . '/legacy/importer/easy-modal-v2.php';

// Phasing Out

/**
 * Load the current main class.
 *
 * This is a placeholder for the eventual removal and deferral to the autoloader.
 */
require_once __DIR__ . '/legacy/class-popup-maker.php';
require_once __DIR__ . '/legacy/class-popmake-fields.php';
require_once __DIR__ . '/legacy/class-popmake-popup-fields.php';

/**
 * v1.4 Additions
 */
require_once __DIR__ . '/legacy/class-pum-fields.php';
require_once __DIR__ . '/legacy/class-pum-form.php';

// Modules
require_once __DIR__ . '/modules/menus.php';
require_once __DIR__ . '/modules/reviews.php';

require_once __DIR__ . '/pum-install-functions.php';
