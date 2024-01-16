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

	public function print_initial_state() {
		if ( ! empty( $this->initial_state ) ) {
			wp_print_inline_script_tag(
				wp_json_encode( $this->initial_state, JSON_HEX_TAG | JSON_HEX_AMP ),
				array(
					'type' => 'application/json',
					'id'   => 'wp-interactivity-initial-state',
				)
			);
		}
	}

	public function add_hooks() {
		add_action( 'wp_footer', array( $this, 'print_initial_state' ), 8 );
	}

	public function process_directives( $html ) {
		$p               = new WP_Interactivity_API_Directives_Processor( $html );
		$tag_stack       = array();
		$namespace_stack = array();
		$context_stack   = array();
		$unbalanced      = false;

		$directive_processor_prefixes          = array_keys( self::$directives_processors );
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
					if ( array_key_exists( $directive_prefix, $this->directive_processors ) ) {
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

			foreach ( $directives_prefixes as $directive_prefix ) {
				call_user_func_array(
					array( $this, self::$directives_processors[ $directive_prefix ] ),
					array( $p, &$context_stack, &$namespace_stack )
				);
			}
		}

		// If the HTML is unbalanced it can't be processed safely so it returns the
		// original content.
		return $unbalanced ? $html : $p->get_updated_html();
	}

	private function evaluate( $reference, $ns, array $context = array() ) {
		// Extract the namespace from the reference (if present).
		list( $ns, $path ) = WP_Directive_Processor::parse_attribute_value( $reference, $ns );

		$store = array(
			'state'   => WP_Interactivity_Initial_State::get_state( $ns ),
			'context' => $context[ $ns ] ?? array(),
		);

		/*
		* Checks first if the directive path is preceded by a negator operator (!),
		* indicating that the value obtained from the Interactivity Store (or the
		* passed context) using the subsequent path should be negated.
		*/
		$should_negate_value = '!' === $path[0];
		$path                = $should_negate_value ? substr( $path, 1 ) : $path;
		$path_segments       = explode( '.', $path );
		$current             = $store;
		foreach ( $path_segments as $p ) {
			if ( isset( $current[ $p ] ) ) {
				$current = $current[ $p ];
			} else {
				return null;
			}
		}

		/*
		* Checks if $current is an anonymous function or an arrow function, and if
		* so, call it passing the store. Other types of callables are ignored on
		* purpose, as arbitrary strings or arrays could be wrongly evaluated as
		* "callables".
		*
		* E.g., "file" is an string and a "callable" (the "file" function exists).
		*/
		if ( $current instanceof Closure ) {
			/*
			 * TODO: Figure out a way to implement derived state without having to
			 * pass the store as argument:
			 *
			 * $current = call_user_func( $current );
			 */
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
	 * @param string $directive The directive attribute.
	 * @return array The array with the prefix and suffix.
	 */
	private function extract_directive_prefix_and_suffix( $directive ) {
		return explode( '--', $directive, 2 );
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
	 *     ( 'actions.foo', 'myPlugin' )                         => array( 'myPlugin', 'actions', 'foo' )
	 *     ( 'state.foo.bar', 'myPlugin' )                       => array( 'myPlugin', 'state', 'foo', 'bar' )
	 *     ( 'otherPlugin::actions.foo', 'myPlugin' )            => array( 'otherPlugin', 'actions', 'foo' )
	 *     ( '{ "isOpen": false }', 'myPlugin' )                 => array( 'myPlugin', array( 'isOpen' => false ) )
	 *     ( 'otherPlugin::{ "isOpen": false }', 'otherPlugin' ) => array( 'myPlugin', array( 'isOpen' => false ) )
	 *
	 * @param string $value             The directive attribute value.
	 * @param string $default_namespace The default namespace that will be used if no explicit namespace is found on the value.
	 * @return array The array containing either the JSON or the reference path.
	 */
	public static function parse_directive_value( $value, $default_namespace = null ) {
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
}
