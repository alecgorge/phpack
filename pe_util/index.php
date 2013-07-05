<?php
/**
	A little util for embeding files in PE exe's by Alec Gorge ( http://ramblingwood.com/ )on 4/19/2010 .
	
	I hereby release this script into the Public Domain (since it isn't very compilcated :D). 
*/

if(php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) {
	chdir(dirname(__FILE__));
}

if($argc !== 5) {
	die(basename($argv[0])." exefile res_section res_subsection fileforcontents\n");
}
res_set($argv[1], $argv[2], $argv[3], file_get_contents($argv[4]));
?>