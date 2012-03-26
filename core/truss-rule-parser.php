<?php
class Truss_Rule_Parser extends Truss_Parser {
	var $tokenizer;

	// Override default constructor.
	function Truss_Rule_Parser() {
		$this->tokenizer = new Truss_Rule_Tokenizer();
		$this->first_match = 'rule';
	}

	/**
	 * Override the default _matches.
	 *
	 * We can't use the original because it uses Truss_Rule_Parser.
	 *
	 * @param string $type The rule to match.
	 * @return bool Whether the match succeeded.
	 */
	function _matches( $type ) {
		return call_user_func( array( &$this, $type ) );
	}

	/**
	 * Matches a rule.
	 * Self-definition: "AT? sequence"
	 *
	 * @return bool Whether a rule was consumed.
	 */
	function rule() {
		$mark = $this->mark();
		$this->token('AT'); // Optionally match an @ first.

		if ( $this->match('sequence') )
			return true;

		$this->restore( $mark ); // Don't consume the caret.
		return false;
	}


	/**
	 * Matches a sequence.
	 * Self-definition: "term+"
	 *
	 * @return bool Whether a sequence was consumed.
	 */
	function sequence() {
		$this->current->rewrite_rule = "@";

		$valid = false;
		while ( $this->match('expr') )
			$valid = true;
		return $valid;
	}

	/**
	 * Matches an expression.
	 * Self-definition: "[ block | term ] [ KLEENE | PLUS | OPTIONAL ]?"
	 *
	 * @return bool Whether an expression was consumed.
	 */
	function expr() {
		$this->current->rewrite_rule = "@";

		if ( $this->match('block') || $this->match('term') ) {
			// Optional operator.
			$this->token('KLEENE')
			|| $this->token('PLUS')
			|| $this->token('OPTIONAL');
			return true;
		}
		return false;
	}

	/**
	 * Matches a term.
	 * Self-definition: "[ STRING | TOKEN ] [ CARET | BANG ]?"
	 *
	 * @return bool Whether a term was consumed.
	 */
	function term() {
		$this->current->rewrite_rule = "@";

		// Required portion
		if ( $this->token('TOKEN')
		|| $this->token('STRING') ) {
			// Optional AST operators.
			$this->token('CARET')
			|| $this->token('BANG');
			return true;
		}
		return false;
	}


	/**
	 * Matches a block.
	 * Self-definition: "OPEN! sequence [ OR! sequence ]* CLOSE!"
	 *
	 * @return bool Whether a block was consumed.
	 */
	function block() {
		$this->current->rewrite_rule = "@";

		if ( ! ( $this->token('OPEN', '', '!') && $this->match('sequence') ) )
			return false;

		while ( true ) {
			// Mark starting position.
			// This handled for us when finding a single token or match,
			// but will not be if 'OR' is matched and 'expr' isn't.
			$mark = $this->mark();
			if ( ! ( $this->token('OR', '', '!') && $this->match('sequence') ) ) {
				$this->restore( $mark ); // Restore state.
				break;
			}
		}

		return $this->token('CLOSE', '', '!');
	}
}