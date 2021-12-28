<?php

$CONFIG = array(
	'debug'=>env('DEBUG', true),
	'cmless_path'=>'../cmless/',
	'errors_ignored'=>array(E_DEPRECATED), // You should not do this but it's possible...
	'error_controller'=>'defaultController.DefaultController.error', // On 'debug' false only for error: 500
	'error_templates'=>array(
		403=>"templates/403.html",
		404=>"templates/404.html",
		500=>"templates/500.html",
	),
	'datetime'=>array(
		'default_timezone'=>'America/Montreal',
	),
	'db'=>array(
		'default'=>array(
			'engine'=>env('DB_DEFAULT_ENGINE', 'mysql'),
			'host'=>env('DB_DEFAULT_HOST', 'localhost'),
			'user'=>env('DB_DEFAULT_USER', 'root'),
			'pass'=>env('DB_DEFAULT_PASS', ''),
			'dbname'=>env('DB_DEFAULT_DBNAME', 'example.cmless'),
			'charset'=>env('DB_DEFAULT_CHARSET', 'utf8'),
		),
		'sqlite'=>array(
			'engine'=>'sqlite',
			'file'=>'database/example.sqlite',
		),
	),
	'modules'=>array(
		'Admin'=>'cmless.admin',
		'News'=>'modules.news',
		'Users'=>'modules.users',
	),
	'app'=>array(
		'urls'=>'urls',
		'base_uri'=>'/', // change this if running on a subdir
		'cache_enabled'=>env('CACHE_ENABLED', true)
	),
	'media_dir'=>'media',
	'static_dir'=>'static',
	'media_url'=>'/media/',
	'static_url'=>'/static/',
	'session'=>array(
		'name'=>"example",
	)
);

?>