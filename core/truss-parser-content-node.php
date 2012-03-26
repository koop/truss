<?php
class Truss_Parser_Content_Node {
	var $parent;
	var $content;
	var $type;
	var $ast_operator;

	function Truss_Parser_Content_Node( $type, $parent, $content, $ast_operator='' ) {
		$this->parent = $parent;
		$this->type = $type;
		$this->content = $content;
		$this->ast_operator = $ast_operator;
	}
	function content() {
		return $this->content;
	}
	function toAST() {
		return array( array(
			'value' => $this->content,
			'children' => array(),
			'kind' => 'content',
			'type' => $this->type ) );
	}
}