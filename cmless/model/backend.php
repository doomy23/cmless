<?php

class BackendException extends Exception { }

final class Backend{
	public static $BACKEND_ENGINES = array('mysql', 'pgsql', 'sqlite');
	
	private $driver;
	private $dsn;
	private $PDO;
	private $queryStrategy;
	
	/**
	 * Backend constructor
	 * @param string $key = which database to connect to
	 */
	public function Backend($key="default")
	{
		if(!array_key_exists($key, Cmless::$config['db'])) 
			throw new BackendException(sprintf("Specified key '%s' was not found into db config.", $key));
		
		if(!array_key_exists("engine", Cmless::$config['db'][$key]))
			throw new BackendException(sprintf("Engine not found in \$config['%s']", $key));
		
		if(!in_array(Cmless::$config['db'][$key]['engine'], self::$BACKEND_ENGINES))
			throw new BackendException(sprintf("Backend engine '%s' not supported.", Cmless::$config['db'][$key]['engine']));
		
		$this->driver = Cmless::$config['db'][$key]['engine'];
		$this->dsn = $this->driver.":";
		
		$dsn_params = array();
		$user = null;
		$pass = null;
		$options = array();
		
		/* Depending on which engine , the required fields and the dsn are different */
		
		if(in_array(Cmless::$config['db'][$key]['engine'], array('mysql', 'pgsql'))):
			if(!array_key_exists("dbname", Cmless::$config['db'][$key]))
				throw new BackendException(sprintf("Database name not found in \$config['%s']", $key));
			
			if(!array_key_exists("host", Cmless::$config['db'][$key]))
				throw new BackendException(sprintf("Database host not found in \$config['%s']", $key));
			
			if(!array_key_exists("user", Cmless::$config['db'][$key]))
				throw new BackendException(sprintf("Database user not found in \$config['%s']", $key));
			
			$dsn_params[] = "host=".Cmless::$config['db'][$key]['host'];
			
			if(array_key_exists("port", Cmless::$config['db'][$key])):
				if(is_int(Cmless::$config['db'][$key]['port']))
					$dsn_params[] = "port=".Cmless::$config['db'][$key]['port'];
				else
					throw new BackendException(sprintf("\$config['%s']['port'] is not an integer", $key));
			endif;
			
			$dsn_params[] = "dbname=".Cmless::$config['db'][$key]['dbname'];
			
			if(Cmless::$config['db'][$key]['engine']=="pgsql"):
				// PGSQL specific
				$dsn_params[] = "user=".Cmless::$config['db'][$key]['user'];
				
				if(array_key_exists("pass", Cmless::$config['db'][$key])):
					$dsn_params[] = "password=".Cmless::$config['db'][$key]['pass'];
				endif;
				
				$this->queryStrategy = null;
				
			else:
				// MySQL specific
				$user = Cmless::$config['db'][$key]['user'];
				
				if(array_key_exists("pass", Cmless::$config['db'][$key])):
					$pass = Cmless::$config['db'][$key]['pass'];
				endif;
				
				$this->queryStrategy = new MysqlBackendStrategy();
				
			endif;
				
			if(array_key_exists("charset", Cmless::$config['db'][$key])):
				$dsn_params[] = "charset=".Cmless::$config['db'][$key]['charset'];
				
				if(Cmless::$config['db'][$key]['engine']=="mysql")
					$options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES ".Cmless::$config['db'][$key]['charset'];
					
			endif;
		endif;
		
		if(Cmless::$config['db'][$key]['engine']=="sqlite"):
			// SQLite
			if(!array_key_exists("file", Cmless::$config['db'][$key]))
				throw new BackendException(sprintf("SQLite file not found in \$config['%s']", $key));
				
			if(!file_exists(Cmless::$config['db'][$key]['file']))
				throw new BackendException(sprintf("SQLite file '%s' does not exist", Cmless::$config['db'][$key]['file']));
				
			$dsn_params[] = Cmless::$config['db'][$key]['file'];
			
			$this->queryStrategy = null;
			
		endif;
		
		$this->dsn .= implode(";", $dsn_params);
		$this->PDO = new PDO($this->dsn, $user, $pass, $options);
		$this->PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	
	/**
	 * Check if a strategy is set for making queries
	 * @throws BackendException
	 */
	private function checkStrategy()
	{
		if(is_null($this->queryStrategy))
			throw new BackendException(sprintf("Backend class does not have a query strategy for '%s' yet...", $this->driver));
	}
	
	/**
	 * Insert with backend strategy
	 * @param string $table
	 * @param array $values
	 */
	public function insert($table, array $values)
	{
		$this->checkStrategy();
		return $this->queryStrategy->insert($this->PDO, $table, $values);
	}
	
	/**
	 * Update with backend strategy
	 * @param string $table
	 * @param array $values
	 */
	public function update($table, array $values, array $conditions)
	{
		$this->checkStrategy();
		return $this->queryStrategy->update($this->PDO, $table, $values, $conditions);
	}
	
	/**
	 * Select with backend strategy
	 * @param unknown $table
	 * @param array $conditions
	 * @param array $fields
	 */
	public function select($table, array $conditions=array())
	{
		$orderBy = (func_num_args() >= 3 && is_array(func_get_arg(2)))? func_get_arg(2) : array();
		$limit_count = (func_num_args() >= 4)? func_get_arg(3) : null;
		$limit_from = (func_num_args() >= 5)? func_get_arg(4) : null;
		$fields = (func_num_args() >= 6)? func_get_arg(5) : array();
		
		$this->checkStrategy();
		$sth = $this->queryStrategy->select($this->PDO, $table, $conditions, $orderBy, $limit_count, $limit_from,  $fields);
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}
	
	/**
	 * Delete with backend strategy
	 * @param string $table
	 * @param array $conditions
	 */
	public function delete($table, array $conditions)
	{
		$this->checkStrategy();
		return $this->queryStrategy->delete($this->PDO, $table, $conditions);
	}
	
	/**
	 * Count with backend strategy
	 * @param string $table
	 * @param array $conditions
	 */
	public function count($table, array $conditions)
	{
		$this->checkStrategy();
		$sth = $this->queryStrategy->count($this->PDO, $table, $conditions);
		$results = $sth->fetch(PDO::FETCH_BOTH);
		return (int) $results[0];
	}
	
	/**
	 * Save/create the model
	 * @param Model $model
	 */
	public function saveModel(Model &$model)
	{
		if($model->is_new()):
			$sth = $this->insert($model->table(), $model->getValues());
			$pk = $model->getPk(); // key, value
			// Set pk if auto
			if(count($pk)!=0):
				$pk_key = $pk[0];
				$pk_value = $pk[1];
				$structure = $model->structure();
			
				if(is_null($pk_value) && in_array("auto", $structure[$pk_key])):
					var_dump($pk_key, $this->PDO->lastInsertId());
					$model->$pk_key = $this->PDO->lastInsertId();
				
				endif;
			endif;
			
			$model->set_is_new(false);
		
		else:
			$this->update($model->table(), $model->getValues(), $model->getIdentity());
		
		endif;
	}
	
	/**
	 * Shortcut for model deletion
	 * @param Model $model
	 */
	public function deleteModel(Model &$model)
	{
		$this->delete($model->table(), $model->getIdentity());
	}
	
}

?>