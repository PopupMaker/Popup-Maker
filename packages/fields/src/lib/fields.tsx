import Field from './field';

import type { FieldPropsWithOnChange } from '../types';

type Props = {
	fields: FieldPropsWithOnChange[];
};

const Fields = ( props: Props ): JSX.Element => {
	const { fields } = props;

	return (
		<>
			{ fields.map( ( field, i ) => (
				<Field key={ i } { ...field } />
			) ) }
		</>
	);
};

export default Fields;
