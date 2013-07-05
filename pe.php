<?php
function pack_pa_into_exe($pa, $exe) {
	global $bootstrap;

	$fhandle = fopen('var://bootstrap/', 'r+');
	bcompiler_write_header($fhandle);
	bcompiler_write_file($fhandle, ROOT.'exe_bootstrap.php');
	bcompiler_write_footer($fhandle);
	fclose($fhandle);
	
	$ok2 = res_set($exe, 'APP', 'PAYLOAD', file_get_contents($pa));
	
	phpack_log('File payload successfully embeded.');
	percent(85);
	
	$ok = res_set($exe, 'PHP', 'RUN', $bootstrap);
	// res_set($exe, 'APP', 'ALIAS', $alias_name);
	
	unlink(Data::get('output_archive'));
	
	phpack_log('Temporary files removed.');
	
	return ($ok && $ok2);
}
?>