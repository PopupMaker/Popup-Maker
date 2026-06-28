import { createSlotFill } from '@wordpress/components';

// Shared Fill/Slot types — avoids TS2742 when destructured Fill/Slot
// components are exported (otherwise tsc tries to name their type via the
// .pnpm/@types+react hoisted path, which isn't portable).
type SlotFillPair = ReturnType< typeof createSlotFill >;
type SlotFillFill = SlotFillPair[ 'Fill' ];
type SlotFillSlot = SlotFillPair[ 'Slot' ];

/**
 * SlotFill for adding items to the start of the header
 */
const headerStart = createSlotFill( 'PopupMakerLayoutHeaderStart' );
export const HeaderStartFill: SlotFillFill = headerStart.Fill;
export const HeaderStartSlot: SlotFillSlot = headerStart.Slot;

/**
 * SlotFill for adding items to the end of the header
 */
const headerEnd = createSlotFill( 'PopupMakerLayoutHeaderEnd' );
export const HeaderEndFill: SlotFillFill = headerEnd.Fill;
export const HeaderEndSlot: SlotFillSlot = headerEnd.Slot;

/**
 * SlotFill for adding action items to the header (before support dropdown)
 */
const headerActions = createSlotFill( 'PopupMakerLayoutHeaderActions' );
export const HeaderActionsFill: SlotFillFill = headerActions.Fill;
export const HeaderActionsSlot: SlotFillSlot = headerActions.Slot;

/**
 * SlotFill for adding items to the support dropdown menu
 */
const supportMenu = createSlotFill( 'PopupMakerLayoutSupportMenu' );
export const SupportMenuFill: SlotFillFill = supportMenu.Fill;
export const SupportMenuSlot: SlotFillSlot = supportMenu.Slot;
