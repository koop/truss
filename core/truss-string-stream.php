<?php
class Truss_String_Stream {
	var $s = '';
	var $len;
	/**
	 * Position in the string.
	 *
	 * @var int
	 * @access private
	 */
	var $pos = 0;

	/**
	 * Constructor
	 */
	function Truss_String_Stream( $string ) {
		$this->s = $string;
		$this->len = strlen( $string );
	}

	function peek() {
		return isset( $this->s[$this->pos] ) ? $this->s[$this->pos] : null;
	}

	function next() {
		// Remember, ++ returns the value *before* incrementing
		return $this->pos < $this->len ? $this->s[$this->pos++] : null;
	}

	function get() {
		// If position is 0, we don't have to do anything.
		if ( ! $this->pos )
			return "";

		// Slice the string
		$ret = substr( $this->s, 0, $this->pos );
		$this->s = substr( $this->s, $this->pos );
		// Reset the length/position
		$this->len = strlen( $this->s );
		$this->pos = 0;

		return $ret;
	}

	function push( $str ) {
		$this->s = $str . $this->s;
		$this->len = strlen( $this->s );
	}

	function more() {
		$next = $this->peek();
		return isset( $next );
	}

	function matches( $re ) {
		$next = $this->peek();
		return isset( $next ) && preg_match( $re, $next );
	}

	function equals( $c ) {
		return $c === $this->peek();
	}

	function next_while_matches( $re ) {
		while ( $this->matches( $re ) )
			$this->next();
	}

	function end_of_line() {
		$next = $this->peek();
		return ! isset( $next ) || $next === "\n";
	}

	/**
	 * Captures a string from the head of the stream.
	 * Returns false if string is not present.
	 *
	 * @param string $re String to match.
	 * @return bool Whether the match succeeded.
	 */
	function consume( $str ) {
		$next = $this->peek();
		if ( ! isset( $next ) )
			return false;

		$len = strlen( $str );
		$head = substr( $this->s, $this->pos, $len );
		if ( $str === $head ) {
			// Capture the contents by adjusting the position.
			$this->pos += $len;
			return true;
		}
		return false;
	}

	/**
	 * Captures a regular expression from the head of the stream.
	 * Returns false if match fails.
	 *
	 * @param string $re Regular expression.
	 * @return bool Whether the match succeeded.
	 */
	function capture( $re ) {
		$next = $this->peek();
		$index = strrpos( $re, '/' );

		// Check if we can continue.
		if ( ! isset( $next ) || empty( $index ) )
			return false;

		// Make the regex head-only.
		$re = substr_replace( $re, ')', $index, 0);
		$re = substr_replace( $re, '^(', 1, 0);
		// Adjust the stream to start at the current position.
		$stream = substr( $this->s, $this->pos );

		// Test for a match.
		$match = array();
		preg_match( $re, $stream, $match );

		if ( empty( $match ) )
			return false;
		// Capture the contents by adjusting the position.
		$this->pos += strlen( $match[0] );
		return true;
	}
}
