<?php
/**
 *
 * ----------------------------------------------------------------------------
 * RULE SYNTAX:
 * ----------------------------------------------------------------------------
 * Rules are comprised of tokens, other rules, and operators.
 *
 * - Tokens are denoted by uppercase strings (e.g. "IDENT")
 * - Rules are denoted by lowercase strings (e.g. "block")
 *
 * The following operators modify how tokens recognized:
 * ? - Optionally matches a token or rule.
 * * - A Kleene closure. Optionally any number of a given token or rule.
 * + - Matches one to any number of a given token or rule.
 *
 * The following operators modify how the AST is processed:
 * @ - When put at the beginning of the rule, this will create a structural
 *     node in the tree. Its value will be the name of the rule, and it will
 *     wrap all nodes in the rule.
 * ^ - Causes the current node to become a parent node of its siblings.
 * ! - Indicates that a token should be ignored in the tree.
 *
 * ----------------------------------------------------------------------------
 */
class Truss_Parser {
	var $tokenizer;
	var $output;
	var $current;
	var $rules = array();
	var $rule_parser;
	var $first_match;

	function Truss_Parser( $tokenizer, $rules, $first_match='' ) {
		$this->tokenizer = $tokenizer;
		$this->add_rules( $rules );
		$this->first_match = $first_match;
	}

	/**
	 * Parse input.
	 *
	 * @param string $string String to parse.
	 * @param bool $ast If true, return the AST instead of the parse tree.
	 * @return Truss_Parser_Node the parse tree or AST.
	 */
	function parse( $string, $ast=false ) {
		$this->tokenizer->reset( $string );
		$this->set_root();
		$this->match( $this->first_match );
		if ( $ast )
			return $this->output->toAST();
		else
			return $this->output;
	}

	/**
	 * Sets the root node for the parse tree.
	 *
	 * @param mixed $node The root node type (string) or an instantiated root node (object).
	 */
	function set_root( $node='root' ) {
		if ( is_string( $node ) )
			$node = new Truss_Parser_Node( $node, null );

		$this->output = $node;
		$this->current = $this->output;
	}

	function test( $mode, $modifier='' ) {
		$args = array_slice( func_get_args(), 2 );

		$valid = '?' === $modifier || '*' === $modifier;
		while ( call_user_func_array( array( &$this, $mode ), $args ) ) {
			$valid = true;
			if ( empty( $modifier ) || '?' === $modifier )
				break;
		}
		return $valid;
	}

	function mark() {
		return array(
			'tokenizer' => $this->tokenizer->position(),
			'current' => $this->current->children()
		);
	}

	function restore( $mark ) {
		$this->tokenizer->seek( $mark['tokenizer'] );
		$this->current->set_children( $mark['current'] );
	}

	function match( $type, $modifier='', $ast_operator='' ) {
		return $this->test( '_match', $modifier, $type, $ast_operator );
	}

	function token( $type, $modifier='', $ast_operator='', $value='' ) {
		return $this->test( '_token', $modifier, $type, $ast_operator, $value );
	}

	function value( $value, $modifier='', $ast_operator='', $type='' ) {
		return $this->test( '_token', $modifier, $type, $ast_operator, $value );
	}

	function _match( $type, $ast_operator='' ) {
		$mark = $this->mark();
		$this->current = $this->current->push( $type, $ast_operator );

		// Call matching function
		$valid = $this->_matches( $type );

		// Switch back to the parent node
		$this->current = $this->current->parent;

		if ( $valid )
			return true;

		// Match is invalid!
		$this->restore( $mark );
		return false;
	}

	function _matches( $type ) {
		if ( ! isset( $this->rules[ $type ] ) )
			return false;

		$rule = $this->rules[ $type ];

		if ( '@' === $rule[0]['value'] ) {
			$this->current->rewrite_rule = "@";
			$node = $rule[1];
		} else {
			$node = $rule[0];
		}

		return $this->_process_node( $node );
	}

	function _process_node( $node ) {
		$children = $node['children'];
		switch ( $node['value'] ) {
			case 'sequence':
				// Sequence is a large AND statement.
				$mark = $this->mark(); // Mark sequence start.
				foreach ( $children as $child ) {
					if ( ! $this->_process_node( $child ) ) {
						$this->restore( $mark ); // Sequence isn't valid, reset position.
						return false;
					}
				}
				return true;
			case 'block':
				// Block is a large OR statement.
				$valid = false;
				foreach ( $children as $child ) {
					$mark = $this->mark();
					if ( $valid = $this->_process_node( $child ) )
						break; // Node is valid
					$this->restore( $mark ); // Node isn't valid.
				}
				return $valid;
			case 'expr':
				$modifier = isset( $children[1] ) ? $children[1]['value'] : '';

				if ( 'block' === $children[0]['value'] )
					return $this->test( '_process_node', $modifier, $children[0] );

				// AST operator node ( value == "term" )
				$children = $children[0]['children'];
				$ast_operator = isset( $children[1] ) ? $children[1]['value'] : '';

				// Token/match node.
				$node = $children[0];
				$value = $node['value'];

				if ( 'TOKEN' === $node['type'] ) {
					if ( isset( $this->rules[ $value ] ) )
						return $this->match( $value, $modifier, $ast_operator );
					else // If we can't find a matching rule, assume we're looking for a token.
						return $this->token( $value, $modifier, $ast_operator );

				} else if ( 'STRING' === $node['type'] ) {
					// If we're looking for a string, check if the next token matches its value.
					return $this->value($value, $modifier, $ast_operator);
				}
			default:
				return false;
		}
	}

	function _token( $type='', $ast_operator='', $value='' ) {
		$token = $this->tokenizer->peek( $type );

		if ( ( empty( $type ) || $type === $token['type'] )
		&& ( empty( $value ) || $value === $token['content'] ) ) {
			$this->tokenizer->next( $type );
			// @todo: Potentially have push take the token itself (instead of just the content) as input.
			$this->current->push( $token['type'], $ast_operator, $token['content'] );
			return true;
		}
		return false;
	}


	function add_rule( $name, $rule ) {
		if ( ! isset( $this->rule_parser ) )
			$this->rule_parser = new Truss_Rule_Parser();

		$this->rules[ $name ] = $this->rule_parser->parse( $rule, true );
	}

	function remove_rule( $name ) {
		unset( $this->rules[ $name ] );
	}

	/**
	 * Add rules to the parser.
	 *
	 * @param array $rules Array of rules, where rule names are the keys.
	 */
	function add_rules( $rules ) {
		foreach( $rules as $name => $rule )
			$this->add_rule( $name, $rule );
	}

	/**
	 * Remove rules from the parser.
	 *
	 * @param array $names An array of rule names.
	 */
	function remove_rules( $names ) {
		$this->rules = array_diff_key( $this->rules, array_fill_keys( $names, true ) );
	}
}