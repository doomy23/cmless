<?php

class MysqlBackendStrategy implements BackendStrategy{
	
	/**
	 * Insert query
	 * @param PDO $PDO
	 * @param string $table
	 * @param array $values
	 */
	public function insert(PDO &$PDO, $table, array $values)
	{
		$query = "INSERT INTO `".$table."` (";
		
		$fields_str = array();
		foreach (array_keys($values) as $key)
			$fields_str[] = "`".$key."`";
		
		$query .= implode(", ", $fields_str).") VALUES (";
		
		$params_str = array();
		foreach (array_keys($values) as $key)
			$params_str[] = ":".$key;
		
		$query .= implode(", ", $params_str).")";
		
		$sth = $PDO->prepare($query);
		
		foreach ($values as $key => $value)
			$sth->bindValue(":".$key, $value);
		
		$sth->execute();
		
		return $sth;
	}

	/**
	 * Update query
	 * @param PDO $PDO
	 * @param string $table
	 * @param array $conditions
	 * @param array $fields
	 */
	public function update(PDO &$PDO, $table, array $values, array $conditions=array())
	{
		$query = "UPDATE `".$table."` SET ";
		
		$fields_str = array();
		foreach (array_keys($values) as $key)
			$fields_str[] = "`".$key."`=:".$key;
		
		$query .= implode(", ", $fields_str);
		
		if(count($conditions)>0):
			$query .=" WHERE ";
			
			$conditions_str = array();
			foreach (array_keys($conditions) as $key)
				$conditions_str[] = "`".$key."`=:cond_".$key;
			
			$query .= implode(" and ", $conditions_str);
			
		endif;
		
		$sth = $PDO->prepare($query);
		
		foreach ($values as $key => $value)
			$sth->bindValue(":".$key, $value);
		
		foreach ($conditions as $key => $value)
			$sth->bindValue(":cond_".$key, $value);
		
		$sth->execute();
		
		return $sth;
	}
	
	/**
	 * Select query
	 * @param PDO $PDO
	 * @param string $table
	 * @param array $conditions
	 * @param array $fields
	 */
	public function select(PDO &$PDO, $table, array $conditions=array(), array $orderBy=array(), $limit_count=null, $limit_from=null, array $fields=array())
	{
		$query = "SELECT ";
		
		if(count($fields)!=0):
			$fields_str = array();
			foreach ($fields as $field)
				$fields_str[] = "`".$field."`";
				
			$query .= implode(", ", $fields_str);
		
		else:
			$query .= "*";
		
		endif;
		
		$query .= " FROM `".$table."`";
		
		if(count($conditions)!=0):
			$conditions_str = array();
			foreach (array_keys($conditions) as $key)
				$conditions_str[] = "`".$key."`=:cond_".$key;
			
			$query .= " WHERE ".implode(" and ", $conditions_str);
		
		endif;
		
		if(count($orderBy)!=0):
			$orderBy_str = array();
			foreach ($orderBy as $key => $order)
				$orderBy_str[] = "`".$key."` ".strtoupper($order);
		
			$query .= " ORDER BY ".implode(", ", $orderBy_str);
			
		endif;
		
		if(!is_null($limit_from)):
			$query .= sprintf(" LIMIT %d,%d ", $limit_from, $limit_count);
		
		elseif(!is_null($limit_count)):
			$query .= sprintf(" LIMIT %d ", $limit_count);
		
		endif;
		
		$sth = $PDO->prepare($query);
		
		if(count($conditions)!=0):
			foreach ($conditions as $key => $value)
				$sth->bindValue(":cond_".$key, $value);
		
		endif;
		
		$sth->execute();
		
		return $sth;
	}
	
	/**
	 * Delete query
	 * @param PDO $PDO
	 * @param string $table
	 * @param array $conditions
	 */
	public function delete(PDO &$PDO, $table, array $conditions=array())
	{
		$query = "DELETE FROM `".$table."`";
		
		if(count($conditions)!=0):
			$conditions_str = array();
			foreach (array_keys($conditions) as $key)
				$conditions_str[] = "`".$key."`=:cond_".$key;
			
			$query .= " WHERE ".implode(" and ", $conditions_str).";";
			
			$sth = $PDO->prepare($query);
			
			foreach ($conditions as $key => $value)
				$sth->bindValue(":cond_".$key, $value);
		
		else:
			$sth = $PDO->prepare($query);
		
		endif;
		
		$sth->execute();
		
		return $sth;
	}
	
	/**
	 * Count query
	 * @param PDO $PDO
	 * @param string $table
	 * @param array $conditions
	 */
	public function count(PDO &$PDO, $table, array $conditions=array())
	{
		$query = "SELECT COUNT(*) FROM `".$table."`";
		
		if(count($conditions)!=0):
			$conditions_str = array();
			foreach (array_keys($conditions) as $key)
				$conditions_str[] = "`".$key."`=:cond_".$key;
				
			$query .= " WHERE ".implode(" and ", $conditions_str).";";
				
			$sth = $PDO->prepare($query);
				
			foreach ($conditions as $key => $value)
				$sth->bindValue(":cond_".$key, $value);
		
		else:
			$sth = $PDO->prepare($query);
		
		endif;
		
		$sth->execute();
		
		return $sth;
	}
	
}

?>