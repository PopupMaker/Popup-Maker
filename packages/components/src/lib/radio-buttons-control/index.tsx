import './editor.scss';

import classnames, { Argument as classNamesArg } from 'classnames';

import { BaseControl, Button } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { ButtonProps } from '@wordpress/components/build-types/button/types';

type Props< T extends string | number = string | number > = {
	id?: string;
	value: T;
	onChange: ( value: T ) => void;
	label?: string | JSX.Element;
	className?: classNamesArg;
	options: ( Partial< ButtonProps > & {
		value: T;
		label: string | JSX.Element;
	} )[];
	orientation?: 'horizontal' | 'vertical';
	equalWidth?: boolean;
	spacing?: string | number;
	hideLabelFromVision?: boolean;
};

const RadioButtonControl = < T extends string | number = string | number >( {
	id,
	label,
	value,
	onChange,
	className,
	options = [],
	orientation = 'horizontal',
	equalWidth = false,
	spacing,
	hideLabelFromVision = false,
}: Props< T > ) => {
	const instanceId = useInstanceId( RadioButtonControl );

	return (
		<BaseControl
			id={ id ? id : `radio-button-control-${ instanceId }` }
			label={ label }
			className={ classnames(
				'components-radio-button-control',
				orientation,
				equalWidth && 'equal-width',
				className
			) }
			hideLabelFromVision={ hideLabelFromVision }
		>
			<div
				className="options"
				style={ spacing ? { gap: `${ spacing }px` } : undefined }
			>
				{ options.map(
					( {
						label: optLabel,
						value: optValue,
						// ...buttonProps
					} ) => (
						<Button
							key={ optValue }
							variant={
								optValue === value ? 'primary' : 'secondary'
							}
							onClick={ () => onChange( optValue ) }
							// { ...buttonProps }
						>
							{ optLabel }
						</Button>
					)
				) }
			</div>
		</BaseControl>
	);
};

export default RadioButtonControl;
