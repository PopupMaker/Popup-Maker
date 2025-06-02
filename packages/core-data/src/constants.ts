export enum DispatchStatus {
	Idle = 'IDLE',
	Resolving = 'RESOLVING',
	Error = 'ERROR',
	Success = 'SUCCESS',
}

export type DispatchStatuses =
	( typeof DispatchStatus )[ keyof typeof DispatchStatus ];

export type ResolutionState = {
	status: DispatchStatuses;
	error?: string;
	timestamp?: number;
};
