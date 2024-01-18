/**
 * Internal dependencies
 */
import { TEMPLATE_POST_TYPE } from '../../utils/constants';

import DataviewsTemplatesTemplateParts from './dataviews-templates-template-parts';

export default function PageTemplates() {
	return <DataviewsTemplatesTemplateParts postType={ TEMPLATE_POST_TYPE } />;
}
