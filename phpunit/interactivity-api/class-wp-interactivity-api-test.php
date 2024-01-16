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
	 * Tests that the initial_state method can merge states.
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
	 * Tests that existing properties in the initial_state can be overwritten.
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
	 * Tests that existing indexed arrays in the initial_state are replaced, not
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
	 * Tests that the initial_state is correctly printed on the client-side.
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
	 * Tests that special characters in the initial_state are properly escaped.
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
}
