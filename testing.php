<?php
function truss_write_output( $o, $file='truss-output' ) {
	$fp = fopen( dirname( __FILE__ ) . "/$file.txt", 'w');
	fwrite( $fp, print_r($o, true) );
	fclose( $fp );
}

function truss_test_css_tokenizer() {
	$css = new Truss_CSS_Tokenizer( file_get_contents( STYLESHEETPATH . '/style.css' ) );

	$start = microtime(true);

	while ( $token = $css->next() ) {
		echo wpautop( str_replace( ' ', '&nbsp;', print_r( $token, true ) ) );
	}

	$time = microtime(true) - $start;
	echo "Tokenized in $time seconds.";
	echo " Peak Memory: " . memory_get_peak_usage( true );

	// truss_write_output( $tokens, 'tokenized-css-output' );
}

function truss_test_css_parser() {
	$start = microtime(true);

	$parser = new Truss_CSS_Parser();
	$p = $parser->parse( file_get_contents( STYLESHEETPATH . '/style.css' ), true );

	$time = microtime(true) - $start;
	echo "Parsed in $time seconds.\n";
	echo " Peak Memory: " . memory_get_peak_usage( true );

	truss_write_output( $p, 'parsed-css-output' );
	// truss_write_output( $parser, 'parser-css-output' );
}


add_action( 'admin_init', 'truss_run_tests' );
function truss_run_tests() {
	if ( isset( $_REQUEST['truss-test'] ) ) {
		if ( strpos( $_REQUEST['truss-test'], 'tokenizer' ) !== false )
			truss_test_css_tokenizer();

		if ( strpos( $_REQUEST['truss-test'], 'parser' ) !== false )
			truss_test_css_parser();

		if ( strpos( $_REQUEST['truss-test'], 'rules' ) !== false )
			truss_test_css_rules();

		die;
	}
}