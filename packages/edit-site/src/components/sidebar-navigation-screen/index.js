/**
 * WordPress dependencies
 */
import {
	__experimentalHStack as HStack,
	__experimentalVStack as VStack,
	__experimentalNavigatorToParentButton as NavigatorToParentButton,
	__experimentalText as Text,
} from '@wordpress/components';
import { isRTL, __ } from '@wordpress/i18n';
import { chevronRight, chevronLeft } from '@wordpress/icons';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { store as editSiteStore } from '../../store';
import { unlock } from '../../private-apis';
import SidebarButton from '../sidebar-button';

export default function SidebarNavigationScreen( {
	isRoot,
	title,
	actions,
	content,
	description,
} ) {
	const { dashboardLink } = useSelect( ( select ) => {
		const { getSettings } = unlock( select( editSiteStore ) );
		return {
			dashboardLink: getSettings().__experimentalDashboardLink,
		};
	}, [] );

	return (
		<VStack spacing={ 2 }>
			<HStack
				spacing={ 0 }
				justify="flex-start"
				className="edit-site-sidebar-navigation-screen__title-icon"
			>
				{ ! isRoot ? (
					<NavigatorToParentButton
						as={ SidebarButton }
						icon={ isRTL() ? chevronRight : chevronLeft }
						aria-label={ __( 'Back' ) }
					/>
				) : (
					<SidebarButton
						icon={ isRTL() ? chevronRight : chevronLeft }
						aria-label={ __( 'Navigate to the Dashboard' ) }
						href={ dashboardLink || 'index.php' }
						label={ __( 'Dashboard' ) }
					/>
				) }
				<Text
					className="edit-site-sidebar-navigation-screen__title"
					size="subheadline"
					as="h2"
					weight="600"
					color="white"
				>
					{ title }
				</Text>
				{ actions }
			</HStack>

			<nav className="edit-site-sidebar-navigation-screen__content">
				{ description && (
					<p className="edit-site-sidebar-navigation-screen__description">
						{ description }
					</p>
				) }
				{ content }
			</nav>
		</VStack>
	);
}
