<?php 

class QueryException extends Exception { }
class ModelNotFoundQueryException extends QueryException { }

class Query{
	private $backendKey;
	private $modelClassName;
	private $newModel;
	private $modelStructure;

	/**
	 * Query constructor
	 * @param string $backendKey 
	 * @param string $modelClassName = __CLASS__
	 */
	function Query($backendKey, $modelClassName)
	{
		$this->backendKey = $backendKey;
		$this->modelClassName = $modelClassName;
		$this->newModel = new $modelClassName($backendKey);
		$this->modelStructure = $this->newModel->structure();
	}
	
	/**
	 * Create and save model
	 * @param array $values = array('var'=>'value', ...)
	 */
	public function create(array $values)
	{
	 	$this->newModel->setValues($values);
		$this->newModel->save();
		return $this->newModel;
	}
	 
	/**
	 * Get a single model
	 * @param array $conditions
	 * @return Model
	 * @throws QueryException
	 */
	public function get(array $conditions)
	{
		if(Cmless::$config['app']['queries_saver'])
		{
			$result = QueriesSaver::getInstance()->lookForModel($this->backendKey, $this->modelClassName, $conditions);
			if($result !== false) return $result;
		}
		
		$backend = Cmless::Backend($this->backendKey);
		$results = $backend->select($this->newModel->table(), $conditions);
		
		if(count($results)==0)
			throw new ModelNotFoundQueryException(sprintf("%s not found", $this->modelClassName));
		
		if(count($results)>1)
			throw new QueryException(sprintf("Get query returned more then one %s model", $this->modelClassName));
		
		$this->newModel->setValues($results[0]);
		$this->newModel->set_is_new(false);
		
		if(Cmless::$config['app']['queries_saver'])
		{
			QueriesSaver::getInstance()->saveModel($this->backendKey, $this->newModel, $conditions);
		}
		
		return $this->newModel;
	}
	
	/**
	 * Shortcut for get with a pk
	 * @param $pk
	 * @return Model
	 * @throws QueryException
	 */
	public function getPk($value)
	{
		$pk = $this->newModel->getPk();
	
		if(count($pk)==0)
			throw new QueryException(sprintf("%s model does not define a primary key", $this->modelClassName));
	
		return $this->get(array($pk[0]=>$value));
	}
	
	/**
	 * Lookup for the values, 
	 * if no model found : create it
	 * @param array $values
	 * @return Model
	 */
	public function get_or_create(array $values)
	{
	 	try
	 	{
	 		return $this->get($values);
	 	}
	 	catch (ModelNotFoundQueryException $e)
	 	{
	 		return $this->create($values);
	 	}
	}
	 
	/**
	 * Select many models
	 * @throws QueryException
	 */
	public function filter()
	{
		$conditions = (func_num_args() >= 1)? func_get_arg(0) : array();
		$orderBy = (func_num_args() >= 2 && is_array(func_get_arg(1)))? func_get_arg(1) : array();
		$limit_count = (func_num_args() >= 3)? func_get_arg(2) : null;
		$limit_from = (func_num_args() >= 4)? func_get_arg(3) : null;
		
		if(Cmless::$config['app']['queries_saver']):
			$qs_params = array_merge($conditions, $orderBy, array('limit_count'=>$limit_count), array('limit_from'=>$limit_from));
			$result = QueriesSaver::getInstance()->lookForQuerySets($this->backendKey, $this->modelClassName, $qs_params);
			if($result !== false) return $result;
		endif;
		
		foreach($orderBy as $key=>$order)
			if(!in_array(strtolower($order), array('asc', 'desc')))
				throw new QueryException(sprintf("Bad query order_by direction '%s'", strtolower($order)));
			
		if(!is_null($limit_from) && is_null($limit_count))
			throw new QueryException(sprintf("The limit count of the query cannot be null if 'limit_from' is set to '%s'", $limit_from));
		
		$backend = Cmless::Backend($this->backendKey);
		$results = $backend->select($this->newModel->table(), $conditions, $orderBy, $limit_count, $limit_from);
		$queryset = new QuerySet($this->backendKey, $this->modelClassName, $results);
		
		if(Cmless::$config['app']['queries_saver']):
			QueriesSaver::getInstance()->saveQuerySet($this->backendKey, $queryset, $qs_params);
		endif;
		
		return $queryset;
	}
	
	/**
	 * Select many models, without conditions
	 */
	public function all()
	{
		$orderBy = (func_num_args() >= 1 && is_array(func_get_arg(0)))? func_get_arg(0) : array();
		$limit_count = (func_num_args() >= 2)? func_get_arg(1) : null;
		$limit_from = (func_num_args() >= 3)? func_get_arg(2) : null;
	 	
		return $this->filter(array(), $orderBy, $limit_count, $limit_from);
	}
	
	/**
	 * Check if the class of the model match
	 * and save model
	 * @param Model $model
	 * @throws QueryException
	 */
	public function save(Model $model)
	{
		$modelClassName = get_class($model);
		
		if(get_class($model) !== $this->modelClassName)
			throw new QueryException(sprintf("Tried to save a '%s' with a Query instancied for a '%s' model", $modelClassName, $this->modelClassName));
		
		$model->beforeSaving();
		$model->validate();
		
		$backend = Cmless::Backend($this->backendKey);
		$backend->saveModel($model);
		
		if(Cmless::$config['app']['queries_saver']):
			QueriesSaver::getInstance()->deleteModel($this->backendKey, $model);
		endif;
		
		$model->afterSaving();
	}
	
	/**
	 * Delete one or many models
	 * ALWAYS with a condition (safer)
	 * @param array $conditions
	 */
	public function delete(array $conditions)
	{
		$backend = Cmless::Backend($this->backendKey);
		$results = $backend->delete($this->newModel->table(), $conditions);
		
		if(Cmless::$config['app']['queries_saver']):
			QueriesSaver::getInstance()->deleteModelConditions($this->backendKey, $this->modelClassName, $conditions);
		endif;
		
		return $results;
	}
	
	/**
	 * Check if the class of the model match
	 * and delete the model
	 * @param Model $model
	 * @throws QueryException
	 */
	public function deleteModel(Model $model)
	{
		$modelClassName = get_class($model);
		
		if(get_class($model) !== $this->modelClassName)
			throw new QueryException(sprintf("Tried to delete a '%s' with a Query instancied for a '%s' model", $modelClassName, $this->modelClassName));
		
		$model->beforeDeleting();
		
		if(Cmless::$config['app']['queries_saver']):
			QueriesSaver::getInstance()->deleteModel($this->backendKey, $model);
		endif;
		
		$backend = Cmless::Backend($this->backendKey);
		$backend->deleteModel($model);
	}
	
	/**
	 * Count the number of rows
	 * @param array $conditions
	 * @return integer
	 */
	public function count(array $conditions=array())
	{
		$backend = Cmless::Backend($this->backendKey);
		$rows = $backend->count($this->newModel->table(), $conditions);
		return $rows;
	}
}

?>