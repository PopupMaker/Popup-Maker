/// <reference types="@testing-library/jest-dom" />
import '@testing-library/jest-dom';
import { render, screen, fireEvent, act } from '@testing-library/react';
import { jest, describe, beforeEach, it, expect } from '@jest/globals';

import { StatusFilter, type ValidStatuses } from '../status';
import type { CallToAction } from '@popup-maker/core-data';

describe( 'StatusFilter', () => {
	const mockItems = [
		{
			id: 1,
			status: 'publish',
			title: { rendered: 'Test 1', raw: 'Test 1' },
			excerpt: { rendered: '', raw: '', protected: false },
			settings: { type: 'link' },
		},
		{
			id: 2,
			status: 'draft',
			title: { rendered: 'Test 2', raw: 'Test 2' },
			excerpt: { rendered: '', raw: '', protected: false },
			settings: { type: 'link' },
		},
	] satisfies Partial< CallToAction< 'edit' > >[];

	const defaultProps = {
		filters: {},
		setFilters: jest.fn(),
		onClose: jest.fn(),
		items: mockItems as CallToAction< 'edit' >[],
		filteredItems: mockItems as CallToAction< 'edit' >[],
	};

	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'renders with default state', () => {
		render( <StatusFilter { ...defaultProps } /> );

		expect( screen.getByText( 'Status:' ) ).toBeInTheDocument();
		expect( screen.getByText( 'All' ) ).toBeInTheDocument();
	} );

	it( 'opens popover on button click', async () => {
		render( <StatusFilter { ...defaultProps } /> );

		const button = screen.getByRole( 'button' );
		await act( async () => {
			fireEvent.click( button );
		} );

		expect(
			screen.getByRole( 'group', { name: 'Status' } )
		).toBeInTheDocument();
	} );

	it( 'shows correct counts for each status', async () => {
		render( <StatusFilter { ...defaultProps } /> );

		const button = screen.getByRole( 'button' );
		await act( async () => {
			fireEvent.click( button );
		} );

		expect( screen.getByLabelText( 'All (2)' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Enabled (1)' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Disabled (1)' ) ).toBeInTheDocument();
	} );

	it( 'calls setFilters with correct value on status change', async () => {
		render( <StatusFilter { ...defaultProps } /> );

		const button = screen.getByRole( 'button' );
		await act( async () => {
			fireEvent.click( button );
		} );

		const draftOption = screen.getByLabelText( 'Disabled (1)' );
		await act( async () => {
			fireEvent.click( draftOption );
		} );

		expect( defaultProps.setFilters ).toHaveBeenCalledWith( {
			status: 'draft',
		} );
	} );

	it( 'closes popover and calls onClose when selecting a status', async () => {
		render( <StatusFilter { ...defaultProps } /> );

		const button = screen.getByRole( 'button' );
		await act( async () => {
			fireEvent.click( button );
		} );

		const draftOption = screen.getByLabelText( 'Disabled (1)' );
		await act( async () => {
			fireEvent.click( draftOption );
		} );

		expect( defaultProps.onClose ).toHaveBeenCalled();
	} );

	it( 'shows only statuses with items when current filter has no matches', async () => {
		const props = {
			...defaultProps,
			filters: { status: 'trash' as ValidStatuses },
			filteredItems: [],
		};

		render( <StatusFilter { ...props } /> );

		const button = screen.getByRole( 'button' );
		await act( async () => {
			fireEvent.click( button );
		} );

		expect( screen.getByLabelText( 'All (0)' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Enabled (0)' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Disabled (0)' ) ).toBeInTheDocument();
		expect(
			screen.queryByLabelText( 'Trash (0)' )
		).not.toBeInTheDocument();
	} );
} );
