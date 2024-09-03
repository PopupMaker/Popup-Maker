<?php
/**
 * Function loader - General
 * - always loaded during bootstrap.
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

require_once __DIR__ . '/namespaced/core.php';
require_once __DIR__ . '/namespaced/upgrades.php';
