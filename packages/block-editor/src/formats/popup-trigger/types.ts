// The way they are stored when editing.
export type TriggerFormatOptions = {
	popupId?: number;
	doDefault?: boolean;
};

// The way they are stored on the link
export interface TriggerFormatAttributes {
	class?: string;
	popupId?: string;
	doDefault?: '1' | '0';
}

export interface TriggerFormat {
	type: string;
	attributes: TriggerFormatAttributes;
}
