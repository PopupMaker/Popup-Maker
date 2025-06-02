import '@testing-library/jest-dom';
import { renderHook } from '@testing-library/react';
import { describe, beforeEach, it, expect } from '@jest/globals';

import {
	EditorHeaderActionsRegistry,
	registerEditorHeaderAction,
	useEditorHeaderActions,
	getEditorHeaderActions,
	registerEditorHeaderActionGroup,
} from '../header-actions';

describe( 'EditorHeaderActionsRegistry', () => {
	beforeEach( () => {
		EditorHeaderActionsRegistry.clear();
	} );

	it( 'registers an editor header action correctly', () => {
		const mockAction = {
			id: 'test-action',
			group: 'general',
			priority: 10,
			render: () => null,
		};
		registerEditorHeaderAction( mockAction );
		const items = getEditorHeaderActions();
		expect( items ).toHaveLength( 1 );
		expect( items[ 0 ] ).toEqual( mockAction );
	} );

	it( 'maintains header action order based on group priority', () => {
		registerEditorHeaderActionGroup( 'custom', { priority: 20 } );
		const actionGeneral = {
			id: 'action-general',
			group: 'general',
			priority: 10,
			render: () => null,
		};
		const actionCustom = {
			id: 'action-custom',
			group: 'custom',
			priority: 10,
			render: () => null,
		};
		registerEditorHeaderAction( actionCustom );
		registerEditorHeaderAction( actionGeneral );
		const items = getEditorHeaderActions();
		expect( items ).toHaveLength( 2 );
		expect( items[ 0 ].id ).toBe( 'action-general' );
		expect( items[ 1 ].id ).toBe( 'action-custom' );
	} );

	it( 'useEditorHeaderActions hook returns registered actions', () => {
		const mockAction = {
			id: 'hook-test-action',
			group: 'general',
			priority: 10,
			render: () => null,
		};
		registerEditorHeaderAction( mockAction );
		const { result } = renderHook( () => useEditorHeaderActions() );
		expect( result.current ).toHaveLength( 1 );
		expect( result.current[ 0 ] ).toEqual( mockAction );
	} );

	it( 'ensures duplicate header action IDs are overwritten', () => {
		const mockAction = {
			id: 'duplicate-action',
			group: 'general',
			priority: 10,
			render: () => null,
		};
		registerEditorHeaderAction( mockAction );
		expect( () => registerEditorHeaderAction( mockAction ) ).not.toThrow();
		const items = getEditorHeaderActions();
		expect( items ).toHaveLength( 1 );
		expect( items[ 0 ] ).toEqual( mockAction );
	} );
} );
