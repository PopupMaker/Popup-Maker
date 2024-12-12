export interface PopupSelectControlBaseProps {
	label?: string;
	hideLabelFromVision?: boolean;
	emptyValueLabel?: string;
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
