import './index.scss';

import clsx from 'clsx';

import { noop } from '@popup-maker/utils';
import { _x, sprintf } from '@wordpress/i18n';
import { Icon, ToggleControl, Tooltip } from '@wordpress/components';

import type { IconType } from '@wordpress/components';

type Props = {
	label: string;
	icon?: IconType | undefined;
	isVisible: boolean;
	onChange?: ( checked: boolean ) => void;
};

const DeviceToggle = ( { label, icon, isVisible, onChange = noop }: Props ) => {
	const toggleLabel = ! isVisible
		? /* translators: 1. Device type. */
		  _x( 'Hide on %1$s', 'Device toggle option', 'popup-maker' )
		: /* translators: 1. Device type. */
		  _x( 'Show on %1$s', 'Device toggle option', 'popup-maker' );

	const toggleIcon = ! isVisible ? 'hidden' : 'visibility';

	const onToggle = () => onChange( ! isVisible );

	return (
		<div
			className={ clsx( [
				'pum__component-device-toggle',
				isVisible && 'is-checked',
			] ) }
		>
			<h3 className="pum__component-device-toggle__label">
				<Icon icon={ icon } />
				{ label }
			</h3>
			<div className="pum__component-device-toggle__control">
				<Tooltip text={ sprintf( toggleLabel, label ) }>
					<span>
						<Icon
							className="pum__component-device-toggle__control-icon"
							onClick={ onToggle }
							icon={ toggleIcon }
						/>
					</span>
				</Tooltip>
				<Tooltip text={ sprintf( toggleLabel, label ) }>
					<span>
						<ToggleControl
							className="pum__component-device-toggle__control-input"
							checked={ isVisible }
							onChange={ onToggle }
							// @ts-ignore
							hideLabelFromVision={ true }
							aria-label={ toggleLabel }
							label={ sprintf(
								/* translators: 1. Device type. */
								_x(
									'Show on %1$s',
									'Device toggle option',
									'popup-maker'
								),
								label
							) }
						/>
					</span>
				</Tooltip>
			</div>
		</div>
	);
};

export default DeviceToggle;
