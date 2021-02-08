const { ctas } = pum_block_editor_vars;
const getCta = ( key ) =>
	callToActions[ key ] || callToActions[ Object.keys( callToActions )[ 0 ] ];

const callToActions = Object.values( ctas );

export { callToActions, getCta };
