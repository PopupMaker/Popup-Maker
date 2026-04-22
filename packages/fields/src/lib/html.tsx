import type { HtmlFieldProps } from '../types';

/**
 * Renders raw HTML.
 *
 * SECURITY: `content` MUST be sanitized server-side (via wp_kses in
 * the REST controller) before reaching this component. Never pass
 * unsanitized user input here.
 *
 * @param {HtmlFieldProps} props         Component props.
 * @param {string}         props.content HTML string (pre-sanitized).
 * @return {JSX.Element} Rendered HTML div.
 */
const HtmlField = ( { content }: HtmlFieldProps ): JSX.Element => {
	return <div dangerouslySetInnerHTML={ { __html: content ?? '' } } />;
};

export default HtmlField;
