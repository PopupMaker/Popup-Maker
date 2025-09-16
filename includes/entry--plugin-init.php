<?php
/**
 * Function loader - Plugin Initialization
 * - only laoded when plugin is initialized.
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
