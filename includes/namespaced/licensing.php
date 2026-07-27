<?php
/**
 * Licensing ownership capability contract.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker;

defined( 'ABSPATH' ) || exit;

/**
 * Get the active licensing and updater ownership capabilities.
 *
 * Extensions should feature-detect these values instead of checking build or
 * edition constants. Owners use stable plugin slugs.
 *
 * @return array{
 *     contract_version:int,
 *     license_provider:string|null,
 *     license_ui_owner:string|null,
 *     pro_updates_owner:string|null,
 *     addon_updates_owner:string|null,
 *     remote_installation:bool,
 *     legacy_core_license_service:bool,
 *     legacy_extension_updater:bool
 * }
 */
function licensing_capabilities() {
	$capabilities = [
		'contract_version'            => 1,
		'license_provider'            => 'popup-maker',
		'license_ui_owner'            => 'popup-maker',
		'pro_updates_owner'           => 'popup-maker-pro',
		'addon_updates_owner'         => 'addon',
		'remote_installation'         => false,
		'legacy_core_license_service' => true,
		'legacy_extension_updater'    => class_exists( '\PUM_Extension_Updater' ),
	];

	/**
	 * Filters the licensing and updater ownership capability contract.
	 *
	 * Providers must preserve unknown keys so the contract can evolve safely.
	 *
	 * @param array<string,mixed> $capabilities Licensing capabilities.
	 */
	$capabilities = apply_filters( 'popup_maker/licensing_capabilities', $capabilities );

	return is_array( $capabilities ) ? $capabilities : [];
}

/**
 * Check whether a specific owner currently owns a capability.
 *
 * @param string $capability Capability key.
 * @param string $owner      Stable owner slug.
 *
 * @return bool
 */
function owns_licensing_capability( $capability, $owner ) {
	$capabilities = licensing_capabilities();

	return isset( $capabilities[ $capability ] ) && $owner === $capabilities[ $capability ];
}
