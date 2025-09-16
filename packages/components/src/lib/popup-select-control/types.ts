import type { ReactNode } from 'react';

export interface PopupSelectControlBaseProps {
	label?: string | ReactNode;
	hideLabelFromVision?: boolean;
	emptyValueLabel?: string;
	required?: boolean;
	options?: {
		value: string;
		label: string;
	}[];
}

export type SinglePopupSelectControlProps = PopupSelectControlBaseProps & {
	multiple?: false;
	value: string;
	onChange: ( value: string ) => void;
};

export type MultiplePopupSelectControlProps = PopupSelectControlBaseProps & {
	multiple: true;
	value: string[];
	onChange: ( value: string[] ) => void;
};

export type PopupSelectControlProps =
	| SinglePopupSelectControlProps
	| MultiplePopupSelectControlProps;
