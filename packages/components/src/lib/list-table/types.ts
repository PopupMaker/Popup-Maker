export interface TableItemBase {
	id: number;
	[ key: string ]: any;
}

export enum SortDirection {
	ASC = 'ASC',
	DESC = 'DESC',
}

export interface SortConfig {
	orderby: string;
	order: SortDirection;
}

export interface TableItem extends TableItemBase {
	id: number;
	[ key: string ]: any;
}
