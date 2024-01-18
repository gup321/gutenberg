<?php
/**
 * Unit tests covering the data_wp_bind_processor functionality of the
 * WP_Interactivity_API class.
 *
 * @package WordPress
 * @subpackage Interactivity API
 *
 * @group interactivity-api
 */
class Tests_WP_Interactivity_API_WP_Bind extends WP_UnitTestCase {
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
		$this->interactivity->initial_state(
			'myPlugin',
			array(
				'id'          => 'some-id',
				'width'       => 100,
				'isOpen'      => false,
				'null'        => null,
				'trueString'  => 'true',
				'falseString' => 'false',
			)
		);
	}

	private function process_directives( $html ) {
		$new_html = $this->interactivity->process_directives( $html );
		$p        = new WP_HTML_Tag_Processor( $new_html );
		$p->next_tag();
		return array( $p, $new_html );
	}

	public function test_wp_bind_sets_attribute() {
		$html    = '<div data-wp-bind--id="myPlugin::state.id">Text</div>';
		list($p) = $this->process_directives( $html );
		$this->assertEquals( 'some-id', $p->get_attribute( 'id' ) );
	}

	public function test_wp_bind_replaces_attribute() {
		$html    = '<div id="other-id" data-wp-bind--id="myPlugin::state.id">Text</div>';
		list($p) = $this->process_directives( $html );
		$this->assertEquals( 'some-id', $p->get_attribute( 'id' ) );
	}

	public function test_wp_bind_sets_number_value() {
		$html    = '<img data-wp-bind--width="myPlugin::state.width">';
		list($p) = $this->process_directives( $html );
		$this->assertEquals( '100', $p->get_attribute( 'width' ) );
	}

	public function test_wp_bind_sets_true_string() {
		$html               = '<div data-wp-bind--id="myPlugin::state.trueString">Text</div>';
		list($p, $new_html) = $this->process_directives( $html );
		$this->assertEquals( 'true', $p->get_attribute( 'id' ) );
		$this->assertEquals( '<div id="true" data-wp-bind--id="myPlugin::state.trueString">Text</div>', $new_html );
	}

	public function test_wp_bind_sets_false_string() {
		$html               = '<div data-wp-bind--id="myPlugin::state.falseString">Text</div>';
		list($p, $new_html) = $this->process_directives( $html );
		$this->assertEquals( 'false', $p->get_attribute( 'id' ) );
		$this->assertEquals( '<div id="false" data-wp-bind--id="myPlugin::state.falseString">Text</div>', $new_html );
	}

	public function test_wp_bind_ignores_empty_bound_attribute() {
		$html     = '<div data-wp-bind="myPlugin::state.id">Text</div>';
		$new_html = $this->interactivity->process_directives( $html );
		$this->assertEquals( $html, $new_html );
	}

	public function test_wp_bind_doesnt_do_anything_on_non_existent_references() {
		$html     = '<div data-wp-bind--id="myPlugin::state.nonExistengKey">Text</div>';
		$new_html = $this->interactivity->process_directives( $html );
		$this->assertEquals( $html, $new_html );
	}

	public function test_wp_bind_ignores_empty_value() {
		$html     = '<div data-wp-bind-id="">Text</div>';
		$new_html = $this->interactivity->process_directives( $html );
		$this->assertEquals( $html, $new_html );
	}

	public function test_wp_bind_ignores_without_value() {
		$html     = '<div data-wp-bind-id>Text</div>';
		$new_html = $this->interactivity->process_directives( $html );
		$this->assertEquals( $html, $new_html );
	}

	public function test_wp_bind_adds_boolean_attribute_if_true() {
		$html               = '<div data-wp-bind--hidden="myPlugin::!state.isOpen">Text</div>';
		list($p, $new_html) = $this->process_directives( $html );
		$this->assertTrue( $p->get_attribute( 'hidden' ) );
		$this->assertEquals( '<div hidden data-wp-bind--hidden="myPlugin::!state.isOpen">Text</div>', $new_html );
	}

	public function test_wp_bind_replaces_existing_attribute_if_true() {
		$html               = '<div hidden="true" data-wp-bind--hidden="myPlugin::!state.isOpen">Text</div>';
		list($p, $new_html) = $this->process_directives( $html );
		$this->assertTrue( $p->get_attribute( 'hidden' ) );
		$this->assertEquals( '<div hidden data-wp-bind--hidden="myPlugin::!state.isOpen">Text</div>', $new_html );
	}

	public function test_wp_bind_doesnt_add_boolean_attribute_if_false_or_null() {
		$html               = '<div data-wp-bind--hidden="myPlugin::state.isOpen">Text</div>';
		list($p, $new_html) = $this->process_directives( $html );
		$this->assertNull( $p->get_attribute( 'hidden' ) );
		$this->assertEquals( $html, $new_html );

		$html               = '<div data-wp-bind--hidden="myPlugin::state.null">Text</div>';
		list($p, $new_html) = $this->process_directives( $html );
		$this->assertNull( $p->get_attribute( 'hidden' ) );
		$this->assertEquals( $html, $new_html );
	}

	public function test_wp_bind_removes_boolean_attribute_if_false_or_null() {
		$html    = '<div hidden data-wp-bind--hidden="myPlugin::state.isOpen">Text</div>';
		list($p) = $this->process_directives( $html );
		$this->assertNull( $p->get_attribute( 'hidden' ) );

		$html    = '<div hidden data-wp-bind--hidden="myPlugin::state.null">Text</div>';
		list($p) = $this->process_directives( $html );
		$this->assertNull( $p->get_attribute( 'hidden' ) );
	}

	public function test_wp_bind_adds_value_if_true_in_aria_or_data_attributes() {
		$html               = '<div data-wp-bind--aria-hidden="myPlugin::!state.isOpen">Text</div>';
		list($p, $new_html) = $this->process_directives( $html );
		$this->assertEquals( 'true', $p->get_attribute( 'aria-hidden' ) );
		$this->assertEquals( '<div aria-hidden="true" data-wp-bind--aria-hidden="myPlugin::!state.isOpen">Text</div>', $new_html );

		$html               = '<div data-wp-bind--data-is-closed="myPlugin::!state.isOpen">Text</div>';
		list($p, $new_html) = $this->process_directives( $html );
		$this->assertEquals( 'true', $p->get_attribute( 'data-is-closed' ) );
		$this->assertEquals( '<div data-is-closed="true" data-wp-bind--data-is-closed="myPlugin::!state.isOpen">Text</div>', $new_html );
	}

	public function test_wp_bind_replaces_value_if_true_in_aria_or_data_attributes() {
		$html               = '<div aria-hidden="false" data-wp-bind--aria-hidden="myPlugin::!state.isOpen">Text</div>';
		list($p, $new_html) = $this->process_directives( $html );
		$this->assertEquals( 'true', $p->get_attribute( 'aria-hidden' ) );
		$this->assertEquals( '<div aria-hidden="true" data-wp-bind--aria-hidden="myPlugin::!state.isOpen">Text</div>', $new_html );

		$html     = '<div data-is-closed="false" data-wp-bind--data-is-closed="myPlugin::!state.isOpen">Text</div>';
		$new_html = $this->interactivity->process_directives( $html );
		$p        = new WP_HTML_Tag_Processor( $new_html );
		$p->next_tag();
		$this->assertEquals( 'true', $p->get_attribute( 'data-is-closed' ) );
		$this->assertEquals( '<div data-is-closed="true" data-wp-bind--data-is-closed="myPlugin::!state.isOpen">Text</div>', $new_html );
	}

	public function test_wp_bind_adds_value_if_false_in_aria_or_data_attributes() {
		$html               = '<div data-wp-bind--aria-hidden="myPlugin::state.isOpen">Text</div>';
		list($p, $new_html) = $this->process_directives( $html );
		$this->assertEquals( 'false', $p->get_attribute( 'aria-hidden' ) );
		$this->assertEquals( '<div aria-hidden="false" data-wp-bind--aria-hidden="myPlugin::state.isOpen">Text</div>', $new_html );

		$html               = '<div data-wp-bind--data-is-closed="myPlugin::state.isOpen">Text</div>';
		list($p, $new_html) = $this->process_directives( $html );
		$this->assertEquals( 'false', $p->get_attribute( 'data-is-closed' ) );
		$this->assertEquals( '<div data-is-closed="false" data-wp-bind--data-is-closed="myPlugin::state.isOpen">Text</div>', $new_html );
	}

	public function test_wp_bind_replaces_value_if_false_in_aria_or_data_attributes() {
		$html               = '<div data-wp-bind--aria-hidden="myPlugin::state.isOpen">Text</div>';
		list($p, $new_html) = $this->process_directives( $html );
		$this->assertEquals( 'false', $p->get_attribute( 'aria-hidden' ) );
		$this->assertEquals( '<div aria-hidden="false" data-wp-bind--aria-hidden="myPlugin::state.isOpen">Text</div>', $new_html );

		$html               = '<div data-wp-bind--data-is-closed="myPlugin::state.isOpen">Text</div>';
		list($p, $new_html) = $this->process_directives( $html );
		$this->assertEquals( 'false', $p->get_attribute( 'data-is-closed' ) );
		$this->assertEquals( '<div data-is-closed="false" data-wp-bind--data-is-closed="myPlugin::state.isOpen">Text</div>', $new_html );
	}

	public function test_wp_bind_removes_value_if_null_in_aria_or_data_attributes() {
		$html    = '<div aria-hidden="true" data-wp-bind--aria-hidden="myPlugin::state.null">Text</div>';
		list($p) = $this->process_directives( $html );
		$this->assertNull( $p->get_attribute( 'aria-hidden' ) );

		$html    = '<div data-is-closed="true" data-wp-bind--data-is-closed="myPlugin::state.null">Text</div>';
		list($p) = $this->process_directives( $html );
		$this->assertNull( $p->get_attribute( 'data-is-closed' ) );
	}

	public function test_wp_bind_handles_nested_bindings() {
		$html    = '<div data-wp-bind--id="myPlugin::state.id"><img data-wp-bind--width="myPlugin::state.width"></div>';
		list($p) = $this->process_directives( $html );
		$this->assertEquals( 'some-id', $p->get_attribute( 'id' ) );
		$p->next_tag();
		$this->assertEquals( '100', $p->get_attribute( 'width' ) );
	}
}
