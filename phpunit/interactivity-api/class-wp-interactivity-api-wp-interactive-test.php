<?php
/**
 * Unit tests covering the data_wp_interactive_processor functionality of the
 * WP_Interactivity_API class.
 *
 * @package WordPress
 * @subpackage Interactivity API
 *
 * @group interactivity-api
 */
class Tests_WP_Interactivity_API_WP_Interactive extends WP_UnitTestCase {
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
		$this->interactivity->initial_state( 'myPlugin', array( 'id' => 'some-id' ) );
		$this->interactivity->initial_state( 'otherPlugin', array( 'id' => 'other-id' ) );
	}

	private function process_directives( $html ) {
		$new_html = $this->interactivity->process_directives( $html );
		$p        = new WP_HTML_Tag_Processor( $new_html );
		$p->next_tag( array( 'class_name' => 'test' ) );
		return array( $p, $new_html );
	}

	public function test_wp_interactive_sets_a_default_namespace() {
		$html    = '
			<div data-wp-interactive=\'{ "namespace": "myPlugin" }\'>
				<div class="test" data-wp-bind--id="state.id">Text</div>
			</div>
		';
		list($p) = $this->process_directives( $html );
		$this->assertEquals( 'some-id', $p->get_attribute( 'id' ) );
	}

	public function test_wp_interactive_replaces_the_previous_default_namespace() {
		$html    = '
			<div data-wp-interactive=\'{ "namespace": "otherPlugin" }\'>
				<div data-wp-interactive=\'{ "namespace": "myPlugin" }\'>
					<div class="test" data-wp-bind--id="state.id">Text</div>
				</div>
				<div class="test" data-wp-bind--id="state.id">Text</div>
			</div>
		';
		list($p) = $this->process_directives( $html );
		$this->assertEquals( 'some-id', $p->get_attribute( 'id' ) );
		$p->next_tag( array( 'class_name' => 'test' ) );
		$this->assertEquals( 'other-id', $p->get_attribute( 'id' ) );
	}

	public function test_wp_interactive_without_namespace_doesnt_replace_the_previous_default_namespace() {
		$html    = '
			<div data-wp-interactive=\'{ "namespace": "myPlugin" }\'>
				<div data-wp-interactive=\'{}\'>
					<div class="test" data-wp-bind--id="state.id">Text</div>
				</div>
				<div class="test" data-wp-bind--id="state.id">Text</div>
			</div>
		';
		list($p) = $this->process_directives( $html );
		$this->assertEquals( 'some-id', $p->get_attribute( 'id' ) );
		$p->next_tag( array( 'class_name' => 'test' ) );
		$this->assertEquals( 'some-id', $p->get_attribute( 'id' ) );
	}

	public function test_wp_interactive_with_empty_value_doesnt_replace_the_previous_default_namespace() {
		$html    = '
			<div data-wp-interactive=\'{ "namespace": "myPlugin" }\'>
				<div data-wp-interactive="">
					<div class="test" data-wp-bind--id="state.id">Text</div>
				</div>
				<div class="test" data-wp-bind--id="state.id">Text</div>
			</div>
		';
		list($p) = $this->process_directives( $html );
		$this->assertEquals( 'some-id', $p->get_attribute( 'id' ) );
		$p->next_tag( array( 'class_name' => 'test' ) );
		$this->assertEquals( 'some-id', $p->get_attribute( 'id' ) );
	}

	public function test_wp_interactive_without_value_doesnt_replace_the_previous_default_namespace() {
		$html    = '
			<div data-wp-interactive=\'{ "namespace": "myPlugin" }\'>
				<div data-wp-interactive>
					<div class="test" data-wp-bind--id="state.id">Text</div>
				</div>
				<div class="test" data-wp-bind--id="state.id">Text</div>
			</div>
		';
		list($p) = $this->process_directives( $html );
		$this->assertEquals( 'some-id', $p->get_attribute( 'id' ) );
		$p->next_tag( array( 'class_name' => 'test' ) );
		$this->assertEquals( 'some-id', $p->get_attribute( 'id' ) );
	}

	public function test_wp_interactive_namespace_can_be_override_by_custom_one() {
		$html    = '
			<div data-wp-interactive=\'{ "namespace": "myPlugin" }\'>
				<div class="test" data-wp-bind--id="otherPlugin::state.id">Text</div>
			</div>
		';
		list($p) = $this->process_directives( $html );
		$this->assertEquals( 'other-id', $p->get_attribute( 'id' ) );
	}
}
