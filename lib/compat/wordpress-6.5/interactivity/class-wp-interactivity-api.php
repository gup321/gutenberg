<?php
/**
 * Interactivity API: WP_Interactivity_API class.
 *
 * @package WordPress
 * @subpackage Interactivity API
 */

if ( class_exists( 'WP_Interactivity_API' ) ) {
	return;
}

/**
 * Class used to process the Interactivity API in the server.
 */
class WP_Interactivity_API {
	private static $directive_processors = array(
		'data-wp-interactive' => 'data_wp_interactive_processor',
		'data-wp-context'     => 'data_wp_context_processor',
		'data-wp-bind'        => 'data_wp_bind_processor',
		'data-wp-class'       => 'data_wp_class_processor',
		'data-wp-style'       => 'data_wp_style_processor',
		'data-wp-text'        => 'data_wp_text_processor',
	);

	private $initial_state = array();

	private $config = array();

	public function initial_state( $store_namespace, $initial_state = null ) {
		if ( ! isset( $this->initial_state[ $store_namespace ] ) ) {
			$this->initial_state[ $store_namespace ] = array();
		}
		if ( is_array( $initial_state ) ) {
			$this->initial_state[ $store_namespace ] = array_replace_recursive(
				$this->initial_state[ $store_namespace ],
				$initial_state
			);
		}
		return $this->initial_state[ $store_namespace ];
	}

	public function print_client_interactivity_data() {
		if ( ! empty( $this->initial_state ) ) {
			wp_print_inline_script_tag(
				wp_json_encode(
					array(
						'config'       => (object) $this->config,
						'initialState' => (object) $this->initial_state,
					),
					JSON_HEX_TAG | JSON_HEX_AMP
				),
				array(
					'type' => 'application/json',
					'id'   => 'wp-interactivity-data',
				)
			);
		}
	}

	public function add_hooks() {
		add_action( 'wp_footer', array( $this, 'print_client_interactivity_data' ), 8 );
	}

	public function process_directives( $html ) {
		$p               = new WP_Interactivity_API_Directives_Processor( $html );
		$tag_stack       = array();
		$namespace_stack = array();
		$context_stack   = array();
		$unbalanced      = false;

		$directive_processor_prefixes          = array_keys( self::$directive_processors );
		$directive_processor_prefixes_reversed = array_reverse( $directive_processor_prefixes );

		while ( $p->next_tag( array( 'tag_closers' => 'visit' ) ) && false === $unbalanced ) {
			$tag_name = $p->get_tag();

			if ( $p->is_tag_closer() ) {
				// Preprocessing for a closing tag.
				if ( 0 === count( $tag_stack ) ) {
					// If the tag stack is empty, it means the HTML is unbalanced and we
					// should stop processing it.
					$unbalanced = true;
					continue;
				}

				list( $opening_tag_name, $directives_prefixes ) = end( $tag_stack );

				if ( $opening_tag_name !== $tag_name ) {
					// If the matching opening tag is not the same than the closing tag,
					// it means the HTML is unbalanced and we should stop processing it.
					$unbalanced = true;
					continue;
				} else {
					// The HTML is still balanced, we can keep processing it.
					array_pop( $tag_stack );

					// If the matching opening tag didn't have any directives, we don't
					// need to do any processing.
					if ( 0 === count( $directives_prefixes ) ) {
						continue;
					}
				}
			} else {
				// Preprocessing for an opening tag.
				$directives_prefixes = array();

				foreach ( $p->get_attribute_names_with_prefix( 'data-wp-' ) as $attribute_name ) {
					// Extracts the directive prefix to see if there is a server directive
					// processor registered for that directive.
					list( $directive_prefix ) = $this->extract_directive_prefix_and_suffix( $attribute_name );
					if ( array_key_exists( $directive_prefix, self::$directive_processors ) ) {
						$directives_prefixes[] = $directive_prefix;
					}
				}

				// If this is not a void element, add it to the tag stack so we can
				// check if all tags are balanced later.
				if ( ! $p->is_void_element() ) {
					$tag_stack[] = array( $tag_name, $directives_prefixes );
				}
			}

			// Sorts the attributes by the order of the `directives_processor`
			// property, considering it as the priority order in which directives
			// should be processed. The order is reversed for tag closers.
			$directives_prefixes = array_intersect(
				$p->is_tag_closer()
					? $directive_processor_prefixes_reversed
					: $directive_processor_prefixes,
				$directives_prefixes
			);

			// Executes the directive processors.
			foreach ( $directives_prefixes as $directive_prefix ) {
				call_user_func_array(
					array( $this, self::$directive_processors[ $directive_prefix ] ),
					array( $p, &$context_stack, &$namespace_stack )
				);
			}
		}

		// It returns the original content if the HTML is unbalanced because it's
		// not safe to process. In that case, the Interactivity API runtime will
		// update the HTML on the client side during the hydration.
		return $unbalanced || 0 < count( $tag_stack ) ? $html : $p->get_updated_html();
	}

	private function evaluate( $directive_value, $default_namespace, $context ) {
		// Extract the namespace from the directive attribute value.
		list( $ns, $path ) = $this->parse_directive_value( $directive_value, $default_namespace );

		$store = array(
			'state'   => isset( $this->initial_state[ $ns ] ) ? $this->initial_state[ $ns ] : array(),
			'context' => isset( $context[ $ns ] ) ? $context[ $ns ] : array(),
		);

		// Checks first if the reference path is preceded by a negator operator (!),
		// indicating that the value obtained should be negated.
		$should_negate_value = '!' === $path[0];
		$path                = $should_negate_value ? substr( $path, 1 ) : $path;

		// Extracts the value from the store using the reference path.
		$path_segments = explode( '.', $path );
		$current       = $store;
		foreach ( $path_segments as $p ) {
			if ( isset( $current[ $p ] ) ) {
				$current = $current[ $p ];
			} else {
				return null;
			}
		}

		// Returns the opposite if it has a negator operator (!).
		return $should_negate_value ? ! $current : $current;
	}

	/**
	 * Extracts and returns the prefix and suffix of a directive attribute.
	 *
	 * The suffix is the optional string after the first double hyphen and the
	 * prefix is everything that comes before the suffix.
	 *
	 * Examples:
	 *
	 *     'data-wp-interactive'   => array( 'data-wp-interactive', null )
	 *     'data-wp-bind--src'     => array( 'data-wp-bind', 'src' )
	 *     'data-wp-foo--and--bar' => array( 'data-wp-foo', 'and--bar' )
	 *
	 * @param string $directive_name The directive attribute name.
	 * @return array The array with the prefix and suffix.
	 */
	private function extract_directive_prefix_and_suffix( $directive_name ) {
		return explode( '--', $directive_name, 2 );
	}

	/**
	 * Parses and extracts the namespace and reference path from the given
	 * directive attribute value.
	 *
	 * If the value doesn't contain an explicit namespace, it returns the default
	 * one. If the value contains a JSON instead of a reference path, the function
	 * parses it and returns the resulting array.
	 *
	 * Examples:
	 *
	 *     ( 'actions.foo', 'myPlugin' )                      => array( 'myPlugin', 'actions.foo' )
	 *     ( 'otherPlugin::actions.foo', 'myPlugin' )         => array( 'otherPlugin', 'actions.foo' )
	 *     ( '{ "isOpen": false }', 'myPlugin' )              => array( 'myPlugin', array( 'isOpen' => false ) )
	 *     ( 'otherPlugin::{ "isOpen": false }', 'myPlugin' ) => array( 'otherPlugin', array( 'isOpen' => false ) )
	 *
	 * @param string $value             The directive attribute value.
	 * @param string $default_namespace The default namespace that will be used if no explicit namespace is found on the value.
	 * @return array The array containing either the JSON or the reference path.
	 */
	private function parse_directive_value( $value, $default_namespace = null ) {
		$matches       = array();
		$has_namespace = preg_match( '/^([\w\-_\/]+)::(.+)$/', $value, $matches );

		// Overwrites both `$default_namespace` and `$value` if `$value` explicitly
		// contains a namespace.
		if ( $has_namespace ) {
			list( , $default_namespace, $value ) = $matches;
		}

		// Tries to decode `$value` as a JSON object. If it works, `$value` is
		// replaced with the resulting array. The original string is preserved
		// otherwise. Note that `json_decode` returns `null` both for an invalid
		// JSON or the `'null'` string (a valid JSON). In the latter case, `$value`
		// is replaced with `null`.
		$data = json_decode( $value, true );
		if ( null !== $data || 'null' === trim( $value ) ) {
			$value = $data;
		}

		return array( $default_namespace, $value );
	}

	private function data_wp_bind_processor( $p, &$context_stack, &$namespace_stack ) {
		if ( $p->is_tag_closer() ) {
			return;
		}

		$prefixed_attributes = $p->get_attribute_names_with_prefix( 'data-wp-bind--' );

		foreach ( $prefixed_attributes as $attribute ) {
			list( , $bound_attr ) = $this->extract_directive_prefix_and_suffix( $attribute );
			if ( empty( $bound_attr ) ) {
				continue;
			}

			$reference = $p->get_attribute( $attribute );
			$value     = $this->evaluate( $reference, end( $namespace_stack ), end( $context_stack ) );
			$p->set_attribute( $bound_attr, $value );
		}
	}

	private function data_wp_context_processor( $p, &$context_stack, &$namespace_stack ) {
		if ( $p->is_tag_closer() ) {
			array_pop( $context_stack );
			return;
		}

		$directive_value = $p->get_attribute( 'data-wp-context' );
		$ns              = end( $namespace_stack );

		// Separate namespace and value from the context directive attribute.
		list( $ns, $data ) = is_string( $directive_value ) && ! empty( $directive_value )
		? $this->parse_directive_value( $directive_value, $ns )
		: array( $ns, null );

		$context = ( end( $context_stack ) !== false ) ? end( $context_stack ) : array();

		// Add parsed data to the context under the corresponding namespace.
		array_push(
			$context_stack,
			array_replace_recursive(
				$context,
				array( $ns => is_array( $data ) ? $data : array() )
			)
		);
	}
}
