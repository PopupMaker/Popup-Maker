<?php
/**
 * Functions
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

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
