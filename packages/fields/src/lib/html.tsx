import type { HtmlFieldProps } from '../types';

/**
 * Renders raw HTML.
 *
 * SECURITY: `content` MUST be sanitized server-side (via wp_kses in
 * the REST controller) before reaching this component. Never pass
 * unsanitized user input here.
 */
const HtmlField = ( { content }: HtmlFieldProps ): JSX.Element => {
	return <div dangerouslySetInnerHTML={ { __html: content ?? '' } } />;
};

export default HtmlField;
