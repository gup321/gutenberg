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
		$this->interactivity->initial_state( 'myPlugin', array( 'id' => 'some-id' ) );
	}

	public function test_wp_bind_directive_sets_attribute() {
		$html           = '<div data-wp-bind--id="myPlugin::state.id">Inner content</div>';
		$processed_html = $this->interactivity->process_directives( $html );
		$p              = new WP_HTML_Tag_Processor( $processed_html );
		$p->next_tag();
		$this->assertEquals( 'some-id', $p->get_attribute( 'id' ) );
	}

	public function test_wp_bind_directive_ignores_empty_bound_attribute() {
		$html           = '<div data-wp-bind="myPlugin::state.id">Inner content</div>';
		$processed_html = $this->interactivity->process_directives( $html );
		$this->assertEquals( $html, $processed_html );
	}

	public function test_wp_bind_directive_doesnt_do_anything_on_non_existent_references() {
		$html           = '<div data-wp-bind="myPlugin::state.nonExistengKey">Inner content</div>';
		$processed_html = $this->interactivity->process_directives( $html );
		$this->assertEquals( $html, $processed_html );
	}
}
