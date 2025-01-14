import { useMemo } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';

import { LICENSE_STORE } from '../license/index';

const useLicense = () => {
	// Fetch needed data from the @popup-paker/core-data & @wordpress/data stores.
	const {
		connectInfo,
		licenseKey,
		licenseStatus,
		isSaving,
		proWasActivated,
		isActivatingPro,
	} = useSelect( ( select ) => {
		const storeSelect = select( LICENSE_STORE );
		return {
			connectInfo: storeSelect.getConnectInfo(),
			licenseKey: storeSelect.getLicenseKey(),
			licenseStatus: storeSelect.getLicenseStatus(),
			isSaving:
				storeSelect.isDispatching( 'activateLicense' ) ||
				storeSelect.isDispatching( 'deactivateLicense' ) ||
				storeSelect.isDispatching( 'checkLicenseStatus' ) ||
				storeSelect.isDispatching( 'updateLicenseKey' ) ||
				storeSelect.isDispatching( 'removeLicense' ),
			isActivatingPro: storeSelect.isDispatching( 'activatePro' ),
			proWasActivated: storeSelect.hasDispatched( 'activatePro' ),
		};
	}, [] );

	// Grab needed action dispatchers.
	const {
		activateLicense,
		deactivateLicense,
		checkLicenseStatus,
		updateLicenseKey,
		removeLicense,
		activatePro,
	} = useDispatch( LICENSE_STORE );

	// Create some helper variables.

	// Check if the license is active.
	const isLicenseActive = 'valid' === licenseStatus?.license;

	// Check if the license is deactivated.
	const isLicenseDeactivated = [
		'deactivated',
		'site_inactive',
		'inactive',
	].includes( licenseStatus?.license ?? '' );

	// Check if the license is invalid.
	const isLicenseInvalid = [ 'invalid', 'failed' ].includes(
		licenseStatus?.license
	);

	// Check if the license is missing (default state).
	const isLicenseMissing =
		isLicenseInvalid &&
		[ '', 'missing' ].includes( licenseStatus?.error ?? '' );

	// Check if the license is expired.
	const isLicenseExpired =
		'expired' === licenseStatus?.license ||
		( [ 'invalid', 'failed' ].includes( licenseStatus?.license ?? '' ) &&
			'expired' === licenseStatus?.error );

	// Check if the license is disabled.
	const isLicenseDisabled =
		'disabled' === licenseStatus?.license ||
		( isLicenseInvalid && 'disabled' === licenseStatus?.error );

	const isLicenseOverQuota = 'no_activations_left' === licenseStatus?.error;

	// Check if there is an error.
	const hasError = !! licenseStatus?.error;

	// Check if there is a general error.
	const isGeneralError =
		isLicenseInvalid &&
		hasError &&
		! [ 'missing', 'expired', 'disabled' ].includes(
			licenseStatus?.error ?? ''
		);

	const isLicenseKeyValid = useMemo(
		() =>
			isLicenseActive ||
			isLicenseDeactivated ||
			isLicenseExpired ||
			isLicenseDisabled ||
			isLicenseOverQuota,
		[
			isLicenseActive,
			isLicenseDeactivated,
			isLicenseExpired,
			isLicenseDisabled,
			isLicenseOverQuota,
		]
	);

	// Create a helper function to get the current license status.
	const getLicenseStatusName = useMemo( () => {
		if ( isLicenseActive ) {
			return 'active';
		} else if ( isLicenseExpired ) {
			return 'expired';
		} else if ( isLicenseMissing ) {
			return 'missing';
		} else if ( isLicenseDeactivated ) {
			return 'deactivated';
		} else if ( isLicenseDisabled ) {
			return 'disabled';
		} else if ( isGeneralError ) {
			return 'error';
		}
		return 'unknown';
	}, [
		isLicenseActive,
		isLicenseExpired,
		isLicenseMissing,
		isLicenseDeactivated,
		isLicenseDisabled,
		isGeneralError,
	] );

	const licenseLevel = useMemo( () => {
		// Price ID as an int
		let priceId = licenseStatus?.price_id ?? null;

		if ( null === priceId ) {
			return -1;
		}

		if ( 'string' === typeof priceId ) {
			priceId = parseInt( priceId, 10 );
		}

		switch ( priceId ) {
			default:
				return -1;

			case false:
			case 0:
				return 0;

			case 1:
			case 2:
			case 3:
			case 4:
				return priceId;
		}
	}, [ licenseStatus?.price_id ] );

	return {
		connectInfo,
		licenseKey,
		licenseStatus,
		licenseLevel,
		activateLicense,
		deactivateLicense,
		checkLicenseStatus,
		updateLicenseKey,
		removeLicense,
		activatePro,
		getLicenseStatusName,
		isSaving,
		isActivatingPro,
		proWasActivated,
		isLicenseKeyValid,
		isLicenseActive,
		isLicenseDeactivated,
		isLicenseMissing,
		isLicenseExpired,
		isLicenseInvalid,
		isLicenseDisabled,
		isLicenseOverQuota,
		isGeneralError,
		hasError,
	};
};

export default useLicense;
