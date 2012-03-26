<?php
class Truss_Parser_Node {
	var $parent;
	var $children;
	var $type;
	var $ast_operator;
	var $rewrite_rule;
	var $count;

	function Truss_Parser_Node( $type, $parent, $ast_operator='' ) {
		$this->parent = $parent;
		$this->type = $type;
		$this->children = array();
		$this->count = 0;
		$this->ast_operator = $ast_operator;
	}

	function push( $type, $ast_operator, $content=null ) {
		if ( isset( $content ) )
			$node = new Truss_Parser_Content_Node( $type, $this, $content, $ast_operator );
		else
			$node = new Truss_Parser_Node( $type, $this, $ast_operator );
		$this->children[ $this->count++ ] = $node;
		return $node;
	}

	function children() {
		return $this->children;
	}

	function set_children( $children ) {
		$this->children = $children;
		$this->count = count( $children );
	}

	function content(){
		$content = '';
		foreach( $this->children as $child )
			$content.= $child->content();
		return $content;
	}

	function toAST() {
		$parent = false;
		$current = array();
		foreach ( $this->children as $child ) {
			if ( '!' === $child->ast_operator )
				continue;

			$ast = $child->toAST();

			if ( '^' === $child->ast_operator ) {
				$ast[0]['children'] = array_merge( $ast[0]['children'], $current );
				$current = $ast;
				$parent = true;
			} else {
				if ( $parent )
					$current[0]['children'] = array_merge( $current[0]['children'], $ast );
				else
					$current = array_merge( $current, $ast );
			}
		}

		if ( '@' === $this->rewrite_rule )
			$current = array( array( 'value' => $this->type, 'children' => $current, 'kind' => 'structure' ) );

		return $current;
	}
}