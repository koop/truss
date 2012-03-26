<?php
/*
Plugin Name: Truss
Plugin URI: http://darylkoop.com/
Description: A CSS tokenizer and parser.
Version: 0.1
Author: koopersmith
Author URI: http://darylkoop.com/

    Copyright 2011  Daryl Koopersmith  (email : d@darylkoop.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Load Truss core.
require_once( 'core/truss-string-stream.php' );
require_once( 'core/truss-tokenizer.php' );
require_once( 'core/truss-rule-tokenizer.php' );
require_once( 'core/truss-parser-content-node.php' );
require_once( 'core/truss-parser-node.php' );
require_once( 'core/truss-parser.php' );
require_once( 'core/truss-rule-parser.php' );

// Load Truss CSS module.
require_once( 'css/truss-css-tokenizer.php' );
require_once( 'css/truss-css-parser.php' );
require_once( 'css/truss-css-rule-extractor.php' );

// Load testing file.
require_once( 'testing.php' );