<?php
/**
 * A default tokenizer. Extend this class to make your own.
 *
 * When extending, either define a "root" method, or set
 * var $state = 'my_default_state".
 */
class Truss_Tokenizer {
	var $state = 'root';
	var $ss;
	var $args;
	var $stream;
	var $pos;
	var $count;
	var $ignored = array();

	function Truss_Tokenizer( $string='' ) {
		$this->reset( $string );
		$this->init();
	}

	function reset( $string ) {
		$this->ss = new Truss_String_Stream( $string );
		$this->args = array( $this->ss );
		$this->stream = array();
		$this->pos = 0;
		$this->count = 0;
	}

	function init() { return; }

	/**
	 * Fetches a token from the string stream.
	 *
	 * @param string $type
	 * @return array Array with parameters:
	 * 					type - the token type
	 * 				 	content - the token content
	 */
	function take( $type ) {
		return array( 'type' => $type, 'content' => $this->ss->get() );
	}

	/**
	 * Fetch the next token from the stream.
	 *
	 * @param string $unignore Optional. Will ensure a token of this type is not ignored.
	 */
	function next( $unignore='' ) {
		do {
			if ( $this->pos < $this->count ) {
				$next = $this->stream[ $this->pos++ ];
			} else {
				if ( ! $this->ss->more() )
					return; // @todo: Maybe throw an exception here.

				$type = '';
				while ( empty( $type ) )
					$type = call_user_func_array( array( &$this, $this->state ), $this->args );

				$next = $this->take( $type );
				// Add the token to the stream
				$this->stream[ $this->count++ ] = $next;
				$this->pos++;
			}
		} while ( $unignore !== $next['type'] && isset( $this->ignored[ $next['type'] ] ) && $this->ignored[ $next['type'] ] );

		return $next;
	}

	/**
	 * Peek at the next token from the stream.
	 *
	 * @param string $type Optional. Will ensure a token of this type is not ignored.
	 */
	function peek( $unignore='' ) {
		$mark = $this->position();
		$next = $this->next( $unignore );
		$this->seek( $mark );
		return $next;
	}

	function seek( $position ) {
		if ( $position <= $this->count ) {
			$this->pos = $position;
		} else {
			while ( $this->ss->more() && $this->pos < $position )
				$this->next();

			if ( $this->pos !== $position )
				return false;
		}
		return true;
	}

	function position() {
		return $this->pos;
	}

	function state( $state ) {
		$this->args = func_get_args();
		$this->args[0] = $this->ss;
		$this->state = $state;
	}

	function root( $source ) {
		$source->next();
		return "undefined";
	}

	function ignore_tokens( $tokens ) {
		$tokens = array_fill_keys( $tokens, true );
		$this->ignored = array_merge( $this->ignored, $tokens );
	}

	function unignore_tokens( $tokens ) {
		$this->ignored = array_diff_key( $this->ignored, array_fill_keys( $tokens, true ) );
	}
}