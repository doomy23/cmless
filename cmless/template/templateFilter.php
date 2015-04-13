<?php 

abstract class TemplateFilter{
	const filter = null;
	
	/**
	 * Function used to filter the value
	 * @param unknown $value
	 * @param array $params
	 * @return unknown
	 */
	public abstract function filter($value, array $params);
	
}

?>