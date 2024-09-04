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

/**
  * NOTE: applies specifically to using the Plugin getter directly.
  * Example Usage: $events = pum_Scheduling_plugin()->get( 'events' );
  */
  override( \PopupMaker\Plugin\Core::get(0), map( [
    // Controllers.
    ''             => '@',
    'connect'      => \PopupMaker\Plugin\Connect::class,
    'license'      => \PopupMaker\Plugin\License::class,
    'logging'      => \PopupMaker\Plugin\Logging::class,
    'options'      => \PopupMaker\Plugin\Options::class,
    'upgrader'     => \PopupMaker\Plugin\Upgrader::class,
    // 'rules'        => \PopupMaker\RuleEngine\Rules::class,
    // 'restrictions' => \PopupMaker\Services\Restrictions::class,
    // 'globals'      => \PopupMaker\Services\Globals::class,
    // 'Frontend\Restrictions\PostContent' => \PopupMaker\Controllers\Frontend\Restrictions\PostContent::class,
  ] ) );

 /**
  * NOTE: applies specifically to using the global getter function.
  * Example Usage: $events = pum_scheduling( 'events' );
  */
  override ( \PopupMaker\plugin(0), map( [
    // Controllers.
    '' => '@',
    'connect'      => \PopupMaker\Plugin\Connect::class,
    'license'      => \PopupMaker\Plugin\License::class,
    'logging'      => \PopupMaker\Plugin\Logging::class,
    'options'      => \PopupMaker\Plugin\Options::class,
    'upgrader'     => \PopupMaker\Plugin\Upgrader::class,
    // 'rules'        => \PopupMaker\RuleEngine\Rules::class,
    // 'restrictions' => \PopupMaker\Services\Restrictions::class,
    // 'globals'      => \PopupMaker\Services\Globals::class,
    // 'Frontend\Restrictions\PostContent' => \PopupMaker\Controllers\Frontend\Restrictions\PostContent::class,
  ] ) );

  /**
  * NOTE: applies specifically to using the global getter function.
  * Example Usage: $events = pum_scheduling( 'events' );
  */
  override ( \PopupMaker\Base\Container::get(0), map( [
    // Controllers.
    '' => '@',
    'connect'      => \PopupMaker\Plugin\Connect::class,
    'license'      => \PopupMaker\Plugin\License::class,
    'logging'      => \PopupMaker\Plugin\Logging::class,
    'options'      => \PopupMaker\Plugin\Options::class,
    'upgrader'     => \PopupMaker\Plugin\Upgrader::class,
    // 'rules'        => \PopupMaker\RuleEngine\Rules::class,
    // 'restrictions' => \PopupMaker\Services\Restrictions::class,
    // 'globals'      => \PopupMaker\Services\Globals::class,
    // 'Frontend\Restrictions\PostContent' => \PopupMaker\Controllers\Frontend\Restrictions\PostContent::class,
  ] ) );

    /**
  * NOTE: applies specifically to using the global getter function.
  * Example Usage: $events = pum_scheduling( 'events' );
  */
override ( \PopupMaker\Base\Container::offsetGet(0), map( [
  // Controllers.
  '' => '@',
  'connect'      => \PopupMaker\Plugin\Connect::class,
  'license'      => \PopupMaker\Plugin\License::class,
  'logging'      => \PopupMaker\Plugin\Logging::class,
  'options'      => \PopupMaker\Plugin\Options::class,
  'upgrader'     => \PopupMaker\Plugin\Upgrader::class,
  // 'rules'        => \PopupMaker\RuleEngine\Rules::class,
  // 'restrictions' => \PopupMaker\Services\Restrictions::class,
  // 'globals'      => \PopupMaker\Services\Globals::class,
  // 'Frontend\Restrictions\PostContent' => \PopupMaker\Controllers\Frontend\Restrictions\PostContent::class,
  ] ) );
