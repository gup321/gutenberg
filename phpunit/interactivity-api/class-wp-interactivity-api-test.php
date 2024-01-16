<?php
/**
 * Unit tests covering WP_Interactivity_API functionality.
 *
 * @package WordPress
 * @subpackage Interactivity API
 *
 * @group interactivity-api
 *
 * @coversDefaultClass WP_Interactivity_API
 */
class Tests_WP_Interactivity_API extends WP_UnitTestCase {
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

	/**
	 * Tests that the initial_state method returns an empty array at the
	 * beginning.
	 *
	 * @covers ::initial_state
	 */
	public function test_initial_state_should_be_empty() {
		$this->assertEquals( array(), $this->interactivity->initial_state( 'myPlugin' ) );
	}

	/**
	 * Tests that the initial_state method can change the state.
	 *
	 * @covers ::initial_state
	 */
	public function test_initial_state_can_be_changed() {
		$state  = array(
			'a'      => 1,
			'b'      => 2,
			'nested' => array( 'c' => 3 ),
		);
		$result = $this->interactivity->initial_state( 'myPlugin', $state );
		$this->assertEquals( $state, $result );
	}

	/**
	 * Tests that different initial states can be merged.
	 *
	 * @covers ::initial_state
	 */
	public function test_initial_state_can_be_merged() {
		$this->interactivity->initial_state( 'myPlugin', array( 'a' => 1 ) );
		$this->interactivity->initial_state( 'myPlugin', array( 'b' => 2 ) );
		$this->interactivity->initial_state( 'otherPlugin', array( 'c' => 3 ) );
		$this->assertEquals(
			array(
				'a' => 1,
				'b' => 2,
			),
			$this->interactivity->initial_state( 'myPlugin' )
		);
		$this->assertEquals(
			array( 'c' => 3 ),
			$this->interactivity->initial_state( 'otherPlugin' )
		);
	}

	/**
	 * Tests that existing properties in the initial state can be overwritten.
	 *
	 * @covers ::initial_state
	 */
	public function test_initial_state_existing_props_can_be_overwritten() {
		$this->interactivity->initial_state( 'myPlugin', array( 'a' => 1 ) );
		$this->interactivity->initial_state( 'myPlugin', array( 'a' => 2 ) );
		$this->assertEquals(
			array( 'a' => 2 ),
			$this->interactivity->initial_state( 'myPlugin' )
		);
	}

	/**
	 * Tests that existing indexed arrays in the initial state are replaced, not
	 * merged.
	 *
	 * @covers ::initial_state
	 */
	public function test_initial_state_existing_indexed_arrays_are_replaced() {
		$this->interactivity->initial_state( 'myPlugin', array( 'a' => array( 1, 2 ) ) );
		$this->interactivity->initial_state( 'myPlugin', array( 'a' => array( 3, 4 ) ) );
		$this->assertEquals(
			array( 'a' => array( 3, 4 ) ),
			$this->interactivity->initial_state( 'myPlugin' )
		);
	}

	/**
	 * Tests that the initial state is correctly printed on the client-side.
	 *
	 * @covers ::print_initial_state
	 */
	public function test_initial_state_is_correctly_printed() {
		$this->interactivity->initial_state( 'myPlugin', array( 'a' => 1 ) );
		$this->interactivity->initial_state( 'myPlugin', array( 'b' => 2 ) );
		$this->interactivity->initial_state( 'otherPlugin', array( 'c' => 3 ) );

		$initial_state_markup = get_echo( array( $this->interactivity, 'print_initial_state' ) );
		preg_match(
			'/<script type="application\/json" id="wp-interactivity-initial-state">.*?(\{.*\}).*?<\/script>/s',
			$initial_state_markup,
			$initial_state_string
		);
		$initial_state = json_decode( $initial_state_string[1], true );

		$this->assertEquals(
			array(
				'myPlugin'    => array(
					'a' => 1,
					'b' => 2,
				),
				'otherPlugin' => array( 'c' => 3 ),
			),
			$initial_state
		);
	}

	/**
	 * Tests that special characters in the initial state are properly escaped.
	 *
	 * @covers ::print_initial_state
	 */
	public function test_initial_state_escapes_special_characters() {
		$this->interactivity->initial_state(
			'myPlugin',
			array(
				'amps' => 'http://site.test/?foo=1&baz=2&bar=3',
				'tags' => 'Do not do this: <!-- <script>',
			)
		);

		$initial_state_markup = get_echo( array( $this->interactivity, 'print_initial_state' ) );
		preg_match(
			'/<script type="application\/json" id="wp-interactivity-initial-state">.*?(\{.*\}).*?<\/script>/s',
			$initial_state_markup,
			$initial_state_string
		);

		$this->assertEquals(
			'{"myPlugin":{"amps":"http:\/\/site.test\/?foo=1\u0026baz=2\u0026bar=3","tags":"Do not do this: \u003C!-- \u003Cscript\u003E"}}',
			$initial_state_string[1]
		);
	}

	public function test_parse_directive_value() {
		$parse_directive_value = new ReflectionMethod( $this->interactivity, 'parse_directive_value' );
		$parse_directive_value->setAccessible( true );

		$result = $parse_directive_value->invoke( $this->interactivity, 'state.foo', 'myPlugin' );
		$this->assertEquals( array( 'myPlugin', 'state.foo' ), $result );

		$result = $parse_directive_value->invoke( $this->interactivity, 'otherPlugin::state.foo', 'myPlugin' );
		$this->assertEquals( array( 'otherPlugin', 'state.foo' ), $result );

		$result = $parse_directive_value->invoke( $this->interactivity, '{ "isOpen": false }', 'myPlugin' );
		$this->assertEquals( array( 'myPlugin', array( 'isOpen' => false ) ), $result );

		$result = $parse_directive_value->invoke( $this->interactivity, 'otherPlugin::{ "isOpen": false }', 'myPlugin' );
		$this->assertEquals( array( 'otherPlugin', array( 'isOpen' => false ) ), $result );
	}

	public function test_parse_directive_value_invalid_json() {
		$parse_directive_value = new ReflectionMethod( $this->interactivity, 'parse_directive_value' );
		$parse_directive_value->setAccessible( true );

		// Invalid JSON due to missing quotes. Returns the original value.
		$result = $parse_directive_value->invoke( $this->interactivity, '{ isOpen: false }', 'myPlugin' );
		$this->assertEquals( array( 'myPlugin', '{ isOpen: false }' ), $result );

		// Null string. Returns null.
		$result = $parse_directive_value->invoke( $this->interactivity, 'null', 'myPlugin' );
		$this->assertEquals( array( 'myPlugin', null ), $result );
	}

	public function test_extract_directive_prefix_and_suffix() {
		$extract_directive_prefix_and_suffix = new ReflectionMethod( $this->interactivity, 'extract_directive_prefix_and_suffix' );
		$extract_directive_prefix_and_suffix->setAccessible( true );

		$result = $extract_directive_prefix_and_suffix->invoke( $this->interactivity, 'data-wp-interactive' );
		$this->assertEquals( array( 'data-wp-interactive' ), $result );

		$result = $extract_directive_prefix_and_suffix->invoke( $this->interactivity, 'data-wp-bind--src' );
		$this->assertEquals( array( 'data-wp-bind', 'src' ), $result );

		$result = $extract_directive_prefix_and_suffix->invoke( $this->interactivity, 'data-wp-foo--and--bar' );
		$this->assertEquals( array( 'data-wp-foo', 'and--bar' ), $result );
	}

	/**
	 * Tests that the process_directives method doesn't change the HTML if it
	 * doesn't contain directives.
	 *
	 * @covers ::process_directives
	 */
	public function test_process_directives_do_nothing_without_directives() {
		$html           = '<div>Inner content here</div>';
		$processed_html = $this->interactivity->process_directives( $html );
		$this->assertEquals( $html, $processed_html );

		$html           = '<div><span>Content</span><strong>More Content</strong></div>';
		$processed_html = $this->interactivity->process_directives( $html );
		$this->assertEquals( $html, $processed_html );
	}

	/**
	 * Tests that the process_directives method doesn't change the HTML if it
	 * doesn't contain directives.
	 *
	 * @covers ::process_directives
	 */
	public function test_process_directives_works_with_bind() {
		$this->markTestSkipped();
		$this->interactivity->initial_state( 'myPlugin', array( 'text' => 'some text' ) );
		$html           = '<div data-wp-class="myPlugin::state.text">Inner content here</div>';
		$processed_html = $this->interactivity->process_directives( $html );
		$p              = new WP_HTML_Tag_Processor( $processed_html );
		$p->next_tag();
		$this->assertEquals( 'some text', $p->get_attribute( 'class' ) );
	}

	/**
	 * Tests that the process_directives returns the same HTML if it contains
	 * unbalanced tags.
	 *
	 * @covers ::process_directives
	 */
	public function test_process_directives_dont_process_if_contains_unbalanced_tags() {
		$this->markTestSkipped();
		$html           = '<div>Inner content here</div>';
		$processed_html = $this->interactivity->process_directives( $html );
		$this->assertEquals( $html, $processed_html );

		$html           = '<div><span>Content</span><strong>More Content</strong></div>';
		$processed_html = $this->interactivity->process_directives( $html );
		$this->assertEquals( $html, $processed_html );
	}
}
