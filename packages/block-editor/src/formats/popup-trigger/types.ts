export interface TriggerFormatAttributes {
	class?: string;
	popupId: string;
	doDefault: string;
}

export interface TriggerFormat {
	type: string;
	attributes: TriggerFormatAttributes;
}
