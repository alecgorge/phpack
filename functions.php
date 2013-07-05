<?php

function em ($x) {
	global $em;
	return $em * $x;
}

function get_avail_stubs() {
	$stubs = glob(ROOT."stubs\*\php5ts.dll");
	$real_stubs = array();
	foreach($stubs as $value) {
		$pieces = explode('\\', dirname($value));
		if(file_exists(dirname($value).'\exe stub\stub.exe'))
			$real_stubs[] = end($pieces);
	}
	natsort($real_stubs);
	return $real_stubs;
}

function get_stub_exe ($x) {
	if($x == 'dll-dependant') return ROOT.'stubs\dll-dependant.exe';
	return ROOT."stubs\\".$x."\exe stub\stub.exe";
}

function get_stub_dll ($x) {
	return ROOT."stubs\\".$x."\php5ts.dll";
}

function array_insert($src, $dest, $pos) {
	$src = (array) $src;
	if (!is_array($src) || !is_array($dest) || $pos <= 0) return FALSE;
	return array_merge(array_slice($dest, 0, $pos), (array)$src, array_slice($dest, $pos));
}

function error ($mess) {
	Dialog::alert($mess, 'Configuration Error', WBC_WARNING);
	return false;
}

function correctPaths ($x) {
	return realpath($x);
}

function phpack_log ($message) {
	WB::appendText('progress_box', "\r\n".$message);
	WB::setText('status', $message);
	WB::scrollToBottom('progress_box');
}
function flush_log() {
	WB::setText('status', 'Ready...');
	WB::setText('progress_box', 'Ready...');
	WB::scrollToBottom('progress_box');
}

function percent ($percent) {
	WB::get('progress_bar')->setValue($percent);
}

function create_pa ($output, $input, $regex) {
	$p = new PHPArchive($output);
	$p->addDirectory($input, '/'.$regex.'/');
	$p->build();
	return true;
}

function set_exe_display_mode($filename, $mode = 'c') {
	//========================================================================
	//       exetype - Change the type of an executable (WINDOWS or CONSOLE)
	//       Port of Perl's exetype.
	//
	//       Author : Eric Colinet <e dot colinet at laposte dot net>
	//       Original Author : Jan Dubois <jand at ActiveState dot com>
	//       Home : http://wildphp.free.fr/wiki/doku?id=win32std:embeder
	//       Original Home : http://jenda.krynicky.cz/perl/GUIscripts.html
	//========================================================================
	$f= fopen($filename, 'r+b');
	if( !$f ) error("Can't open '{$filename}'");

	$type_record= unpack('Smagic/x58/Loffset', fread($f, 32*4));
	if( $type_record['magic'] != 0x5a4d ) error("Not an MSDOS executable file");

	if( fseek($f, $type_record['offset'], SEEK_SET) != 0 ) error("seeking error (+{$type_record['offset']})");
	$pe_record= unpack('Lmagic/x16/Ssize', fread($f, 24));
	if( $pe_record['magic'] != 0x4550 ) error("PE header not found");
	if( $pe_record['size'] != 224 ) error("Optional header not in NT32 format");

	if( fseek($f, $type_record['offset']+24+68, SEEK_SET) != 0 ) error("seeking error (+{$type_record['offset']})");
	if( fwrite($f, pack('S', $mode=='c'?3:2))===false ) error("write error");

	fclose($f);
}
?>