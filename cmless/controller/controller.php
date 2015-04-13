<?php

abstract class Controller{
	private $path;
	private $url;
	private $called;
	
	public function __construct($url=null, $path=null, $function=null)
	{
		$this->path = $path;
		$this->called = $function;
		$this->url = $url;
	}
	
	final public function getCurrentPath()
	{
		return $this->path;
	}
	
	final public function getCurrentUrl()
	{
		return $this->url;
	}
	
	final public function getCalledFunction()
	{
		return $this->called;
	}
}

?>