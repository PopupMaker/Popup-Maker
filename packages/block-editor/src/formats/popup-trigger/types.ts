export interface TriggerFormatAttributes {
	class?: string;
	popupId: string | number;
	doDefault: boolean | '0' | '1';
}

export interface TriggerFormat {
	type: string;
	attributes: TriggerFormatAttributes;
}
