<?php
function exit_app () {
	_real_exit();
}

WB::bindClick('output_browse', function () { 
	$location = Dialog::saveAs('Where to save your built EXE.', array(
		array(
			'Executable Files',
			'*.exe'
		)
	));
	if(empty($location)) return;

	if(substr($location, -4, 4) != '.exe') $location .= '.exe';
	
	WB::get('output_box')->setText($location);
});

WB::bindClick('input_d', function () {
	global $wbSystem, $em;

	Data::del('override');
	$location = Dialog::folder('The directory that contains your application.');
	if(empty($location)) return;
	WB::get('input_box')->setText($location.'\\');
	
	if(!file_exists(correctPaths(rtrim($location, '\\')).'\index.php')) {
		$ok = Dialog::alert("The input directory doesn't have a index.php file. Your application won't automatically run.\n\nDo you still want to use this directory?", 'index.php doesn\'t exist.', WBC_YESNO);
		if($ok) {
		}
		else return 0;
	}
	
});

function validateSelections () {
	$input = WB::getText('input_box');
	if(empty($input)) return error("You have to have an input directory!");
	$output = WB::getText('output_box');
	if(empty($output)) return error("You have to output something!");
	
	// make sure input exists
	if(!is_dir(correctPaths(WB::getText('input_box'))))
		return error("The input box doesn't contain a directory!");
	if(!file_exists(correctPaths(WB::getText('input_box')).'\index.php')) {
		$ok = Dialog::alert("The input directory doesn't have a index.php file. Your application won't automatically run.\n\nDo you still want to use this directory?", 'index.php doesn\'t exist.', WBC_YESNO);
		if(!$ok) return 0;
	}

	///
	$files = array(); $flags = null;
	if($flags === -1 || empty($flags)) $flags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_PATHNAME;
	$d =  new RecursiveIteratorIterator(new RecursiveDirectoryIterator(correctPaths(WB::getText('input_box')), $flags));

	while($d->valid()) {
		if(@preg_match('/'.WB::getText('files_match').'/', $d->current()->getPathname()) == 1 || is_dir($d->current()->getPathname()))
			$files[] = $d->current()->getPathname();
		$d->next();
	}

	if(count($files) == 0) {
		return error("The input directory doesn't have any files that match the specified regex pattern!");
	}
	///
	
	if(!is_dir(correctPaths(dirname(WB::getText('output_box'))))) {
		return error("You can't output an exe at: '".WB::getText('output_box')."'!");
	}
	return 'valid';
}

function save_project () {
	phpack_log('Checking environment...');

	$valid = validateSelections();
	if($valid === 'valid') {
		$location = Dialog::saveAs('Where to save your project file.', array(
			array(
				'phpack project files.',
				'*.phpack'
			)
		));
		if(empty($location)) return;
		
		if(substr($location, -7, 7) != '.phpack') $location .= '.phpack';

		
		phpack_log('Parsing configuration, please wait...');
		
		setBuildData();
		
		global $stubs;
		$iniData = array(
			'phpack_version' => VERSION,
			'output_file' => Data::get('output_file'),
			'output_archive' => Data::get('output_archive'),
			'output_file_dir' => Data::get('output_file_dir'),
			'input' => Data::get('input'),
			'regex' => Data::get('regex'),
			'stub' => array_search(Data::get('stub'), $stubs),
			'build_type' => Data::get('build_type'),
			'upx' => Data::get('upx'),
			'display' => Data::get('display'),
		);
		
		phpack_log('Creating project file...');
		
		file_put_contents($location, json_encode($iniData));
		
	}
	phpack_log('Ready...');
}

function setBuildData () {
	global $stubs;
	Data::set('output_file', WB::getText('output_box'));
	Data::set('output_archive', Data::get('output_file').'.pa');
	Data::set('output_file_dir', dirname(correctPaths(WB::getText('output_box'))));
	Data::set('input', correctPaths(WB::getText('input_box')));
	Data::set('regex', WB::getText('files_match'));
	Data::set('stub', $stubs[WB::getSelected('exe_stub')]);
	Data::set('build_type', (WB::getSelected('type_exe') == 1 ? 'exe' : 'dll'));
	Data::set('upx', (WB::getSelected('upx_yes') == 1 ? true : false));
	Data::set('display', (WB::getSelected('display_w') == 1 ? 'w' : 'c'));
}

WB::get('header')->onMainEvent = function () {
	var_dump(wb_exec('http://winbinder.org/forum/viewtopic.php?f=8&t=1148'));
};

WB::bindClick('build', function () {
	$valid = validateSelections();
	if($valid === 'valid') {
		percent(0);
		flush_log();
		setBuildData();
		
		phpack_log("Settings Verified.");
		if(Data::get('build_type') == 'exe')
			$ok = copy(get_stub_exe(Data::get('stub')), Data::get('output_file'));
		else {
			$ok = copy(get_stub_exe('dll-dependant'), Data::get('output_file'));
			$ok2 = copy(get_stub_dll(Data::get('stub')), Data::get('output_file_dir').'\\php5ts.dll');
			$ok = $ok && $ok2;
		}
		if($ok) {
			phpack_log('Base binaries copied.');
			percent(25);
		}
		else {
			phpack_log('[ERROR] Failure in copying binaries!');
			return error('An error occured in copying the base binaries!');
		}
		
		if(create_pa(Data::get('output_archive'), Data::get('input'), Data::get('regex'))) {
			phpack_log('File payload successfully created.');
			percent(65);
		}
		else {
			phpack_log('[ERROR] Failure in creating the file payload!');
			return error('An error occured in creating the file payload!');
		}
		
		if(pack_pa_into_exe(Data::get('output_archive'), Data::get('output_file'))) {
			phpack_log('Success! Executable generated!');
			percent(90);
		}
		else {
			phpack_log('[ERROR] Failure in storing payload in exe!');
			return error('An error occured in storing payload in exe!');
		}
		
		if(Data::get('display') == 'w') {
			phpack_log('Console is hidden at runtime.');
			set_exe_display_mode(Data::get('output_file'), 'w');
		}
		
		if(Data::get('upx')) {
			phpack_log('Compressing EXE. Program may hang, but it is still running!');
			system('"'.ROOT.'\upx.exe" -q -9 "'.Data::get('output_file').'" > NUL');
		}
		phpack_log('Build complete!');
		percent(100);
		Dialog::alert('Build complete! The executable was generated without errors!', 'Success', WBC_OK);
	}
});
?>