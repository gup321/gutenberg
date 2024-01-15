/**
 * Internal dependencies
 */
import { test, expect } from './fixtures';

test.describe( 'data-wp-each', () => {
	test.beforeAll( async ( { interactivityUtils: utils } ) => {
		await utils.activatePlugins();
		await utils.addPostWithBlock( 'test/directive-each' );
	} );

	test.beforeEach( async ( { interactivityUtils: utils, page } ) => {
		await page.goto( utils.getLink( 'test/directive-each' ) );
	} );

	test.afterAll( async ( { interactivityUtils: utils } ) => {
		await utils.deactivatePlugins();
		await utils.deleteAllPosts();
	} );

	test( 'should use `item` as the defaul item name in the context', async ( {
		page,
	} ) => {
		const elements = page.getByTestId( 'letters' ).getByTestId( 'item' );
		await expect( elements ).toHaveText( [ 'A', 'B', 'C' ] );
	} );

	test( 'should use the specified item name in the context', async ( {
		page,
	} ) => {
		const elements = page.getByTestId( 'fruits' ).getByTestId( 'item' );
		await expect( elements ).toHaveText( [
			'avocado',
			'banana',
			'cherimoya',
		] );
	} );

	test.describe( 'without `wp-each-key`', () => {
		test.beforeEach( async ( { page } ) => {
			const elements = page.getByTestId( 'fruits' ).getByTestId( 'item' );

			// These tags are included to check that the elements are not unmounted
			// and mounted again. If an element remounts, its tag should be missing.
			await elements.evaluateAll( ( refs ) =>
				refs.forEach( ( ref, index ) => {
					if ( ref instanceof HTMLElement ) {
						ref.dataset.tag = `${ index }`;
					}
				} )
			);
		} );

		test( 'should preserve elements on deletion', async ( { page } ) => {
			const elements = page.getByTestId( 'fruits' ).getByTestId( 'item' );

			// An item is removed when clicked.
			await elements.first().click();

			await expect( elements ).toHaveText( [ 'banana', 'cherimoya' ] );
			await expect( elements.getByText( 'avocado' ) ).toBeHidden();

			// Get the tags. They should not have disappeared.
			const [ banana, cherimoya ] = await elements.all();
			await expect( banana ).toHaveAttribute( 'data-tag', '1' );
			await expect( cherimoya ).toHaveAttribute( 'data-tag', '2' );
		} );

		test( 'should preserve elements on reordering', async ( { page } ) => {
			const elements = page.getByTestId( 'fruits' ).getByTestId( 'item' );

			await page.getByTestId( 'fruits' ).getByTestId( 'rotate' ).click();

			await expect( elements ).toHaveText( [
				'cherimoya',
				'avocado',
				'banana',
			] );

			// Get the tags. They should not have disappeared or changed.
			const [ cherimoya, avocado, banana ] = await elements.all();
			await expect( cherimoya ).toHaveAttribute( 'data-tag', '2' );
			await expect( avocado ).toHaveAttribute( 'data-tag', '0' );
			await expect( banana ).toHaveAttribute( 'data-tag', '1' );
		} );

		test( 'should preserve elements on addition', async ( { page } ) => {
			const elements = page.getByTestId( 'fruits' ).getByTestId( 'item' );

			await page.getByTestId( 'fruits' ).getByTestId( 'add' ).click();

			await expect( elements ).toHaveText( [
				'ananas',
				'avocado',
				'banana',
				'cherimoya',
			] );

			// Get the tags. They should not have disappeared or changed,
			// except for the newly created element.
			const [ ananas, avocado, banana, cherimoya ] = await elements.all();
			await expect( ananas ).not.toHaveAttribute( 'data-tag' );
			await expect( avocado ).toHaveAttribute( 'data-tag', '0' );
			await expect( banana ).toHaveAttribute( 'data-tag', '1' );
			await expect( cherimoya ).toHaveAttribute( 'data-tag', '2' );
		} );

		test( 'should preserve elements on replacement', async ( { page } ) => {
			const elements = page.getByTestId( 'fruits' ).getByTestId( 'item' );

			await page.getByTestId( 'fruits' ).getByTestId( 'replace' ).click();

			await expect( elements ).toHaveText( [
				'ananas',
				'banana',
				'cherimoya',
			] );

			// Get the tags. They should not have disappeared or changed,
			// except for the newly created element.
			const [ ananas, banana, cherimoya ] = await elements.all();
			await expect( ananas ).not.toHaveAttribute( 'data-tag' );
			await expect( banana ).toHaveAttribute( 'data-tag', '1' );
			await expect( cherimoya ).toHaveAttribute( 'data-tag', '2' );
		} );
	} );

	test.describe( 'with `wp-each-key`', () => {
		test.beforeEach( async ( { page } ) => {
			const elements = page.getByTestId( 'books' ).getByTestId( 'item' );

			// These tags are included to check that the elements are not unmounted
			// and mounted again. If an element remounts, its tag should be missing.
			await elements.evaluateAll( ( refs ) =>
				refs.forEach( ( ref, index ) => {
					if ( ref instanceof HTMLElement ) {
						ref.dataset.tag = `${ index }`;
					}
				} )
			);
		} );

		test( 'should preserve elements on deletion', async ( { page } ) => {
			const elements = page.getByTestId( 'books' ).getByTestId( 'item' );

			await expect( elements ).toHaveText( [
				'A Game of Thrones',
				'A Clash of Kings',
				'A Storm of Swords',
			] );

			// An item is removed when clicked.
			await elements.first().click();

			await expect( elements ).toHaveText( [
				'A Clash of Kings',
				'A Storm of Swords',
			] );

			// Get the tags. They should not have disappeared.
			const [ acok, asos ] = await elements.all();
			await expect( acok ).toHaveAttribute( 'data-tag', '1' );
			await expect( asos ).toHaveAttribute( 'data-tag', '2' );
		} );

		test( 'should preserve elements on reordering', async ( { page } ) => {
			const elements = page.getByTestId( 'books' ).getByTestId( 'item' );

			await page.getByTestId( 'books' ).getByTestId( 'rotate' ).click();

			await expect( elements ).toHaveText( [
				'A Storm of Swords',
				'A Game of Thrones',
				'A Clash of Kings',
			] );

			// Get the tags. They should not have disappeared or changed.
			const [ asos, agot, acok ] = await elements.all();
			await expect( asos ).toHaveAttribute( 'data-tag', '2' );
			await expect( agot ).toHaveAttribute( 'data-tag', '0' );
			await expect( acok ).toHaveAttribute( 'data-tag', '1' );
		} );

		test( 'should preserve elements on addition', async ( { page } ) => {
			const elements = page.getByTestId( 'books' ).getByTestId( 'item' );

			await page.getByTestId( 'books' ).getByTestId( 'add' ).click();

			await expect( elements ).toHaveText( [
				'A Feast for Crows',
				'A Game of Thrones',
				'A Clash of Kings',
				'A Storm of Swords',
			] );

			// Get the tags. They should not have disappeared or changed,
			// except for the newly created element.
			const [ affc, agot, acok, asos ] = await elements.all();
			await expect( affc ).not.toHaveAttribute( 'data-tag' );
			await expect( agot ).toHaveAttribute( 'data-tag', '0' );
			await expect( acok ).toHaveAttribute( 'data-tag', '1' );
			await expect( asos ).toHaveAttribute( 'data-tag', '2' );
		} );

		test( 'should preserve elements on replacement', async ( { page } ) => {
			const elements = page.getByTestId( 'books' ).getByTestId( 'item' );

			await page.getByTestId( 'books' ).getByTestId( 'replace' ).click();

			await expect( elements ).toHaveText( [
				'A Feast for Crows',
				'A Clash of Kings',
				'A Storm of Swords',
			] );

			// Get the tags. They should not have disappeared or changed,
			// except for the newly created element.
			const [ affc, acok, asos ] = await elements.all();
			await expect( affc ).not.toHaveAttribute( 'data-tag' );
			await expect( acok ).toHaveAttribute( 'data-tag', '1' );
			await expect( asos ).toHaveAttribute( 'data-tag', '2' );
		} );

		test( 'should preserve elements on modification', async ( {
			page,
		} ) => {
			const elements = page.getByTestId( 'books' ).getByTestId( 'item' );

			await page.getByTestId( 'books' ).getByTestId( 'modify' ).click();

			await expect( elements ).toHaveText( [
				'A GAME OF THRONES',
				'A Clash of Kings',
				'A Storm of Swords',
			] );

			// Get the tags. They should not have disappeared or changed.
			const [ agot, acok, asos ] = await elements.all();
			await expect( agot ).toHaveAttribute( 'data-tag', '0' );
			await expect( acok ).toHaveAttribute( 'data-tag', '1' );
			await expect( asos ).toHaveAttribute( 'data-tag', '2' );
		} );
	} );

	test( 'should respect elements after', async ( { page } ) => {
		const elements = page.getByTestId( 'numbers' ).getByTestId( 'item' );
		await expect( elements ).toHaveText( [ '1', '2', '3', '4' ] );
		await page.getByTestId( 'numbers' ).getByTestId( 'shift' ).click();
		await expect( elements ).toHaveText( [ '2', '3', '4' ] );
		await page
			.getByTestId( 'numbers' )
			.getByTestId( 'unshift' )
			.click( { clickCount: 2 } );
		await expect( elements ).toHaveText( [ '0', '1', '2', '3', '4' ] );
	} );

	test( 'should support initial empty lists', async ( { page } ) => {
		const elements = page.getByTestId( 'empty' ).getByTestId( 'item' );
		await expect( elements ).toHaveText( [ 'item X' ] );
		await page
			.getByTestId( 'empty' )
			.getByTestId( 'add' )
			.click( { clickCount: 2 } );

		await expect( elements ).toHaveText( [ 'item 0', 'item 1', 'item X' ] );
	} );

	test( 'should support multiple siblings inside the template', async ( {
		page,
	} ) => {
		const elements = page.getByTestId( 'siblings' ).getByTestId( 'item' );
		await expect( elements ).toHaveText( [
			'two',
			'2',
			'three',
			'3',
			'four',
			'4',
		] );
		await page.getByTestId( 'siblings' ).getByTestId( 'unshift' ).click();
		await expect( elements ).toHaveText( [
			'one',
			'1',
			'two',
			'2',
			'three',
			'3',
			'four',
			'4',
		] );
	} );

	test( 'should work on navigation', async ( { page } ) => {} );
} );
