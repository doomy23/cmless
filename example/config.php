<?php

$CONFIG = array(
	'debug'=>true,
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
			'engine'=>'mysql',
			'host'=>'localhost',
			'user'=>'root',
			'pass'=>'',
			'dbname'=>'example.cmless',
			'charset'=>'utf8',
		),
		'sqlite'=>array(
			'engine'=>'sqlite',
			'file'=>'database/example.sqlite',
		),
	),
	'modules'=>array(
		'Admin'=>'cmless.admin',
		'News'=>'modules.news',
	),
	'app'=>array(
		'urls'=>'urls',
		'base_uri'=>'/', // change this if running on a subdir
		'cache_enabled'=>true
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