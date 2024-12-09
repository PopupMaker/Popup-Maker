/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/
import jQuery from 'jquery';

const $ = jQuery;

const patterns = {
	validate: /^[a-z_][a-z0-9_]*(?:\[(?:\d*|[a-z0-9_]+)\])*$/i,
	key: /[a-z0-9_]+|(?=\[\])/gi,
	push: /^$/,
	fixed: /^\d+$/,
	named: /^[a-z0-9_]+$/i,
};

function FormSerializer( helper, $form ) {
	// private variables
	var data = {},
		pushes = {};

	// private API
	function build( base, key, value ) {
		base[ key ] = value;
		return base;
	}

	function makeObject( root, value ) {
		var keys = root.match( patterns.key ),
			k;

		try {
			value = JSON.parse( value );
		} catch ( Error ) {}

		// nest, nest, ..., nest
		while ( ( k = keys.pop() ) !== undefined ) {
			// foo[]
			if ( patterns.push.test( k ) ) {
				var idx = incrementPush( root.replace( /\[\]$/, '' ) );
				value = build( [], idx, value );
			}

			// foo[n]
			else if ( patterns.fixed.test( k ) ) {
				value = build( [], k, value );
			}

			// foo; foo[bar]
			else if ( patterns.named.test( k ) ) {
				value = build( {}, k, value );
			}
		}

		return value;
	}

	function incrementPush( key ) {
		if ( pushes[ key ] === undefined ) {
			pushes[ key ] = 0;
		}
		return pushes[ key ]++;
	}

	function encode( pair ) {
		switch ( $( '[name="' + pair.name + '"]', $form ).attr( 'type' ) ) {
			case 'checkbox':
				return pair.value === '1' ? true : pair.value;
			default:
				return pair.value;
		}
	}

	function addPair( pair ) {
		if ( ! patterns.validate.test( pair.name ) ) return this;
		var obj = makeObject( pair.name, encode( pair ) );

		data = helper.extend( true, data, obj );
		return this;
	}

	function addPairs( pairs ) {
		if ( ! helper.isArray( pairs ) ) {
			throw new Error( 'formSerializer.addPairs expects an Array' );
		}
		for ( var i = 0, len = pairs.length; i < len; i++ ) {
			this.addPair( pairs[ i ] );
		}
		return this;
	}

	function serialize() {
		return data;
	}

	function serializeJSON() {
		return JSON.stringify( serialize() );
	}

	// public API
	this.addPair = addPair;
	this.addPairs = addPairs;
	this.serialize = serialize;
	this.serializeJSON = serializeJSON;
}

FormSerializer.patterns = patterns;

FormSerializer.serializeObject = function serializeObject() {
	var serialized;

	if ( this.is( 'form' ) ) {
		serialized = this.serializeArray();
	} else {
		serialized = this.find( ':input' ).serializeArray();
	}

	return new FormSerializer( $, this ).addPairs( serialized ).serialize();
};

FormSerializer.serializeJSON = function serializeJSON() {
	var serialized;

	if ( this.is( 'form' ) ) {
		serialized = this.serializeArray();
	} else {
		serialized = this.find( ':input' ).serializeArray();
	}

	return new FormSerializer( $, this ).addPairs( serialized ).serializeJSON();
};

// Add plugin to jQuery
$.fn.pumSerializeObject = FormSerializer.serializeObject;
$.fn.pumSerializeJSON = FormSerializer.serializeJSON;

export default FormSerializer;
