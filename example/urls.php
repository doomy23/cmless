<?php

$urls = array(
	array('/', 'defaultController', 'DefaultController.index'),
	array('/benchtest/', 'benchtestController', 'BenchtestController.test'),
	array('/news*', 'News.urls'),
);

?>