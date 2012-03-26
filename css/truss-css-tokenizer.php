<?php
class Truss_CSS_Tokenizer extends Truss_Tokenizer {
	var $defs;

	function init() {
		$this->ignore_tokens( array('COMMENT', 'S', 'INVALID') );

		// Build definitions for productions.
		$nl			= "\n|\r\n|\r|\f";
		$w			= "[ \t\n\r\f]*";
		$s			= "[ \t\n\r\f]+";
		$nonascii	= "[^\\0-\\177]";
		$unicode	= "\\\[0-9a-f]{1,6}(?:\r\n|[ \t\n\r\f])?";
		$escape		= "(?:$unicode)|\\\[^\n\r\f0-9a-f]";
		$nmchar		= "[_a-z0-9-]|(?:$nonascii)|(?:$escape)";
		$nmstart	= "[_a-z]|(?:$nonascii)|(?:$escape)";
		$num		= "[+-]?([0-9]*\.)?[0-9]+";
		$invalid1	= "\"(?:[^\n\r\f\"\\\]|\\\(?:$nl)|(?:$nonascii)|(?:$escape))*";
		$invalid2	= "'(?:[^\n\r\f\'\\\]|\\\(?:$nl)|(?:$nonascii)|(?:$escape))*";
		$string1	= "$invalid1\"";
		$string2	= "$invalid2'";
		$string		= "(?:$string1)|(?:$string2)";
		$invalid	= "(?:$invalid1)|(?:$invalid2)";
		// $ident		= "-?(?:$nmstart)(?:$nmchar)*";
		$ident		= "(?:$nmchar)+";

		$this->defs = array(
			'ident'		=> $ident,
			'string'	=> $string,
			'invalid'	=> $invalid,
			'num'		=> $num,
			's'			=> $s
		);

		// Build regular expressions
		foreach ( array_keys( $this->defs ) as $id ) {
			$this->defs[$id] = "/" . $this->defs[$id] . "/i";
		}
	}

	function root( $source ) {
		// Peek (first char)
		$c = $source->peek();
		if ( ('"' === $c || "'" === $c ) ) {
			if ( $source->capture( $this->defs['string'] ) )
				return "STRING";
			$source->capture( $this->defs['invalid'] );
			return "INVALID";
		} else if ( $source->matches('/[0-9\.+-]/') && $source->capture( $this->defs['num'] ) ) {
			return "NUMBER";
		} else if ( $source->capture( $this->defs['ident'] ) ) {
			return "IDENT";
		} else if ( $source->capture( $this->defs['s'] ) ) {
			return 'S';
		}

		// Fetch char
		$source->next();

		// Test $c (first char)
		if ( "@" === $c ) {
			$ts = $source->capture( $this->defs['ident'] );
			return "ATKEYWORD";
		} else if ( "/" === $c && $source->equals('*') ) {
			return $this->state('c_comment');
		} else if ( "<" === $c && $source->consume("!--") ) {
			return $this->state('sgml_comment');
		} else if ( preg_match( '/[\/,+>~|:\.*!%#^$=]/', $c ) ) {
			return "DELIM";
		}
		return "CHAR";
	}

	function c_comment( $source ) {
		do {
			$c = $source->next();
			if ( "*" === $c && $source->equals('/') ) {
				$source->next();
				$this->state('root');
				break;
			}
		} while ( ! $source->end_of_line() );
		return 'COMMENT';
	}

	function sgml_comment( $source ) {
		$dashes = 0;
		do {
			$c = $source->next();
			if ( $dashes >= 2 && $c === ">" ) {
				$this->state('root');
				break;
			}
			$dashes = ($c == "-") ? $dashes + 1 : 0;
		} while ( ! $source->end_of_line() );
		return 'COMMENT';
	}
}