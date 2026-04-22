import type { HtmlFieldProps } from '../types';

const HtmlField = ( { content }: HtmlFieldProps ): JSX.Element => {
	return <div dangerouslySetInnerHTML={ { __html: content ?? '' } } />;
};

export default HtmlField;
