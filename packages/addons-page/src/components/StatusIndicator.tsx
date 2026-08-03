import React from 'react';
import clsx from 'clsx';

import { getStatusLabel } from '../lib/status';

import type { AddonStatus } from '../types';

interface Props {
	status: AddonStatus;
	proPlus?: boolean;
}

const StatusIndicator = ( { status, proPlus }: Props ) => (
	<span
		className={ clsx(
			'pum-addons__status',
			`pum-addons__status--${ status }`,
			{ 'pum-addons__status--pro-plus': proPlus }
		) }
	>
		<span className="pum-addons__status-dot" />
		<span>{ getStatusLabel( status, Boolean( proPlus ) ) }</span>
	</span>
);

export default StatusIndicator;
