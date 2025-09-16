import { createSlotFill } from '@wordpress/components';

/**
 * SlotFill for adding items to the start of the header
 */
export const { Fill: HeaderStartFill, Slot: HeaderStartSlot } = createSlotFill(
	'PopupMakerLayoutHeaderStart'
);

/**
 * SlotFill for adding items to the end of the header
 */
export const { Fill: HeaderEndFill, Slot: HeaderEndSlot } = createSlotFill(
	'PopupMakerLayoutHeaderEnd'
);

/**
 * SlotFill for adding action items to the header (before support dropdown)
 */
export const { Fill: HeaderActionsFill, Slot: HeaderActionsSlot } =
	createSlotFill( 'PopupMakerLayoutHeaderActions' );

/**
 * SlotFill for adding items to the support dropdown menu
 */
export const { Fill: SupportMenuFill, Slot: SupportMenuSlot } = createSlotFill(
	'PopupMakerLayoutSupportMenu'
);
