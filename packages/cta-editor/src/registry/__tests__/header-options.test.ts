import '@testing-library/jest-dom';
import { renderHook } from '@testing-library/react';
import { describe, beforeEach, it, expect } from '@jest/globals';
import {
	EditorHeaderOptionsRegistry,
	registerEditorHeaderOption,
	useEditorHeaderOptions,
	getEditorHeaderOptions,
	registerEditorHeaderOptionGroup,
} from '../header-options';

describe( 'EditorHeaderOptionsRegistry', () => {
	beforeEach( () => {
		EditorHeaderOptionsRegistry.clear();
	} );

	it( 'registers an editor header option correctly', () => {
		const mockOption = {
			id: 'test-option',
			group: 'general',
			priority: 10,
			render: () => null,
		};
		registerEditorHeaderOption( mockOption );
		const items = getEditorHeaderOptions();
		expect( items ).toHaveLength( 1 );
		expect( items[ 0 ] ).toEqual( mockOption );
	} );

	it( 'maintains header option order based on group priority', () => {
		registerEditorHeaderOptionGroup( 'advanced', { priority: 20 } );
		const optionGeneral = {
			id: 'option-general',
			group: 'general',
			priority: 10,
			render: () => null,
		};
		const optionAdvanced = {
			id: 'option-advanced',
			group: 'advanced',
			priority: 10,
			render: () => null,
		};
		registerEditorHeaderOption( optionAdvanced );
		registerEditorHeaderOption( optionGeneral );
		const items = getEditorHeaderOptions();
		expect( items ).toHaveLength( 2 );
		expect( items[ 0 ].id ).toBe( 'option-general' );
		expect( items[ 1 ].id ).toBe( 'option-advanced' );
	} );

	it( 'useEditorHeaderOptions hook returns registered options', () => {
		const mockOption = {
			id: 'hook-test-option',
			group: 'general',
			priority: 10,
			render: () => null,
		};
		registerEditorHeaderOption( mockOption );
		const { result } = renderHook( () => useEditorHeaderOptions() );
		expect( result.current ).toHaveLength( 1 );
		expect( result.current[ 0 ] ).toEqual( mockOption );
	} );

	it( 'ensures duplicate header option IDs are overwritten', () => {
		const mockOption = {
			id: 'duplicate-option',
			group: 'general',
			priority: 10,
			render: () => null,
		};
		registerEditorHeaderOption( mockOption );
		expect( () => registerEditorHeaderOption( mockOption ) ).not.toThrow();
		const items = getEditorHeaderOptions();
		expect( items ).toHaveLength( 1 );
		expect( items[ 0 ] ).toEqual( mockOption );
	} );
} );
