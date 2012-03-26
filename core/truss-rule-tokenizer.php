<?php
class Truss_Rule_Tokenizer extends Truss_Tokenizer {
	function init() {
		$this->ignore_tokens( array('QUOTE', 'SPACE') );
	}

	function root( $source ) {
		$c = $source->next();
		if ( "'" === $c || '"' === $c ) {
			$this->state('string', $c);
			return 'QUOTE';
		} else if ( '[' === $c ) {
			return 'OPEN';
		} else if ( ']' === $c ) {
			return 'CLOSE';
		} else if ( '|' === $c ) {
			return 'OR';
		} else if ( '*' === $c ) {
			return 'KLEENE';
		} else if ( '?' === $c ) {
			return 'OPTIONAL';
		} else if ( '+' === $c ) {
			return 'PLUS';
		} else if ( '!' === $c ) {
			return 'BANG';
		} else if ( '^' === $c ) {
			return 'CARET';
		} else if ( '@' === $c ) {
			return 'AT';
		} else if ( preg_match('/\s/', $c) ) {
			$source->next_while_matches('/\s/');
			return 'SPACE';
		} else if ( preg_match('/[a-zA-Z_\x7f-\xff]/u', $c) ) {
			$source->next_while_matches('/[a-zA-Z0-9_\x7f-\xff]/u');
			return 'TOKEN';
		}
		return "UNKNOWN";
	}

	function string( $source, $quote ) {
		if ( $source->peek() === $quote ) {
			$source->next();
			$this->state('root');
			return 'QUOTE';
		}

		while ( $source->peek() !== $quote )
			$source->next();
		return 'STRING';
	}
}