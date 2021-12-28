<?php

if(!isset($CONFIG))
	die("\$CONFIG is not set.");

if(!array_key_exists('cmless_path', $CONFIG)):
	die("\$CONFIG['cmless_path'] is not set.");
	
else:
	define('CMLESS_PATH', $CONFIG['cmless_path']);
	define('RUN_PATH', getcwd());
	
endif;

// Set unfilled config with defaults
require_once CMLESS_PATH.'config.php';
set_defaults($CONFIG, $DEFAULT_CONFIG);
unset($DEFAULT_CONFIG);

// Set DEBUG
if(!array_key_exists('debug', $CONFIG) || !is_bool($CONFIG['debug'])):
	define('CMLESS_DEBUG', true);
	
else:
	define('CMLESS_DEBUG', $CONFIG['debug']);
	
endif;

require_once CMLESS_PATH.'utilities/init.php';
require_once CMLESS_PATH.'model/init.php';
require_once CMLESS_PATH.'template/init.php';
require_once CMLESS_PATH.'controller/init.php';
require_once CMLESS_PATH.'module.php';
require_once CMLESS_PATH.'urls.php';
require_once CMLESS_PATH.'cmless.php';
require_once CMLESS_PATH.'user/init.php';
require_once CMLESS_PATH.'csrf.php';
require_once CMLESS_PATH.'error.php';

Cmless::getInstance()->setConfig($CONFIG);
unset($CONFIG);

?>