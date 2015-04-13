<?php

class ModelException extends Exception { }

abstract class Model{
	private $_model;
	private $_backendKey;
	private $_is_new;
	
	/**
	 * Constructor
	 * @param string $backendKey
	 */
	public function __construct($backendKey="default")
	{
		$this->_model = get_class($this);
		$this->_backendKey = $backendKey;
		$this->_is_new = true;
	}
	
	/**
	 * Static manager
	 * @param string $backendKey
	 * @param string $modelClassName = __CLASS__
	 * @return Query
	 */
	public static function objects($backendKey, $modelClassName)
	{
		return new Query($backendKey, $modelClassName);
	}
	
	/**
	 * Return readable model name
	 */
	public function __toString()
	{
		return $this->_model;
	}
	
	/**
	 * @return string = table used
	 */
	abstract public function table();
	
	/**
	 * @return array = ('field'=> array('type'=>'', ...), ...)
	 */
	abstract public function structure();

	/**
	 * Set values to the model
	 * No validations except for the keys
	 * @param array $values = ('var'=>'value', ...)
	 */
	public final function setValues(array $values)
	{	
		if(count($values)==0)
			throw new ModelException(sprintf("%s->setValues() received an empty array as argument", $this->_model));
		
		$this->validated = false;
		
		foreach ($values as $key => $value):
			if(!in_array($key, array_keys($this->structure())))
				throw new ModelException(sprintf("'%s' not found in %s structure", $key, $this->_model));
			
			$this->$key = $value;
			
		endforeach;
	}
	
	/**
	 * Returns an array with the properties
	 * @return array = prop=>value
	 */
	public final function getValues()
	{
		$values = array();
		
		foreach(array_keys($this->structure()) as $key):
			$values[$key] = $this->$key;
			
		endforeach;
		
		return $values;
	}
	
	/**
	 * Get the pk
	 * @return array
	 */
	public final function getPk()
	{
		foreach(array_keys($this->structure()) as $key):
			$structure = $this->structure();
		
			if(array_key_exists("type", $structure[$key])):
				if($structure[$key]["type"]=="pk")
					return array($key, $this->$key);
		
			endif;
		endforeach;
	
		return array();
	}
	
	/**
	 * Can be overwritten for models
	 * without "pk"...
	 * @return array prop=>value
	 */
	public function getIdentity()
	{
		$pk = $this->getPk();
		
		if(count($pk)!=0)
			return array($pk[0]=>$pk[1]);
			
		return array();
	}
	
	/**
	 * Set the property to its default value
	 * @param string $property
	 */
	public final function setDefault($property)
	{
		$structure = $this->structure();
		
		if(!in_array($property, array_keys($structure)))
			throw new ModelException(sprintf("'%s' not found in %s structure", $key, $this->_model));
		
		if(in_array("today", $structure[$property])):
			$datetime = new DateTime('NOW', new DateTimeZone(Cmless::$config['datetime']['save_as']));
			$this->$property = $datetime->format('Y-m-d');
		
		elseif(in_array("now", $structure[$property])):
			$datetime = new DateTime('NOW', new DateTimeZone(Cmless::$config['datetime']['save_as']));
			$this->$property = $datetime->format('Y-m-d H:i:s');
		
		elseif(array_key_exists("default", $structure[$property])):
			$this->$property = $structure[$property]["default"];
		
		else:
			throw new ModelException(sprintf("'%s' property in %s does not define a default value", $key, $this->_model));
		
		endif;
	}
	
	/**
	 * Shortcut to save the model with Query object
	 */
	public final function save()
	{
		self::objects($this->_backendKey, $this->_model)->save($this);
	}
	
	/**
	 * Before/After saving functions 
	 * that can be overwritten
	 */
	public function beforeSaving(){ }
	public function afterSaving(){ }
	
	/**
	 * Shortcut to delete the model with Query object
	 */
	public final function delete()
	{
		self::objects($this->_backendKey, $this->_model)->deleteModel($this);
	}
	
	/**
	 * Before deleting function
	 * that can be overwritten
	 */
	public function beforeDeleting(){ }
	
	/**
	 * Validate all fields
	 */
	public final function validate()
	{
		$validator = Validator::validate($this);
		unset($validator);
	}
	
	/**
	 * Setter of is_new
	 */
	public final function set_is_new($bool)
	{
		$this->_is_new = $bool;
	}
	
	/**
	 * Getter of is_new
	 */
	public final function is_new()
	{
		return $this->_is_new;
	}

}

?>