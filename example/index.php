<?php

$cmless_path = "../cmless/";
include $cmless_path.'env.php';
require_once 'config.php';
include $cmless_path.'init.php';
unset($cmless_path);
Cmless::getInstance()->start();

?>