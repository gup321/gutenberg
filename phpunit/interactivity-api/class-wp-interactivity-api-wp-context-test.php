<?php
/**
 * Unit tests covering the data_wp_context_processor functionality of the
 * WP_Interactivity_API class.
 *
 * @package WordPress
 * @subpackage Interactivity API
 *
 * @group interactivity-api
 */
class Tests_WP_Interactivity_API_WP_Context extends WP_UnitTestCase {
	/**
	 * Instance of WP_Interactivity_API.
	 *
	 * @var WP_Interactivity_API
	 */
	protected $interactivity;

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();
		$this->interactivity = new WP_Interactivity_API();
	}

	public function test_wp_context_directive_sets_attribute() {
		$html           = '
			<div data-wp-context=\'myPlugin::{ "id": "some-id" }\'>
				<div data-wp-bind--id="myPlugin::context.id">Inner content</div>
			</div>
		';
		$processed_html = $this->interactivity->process_directives( $html );
		$p              = new WP_HTML_Tag_Processor( $processed_html );
		$p->next_tag();
		$p->next_tag();
		$this->assertEquals( 'some-id', $p->get_attribute( 'id' ) );
	}

	public function test_directive_merges_context_correctly_upon_wp_context_attribute_on_opening_tag() {
		$this->markTestSkipped();
		$context = new WP_Directive_Context(
			array(
				'myblock'    => array( 'open' => false ),
				'otherblock' => array( 'somekey' => 'somevalue' ),
			)
		);

		$ns     = 'myblock';
		$markup = '<div data-wp-context=\'{ "open": true }\'>';
		$tags   = new WP_HTML_Tag_Processor( $markup );
		$tags->next_tag();

		gutenberg_interactivity_process_wp_context( $tags, $context, $ns );

		$this->assertSame(
			array(
				'myblock'    => array( 'open' => true ),
				'otherblock' => array( 'somekey' => 'somevalue' ),
			),
			$context->get_context()
		);
	}

	public function test_directive_resets_context_correctly_upon_closing_tag() {
		$this->markTestSkipped();
		$context = new WP_Directive_Context(
			array( 'myblock' => array( 'my-key' => 'original-value' ) )
		);

		$context->set_context(
			array( 'myblock' => array( 'my-key' => 'new-value' ) )
		);

		$markup = '</div>';
		$tags   = new WP_HTML_Tag_Processor( $markup );
		$tags->next_tag( array( 'tag_closers' => 'visit' ) );

		gutenberg_interactivity_process_wp_context( $tags, $context, 'myblock' );

		$this->assertSame(
			array( 'my-key' => 'original-value' ),
			$context->get_context()['myblock']
		);
	}

	public function test_directive_doesnt_throw_on_malformed_context_objects() {
		$this->markTestSkipped();
		$context = new WP_Directive_Context(
			array( 'myblock' => array( 'my-key' => 'some-value' ) )
		);

		$markup = '<div data-wp-context=\'{ "wrong_json_object: }\'>';
		$tags   = new WP_HTML_Tag_Processor( $markup );
		$tags->next_tag();

		gutenberg_interactivity_process_wp_context( $tags, $context, 'myblock' );

		$this->assertSame(
			array( 'my-key' => 'some-value' ),
			$context->get_context()['myblock']
		);
	}

	public function test_directive_keeps_working_after_malformed_context_objects() {
		$this->markTestSkipped();
		$context = new WP_Directive_Context();

		$markup = '
			<div data-wp-context=\'{ "my-key": "some-value" }\'>
				<div data-wp-context=\'{ "wrong_json_object: }\'>
				</div>
			</div>
		';
		$tags   = new WP_HTML_Tag_Processor( $markup );

		// Parent div.
		$tags->next_tag( array( 'tag_closers' => 'visit' ) );
		gutenberg_interactivity_process_wp_context( $tags, $context, 'myblock' );

		$this->assertSame(
			array( 'my-key' => 'some-value' ),
			$context->get_context()['myblock']
		);

		// Children div.
		$tags->next_tag( array( 'tag_closers' => 'visit' ) );
		gutenberg_interactivity_process_wp_context( $tags, $context, 'myblock' );

		// Still the same context.
		$this->assertSame(
			array( 'my-key' => 'some-value' ),
			$context->get_context()['myblock']
		);

		// Closing children div.
		$tags->next_tag( array( 'tag_closers' => 'visit' ) );
		gutenberg_interactivity_process_wp_context( $tags, $context, 'myblock' );

		// Still the same context.
		$this->assertSame(
			array( 'my-key' => 'some-value' ),
			$context->get_context()['myblock']
		);

		// Closing parent div.
		$tags->next_tag( array( 'tag_closers' => 'visit' ) );
		gutenberg_interactivity_process_wp_context( $tags, $context, 'myblock' );

		// Now the context is empty.
		$this->assertSame(
			array(),
			$context->get_context()
		);
	}

	public function test_directive_keeps_working_with_a_directive_without_value() {
		$this->markTestSkipped();
		$context = new WP_Directive_Context();

		$markup = '
			<div data-wp-context=\'{ "my-key": "some-value" }\'>
				<div data-wp-context>
				</div>
			</div>
		';
		$tags   = new WP_HTML_Tag_Processor( $markup );

		// Parent div.
		$tags->next_tag( array( 'tag_closers' => 'visit' ) );
		gutenberg_interactivity_process_wp_context( $tags, $context, 'myblock' );

		$this->assertSame(
			array( 'my-key' => 'some-value' ),
			$context->get_context()['myblock']
		);

		// Children div.
		$tags->next_tag( array( 'tag_closers' => 'visit' ) );
		gutenberg_interactivity_process_wp_context( $tags, $context, 'myblock' );

		// Still the same context.
		$this->assertSame(
			array( 'my-key' => 'some-value' ),
			$context->get_context()['myblock']
		);

		// Closing children div.
		$tags->next_tag( array( 'tag_closers' => 'visit' ) );
		gutenberg_interactivity_process_wp_context( $tags, $context, 'myblock' );

		// Still the same context.
		$this->assertSame(
			array( 'my-key' => 'some-value' ),
			$context->get_context()['myblock']
		);

		// Closing parent div.
		$tags->next_tag( array( 'tag_closers' => 'visit' ) );
		gutenberg_interactivity_process_wp_context( $tags, $context, 'myblock' );

		// Now the context is empty.
		$this->assertSame(
			array(),
			$context->get_context()
		);
	}

	public function test_directive_keeps_working_with_an_empty_directive() {
		$this->markTestSkipped();
		$context = new WP_Directive_Context();

		$markup = '
			<div data-wp-context=\'{ "my-key": "some-value" }\'>
				<div data-wp-context="">
				</div>
			</div>
		';
		$tags   = new WP_HTML_Tag_Processor( $markup );

		// Parent div.
		$tags->next_tag( array( 'tag_closers' => 'visit' ) );
		gutenberg_interactivity_process_wp_context( $tags, $context, 'myblock' );

		$this->assertSame(
			array( 'my-key' => 'some-value' ),
			$context->get_context()['myblock']
		);

		// Children div.
		$tags->next_tag( array( 'tag_closers' => 'visit' ) );
		gutenberg_interactivity_process_wp_context( $tags, $context, 'myblock' );

		// Still the same context.
		$this->assertSame(
			array( 'my-key' => 'some-value' ),
			$context->get_context()['myblock']
		);

		// Closing children div.
		$tags->next_tag( array( 'tag_closers' => 'visit' ) );
		gutenberg_interactivity_process_wp_context( $tags, $context, 'myblock' );

		// Still the same context.
		$this->assertSame(
			array( 'my-key' => 'some-value' ),
			$context->get_context()['myblock']
		);

		// Closing parent div.
		$tags->next_tag( array( 'tag_closers' => 'visit' ) );
		gutenberg_interactivity_process_wp_context( $tags, $context, 'myblock' );

		// Now the context is empty.
		$this->assertSame(
			array(),
			$context->get_context()
		);
	}
}
