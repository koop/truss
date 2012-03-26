<?php

class Truss_Existential_Tests extends PHPUnit_Framework_TestCase {
	public function test_truss_exists() {
		$this->assertTrue( class_exists( 'Truss_Parser' ) );
	}
}