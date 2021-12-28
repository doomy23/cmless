<?php

interface BackendStrategy{
	
	/**
	 * Insert query
	 * @param PDO $PDO
	 * @param string $table
	 * @param array $values
	 */
	public function insert(PDO &$PDO, $table, array $values);
	
	/**
	 * Update query
	 * @param PDO $PDO
	 * @param string $table
	 * @param array $conditions
	 * @param array $fields
	 */
	public function update(PDO &$PDO, $table, array $values, array $conditions=array());
	
	/**
	 * Select query
	 * @param PDO $PDO
	 * @param string $table
	 * @param array $conditions
	 * @param array $fields
	 */
	public function select(PDO &$PDO, $table, array $conditions=array(), array $orderBy=array(), $limit_count=null, $limit_from=null, array $fields=array());
	
	/**
	 * Delete query
	 * @param PDO $PDO
	 * @param string $table
	 * @param array $conditions
	 */
	public function delete(PDO &$PDO, $table, array $conditions=array());
	
	/**
	 * Count query
	 * @param PDO $PDO
	 * @param string $table
	 * @param array $conditions
	 */
	public function count(PDO &$PDO, $table, array $conditions=array());
	
}

?>