<?php
class test {public static function lol() {var_dump(__FILE__);}}
var_dump('I AM include_b.php!','getcwd(): '.getcwd(), '__FILE__: '.__FILE__);
 include "folder/sub_folder/include_c.php";
?>