import '@testing-library/jest-dom/jest-globals';
import '@testing-library/jest-dom';
import { jest, describe, beforeEach, it, expect } from '@jest/globals';
import { render, screen, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

import { SlotFillProvider } from '@wordpress/components';
import type { CallToAction } from '@popup-maker/core-data';
import { TypeFilter } from '../type';

describe( 'TypeFilter', () => {
	beforeEach( () => {
		window.popupMakerCtaAdmin = {
			cta_types: [
				{
					key: 'button',
					label: 'Button',
					fields: [],
				},
				{
					key: 'link',
					label: 'Link',
					fields: [],
				},
			],
		};
	} );

	const renderWithProviders = ( ui: React.ReactElement ) => {
		return render( <SlotFillProvider>{ ui }</SlotFillProvider> );
	};

	const mockItems = [
		{
			settings: {
				type: 'link',
			},
		},
		{
			settings: {
				type: 'link',
			},
		},
	] satisfies Partial< CallToAction< 'edit' > >[];

	it( 'shows popover when clicked', async () => {
		const setFilters = jest.fn();
		renderWithProviders(
			<TypeFilter
				filters={ {} }
				onClose={ jest.fn() }
				items={ mockItems as CallToAction< 'edit' >[] }
				filteredItems={ mockItems as CallToAction< 'edit' >[] }
				setFilters={ setFilters }
			/>
		);
		// Click the filter button
		const button = screen.getByRole( 'button' );
		await act( async () => {
			await userEvent.click( button );
		} );

		// Wait for the popover to be rendered
		await waitFor(
			() => {
				const allOption = screen.getByRole( 'radio', {
					name: 'All (2)',
				} );
				expect( allOption ).toBeInTheDocument();
				expect( allOption ).toBeChecked();
			},
			{ timeout: 2000 }
		);
	} );

	it( 'filters items correctly', async () => {
		const setFilters = jest.fn();
		renderWithProviders(
			<TypeFilter
				filters={ {} }
				onClose={ jest.fn() }
				items={ mockItems as CallToAction< 'edit' >[] }
				filteredItems={ mockItems as CallToAction< 'edit' >[] }
				setFilters={ setFilters }
			/>
		);

		// Click the filter button
		const button = screen.getByRole( 'button' );
		await act( async () => {
			await userEvent.click( button );
		} );

		// Wait for the popover to be rendered
		await waitFor(
			() => {
				const linkOption = screen.getByRole( 'radio', {
					name: 'Link (2)',
				} );
				expect( linkOption ).toBeInTheDocument();
			},
			{ timeout: 2000 }
		);
	} );

	it( 'calls setFilters with correct value when type is selected', async () => {
		const setFilters = jest.fn();
		renderWithProviders(
			<TypeFilter
				filters={ {} }
				onClose={ jest.fn() }
				items={ mockItems as CallToAction< 'edit' >[] }
				filteredItems={ mockItems as CallToAction< 'edit' >[] }
				setFilters={ setFilters }
			/>
		);

		// Click the filter button
		const button = screen.getByRole( 'button' );
		await act( async () => {
			await userEvent.click( button );
		} );

		// Wait for the popover to be rendered and click the Link option
		await waitFor(
			() => {
				const linkOption = screen.getByRole( 'radio', {
					name: 'Link (2)',
				} );
				expect( linkOption ).toBeInTheDocument();
				return linkOption;
			},
			{ timeout: 2000 }
		).then( async ( linkOption ) => {
			await act( async () => {
				await userEvent.click( linkOption as HTMLElement );
			} );
		} );

		expect( setFilters ).toHaveBeenCalledWith( { type: 'link' } );
	} );

	it( 'calls setFilters with undefined when "All" is selected', async () => {
		const setFilters = jest.fn();
		renderWithProviders(
			<TypeFilter
				filters={ { type: 'link' } } // Start with Link selected
				onClose={ jest.fn() }
				items={ mockItems as CallToAction< 'edit' >[] }
				filteredItems={ mockItems as CallToAction< 'edit' >[] }
				setFilters={ setFilters }
			/>
		);

		// Click the filter button
		const button = screen.getByRole( 'button' );
		await act( async () => {
			await userEvent.click( button );
		} );

		// Wait for the popover to be rendered and click the All option
		await waitFor(
			() => {
				const allOption = screen.getByRole( 'radio', {
					name: 'All (2)',
				} );
				expect( allOption ).toBeInTheDocument();
				return allOption;
			},
			{ timeout: 2000 }
		).then( async ( allOption ) => {
			await act( async () => {
				await userEvent.click( allOption as HTMLElement );
			} );
		} );

		expect( setFilters ).toHaveBeenCalledWith( { type: undefined } );
	} );
} );
