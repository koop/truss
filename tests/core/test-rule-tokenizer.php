<?php

class Truss_Rule_Tokenizer_Tests extends PHPUnit_Framework_TestCase {
	/**
	 * @dataProvider token_provider
	 */
	public function test_token( $string, $type, $content ) {
		$tokenizer = new Truss_Rule_Tokenizer();
		$tokenizer->reset( $string );

		$token = $tokenizer->next();

		$this->assertEquals( $token['type'],    $type    );
		$this->assertEquals( $token['content'], $content );
	}

	public function token_provider() {
		return array(
			// Strings
			array( '"testing"', 'STRING', 'testing' ), // note the ignored quotes
			array( "'testing'", 'STRING', 'testing' ), // note the ignored quotes

			// Char tokens
			array( '[', 'OPEN',     '[' ),
			array( ']', 'CLOSE',    ']' ),
			array( '|', 'OR',       '|' ),
			array( '*', 'KLEENE',   '*' ),
			array( '?', 'OPTIONAL', '?' ),
			array( '+', 'PLUS',     '+' ),
			array( '!', 'BANG',     '!' ),
			array( '^', 'CARET',    '^' ),
			array( '@', 'AT',       '@' ),

			// Regular input
			array( 'EXAMPLE', 'TOKEN', 'EXAMPLE' ),

			// Unrecognized input
			array( ',', 'UNKNOWN',  ',' ),
			array( '{', 'UNKNOWN',  '{' ),
			array( '}', 'UNKNOWN',  '}' ),
		);
	}

	/**
	 * @dataProvider ignored_token_provider
	 */
	public function test_ignored_token( $string, $type, $content ) {
		$tokenizer = new Truss_Rule_Tokenizer();
		$tokenizer->reset( $string );

		// Check that the token is ignored by default.
		$token = $tokenizer->next();
		$this->assertEmpty( $token );

		// Reset the tokenizer and check that the token is recognized properly when forced.
		$tokenizer->reset( $string );

		// Pass the token type to next() to ensure that the token is not ignored.
		$token = $tokenizer->next( $type );

		$this->assertEquals( $token['type'],    $type    );
		$this->assertEquals( $token['content'], $content );
	}

	public function ignored_token_provider() {
		return array(
			array( " \n\t", 'SPACE', " \n\t" ),
		);
	}
}