<?php

class Truss_CSS_Parser_Tests extends PHPUnit_Framework_TestCase {
	public function test_simple_ruleset() {
		$parser = new Truss_CSS_Parser();
		$css    = 'body { background: red; }';
		$result = $parser->rules( $css );

		$this->assertNotEmpty( $result );
		$this->assertNotEmpty( $result['rules'] );
		$this->assertNotEmpty( $result['rules'][0] );

		$rule = $result['rules'][0];
		$this->assertEquals( $rule['selector'], 'body' );
		$this->assertNotEmpty( $rule['declarations'] );
		$this->assertNotEmpty( $rule['declarations'][0] );

		$decl = $rule['declarations'][0];
		$this->assertEquals( $decl['property'], 'background' );
		$this->assertEquals( $decl['value'],    'red'        );
	}

	/**
	 * @depends test_simple_ruleset
	 */
	public function test_multiple_selectors() {
		$parser = new Truss_CSS_Parser();
		$css    = 'html, body { background: red; }';
		$result = $parser->rules( $css );

		$rule = $result['rules'][0];
		$this->assertEquals( $rule['selector'], 'html, body' );
	}

	/**
	 * @depends test_simple_ruleset
	 */
	public function test_bracket_selector() {
		$parser = new Truss_CSS_Parser();
		$css    = 'input[type="text"] { background: red; }';
		$result = $parser->rules( $css );

		$rule = $result['rules'][0];
		$this->assertEquals( $rule['selector'], 'input[type="text"]' );
	}

	/**
	 * @depends test_simple_ruleset
	 */
	public function test_url_value() {
		$parser = new Truss_CSS_Parser();
		$css    = 'body { background-image: url(http://wordpress.org/test.png); }';
		$result = $parser->rules( $css );

		$decl = $result['rules'][0]['declarations'][0];
		$this->assertEquals( $decl['value'], 'url(http://wordpress.org/test.png)' );
	}
}