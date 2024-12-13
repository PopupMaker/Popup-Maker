import { useCallback, useRef, useState } from '@wordpress/element';

export default function useControlledState< T >(
	value: T,
	defaultValue: T,
	onChange: ( value: T, ...args: any[] ) => void
): [ T, ( value: T, ...args: any[] ) => void ] {
	const [ stateValue, setStateValue ] = useState( value || defaultValue );
	const ref = useRef( value !== undefined );
	const wasControlled = ref.current;
	const isControlled = value !== undefined;
	// Internal state reference for useCallback
	const stateRef = useRef( stateValue );
	if ( wasControlled !== isControlled ) {
		// eslint-disable-next-line no-console
		console.warn(
			`WARN: A component changed from ${
				wasControlled ? 'controlled' : 'uncontrolled'
			} to ${ isControlled ? 'controlled' : 'uncontrolled' }.`
		);
	}

	ref.current = isControlled;

	const setValue = useCallback(
		( _value, ...args ) => {
			const onChangeCaller = ( newValue, ...onChangeArgs ) => {
				if ( onChange ) {
					if ( ! Object.is( stateRef.current, newValue ) ) {
						onChange( newValue, ...onChangeArgs );
					}
				}
				if ( ! isControlled ) {
					stateRef.current = newValue;
				}
			};

			if ( typeof _value === 'function' ) {
				// eslint-disable-next-line no-console
				console.warn(
					'We can not support a function callback. See Github Issues for details https://github.com/adobe/react-spectrum/issues/2320'
				);
				// this supports functional updates https://reactjs.org/docs/hooks-reference.html#functional-updates
				// when someone using useControlledState calls setControlledState(myFunc)
				// this will call our useState setState with a function as well which invokes myFunc and calls onChange with the value from myFunc
				// if we're in an uncontrolled state, then we also return the value of myFunc which to setState looks as though it was just called with myFunc from the beginning
				// otherwise we just return the controlled value, which won't cause a rerender because React knows to bail out when the value is the same
				const updateFunction = ( oldValue, ...functionArgs ) => {
					const interceptedValue = _value(
						isControlled ? stateRef.current : oldValue,
						...functionArgs
					);
					onChangeCaller( interceptedValue, ...args );
					if ( ! isControlled ) {
						return interceptedValue;
					}
					return oldValue;
				};
				setStateValue( updateFunction );
			} else {
				if ( ! isControlled ) {
					setStateValue( _value );
				}
				onChangeCaller( _value, ...args );
			}
		},
		[ isControlled, onChange ]
	);

	// If a controlled component's value prop changes, we need to update stateRef
	if ( isControlled ) {
		stateRef.current = value;
	} else {
		value = stateValue;
	}

	return [ value, setValue ];
}
