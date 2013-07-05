<?php
error_reporting(E_ALL);
define('VERSION', '0.8.0 beta');

if(defined('PHPACK')) {
	define('ROOT', dirname($argv[0]).'\\');
	chdir(dirname($argv[0]).'\\');
}
else {
	chdir(dirname(__FILE__));
	define('ROOT', dirname(__FILE__).'\\');
}


$bootstrap = "";

require "functions.php";
require "streams.php";
require "pe.php";
require "gui.php";
?>