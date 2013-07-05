<?php
class Dialog extends WBHelper {
	public static function open ($title=null, $filter = array(array('All Files', '*.*')), $path = null, $filename = null) {
		return parent::$window->dialogOpen($title, $filter, $path, $filename);
	}
	public static function folder ($title=null, $path = null) {
		return parent::$window->dialogPath($title, $path);
	}
	public static function alert ($message, $title = null, $style=null) {
		return parent::$window->messageBox($message, $title, $style);
	}
	public static function color ($title=null, $color = null) {
		return parent::$window->dialogColor($title, $color);
	}
	public static function saveAs ($title=null, $filter = array(array('All Files', '*.*')), $path = null, $filename = null, $def = null) {
		return parent::$window->dialogSave($title, $filter, $path, $filename, $def);
	}
}
?>