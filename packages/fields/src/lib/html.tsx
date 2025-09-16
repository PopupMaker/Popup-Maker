import type { HtmlFieldProps } from '../types';

const HtmlField = ( { content }: HtmlFieldProps ) => {
	return <div dangerouslySetInnerHTML={ { __html: content ?? '' } } />;
};

export default HtmlField;
