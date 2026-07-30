<?php
/**
 * Function loader - General
 * - always loaded during bootstrap.
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

require_once __DIR__ . '/namespaced/call-to-actions.php';
// require_once __DIR__ . '/namespaced/cacheit.php';
require_once __DIR__ . '/namespaced/core.php';
require_once __DIR__ . '/namespaced/condition-helpers.php';
require_once __DIR__ . '/namespaced/default-values.php';
require_once __DIR__ . '/namespaced/install.php';
require_once __DIR__ . '/namespaced/filesystem.php';
require_once __DIR__ . '/namespaced/popups.php';
require_once __DIR__ . '/namespaced/types.php';
require_once __DIR__ . '/namespaced/upgrades.php';
require_once __DIR__ . '/namespaced/utils.php';
