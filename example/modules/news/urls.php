<?php

$urls = array(
	array('/', 'controller', 'NewsController.index'),
	array('/page-%d/', 'controller', 'NewsController.index'),
	array('/%s/', 'controller', 'NewsController.index'),
	array('/%s/page-%d/', 'controller', 'NewsController.index'),
	array('/%s/%s/', 'controller', 'NewsController.details'),
);

?>