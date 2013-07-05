<?php
if(!defined('PHPArchive_Splitter'))
	define('PHPArchive_Splitter',  "|".base64_encode('PHPACK FILE SPLITTER')."|");

if(!class_exists('PHPArchive')) {
$PHPARCHIVE_TEMP_VAR = "";
class PHPArchive {
	private $handle, $files = array(), $dirs = array(), $hasWritten = false, $context = '';
	const DEBUG = FALSE;
	public static $alias = array();
	public static $alias_files = array();
	public static $alias_dirs = array();
	
	public function __construct ($handle) {
		if(is_string($handle)) {
			$this->handleString = $handle;
			$this->setHandle(fopen($handle, 'w'));
		}
		elseif(is_resource($handle)) {
			$this->setHandle($handle);
		}
	}
	
	
	public function setHandle ($handle) {
		if(!is_resource($handle)) {
			throw new Exception('Argument $handle isn\'t a fopen resource!');
		}
		$this->handle = $handle;
	}
	public function addDirectory ($dir, $regex, $context = null, $flags = null) {
		if($context === null) $context = $dir;
		if($flags === -1 || empty($flags)) $flags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_PATHNAME;
		$d =  new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, $flags));

		while($d->valid()) {
			if(preg_match($regex, $d->current()->getPathname()) == 1 || is_dir($d->current()->getPathname()))
				$this->files[] = $d->current()->getPathname();

			$d->next();
		}
		$this->context = trim($context, '/\\');
	}
	public function build () {
		$h = &$this->handle;
		$f = $this->files;
		$f['context'] = $this->context;
		fwrite($h, $this->gzdeflate(serialize($f)).$this->getSplitter());
		
		foreach($this->files as $val) {
			if(!is_dir($val)) {
				fwrite($h, base64_encode($val)."|".$this->gzdeflate($this->bcompileFile($val)));
				fwrite($h, $this->getSplitter());
			}
		}
	}
	public function bcompileFile ($file) {
		return file_get_contents($file);
		
		if(substr($file, -4, 4) !== '.php' || substr($file, -4, 4) !== '.php' ) return file_get_contents($file);
		
		$descriptorspec = array(
			0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
			1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		);

		$process = proc_open('bcompiler.exe '.escapeshellarg($file).' php://stdout', $descriptorspec, $pipes);
		if (is_resource($process)) {
			// $pipes now looks like this:
			// 0 => writeable handle connected to child stdin
			// 1 => readable handle connected to child stdout
			$bcompiled_file = stream_get_contents($pipes[1]);
			fclose($pipes[1]);

			// It is important that you close any pipes before calling
			// proc_close in order to avoid a deadlock
			proc_close($process);
		}
		return $bcompiled_file;
	}
	
	private function gzdeflate ($data, $level = -1) {
		if(PHPArchive::DEBUG)
			return $data;
		return gzdeflate($data, $level);
	}
	private function gzinflate ($data) {
		if(PHPArchive::DEBUG)
			return $data;
		return gzinflate($data);
	}
	public static function gzinflate_s ($data) {
		if(PHPArchive::DEBUG)
			return $data;
		return gzinflate($data);
	}
	private static function getSplitter () {
		return PHPArchive_Splitter;
	}
	
	public static function loadPA ($handle, $alias) {
		if(array_key_exists($alias, self::$alias)) throw new Exception ("Alias $alias is already in use!");

		if(is_string($handle)) {
			$h = fopen($handle, 'r');
		}
		elseif(is_resource($handle)) {
			$h = $handle;
		}
		set_include_path("pa://" . $alias . PATH_SEPARATOR . "pa:///". PATH_SEPARATOR . get_include_path());
		self::$alias[] = $alias;
		
		$content = stream_get_contents($h);

		$content = explode(self::getSplitter(), $content);
		array_pop($content);
		
		$payload = array(); $contex = '';
		foreach($content as $k => $val) {
			global $payload;
			if($k === 0) { $payload[0] = unserialize(self::gzinflate_s($val)); } else {
				$val = explode("|", $val, 2);
				if(PHPArchive::DEBUG) return $val;
				$k = base64_decode($val[0]);
				$val = self::gzinflate_s($val[1]);
				$payload[$k] = $val;
			}
		}
		$fileList = $payload[0];
		$context = $fileList['context'];
		unset($fileList['context']);
		array_shift($payload);
		
		$contextualPayload = array();
		foreach($payload as $file => $contents) {
			$parts = explode($context, $file, 2);
			$correct = self::correctPaths($parts[1]);
			$dirs[] = rtrim(self::correctPaths(dirname($correct), true), '/').'/';
			$contextualPayload[$correct] = $contents;
		}
		$dirs = array_unique($dirs);
		
		self::$alias_files[$alias] = $contextualPayload;
		self::$alias_dirs[$alias] = $dirs;
		
		
		// var_dump(self::$alias_files);
	}
	
	public static function getFiles ($alias) {
		return self::$alias_files[$alias];
	}
	public static function getDirs ($alias) {
		return self::$alias_dirs[$alias];
	}
	public static function aliasExists ($x) {
		return (array_search($x, self::$alias) === false ? false : true);
	}
	public static function getFile($alias, $file) {
		return self::$alias_files[$alias][self::correctPaths($file)];
	}
	public static function fileExists($alias, $file) {
		return array_key_exists(self::correctPaths($file), (array)self::$alias_files[$alias]);
	}
	public static function correctPaths ($x, $over = false) {
		$r = '/'.ltrim(strtr($x, '\\', '/'),'/');
		return ((substr($r, -1, 1) == '/' || empty($r)) && !$over ? $r.'/index.php' : $r);
	}
}
class PHPFileStream /*extends WrapperAbstract*/ {
	public $pos = 0, $alias, $file, $length, $content, $isDir;
	public function stream_open ($path, $mode, $options, $opened) {
		if(substr($path, 0, 6) == 'pa:///') {
			$backtrace = debug_backtrace();
			$caller_path = parse_url($backtrace[0]['file']);
			$path = 'pa://'.$caller_path['host'].substr($path, 5);
		}
		$url = parse_url($path);

		if(substr_count($url['path'], '..') > 0) {
			$temp_url = ltrim(PHPArchive::correctPaths($url['path']), '/');
			if(substr($temp_url, 0, 3) == '../') {
				$temp_url = '/'.substr($temp_url, 2);
			}
			$temp_url = '/'.ltrim(preg_replace('/\w+\/(\.\.\/)+/', '', $temp_url), '/');
			$url['path'] = $temp_url;
		}

		if(!PHPArchive::aliasExists($url['host'])) {
			throw new Exception("The PHPArchive {$url['host']} has not been loaded. Don't forget to use PHPArchive::loadPa(\$filename/\$fopen_handle, \$alias)");
			return false;
		}
		
		$url['path'] = ltrim($url['path'], '/');
		if(empty($url['path'])) $url['path'] == '/';
		
		if(substr_count($url['path'], '..') > 0) {
			$correct = PHPArchive::correctPaths($url['path']);
			if(substr($correct, 0, 2) == '..') {
				$url['path'] = substr_replace($correct, '/', 0, 2);
			}
			else {
				$url['path'] = '/'.preg_replace('/\w+\/(\.\.\/)+/', '', $correct);
			}
		}

		$this->isDir = substr(PHPArchive::correctPaths($url['path'], true), -1, 1) == '/';
		
		$this->alias = $url['host'];
		$this->file = PHPArchive::correctPaths($url['path']);
		$this->content = PHPArchive::getFile($this->alias, $this->file);
		$this->length = strlen($this->content);

		return (PHPArchive::aliasExists($url['host']) && PHPArchive::fileExists($url['host'], $this->file));
	}
	public function stream_read ($count) {
		$r = substr($this->content, $this->pos, $count);
		$this->advancePos($count);
		return $r;
	}
	public function stream_eof () {
		return ($this->pos === $this->length);
	}
	public function stream_close () {
		unset($this->alias);
		unset($this->file);
		unset($this->length);
		unset($this->content);
	}
	public function stream_seek ($offset, $whence = SEEK_SET) {
		if($whence == SEEK_SET) {
			$this->setPos($offset);
		}
		if($whence == SEEK_CUR) {
			$this->advancePos($offset);
		}
		if($whence == SEEK_END) {
			$this->advancePosToEnd();
			$this->advancePos($offset, true);
		}
	}
	public function stream_stat () {
		return array(
			7 => $this->length,
			'size' => $this->length,
		);
	}
	public function stream_flush () {
		return true;
	}
	public function url_stat ($path, $flag = 0) {
		return array(
			7 => $this->length,
			'size' => $this->length,
			9 => 1,
			'mtime' => 1,
			8 => 1,
			'atime' => 1,
			10 => 1,
			'ctime' => 1,
		);
	}

	
	private function setPos ($pos) {
		$this->pos = $pos;
	}
	private function advancePos ($count, $override = false) {
		if($this->pos + $count > $this->length && $override === false)
			$this->pos = $this->length;
		else
			$this->pos += $count;
	}
	private function advancePosToEnd() {
		$this->pos = $this->length - 1;
	}
	public function __call ($name, $args) {
		var_dump($name, $args);
	}
}

stream_wrapper_register("pa", "PHPFileStream") or die("failed to register pa:// with PHPFileStream");
class VariableStream {
	 var $position;
	 var $varname;

	 function stream_open($path, $mode, $options, &$opened_path)
	 {
		  $url = parse_url($path);
		  $this->varname = $url["host"];
		  $this->position = 0;

		  return true;
	 }

	 function stream_read($count)
	 {
		  $ret = substr($GLOBALS[$this->varname], $this->position, $count);
		  $this->position += strlen($ret);
		  return $ret;
	 }

	 function stream_write($data)
	 {
		  $left = substr($GLOBALS[$this->varname], 0, $this->position);
		  $right = substr($GLOBALS[$this->varname], $this->position + strlen($data));
		  $GLOBALS[$this->varname] = $left . $data . $right;
		  $this->position += strlen($data);
		  return strlen($data);
	 }

	 function stream_tell()
	 {
		  return $this->position;
	 }

	 function stream_eof()
	 {
		  return $this->position >= strlen($GLOBALS[$this->varname]);
	 }

	 function stream_seek($offset, $whence)
	 {
		  switch ($whence) {
				case SEEK_SET:
					 if ($offset < strlen($GLOBALS[$this->varname]) && $offset >= 0) {
							$this->position = $offset;
							return true;
					 } else {
							return false;
					 }
					 break;

				case SEEK_CUR:
					 if ($offset >= 0) {
							$this->position += $offset;
							return true;
					 } else {
							return false;
					 }
					 break;

				case SEEK_END:
					 if (strlen($GLOBALS[$this->varname]) + $offset >= 0) {
							$this->position = strlen($GLOBALS[$this->varname]) + $offset;
							return true;
					 } else {
							return false;
					 }
					 break;

				default:
					 return false;
		  }
	 }
}
stream_wrapper_register("var", "VariableStream")
	or die("Failed to register protocol var with the class VariableStream");
}
	
class Data {
	private static $info = array();
	public static function set($key, $v) {
		self::$info[$key] = $v;
		return count(self::$info) - 1;
	}
	public static function get($k) {
		return self::$info[$k];
	}
	public static function getAll() {
		return self::$info;
	}
	private static function r_del($x) {
		unset(self::$info[$k]);
	}
	public static function del($x) {
		return (array_key_exists($x, self::$info) ? self::r_del($x) : true);
	}
}
?>