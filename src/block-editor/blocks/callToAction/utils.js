const { ctas } = pum_block_editor_vars;
const getCta = ( key ) => ctas[ key ] || ctas[ Object.keys( ctas )[ 0 ] ];

const callToActions = Object.values( ctas );

const getDefaults = ( key ) => {
	const cta = getCta( key );
	const tabs = cta.fields;
	const fields = {};

	Object.keys( tabs ).forEach( ( tabId ) => {
		Object.keys( tabs[ tabId ] ).forEach( ( fieldId ) => {
			const std =
				tabs[ tabId ][ fieldId ].std !== undefined
					? tabs[ tabId ][ fieldId ].std
					: null;
			fields[ fieldId ] = std;
		} );
	} );

	return fields;
};

export { callToActions, getCta, getDefaults };
