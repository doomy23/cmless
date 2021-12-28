<?php

if(!defined('CMLESS_PATH')) define('CMLESS_PATH', '');
if(!defined('RUN_PATH')) define('RUN_PATH', getcwd());

$DEFAULT_CONFIG = array(
	'debug'=>true,
	'cmless_path'=>CMLESS_PATH,
	'run_path'=>RUN_PATH,
	'templates_aliases'=>array(
		"cmless"=>CMLESS_PATH."frontend",
	),
	'error_controller'=>null,
	'errors_ignored'=>array(),
	'error_templates'=>array(
		403=>"cmless/403.html",
		404=>"cmless/404.html",
		500=>"cmless/500.html",
	),
	'datetime'=>array(
		'default_timezone'=>'UTC',
		'save_as'=>'UTC',
	),
	'i18n'=>array(
		'on'=>false,
		'detect'=>false,
		'langs'=>array(),
		'default'=>null,
		'defaultEngine'=>'cmless.i18n.ai18n'
	),
	'media_dir'=>'media',
	'static_dir'=>'static',
	'media_url'=>'/media/',
	'static_url'=>'/static/',
	'db'=>array(),
	'modules'=>array(),
	'app'=>array(
		'urls'=>null,
		'base_uri'=>'/',
		'queries_saver'=>true,
		'default_db_key'=>'default',
		'default_cache_db_key'=>'default',
		'default_cache_lifetime'=>120,
		'cache_enabled'=>true
	),
	'hashing'=>array(
		'algo'=>PASSWORD_BCRYPT,
	),
	'session'=>array(
		'name'=>"site_session",
		'lifetime'=>3600,
		'httponly'=>true,
	),
);

// Recursive function that set the defaults value if not specified
function set_defaults(&$array, &$default)
{
	foreach ($default as $key => $value):
	    if(array_key_exists($key, $array))
		{
	        if(is_array($array[$key]))
				set_defaults($array[$key], $value);
		}
		else
		{
			$array[$key] = $value;
		}
	endforeach;
}

?>