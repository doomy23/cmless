<?php 

class QuerySet implements IteratorAggregate, Countable{
	private $_backendKey;
	private $_model;
	private $_aggregate = array();
	
	/**
	 * Constructor
	 * @param unknown $backendKey
	 * @param unknown $modelClassName
	 * @param array $dataSet
	 */
	public function QuerySet($backendKey, $modelClassName, array $dataSet)
	{
		$this->_backendKey = $backendKey;
		$this->_model = $modelClassName;
		
		foreach($dataSet as $values):
			$model = new $modelClassName($backendKey);
			$model->setValues($values);
			$model->set_is_new(false);
			$this->_aggregate[] = $model;
			
		endforeach;
	}
	
	/**
	 * IteratorAggregate
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->_aggregate);
	}
	
	/**
	 * Countable
	 */
	public function count()
	{
		return count($this->_aggregate);
	}
	
	/**
	 * Get the model class name
	 */
	public function getModel()
	{
		return $this->_model;
	}
	
}

?>