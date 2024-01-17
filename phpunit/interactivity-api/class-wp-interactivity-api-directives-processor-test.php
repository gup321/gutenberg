<?php
/**
 * Unit tests covering WP_Interactivity_API_Directives_Processor functionality.
 *
 * @package WordPress
 * @subpackage Interactivity API
 *
 * @group interactivity-api
 *
 * @coversDefaultClass WP_Interactivity_API_Directives_Processor
 */
class Tests_WP_Interactivity_API_Directives_Processor extends WP_UnitTestCase {
	/**
	 * Instance of WP_Interactivity_API_Directives_Processor.
	 *
	 * @var WP_Interactivity_API_Directives_Processor
	 */
	protected $directives_processor;

	/**
	 * Tests the get_content_between_balanced_tags method on a standard tag.
	 *
	 * @covers ::get_content_between_balanced_tags
	 */
	public function test_get_content_between_balanced_tags_standard_tag() {
		$html = '<div>Inner content here</div>';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$this->assertEquals( 'Inner content here', $p->get_content_between_balanced_tags() );

		$html = '<div>Inner content here</div><div></div>';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$this->assertEquals( 'Inner content here', $p->get_content_between_balanced_tags() );
	}

	/**
	 * Tests the get_content_between_balanced_tags method on an empty tag.
	 *
	 * @covers ::get_content_between_balanced_tags
	 */
	public function test_get_content_between_balanced_tags_empty_tag() {
		$html = '<div></div>';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$this->assertEquals( '', $p->get_content_between_balanced_tags() );
	}

	/**
	 * Tests the get_content_between_balanced_tags method with a self-closing tag.
	 *
	 * @covers ::get_content_between_balanced_tags
	 */
	public function test_get_content_between_balanced_tags_self_closing_tag() {
		$html = '<img src="example.jpg">';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$this->assertFalse( $p->get_content_between_balanced_tags() );
	}

	/**
	 * Tests the get_content_between_balanced_tags method with nested tags.
	 *
	 * @covers ::get_content_between_balanced_tags
	 */
	public function test_get_content_between_balanced_tags_nested_tags() {
		$html = '<div><span>Content</span><strong>More Content</strong></div>';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$this->assertEquals( '<span>Content</span><strong>More Content</strong>', $p->get_content_between_balanced_tags() );

		$html = '<div><div>Content</div><img src="example.jpg"></div>';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$this->assertEquals( '<div>Content</div><img src="example.jpg">', $p->get_content_between_balanced_tags() );
	}

	/**
	 * Tests the get_content_between_balanced_tags method when no tags are present.
	 *
	 * @covers ::get_content_between_balanced_tags
	 */
	public function test_get_content_between_balanced_tags_no_tags() {
		$html = 'Just a string with no HTML tags.';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$this->assertFalse( $p->get_content_between_balanced_tags() );
	}

	/**
	 * Tests the get_content_between_balanced_tags method with unbalanced tags.
	 *
	 * @covers ::get_content_between_balanced_tags
	 */
	public function test_get_content_between_balanced_tags_malformed_html() {
		$html = '<div>Missing closing div';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$this->assertFalse( $p->get_content_between_balanced_tags() );

		$html = '<div><div>Missing closing div</div>';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$this->assertFalse( $p->get_content_between_balanced_tags() );

		$html = '<div>Missing closing div</span>';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$this->assertFalse( $p->get_content_between_balanced_tags() );

		// It supports unbalanced tags inside the content.
		$html = '<div>Missing closing div</span></div>';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$this->assertEquals( 'Missing closing div</span>', $p->get_content_between_balanced_tags() );
	}

	/**
	 * Tests the get_content_between_balanced_tags method when called on a closing
	 * tag.
	 *
	 * @covers ::get_content_between_balanced_tags
	 */
	public function test_get_content_between_balanced_tags_on_closing_tag() {
		$html = '<div></div>';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag( array( 'tag_closers' => 'visit' ) );
		$p->next_tag( array( 'tag_closers' => 'visit' ) );
		$this->assertFalse( $p->get_content_between_balanced_tags() );
	}

	/**
	 * Tests the set_content_between_balanced_tags method on a standard tag.
	 *
	 * @covers ::set_content_between_balanced_tags
	 */
	public function test_set_content_between_balanced_tags_standard_tag() {
		$html = '<div></div>';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$result = $p->set_content_between_balanced_tags( 'New content' );
		$this->assertTrue( $result );
		$this->assertEquals( '<div>New content</div>', $p->get_updated_html() );

		$html = '<div></div><div></div>';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$result = $p->set_content_between_balanced_tags( 'New content' );
		$this->assertTrue( $result );
		$this->assertEquals( '<div>New content</div><div></div>', $p->get_updated_html() );
	}

	/**
	 * Tests the set_content_between_balanced_tags method when called on a closing
	 * tag.
	 *
	 * @covers ::set_content_between_balanced_tags
	 */
	public function test_set_content_between_balanced_tags_on_closing_tag() {
		$html = '<div>Old content</div>';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag( array( 'tag_closers' => 'visit' ) );
		$p->next_tag( array( 'tag_closers' => 'visit' ) );
		$result = $p->set_content_between_balanced_tags( 'New content' );
		$this->assertFalse( $result );
		$this->assertEquals( '<div>Old content</div>', $p->get_updated_html() );
	}

	/**
	 * Tests the set_content_between_balanced_tags method on multiple calls to the
	 * same tag.
	 *
	 * @covers ::set_content_between_balanced_tags
	 */
	public function test_set_content_between_balanced_tags_multiple_calls_in_same_tag() {
		$html = '<div></div>';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$result = $p->set_content_between_balanced_tags( 'New content' );
		$this->assertTrue( $result );
		$result = $p->set_content_between_balanced_tags( 'Yet more content' );
		$this->assertTrue( $result );
		$this->assertEquals( '<div>Yet more content</div>', $p->get_updated_html() );
	}

	/**
	 * Tests the set_content_between_balanced_tags method on combinations with
	 * set_attribute calls.
	 *
	 * @covers ::set_content_between_balanced_tags
	 */
	public function test_set_content_between_balanced_tags_with_set_attribute() {
		$html = '<div></div>';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$p->set_attribute( 'class', 'test' );
		$result = $p->set_content_between_balanced_tags( 'New content' );
		$this->assertTrue( $result );
		$this->assertEquals( '<div class="test">New content</div>', $p->get_updated_html() );

		$html = '<div></div>';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$result = $p->set_content_between_balanced_tags( 'New content' );
		$this->assertTrue( $result );
		$p->set_attribute( 'class', 'test' );
		$this->assertEquals( '<div class="test">New content</div>', $p->get_updated_html() );
	}

	/**
	 * Tests the set_content_between_balanced_tags method where the existing
	 * content includes HTML tags.
	 *
	 * @covers ::set_content_between_balanced_tags
	 */
	public function test_set_content_between_balanced_tags_with_existing_tags() {
		$html        = '<div><span>Old content</span></div>';
		$new_content = '<span>New content</span><a href="#">Link</a>';
		$p           = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$result = $p->set_content_between_balanced_tags( $new_content );
		$this->assertTrue( $result );
		$this->assertEquals( '<div><span>New content</span><a href="#">Link</a></div>', $p->get_updated_html() );
	}

	/**
	 * Tests the set_content_between_balanced_tags method where the new content
	 * includes HTML tags.
	 *
	 * @covers ::set_content_between_balanced_tags
	 */
	public function test_set_content_between_balanced_tags_with_new_tags() {
		$html        = '<div></div>';
		$new_content = '<span>New content</span><a href="#">Link</a>';
		$p           = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$p->set_content_between_balanced_tags( $new_content );
		$this->assertEquals( '<div><span>New content</span><a href="#">Link</a></div>', $p->get_updated_html() );
	}

	/**
	 * Tests the set_content_between_balanced_tags method with an empty string.
	 *
	 * @covers ::set_content_between_balanced_tags
	 */
	public function test_set_content_between_balanced_tags_empty() {
		$html = '<div>Old content</div>';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$result = $p->set_content_between_balanced_tags( '' );
		$this->assertTrue( $result );
		$this->assertEquals( '<div></div>', $p->get_updated_html() );

		$html = '<div><div>Old content</div></div>';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$result = $p->set_content_between_balanced_tags( '' );
		$this->assertTrue( $result );
		$this->assertEquals( '<div></div>', $p->get_updated_html() );
	}

	/**
	 * Tests the set_content_between_balanced_tags method on self-closing tags
	 * (should not change anything).
	 *
	 * @covers ::set_content_between_balanced_tags
	 */
	public function test_set_content_between_balanced_tags_self_closing_tag() {
		$html = '<img src="example.jpg">';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$result = $p->set_content_between_balanced_tags( 'New content' );
		$this->assertFalse( $result );
		$this->assertEquals( $html, $p->get_updated_html() );
	}

	/**
	 * Tests the set_content_between_balanced_tags method on a non-existent tag
	 * (should not change anything).
	 *
	 * @covers ::set_content_between_balanced_tags
	 */
	public function test_set_content_between_balanced_tags_non_existent_tag() {
		$html = '';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$result = $p->set_content_between_balanced_tags( 'New content' );
		$this->assertFalse( $result );
		$this->assertEquals( $html, $p->get_updated_html() );
	}

	/**
	 * Tests the set_content_between_balanced_tags method with unbalanced tags.
	 *
	 * @covers ::set_content_between_balanced_tags
	 */
	public function test_set_content_between_balanced_tags_malformed_html() {
		$new_content = 'New content';

		$html = '<div>Missing closing div';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$result = $p->set_content_between_balanced_tags( $new_content );
		$this->assertFalse( $result );
		$this->assertEquals( '<div>Missing closing div', $p->get_updated_html() );

		$html = '<div><div>Missing closing div</div>';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$result = $p->set_content_between_balanced_tags( $new_content );
		$this->assertFalse( $result );
		$this->assertEquals( '<div><div>Missing closing div</div>', $p->get_updated_html() );

		$html = '<div>Missing closing div</span>';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$result = $p->set_content_between_balanced_tags( $new_content );
		$this->assertFalse( $result );
		$this->assertEquals( '<div>Missing closing div</span>', $p->get_updated_html() );

		// It supports unbalanced tags inside the content.
		$html = '<div>Missing closing div</span></div>';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$result = $p->set_content_between_balanced_tags( $new_content );
		$this->assertTrue( $result );
		$this->assertEquals( '<div>New content</div>', $p->get_updated_html() );
	}

	/**
	 * Tests the is_void_element method.
	 *
	 * @covers ::is_void_element
	 */
	public function test_is_void_element() {
		$void_elements = array( 'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr' );
		foreach ( $void_elements as $tag_name ) {
			$html = "<{$tag_name} id={$tag_name}>";
			$p    = new WP_Interactivity_API_Directives_Processor( $html );
			$p->next_tag();
			$this->assertTrue( $p->is_void_element() );
		}

		$non_void_elements = array( 'div', 'span', 'p', 'script', 'style' );
		foreach ( $non_void_elements as $tag_name ) {
			$html = "<{$tag_name} id={$tag_name}>Some content</{$tag_name}>";
			$p    = new WP_Interactivity_API_Directives_Processor( $html );
			$p->next_tag();
			$this->assertFalse( $p->is_void_element() );
		}

		// Test an upercase tag.
		$html = '<IMG src="example.jpg">';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$this->assertTrue( $p->is_void_element() );

		// Test a non-existent tag.
		$html = '';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$this->assertFalse( $p->is_void_element() );

		// Test on text nodes.
		$html = 'This is just some text';
		$p    = new WP_Interactivity_API_Directives_Processor( $html );
		$p->next_tag();
		$this->assertFalse( $p->is_void_element() );
	}
}
