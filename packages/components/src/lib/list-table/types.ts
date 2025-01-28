export interface TableItemBase {
	id: number;
	[ key: string ]: any;
}

export enum SortDirection {
	ASC = 'ASC',
	DESC = 'DESC',
}

export interface SortConfig {
	key: string;
	direction: SortDirection;
}

export interface TableItem extends TableItemBase {
	id: number;
	[ key: string ]: any;
}
