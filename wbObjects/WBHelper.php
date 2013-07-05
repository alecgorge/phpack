<?php
class WBHelper {
	protected static $window, $items = array();
	public static function setWindow ($window) {
		self::$window = $window;
	}
	public static function setIcon ($icon) {
		self::$window->setIcon($icon);
	}
	public static function add() {
		$args = func_get_args();
		$name = array_shift($args);

		$args = array_insert(array($name), $args, 1);

		if(empty($name))
			self::$items[] = call_user_func_array(array(self::$window, 'createControl'), $args);
		else
			self::$items[$name] = call_user_func_array(array(self::$window, 'createControl'), $args);
			
		return end(self::$items);
	}
	public static function get($item) {
		return self::$items[$item];
	}
	public static function bindClick($item, $callback) {
		return self::get($item)->onMainEvent = $callback;
	}
	public static function getText($item) {
		return self::get($item)->getText();
	}
	public static function setText($item, $text) {
		return self::get($item)->setText($text);
	}
	public static function appendText($item, $text) {
		$x = self::get($item);
		return $x->setText($x->getText().$text);
	}
	public static function getSelected($item) {
		return self::get($item)->getSelected();
	}
	public static function scrollToBottom ($item) {
		$WM_VSCROLL = 277;
		$SB_BOTTOM  = 7;
		wb_send_message(WB::get($item)->wbObj,$WM_VSCROLL,$SB_BOTTOM,0);
	}
}
class WBHelperChild extends WBHelper {
	public static function setWindow ($window) {
		self::$window = $window;
	}
}
class_alias('WBHelper', 'WB');
class_alias('WBHelperChild', 'WBC');
?>