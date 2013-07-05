<?php
/**
	Bcompiling script by Alec Gorge ( http://ramblingwood.com/ )on 4/19/2010 .
	
	I hereby release this script into the Public Domain (since it isn't very compilcated :D). 
*/

if($argc !== 3) {
	die(basename($argv[0])." inputfile outputfile\n");
}
$fhandle = fopen($argv[2], 'w');
bcompiler_write_header($fhandle);
bcompiler_write_file($fhandle, $argv[1]);
bcompiler_write_footer($fhandle);
fclose($fhandle);

?>
