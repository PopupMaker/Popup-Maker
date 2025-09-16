export type PermissionValue = string | false;

export interface Settings {
	permissions: {
		// Block Controls
		view_block_controls: PermissionValue;
		edit_block_controls: PermissionValue;
		// Restrictions
		edit_restrictions: PermissionValue;
		// Settings
		manage_settings: PermissionValue;
		// Extendable
		[ key: string ]: PermissionValue;
	};
}
