<?php

class SavedQuery{
	public $backendKey;
	
	private $time;
	private $used;
	private $result;
	private $conditions;
	
	public function SavedQuery($backendKey, $result, array $conditions)
	{
		$this->backendKey = $backendKey;
		
		$this->time = time();
		$this->used = 0;
		$this->result = $result;
		$this->conditions = $conditions;
	}
	
	/**
	 * Get the model or queryset and increase
	 * or not the priority of the savedquery
	 * @param string $count
	 */
	public function getResult($count=true)
	{
		if($count) $this->used++;
		return $this->result;
	}
	
	/**
	 * Calculate object priority
	 * @return number
	 */
	public function getPriority()
	{
		return $this->time+($this->used*1000000000);
	}
	
	/**
	 * Get the class of the result
	 * @return string
	 */
	public function getClass()
	{
		return get_class($this->result);
	}
	
	/**
	 * Get the query conditions
	 * @return array
	 */
	public function getConditions()
	{
		return $this->conditions;
	}
	
}

class QueriesSaver{
	public static $MAX_SAVED_QUERIES = 10; // You can set it anytine you want
	private static $_instance;
	
	private $_saved = 0;
	private $_queries = array();
	
	/**
	 * Private constructor
	 */
	private function QueriesSaver(){}
	
	/**
	 * Instance creator/getter
	 */
	public static function getInstance()
	{
		if(is_null(self::$_instance))
			self::$_instance = new QueriesSaver();
		
		return self::$_instance;
	}
	
	/**
	 * Clear saved queries
	 */
	public function clear()
	{
		$this->_queries = array();
		$this->_saved = 0;
	}
	
	/**
	 * Delete any queries that
	 * conditions match with $model->getValues()
	 * @param string $backendKey
	 * @param Model $model
	 */
	public function deleteModel($backendKey, Model $model)
	{
		$modelClassName = get_class($model);
		
		if(array_key_exists($modelClassName, $this->_queries))
		{
			foreach($this->_queries[$modelClassName] as $serialKey => $savedQuery):
				$containsSearch = count(array_intersect_assoc($savedQuery->getConditions(), $model->getValues())) == count($savedQuery->getConditions()) || count($model->getValues());
			
				if($containsSearch):
					unset($this->_queries[$modelClassName][$serialKey]);
					$this->_saved--;
				
				endif;
			endforeach;
		}
	}
	
	/**
	 * Delete any queries that
	 * conditions match with $model->getValues()
	 * @param string $backendKey
	 * @param Model $model
	 * @param array $conditions
	 */
	public function deleteModelConditions($backendKey, $modelClassName, array $conditions)
	{
		if(array_key_exists($modelClassName, $this->_queries)):
			foreach($this->_queries[$modelClassName] as $serialKey => $savedQuery):
				$containsSearch = count(array_intersect_assoc($conditions, $savedQuery->getConditions())) == count($conditions) || count($savedQuery->getConditions());
					
				if($containsSearch):
					unset($this->_queries[$modelClassName][$serialKey]);
					$this->_saved--;
		
				endif;
			endforeach;
		endif;
	}
	
	/**
	 * Pass through saved queries of a model and delete
	 * in order of priority
	 * @param string $backendKey
	 * @param string $modelClassName
	 */
	private function cleanUp($backendKey, $modelClassName=null)
	{
		$queriesToDelete = $this->_saved-self::$MAX_SAVED_QUERIES;

		if($queriesToDelete>0):
			$prorities = array();
			
			foreach($this->_queries as $model => $modelQueries):
				foreach($modelQueries as $key => $savedQuery):
					$prorities[$savedQuery->backendKey." == ".$model." == ".$key] = $savedQuery->getPriority();
				endforeach;
			endforeach;

			foreach($prorities as $prorityKey => $prority):
				list($bk, $model, $serial) = sscanf($prorityKey, "%s == %s == %s");
				
				if($bk!=$backendKey || $model!=$modelClassName || 
					($model==$modelClassName && count($this->_queries[$modelClassName]) >= 1))
				{
					unset($this->_queries[$model][$serial]);
					$this->_saved--;
					
					if($this->_saved-self::$MAX_SAVED_QUERIES<=0)
						break;
				}
			
			endforeach;
		endif;
	}
	
	/**
	 * Add a SavedQuery to the agregate
	 * replacing older one if there is
	 * @param string $modelClassName
	 * @param string $serialKey
	 * @param SavedQuery $savedQuery
	 */
	private function addSavedQuery($modelClassName, $serialKey, SavedQuery $savedQuery)
	{
		$result = $savedQuery->getResult(false);
		
		if(is_a($result, "Model")):
			$this->deleteModel($savedQuery->backendKey, $result);
		elseif(is_a($result, "Queryset")):
			$this->deleteModelConditions($savedQuery->backendKey, $result->getModel(), $savedQuery->getConditions());
		endif;
		
		$this->_saved++;
		$this->_queries[$modelClassName][$serialKey] = $savedQuery;
	}
	
	/**
	 * Save a model
	 * @param string $backendKey
	 * @param Model $model
	 * @param array $conditions
	 */
	public function saveModel($backendKey, Model $model, array $conditions)
	{
		$modelClassName = get_class($model);
		if(!array_key_exists($modelClassName, $this->_queries)) $this->_queries[$modelClassName] = array();
		
		$identity = $model->getIdentity();
		
		// Saves with the biggest conditions
		// Because when using 'lookForModel' we check that the conditions
		// are all CONTAINED in the savedQuery conditions
		if(count($identity)>count($conditions))
		{
			$this->addSavedQuery($modelClassName, md5(print_r($identity, true)), new SavedQuery($backendKey, $model, $identity));
		}
		else 
		{
			$this->addSavedQuery($modelClassName, md5(print_r($conditions, true)), new SavedQuery($backendKey, $model, $conditions));
		}
		
		$this->cleanUp($backendKey, $modelClassName);
		
		return true;
	}
	
	/**
	 * Save a queryset
	 * @param string $backendKey
	 * @param QuerySet $queryset
	 * @param array $conditions
	 * @return boolean
	 */
	public function saveQuerySet($backendKey, QuerySet $queryset, array $conditions)
	{
		if(count($queryset)>0):
			$modelClassName = $queryset->getModel();
			if(!array_key_exists($modelClassName, $this->_queries)) $this->_queries[$modelClassName] = array();
			
			$this->addSavedQuery($modelClassName, md5(print_r($conditions, true)), new SavedQuery($backendKey, $queryset, $conditions));
			$this->cleanUp($backendKey, $modelClassName);
			
			return true;
			
		else:
			return false;
		
		endif;
	}
	
	/**
	 * For for a model with matching conditions
	 * @param string $backendKey
	 * @param string $modelClassName
	 * @param array $conditions
	 * @return false or $modelClassName instance
	 */
	public function lookForModel($backendKey, $modelClassName, array $conditions)
	{
		if(array_key_exists($modelClassName, $this->_queries))
		{
			foreach($this->_queries[$modelClassName] as $savedQuery):
				if($savedQuery->backendKey == $backendKey && $savedQuery->getClass()==$modelClassName):
					$containsSearch = count(array_intersect_assoc($conditions, $savedQuery->getConditions())) == count($conditions);
						
					if($containsSearch):
						return $savedQuery->getResult();
						
					endif;
				endif;
			endforeach;
		}
		
		return false;
	}
	
	/**
	 * Same as lookForModel but for querySets
	 * @param string $backendKey
	 * @param string $modelClassName
	 * @param array $conditions
	 */
	public function lookForQuerySets($backendKey, $modelClassName, array $conditions)
	{
		if(array_key_exists($modelClassName, $this->_queries))
		{
			foreach($this->_queries[$modelClassName] as $savedQuery):
				if($savedQuery->backendKey == $backendKey && $savedQuery->getClass()=="QuerySet"):
					$containsSearch = count(array_intersect_assoc($conditions, $savedQuery->getConditions())) == count($conditions);
				
					if($containsSearch):
						return $savedQuery->getResult();
				
					endif;
				endif;
			endforeach;
		}
		
		return false;
	}
	
}

?>