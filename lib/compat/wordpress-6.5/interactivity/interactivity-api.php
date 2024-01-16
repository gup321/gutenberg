<?php
/**
 * Interactivity API: Functions and hooks
 *
 * @package Gutenberg
 * @subpackage Interactivity API
 */

if ( ! function_exists( 'wp_processs_directives_of_interactive_blocks' ) ) {
	/**
	 * Processes the directives on the rendered HTML of interactive
	 * blocks.
	 *
	 * It only processes one root interactive block at a time. While there is an
	 * interactive block marked as the root interactive block, it ignores all the
	 * subsequent ones because their rendered HTML will be already contained in the
	 * HTML of the initial root interactive block. In other words, it ignores all
	 * the interactive inner blocks of the first interactive block.
	 *
	 * @param array $parsed_block The parsed block.
	 * @return array The same parsed block.
	 */
	function wp_processs_directives_of_interactive_blocks( $parsed_block ) {
		static $root_interactive_block = null;

		if ( null === $root_interactive_block ) {
			$block_name = $parsed_block['blockName'];
			$block_type = WP_Block_Type_Registry::get_instance()->get_registered( $block_name );

			if ( isset( $block_name ) && isset( $block_type->supports['interactivity'] ) && $block_type->supports['interactivity'] ) {
				$root_interactive_block = array( $block_name, md5( serialize( $parsed_block ) ) );

				$process_interactive_blocks = static function ( $content, $parsed_block ) use ( &$root_interactive_block ) {
					list($root_block_name, $root_block_md5) = $root_interactive_block;
					if ( $root_block_name === $parsed_block['blockName'] && md5( serialize( $parsed_block ) ) === $root_block_md5 ) {
						$root_interactive_block = null;
						$content                = wp_interactivity_process_directives( $content );
					}
					return $content;
				};

				add_filter( 'render_block', $process_interactive_blocks, 10, 2 );
			}
		}

		return $parsed_block;
	}
	add_filter( 'render_block_data', 'wp_processs_directives_of_interactive_blocks', 10, 1 );
}

if ( ! function_exists( 'wp_interactivity' ) ) {
	/**
	 * Retrieves the main WP_Interactivity_API instance.
	 *
	 * This function provides access to the WP_Interactivity_API instance, creating
	 * one if it doesn't exist yet.
	 *
	 * @return WP_Interactivity_API The main WP_Interactivity_API instance.
	 */
	function wp_interactivity() {
		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new WP_Interactivity_API();
			$instance->add_hooks();
		}
		return $instance;
	}
}

if ( ! function_exists( 'wp_interactivity_process_directives' ) ) {
	/**
	 * Processes the directives present in an HTML string.
	 *
	 * @param string $html The HTML string that contains the directives.
	 * @return string $html The same HTML string with all the directives already processed.
	 */
	function wp_interactivity_process_directives( $html ) {
		return wp_interactivity()->process_directives( $html );
	}
}

if ( ! function_exists( 'wp_initial_state' ) ) {
	/**
	 * Merges data into the initial state for the given namespace.
	 *
	 * This state will be used when processing the directives in the server and
	 * then it will be serialized and sent to the client.
	 *
	 * @param string $store_namespace The namespace of the store.
	 * @param array  $state           Optional. The state to will be merged on the store of the given namespace.
	 * @return array The current state for the given namespace.
	 */
	function wp_initial_state( $store_namespace, $initial_state = null ) {
		return wp_interactivity()->initial_state( $store_namespace, $initial_state );
	}
}
