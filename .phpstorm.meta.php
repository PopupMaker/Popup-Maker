<?php
/**
 * This file declares all of the plugin containers available services and accessors for IDEs to read.
 *
 * NOTE: VS Code can use this file as well when the PHP Intelliphense extension is installed to provide autocompletion.
 *
 * @author    Code Atlantic
 * @package   PopupMaker
 * @copyright (c) 2024, Code Atlantic LLC.
 */

namespace PHPSTORM_META;

/**
 * Provide autocompletion for plugin container access.
 *
 * Return lists below all must match, it cannot be defined as a variable.
 * Thus all the duplication is needed.
 */
override(\PopupMaker\Plugin\Core::get_controller(0), map([
    // Controllers
    'PostTypes'     => \PopupMaker\Controllers\PostTypes::class,
    'Assets'        => \PopupMaker\Controllers\Assets::class,
    'Admin'         => \PopupMaker\Controllers\Admin::class,
    'Frontend\Popups' => \PopupMaker\Controllers\Frontend\Popups::class,
    'Compatibility' => \PopupMaker\Controllers\Compatibility::class,
    'CallToActions' => \PopupMaker\Controllers\CallToActions::class,
    'RestAPI'       => \PopupMaker\Controllers\RestAPI::class,
]));

override(\PopupMaker\Plugin\Container::get_controller(0), map([
    // Controllers
    'PostTypes'     => \PopupMaker\Controllers\PostTypes::class,
    'Assets'        => \PopupMaker\Controllers\Assets::class,
    'Admin'         => \PopupMaker\Controllers\Admin::class,
    'Frontend\Popups' => \PopupMaker\Controllers\Frontend\Popups::class,
    'Compatibility' => \PopupMaker\Controllers\Compatibility::class,
    'CallToActions' => \PopupMaker\Controllers\CallToActions::class,
    'RestAPI'       => \PopupMaker\Controllers\RestAPI::class,
]));

override(\PopupMaker\Plugin\Core::get(0), map([
    // Services
    'popups'       => \PopupMaker\Services\Repository\Popups::class,
    'ctas'         => \PopupMaker\Services\Repository\CallToActions::class,
    'cta_types'    => \PopupMaker\Services\Collector\CallToActionTypes::class,
    'globals'      => \PopupMaker\Services\Globals::class,
    'logging'      => \PopupMaker\Services\Logging::class,
    'license'      => \PopupMaker\Services\License::class,
	'connect'      => \PopupMaker\Services\Connect::class,

    // Config Values
    'path'         => 'string',
    'url'          => 'string',
    'slug'         => 'string',
    'version'      => 'string',
    'db_ver'       => 'string',
    'prefix'       => 'string',
]));

// Required for external plugin() function access.
override(\PopupMaker\plugin(0), map([
    // Services
    'popups'       => \PopupMaker\Services\Repository\Popups::class,
    'ctas'         => \PopupMaker\Services\Repository\CallToActions::class,
    'cta_types'    => \PopupMaker\Services\Collector\CallToActionTypes::class,
    'globals'      => \PopupMaker\Services\Globals::class,
    'logging'      => \PopupMaker\Services\Logging::class,
    'license'      => \PopupMaker\Services\License::class,

    // Config Values
    'path'         => 'string',
    'url'          => 'string',
    'slug'         => 'string',
    'version'      => 'string',
    'db_ver'       => 'string',
    'prefix'       => 'string',
]));

// Required for internal $controller->container->get($id);
override( \PopupMaker\Plugin\Container::get(0), map([
    // Services
    'popups'       => \PopupMaker\Services\Repository\Popups::class,
    'ctas'         => \PopupMaker\Services\Repository\CallToActions::class,
    'cta_types'    => \PopupMaker\Services\Collector\CallToActionTypes::class,
    'globals'      => \PopupMaker\Services\Globals::class,
    'logging'      => \PopupMaker\Services\Logging::class,
    'license'      => \PopupMaker\Services\License::class,
	'connect'      => \PopupMaker\Services\Connect::class,

    // Config Values
    'path'         => 'string',
    'url'          => 'string',
    'slug'         => 'string',
    'version'      => 'string',
    'db_ver'       => 'string',
    'prefix'       => 'string',
]));
