const { ctas: callToActions } = pum_block_editor_vars;
const getCta = ( key ) => callToActions[ key ] || callToActions[ Object.keys( callToActions )[ 0 ] ];

export { getCta };
