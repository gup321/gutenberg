<?php
/**
 * Unit tests covering the data_wp_text_processor functionality of the
 * WP_Interactivity_API class.
 *
 * @package WordPress
 * @subpackage Interactivity API
 *
 * @group interactivity-api
 */
class Tests_WP_Interactivity_API_WP_Text extends WP_UnitTestCase {
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
		$this->interactivity->initial_state( 'myPlugin', array( 'text' => 'Updated' ) );
	}

	public function test_wp_text_sets_inner_content() {
		$html     = '<div class="test" data-wp-text="myPlugin::state.text">Text</div>';
		$new_html = $this->interactivity->process_directives( $html );
		$this->assertEquals( '<div class="test" data-wp-text="myPlugin::state.text">Updated</div>', $new_html );
	}

	public function test_wp_text_sets_inner_content_numbers() {
		$this->interactivity->initial_state( 'myPlugin', array( 'number' => 100 ) );
		$html     = '<div class="test" data-wp-text="myPlugin::state.number">Text</div>';
		$new_html = $this->interactivity->process_directives( $html );
		$this->assertEquals( '<div class="test" data-wp-text="myPlugin::state.number">100</div>', $new_html );
	}

	public function test_wp_text_removes_inner_content_on_types_that_are_not_strings_or_numbers() {
		$this->interactivity->initial_state(
			'myPlugin',
			array(
				'true'  => true,
				'false' => false,
				'null'  => null,
				'array' => array(),
				'func'  => function () {},
			)
		);
		$html     = '<div class="test" data-wp-text="myPlugin::state.true">Text</div>';
		$new_html = $this->interactivity->process_directives( $html );
		$this->assertEquals( '<div class="test" data-wp-text="myPlugin::state.true"></div>', $new_html );

		$html     = '<div class="test" data-wp-text="myPlugin::state.false">Text</div>';
		$new_html = $this->interactivity->process_directives( $html );
		$this->assertEquals( '<div class="test" data-wp-text="myPlugin::state.false"></div>', $new_html );

		$html     = '<div class="test" data-wp-text="myPlugin::state.null">Text</div>';
		$new_html = $this->interactivity->process_directives( $html );
		$this->assertEquals( '<div class="test" data-wp-text="myPlugin::state.null"></div>', $new_html );

		$html     = '<div class="test" data-wp-text="myPlugin::state.array">Text</div>';
		$new_html = $this->interactivity->process_directives( $html );
		$this->assertEquals( '<div class="test" data-wp-text="myPlugin::state.array"></div>', $new_html );

		$html     = '<div class="test" data-wp-text="myPlugin::state.func">Text</div>';
		$new_html = $this->interactivity->process_directives( $html );
		$this->assertEquals( '<div class="test" data-wp-text="myPlugin::state.func"></div>', $new_html );
	}

	public function test_wp_text_sets_inner_content_with_nested_tags() {
		$html     = '<div class="test" data-wp-text="myPlugin::state.text"><span>Text</span></div>';
		$new_html = $this->interactivity->process_directives( $html );
		$this->assertEquals( '<div class="test" data-wp-text="myPlugin::state.text">Updated</div>', $new_html );
	}

	public function test_wp_text_sets_inner_content_even_with_unbalanced_tags_inside() {
		$html     = '<div class="test" data-wp-text="myPlugin::state.text"><span>Text</div>';
		$new_html = $this->interactivity->process_directives( $html );
		$this->assertEquals( '<div class="test" data-wp-text="myPlugin::state.text">Updated</div>', $new_html );
	}

	public function test_wp_text_cant_set_inner_html_in_the_content() {
		$this->interactivity->initial_state( 'myPlugin', array( 'text' => '<span>Updated</span>' ) );
		$html     = '<div class="test" data-wp-text="myPlugin::state.text">Text</div>';
		$new_html = $this->interactivity->process_directives( $html );
		$this->assertEquals( '<div class="test" data-wp-text="myPlugin::state.text">&lt;span&gt;Updated&lt;/span&gt;</div>', $new_html );
	}
}
