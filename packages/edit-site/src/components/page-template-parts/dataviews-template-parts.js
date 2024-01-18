/**
 * Internal dependencies
 */
import { TEMPLATE_PART_POST_TYPE } from '../../utils/constants';

import DataviewsTemplatesTemplateParts from '../page-templates/dataviews-templates-template-parts';

export default function DataviewsTemplateParts() {
	return (
		<DataviewsTemplatesTemplateParts postType={ TEMPLATE_PART_POST_TYPE } />
	);
}
