<?php
/**
	- = later release
	+ = done
	to write:
		-save_project
		-open_project
		+exit_app
		show_manual
		check_for_updates
		show_about
		show_website
		
		+browse for output
		+input directory
		
		+file_regex
		+exe stub
		+build type
		
		update progress
		build
*/
require "wbObjects\wbObjects.php";
require "wbObjects\WBHelper.php";
require "wbObjects\WBDialog.php";

$em = wb_get_size("m");
$em = $em[0]/10;

$main = $wbSystem->createWindow(AppWindow, 'main', 'phpack '.VERSION, WBC_CENTER, WBC_CENTER, $em * 540, $em * 560);
WB::setWindow($main);
WB::setIcon(ROOT.'phpack.ico');

// Insert controls
WB::add("menu", Menu, array(
	'&File',
		array('save_project', "&Save Project (SOON)\tCtrl+S", "", "", "CTRL+F"),
		array('open_project', "&Open Project (SOON)\tCtrl+O", "", "", "CTRL+O"),
		null,
		array('exit_app', "&Exit\tCtrl+Q", "", "", "CTRL+Q"),
	
	'&Tools',
		array('open_options', "&Options\tCtrl+,", "", "", "CTRL+,"),
	
	'&Help',
		array('show_manual', "Online &Manual\tF1", "", "", "F1"),
		array('check_for_updates', "Check for &Updates"),
		null,
		array('show_about', "About", "", "", ""),
		array('show_website', "Website"),
		
));


WB::add("status", StatusBar, "Ready...");

WB::add("header", HyperLink, "phpack by Alec Gorge", $em * 10, $em * 10, $em * 240, $em * 25, 0x00000080, 12582912, 0);
WB::get("header")->setFont(wb_create_font("Arial", 16), false);

WB::add("", Frame, "Required Configuration", $em * 10, $em * 45, $em * 505, $em * 125, 0x00000000, 0, 0);
	WB::add("", Label, "Output Location:", $em * 20, $em * 64, $em * 90, $em * 15, 0x00000000, 0, 0);
	WB::add("output_browse", PushButton, "Browse", $em * 414, $em * 80, $em * 90, $em * 25, 0x00000000, 0, 0);
	WB::add("output_box", EditBox, "", $em * 20, $em * 81, $em * 385, $em * 25, 0x00000000, 0, 0);
	
	WB::add("", Label, "Input File(s):", $em * 20, $em * 114, $em * 90, $em * 15, 0x00000000, 0, 0);
	// WB::add("input_f", PushButton, "Browse (File)", $em * 410, $em * 130, $em * 90, $em * 25, 0x00000000, 0, 0);
	WB::add("input_d", PushButton, "Browse (Directory)", $em * 399, $em * 130, $em * 105, $em * 25, 0x00000000, 0, 0);
	WB::add("input_box", EditBox, "", $em * 20, $em * 131, $em * 370, $em * 25, 0x00000000, 0, 0);
	
WB::add("", Frame, "Options", $em * 10, $em * 180, $em * 505, $em * 180, 0x00000000, 0, 0);
	WB::add("", Label, "Match these files in the directory:", $em * 23, $em * 202, $em * 180, $em * 15, 0x00000000, 0, 0);
	WB::add("files_match", EditBox, ".*", $em * 220, $em * 200, $em * 285, $em * 20, 0x00000000, 0, 0);
	WB::add("", Label, "PHP stub used to build the exe:", $em * 23, $em * 231, $em * 180, $em * 15, 0x00000000, 0, 0);
	WB::add("exe_stub", ComboBox, '', $em * 220, $em * 230, $em * 285, $em * 60, 0x00000040, 0, 0);
	$stubs = get_avail_stubs();
	WB::get('exe_stub')->setText($stubs);
	WB::add("", Label, "Single EXE or external DLL:", $em * 23, $em * 260, $em * 175, $em * 15, 0x00000000, 0, 0);
	WB::add("type_exe", RadioButton, "Single EXE", $em * 220, $em * 261, $em * 81, $em * 15, WBC_GROUP, 1, 0);
	WB::add("type_dll", RadioButton, "EXE with external DLL", $em * 308, $em * 261, $em * 159, $em * 15, 0x00000000, 0, 0);
	WB::add("", Label, "Use UPX compression:", $em * 23, $em * 286, $em * 175, $em * 15, 0x00000000, 0, 0);
	WB::add("upx_yes", RadioButton, "Yes", $em * 220, $em * 286, $em * 81, $em * 15, WBC_GROUP, 1, 0);
	WB::add("upx_no", RadioButton, "No", $em * 308, $em * 286, $em * 159, $em * 15, 0x00000000, 0, 0);
	WB::add("", Label, "Application display mode:", $em * 23, $em * 312, $em * 175, $em * 15, 0x00000000, 0, 0);
	WB::add("display_w", RadioButton, "Windowed", $em * 220, $em * 312, $em * 81, $em * 15, WBC_GROUP, 1, 0);
	WB::add("display_c", RadioButton, "Console", $em * 308, $em * 312, $em * 159, $em * 15, 0x00000000, 0, 0);
	WB::add("", Label, "Bcompile Sources:", $em * 23, $em * 336, $em * 175, $em * 15, 0x00000000, 0, 0);
	WB::add("bcompile_yes", RadioButton, "Yes", $em * 220, $em * 336, $em * 81, $em * 15, WBC_GROUP, 1, 0);
	WB::add("bcompile_no", RadioButton, "No", $em * 308, $em * 336, $em * 159, $em * 15, 0x00000000, 0, 0);


WB::add("", Label, "Progress", $em * 10, $em * 445, $em * 90, $em * 15, 0x00000000, 0, 0);
WB::add("progress_bar", Gauge, "0", $em * 10, $em * 465, $em * 505, $em * 20, 0x00000000, 50, 0);
wb_set_range(WB::get('progress_bar')->wbObj, 0, 100);
WB::get('progress_bar')->setValue(0);

WB::add("build", PushButton, "Build EXE", $em * 365, $em * 370, $em * 150, $em * 40, 0x00000000, 0, 0);
WB::add("progress_box", EditBox, "Ready...", 10, $em * 370, $em * 340, $em * 70, WBC_MULTILINE | WBC_READONLY, 0, 0);

			$child = $wbSystem->createWindow(PopupWindow, 'main', 'Pick Main File', WBC_CENTER, WBC_CENTER, em(270),  em(125));	
			WBC::setWindow($child);
			WBC::add("", Label, "Select the main file you want to use:", em(10), em(10), em(245), em(15));
			WBC::add("main_file", ComboBox, '', em(10), em(35), em(245), em(35));
			
			WB::add("main_file_ok", PushButton, "OK", em(95), em(65), em(75), em(25));
			WB::add("main_file_cancel", PushButton, "Cancel", em(179), em(65), em(75), em(25));

			
			wb_main_loop();



require "binds.php";

$wbSystem->start();

// End controls

// thanks to stevenmartin99
function _real_exit () {
	_exit('ExitProcess',array(0),'KERNEL');
}

function _exit($selected_function, $function_array=array(), $library='USER32'){
   $library_functions = &$GLOBALS['_win32api'];
   if(!isset($library_functions['lib'][$library]))
   $library_functions['lib'][$library] = wb_load_library($library);
   if(!isset($library_functions['fun'][$selected_function]))
   $library_functions['fun'][$selected_function] = wb_get_function_address($selected_function, $library_functions['lib'][$library]);
   return wb_call_function($library_functions['fun'][$selected_function], $function_array);}
?>