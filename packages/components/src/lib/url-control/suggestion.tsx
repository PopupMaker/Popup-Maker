import classNames, { Argument } from 'classnames';

import { noop } from '@popup-maker/utils';
import { Button, Icon } from '@wordpress/components';
import { forwardRef } from '@wordpress/element';

type Props = {
	icon?: JSX.Element;
	title: string | React.ReactNode;
	info: string | React.ReactNode;
	type?: string | React.ReactNode;
	prefix?: string;
	className?: Argument;
	isSelected: boolean;
	onFocus: () => void;
	onSelect: () => void;
	[ key: string ]: any;
};

const Suggestion = forwardRef< HTMLButtonElement, Props >(
	(
		{
			icon,
			title,
			info,
			type,
			className,
			isSelected,
			onFocus = noop,
			onSelect = noop,
			...buttonProps
		},
		ref
	) => {
		return (
			<Button
				type="button"
				className={ classNames( [
					'suggestion',
					isSelected && 'is-selected',
					className,
				] ) }
				ref={ ref }
				onClick={ onSelect }
				onFocus={ onFocus }
				onMouseOver={ onFocus }
				aria-selected={ isSelected }
				role="option"
				tabIndex={ -1 }
				{ ...buttonProps }
			>
				{ icon && (
					<Icon icon={ icon } className="suggestion-item-icon" />
				) }
				<span className="suggestion-item-header">
					<span className="suggestion-item-title">{ title }</span>
					{ info && (
						<span
							aria-hidden="true"
							className="suggestion-item-info"
						>
							{ info }
						</span>
					) }
				</span>
				{ type && (
					<span className="suggestion-item-type">{ type }</span>
				) }
			</Button>
		);
	}
);

export default Suggestion;
