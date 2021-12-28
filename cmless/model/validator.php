<?php

class ModelValidationException extends Exception { 

	public $field;

    public function __construct($message, $code = 0, Throwable $previous = null, $field = null) {
        
		$this->field = $field;
        parent::__construct($message, $code, $previous);
    }

}

class Validator{
	// Class constants
	public static $TYPES = array("pk", "fk", "int", "bool", "float", "decimal", 
			"char", "text", "image", "date", "datetime");
	
	public static $ALLOWED_PARAMS = array(
			"pk"=>array("auto"),
			"fk"=>array("required", "model"),
			"int"=>array("required", "default"),
			"bool"=>array("required", "default"),
			"float"=>array("required", "default"),
			"decimal"=>array("required", "default"),
			"char"=>array("required", "length", "default"),
			"text"=>array("required", "length", "default"),
			"image"=>array("required", "upload_to", "default"),
			"date"=>array("required", "today", "default"),
			"datetime"=>array("required", "now", "default"),
	);
	
	public static $MANDATORY_PARAMS = array(
			"fk"=>array("model"),
	);
	
	// Model to validate
	private $model;
	private $has_pk = false;
	
	/**
	 * Public constructor
	 * @param Model $model
	 * @throws ModelValidationException
	 */
	public function __construct(Model $model)
	{
		$this->model = $model;
		
		foreach($this->model->structure() as $property=>$params):
			if(!property_exists($this->model, $property))
				throw new ModelValidationException(sprintf("'%s' field not found in %s properties", $property, $this->model));
				
			self::validate_property($property, $params);
			
		endforeach;
	}
	
	/**
	 * Static helper
	 * @param Model $model
	 * @return Validator
	 */
	public static function validate(Model $model)
	{
		return new self($model);
	}
	
	/**
	 * Validate a property with its parameters
	 * @param string $property
	 * @param array $params
	 * @throws ModelValidationException
	 */
	private function validate_property($property, $params)
	{
		if(!array_key_exists("type", $params))
			throw new ModelValidationException(sprintf("'%s' (%s) field in %s needs a 'type' parameter", $property, $params["type"], $this->model));
		
		if(!in_array($params["type"], self::$TYPES))
			throw new ModelValidationException(sprintf("'%s' (%s) field in %s has an unknown type", $property, $params["type"], $this->model));
		
		// Value check with type
		// TODO : add logic...
		
		// Unique Pk check
		if($params["type"]=="pk" && $this->has_pk):
			throw new ModelValidationException(sprintf("%s has more then one primary key in its structure", $this->model));
		
		elseif($params["type"]=="pk"):
			$this->has_pk = true;
			$this->check_pk($property, $params);
		
		endif;
		
		// Check for mandatory params (with values)
		if(array_key_exists($params["type"], self::$MANDATORY_PARAMS)):
			foreach(self::$MANDATORY_PARAMS[$params["type"]] as $param):
				if(!array_key_exists($param, $params))
					throw new ModelValidationException(sprintf("'%s' (%s) field in %s needs to have a '%s' parameter", $property, $params["type"], $this->model, $param));
			
			endforeach;
		endif;
		
		// Check each params
		foreach($params as $param=>$param_value):
			if(is_int($param)):
				// param = value, exemple : "required"
				if(!in_array($param_value, self::$ALLOWED_PARAMS[$params["type"]]))
					throw new ModelValidationException(sprintf("'%s' (%s) field in %s cannot have parameter '%s' ", $property, $params["type"], $this->model, $param_value));
		
				switch($param_value)
				{
					case "required":
						$this->check_param_required($property, $params);
						break;
						
					case "today":
						$this->check_param_default($property, $params);
						break;
						
					case "now":
						$this->check_param_default($property, $params);
						break;
				}
			
			else:
				// param=>value
				if(!in_array($param, self::$ALLOWED_PARAMS[$params["type"]]) && $param != "type")
					throw new ModelValidationException(sprintf("'%s' (%s) field in %s cannot have parameter '%s' ", $property, $params["type"], $this->model, $param));
				
				switch($param)
				{
					case "model":
						$this->check_param_model($property, $params);
						break;
						
					case "default":
						$this->check_param_default($property, $params);
						break;
					
					case "length":
						$this->check_param_length($property, $params);
						break;
					
					case "upload_to":
						$this->check_param_upload_to($property, $params);
						break;
				}
			
			endif;
		endforeach;
	}
	
	/**
	 * Check pk
	 * @param string $property
	 * @param array $params
	 * @throws ModelValidationException
	 */
	private function check_pk($property, &$params)
	{
		if($this->model->is_new() && !in_array("auto", $params) && is_null($this->model->$property))
			throw new ModelValidationException(sprintf("'%s' (%s) field in %s cannot be null", $property, $params["type"], $this->model));
	}
	
	/**
	 * Check if model exists...
	 * Does not replace ID with the model
	 * Need to use a model function for this...
	 * @param string $property
	 * @param array $params
	 * @throws ModelValidationException
	 */
	private function check_param_model($property, &$params)
	{
		// TODO : logic...
	}
	
	/**
	 * Check required param
	 * @param string $property
	 * @param array $params
	 * @throws ModelValidationException
	 */
	private function check_param_required($property, &$params)
	{
		if(is_null($this->model->$property) && !(array_key_exists("default", $params) && $this->model->is_new()))
			throw new ModelValidationException(sprintf("'%s' (%s) field in %s cannot be null", $property, $params["type"], $this->model));
	}
	
	/**
	 * Check default param
	 * @param string $property
	 * @param array $params
	 * @throws ModelValidationException
	 */
	private function check_param_default($property, &$params)
	{	
		if(is_null($this->model->$property) && $this->model->is_new())
			$this->model->setDefault($property);
	}
	
	/**
	 * Check length param
	 * @param string $property
	 * @param array $params
	 * @throws ModelValidationException
	 */
	private function check_param_length($property, &$params)
	{
		if(!is_null($this->model->$property) && mb_strlen($this->model->$property) > $params['length'])
			throw new ModelValidationException(sprintf("'%s' (%s) field in %s exceed maximum length", $property, $params["type"], $this->model));
	}
	
	/**
	 * Check upload_to param
	 * @param string $property
	 * @param array $params
	 * @throws ModelValidationException
	 */
	private function check_param_upload_to($property, &$params)
	{
		if(empty($params['upload_to']))
			throw new ModelValidationException(sprintf("'%s' (%s) field in %s : 'upload_to' cannot be empty", $property, $params["type"], $this->model));
		
		if(!is_dir(Cmless::$config['media_dir']."/".$params['upload_to']))
			throw new ModelValidationException(sprintf("'%s' (%s) field in %s : '%s' directory does not exist", $property, $params["type"], $this->model, Cmless::$config['media_dir']."/".$params['upload_to']));
	}
	
}

?>