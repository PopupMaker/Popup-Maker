/**
 * Whether debugging is enabled.
 * Can be enabled via localStorage.debug = 'pum:*' or specific features like 'pum:effects'
 */
const getDebugConfig = () => {
	try {
		const debug = localStorage.getItem( 'debug' ) || '';
		if ( debug === 'pum:*' ) {
			return {
				component: true,
				effects: true,
				selectors: true,
				actions: true,
				resolvers: true,
				state: true,
				stack: true,
			};
		}

		return {
			component: debug.includes( 'pum:component' ),
			effects: debug.includes( 'pum:effects' ),
			selectors: debug.includes( 'pum:selectors' ),
			actions: debug.includes( 'pum:actions' ),
			resolvers: debug.includes( 'pum:resolvers' ),
			state: debug.includes( 'pum:state' ),
			stack: debug.includes( 'pum:stack' ),
		};
	} catch ( e ) {
		return {
			component: false,
			effects: false,
			selectors: false,
			actions: false,
			resolvers: false,
			state: false,
			stack: false,
		};
	}
};

interface DebugConfig {
	component?: boolean;
	effects?: boolean;
	selectors?: boolean;
	actions?: boolean;
	resolvers?: boolean;
	state?: boolean;
	stack?: boolean;
}

let debugConfig = getDebugConfig();

// Update config when localStorage changes
window.addEventListener( 'storage', () => {
	debugConfig = getDebugConfig();
} );

const getTimestamp = () => new Date().toISOString().split( 'T' )[ 1 ];

/**
 * Get a cleaned up stack trace
 */
const getStack = () => {
	try {
		const stack = new Error().stack
			?.split( '\n' )
			.slice( 3 ) // Remove Error and debug function calls
			.map( ( line ) => line.trim() )
			.filter( ( line ) => ! line.includes( 'debug.ts' ) ) // Remove debug utility frames
			.join( '\n' );
		return stack;
	} catch ( e ) {
		return '';
	}
};

export const debug = {
	component: ( component: string, ...args: any[] ) => {
		if ( debugConfig.component ) {
			console.groupCollapsed(
				`🔷 [${ getTimestamp() }][Component:${ component }]`,
				...args
			);
			if ( debugConfig.stack ) {
				console.log( getStack() );
			}
			console.groupEnd();
		}
	},
	effect: ( component: string, effect: string, ...args: any[] ) => {
		if ( debugConfig.effects ) {
			console.groupCollapsed(
				`🔶 [${ getTimestamp() }][Effect:${ component }:${ effect }]`,
				...args
			);
			if ( debugConfig.stack ) {
				console.log( getStack() );
			}
			console.groupEnd();
		}
	},
	selector: ( selector: string, ...args: any[] ) => {
		if ( debugConfig.selectors ) {
			console.groupCollapsed(
				`💠 [${ getTimestamp() }][Select:${ selector }]`,
				...args
			);
			if ( debugConfig.stack ) {
				console.log( getStack() );
			}
			console.groupEnd();
		}
	},
	action: ( action: string, ...args: any[] ) => {
		if ( debugConfig.actions ) {
			console.groupCollapsed(
				`⚡ [${ getTimestamp() }][Action:${ action }]`,
				...args
			);
			if ( debugConfig.stack ) {
				console.log( getStack() );
			}
			console.groupEnd();
		}
	},
	resolver: ( resolver: string, ...args: any[] ) => {
		if ( debugConfig.resolvers ) {
			console.groupCollapsed(
				`🔄 [${ getTimestamp() }][Resolver:${ resolver }]`,
				...args
			);
			if ( debugConfig.stack ) {
				console.log( getStack() );
			}
			console.groupEnd();
		}
	},
	state: ( reducer: string, ...args: any[] ) => {
		if ( debugConfig.state ) {
			console.groupCollapsed(
				`📦 [${ getTimestamp() }][State:${ reducer }]`,
				...args
			);
			if ( debugConfig.stack ) {
				console.log( getStack() );
			}
			console.groupEnd();
		}
	},
};

export default debug;