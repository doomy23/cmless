<?php 

abstract class CachedController extends Controller{
	public $cache;
	
	public function load_cache($params=array())
	{
		$this->cache = Cmless::CachedTemplate()->check_cache(null, null, array(), $params, $this->getCurrentUrl());
		return $this->cache;
	}
	
}

?>