<?php
class Truss_CSS_Parser extends Truss_Parser {
	function Truss_CSS_Parser() {
		$rules = array(
			'stylesheet'  => "@ [ruleset | at_rule]+",
			'at_rule'     => "@ ATKEYWORD any* [ block | ';' ]",
			'block'       => "'{'! [ any | block | ATKEYWORD | ';' ]* '}'!",
			'ruleset'     => "@ selector? '{'! declaration? [ ';'! declaration? ]* '}'!",
			'selector'    => "@ any+",
			'declaration' => "IDENT^ ':'! [ any | block | ATKEYWORD ]+",
			'any'         => "[ S | IDENT | NUMBER | DELIM | STRING
			                  | '(' any* ')' | '[' any* ']' ]"
		);
		parent::Truss_Parser( new Truss_CSS_Tokenizer(), $rules, 'stylesheet');

		$this->rulex = new Truss_CSS_Rule_Extractor();
	}

	function rules( $string, $reset_extractor=false ) {
		if ( $reset_extractor )
			$this->rulex = new Truss_CSS_Rule_Extractor();
		$ast = $this->parse( $string, true );
		return $this->rulex->parse( $ast );
	}
}