<?php 

abstract class Module{
	private $_path;
	private $_models = array();
	private $_templatetags = array();
	
	protected $modelsFile = "models.php";
	protected $templatetagsFile = "templatetags.php";
	
	/**
	 * Set path and load models
	 * if there is...
	 * @param string $path
	 */
	public final function __construct($path)
	{
		$this->_path = $path;
		
		if(file_exists($this->_path.$this->modelsFile)):
			$this->_models = Cmless::getInstance()->importSubclasses($this->_path.$this->modelsFile, array('Model'));
		
		endif;
		
		if(file_exists($this->_path.$this->templatetagsFile)):
			$this->_templatetags = Cmless::getInstance()->importSubclasses($this->_path.$this->templatetagsFile, array('TemplateTag', 'TemplateFilter'));
		
		endif;
	}
	
	/**
	 * Template aliases getter (can be overwritten)
	 * @return array: alias=>path
	 */
	public function getTemplatesAliases()
	{
		return array();
	}
	
	/**
	 * Module path getter
	 * @return string _path
	 */
	public final function getPath()
	{
		return $this->_path;
	}
	
	/**
	 * Module models getter
	 * @return array of string
	 */
	public final function getModels()
	{
		return $this->_models;
	}
	
	/**
	 * Module templatetags getter
	 * @return array of string
	 */
	public final function getTemplatetags()
	{
		return $this->_templatetags;
	}
	
}

?>