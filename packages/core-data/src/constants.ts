export enum Status {
	Idle = 'IDLE',
	Resolving = 'RESOLVING',
	Error = 'ERROR',
	Success = 'SUCCESS',
}

export type Statuses = ( typeof Status )[ keyof typeof Status ];
