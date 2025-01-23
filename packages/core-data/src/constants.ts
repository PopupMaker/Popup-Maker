export enum DispatchStatus {
	Idle = 'IDLE',
	Resolving = 'RESOLVING',
	Error = 'ERROR',
	Success = 'SUCCESS',
}

export type DispatchStatuses =
	( typeof DispatchStatus )[ keyof typeof DispatchStatus ];
