<?php
class Truss_CSS_Rule_Extractor {
	var $rules = array();
	var $sorted = array();
	var $priorities = array();
	var $sortedCount = 0;

	function parse( $ast ) {
		foreach( $ast as $node ) {
			if ( 'stylesheet' === $node['value'] )
				$this->parse_stylesheet( $node );
		}
		return array(
			'rules' => $this->rules,
			'sorted' => array_flip( $this->sorted ),
			'priorities' => $this->priorities
		);
	}

	function parse_stylesheet( $ast ) {
		$ast = $ast['children'];

		foreach( $ast as $node ) {
			if ( 'ruleset' !== $node['value'] )
				continue;

			$children = $node['children'];

			if ( 'selector' === $children[0]['value'] ) {
				$selector_node = array_shift( $children );

				// Initialize vars.
				$selectors = array();
				$selector = '';
				$priority = array('a' => 0, 'b' => 0, 'c' => 0);
				$mode = '';
				$not = false;
				// Calculate selector info.
				foreach( $selector_node['children'] as $part ) {
					$value = $part['value'];
					$type = $part['type'];

					// Comma, complete simple selector.
					if ( ',' === $value ) {
						// Save selector
						$selector = trim( $selector );
						$selectors[] = $selector;
						if ( !isset( $this->priorities[ $selector ] ) )
							$this->priorities[ $selector ] = $priority;

						// Reset vars.
						$priority = array('a' => 0, 'b' => 0, 'c' => 0);
						$selector = '';

						continue;
					}

					// Consume the token.
					$selector.= 'S' === $type ? ' ' : $value;

					// A paren ends a :not()
					if ( $not && ')' === $value ) {
						$not = false;
						$mode = '';
					// ID
					} else if ( '#' === $mode ) {
						$mode = '';
						$priority['a']++;
					// Class
					} else if ( '.' === $mode ) {
						$mode = '';
						$priority['b']++;
					// Attribute selector
					} else if ( '[' === $mode ) {
						if ( ']' === $value ) {
							$mode = '';
							$priority['b']++;
						}
					// Pseudo-classes
					} else if ( ':' === $mode ) {
						// Pseudo-elements
						if ( ':' === $value ) {
							$mode = '::';
						// exceptions to the :: rule
						} else if ( 'before' === $value || 'after' === $value
						|| 'first-line' === $value || 'first-letter' === $value ) {
							$mode = '';
							$priority['c']++;
						// The :not pseudo-class
						// :not has its own variable, because we need to count
						// elements within the :not(), but not the :not pseudo.
						} else if ( 'not' === $value ) {
							$not = true;
						} else {
							$mode = '';
							$priority['b']++;
						}
					// Pseudo-elements
					} else if ( '::' === $mode ) {
						$mode = '';
						$priority['c']++;
					// Parens--ignore contents.
					} else if ( '(' === $mode ) {
						if ( ')' === $value ) {
							$mode = '';
						}
					// Set mode.
					} else if ( '#' === $value || '.' === $value
					|| '[' === $value || ':' === $value || '(' === $value ) {
						$mode = $value;
					// A regular element (but not the universal selector)
					} else if ( 'IDENT' === $type ) {
						// @todo: potentially lowercase these elements;
						// however, this may affect other tokens that should stay case-sensitive.
						// if implemented, test this carefully!
						$priority['c']++;
					}
				}
				// Save final selector
				$selector = trim( $selector );
				$selectors[] = $selector;
				if ( !isset( $this->priorities[ $selector ] ) )
					$this->priorities[ $selector ] = $priority;
			}

			$decls = array();
			foreach( $children as $child ) {
				$decl = array(
					'property' => $child['value'],
					'value' => ''
				);
				foreach( $child['children'] as $part )
					$decl['value'].= 'S' === $part['type'] ? ' ' : $part['value'];

				$decl['value'] = trim( $decl['value'] );
				$decls[] = $decl;
			}

			// Sorting the selectors ensures that no duplicate
			// permutations of the selector group exist
			$unsorted = implode(', ', $selectors);
			sort( $selectors );
			$sorted = implode(', ', $selectors);

			// Instead of pairing the sorted selector with each ruleset,
			// we keep the sorted selectors in their own array, and rulesets
			// keep track of the index corresponding to their sorted selector.
			// This prevents any duplicate data that would stem from
			// repeating sorted selectors with multiple matching rulesets.
			if ( ! isset( $this->sorted[ $sorted ] ) )
				$this->sorted[ $sorted ] = $this->sortedCount++;

			$sortedIndex = $this->sorted[ $sorted ];
			$this->rules[] = array(
				'declarations' => $decls,
				'selector' => $unsorted,
				'sorted' => $sortedIndex
			);
		}
	}
}