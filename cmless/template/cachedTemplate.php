<?php

class CachedTemplateModel extends Model{
	public $file;
	public $template_id;
	public $datetime;
	public $html;
	public $vars;
	public $params;
	public $url;
	
	public static function objects($backendKey="default", $modelClassName=__CLASS__)
	{
		return parent::objects($backendKey, $modelClassName);
	}
	
	public function table()
	{
		return "cmless_template_cache";
	}
	
	public function structure()
	{
		return array(
			'file'=>array('type'=>'text'),
			'template_id'=>array('type'=>'char', 'length'=>255),
			'datetime'=>array('type'=>'datetime', 'now', 'required'),
			'lifetime'=>array('type'=>'int', 'required'),
			'html'=>array('type'=>'text', 'required'),
			'vars'=>array('type'=>'char', 'length'=>32),
			'params'=>array('type'=>'char', 'length'=>32),
			'url'=>array('type'=>'text')
		);
	}
	
	/**
	 * Needs to set identity manually as it has no PK
	 */
	public function getIdentity()
	{
		$identity = array();
		if($this->file !== null) $identity['file'] = $this->file;
		if($this->template_id !== null) $identity['template_id'] = $this->template_id;
		if($this->vars !== null && $this->params === null) $identity['vars'] = $this->vars;
		elseif($this->params !== null) $identity['params'] = $this->params;
		return $identity;
	}
	
}

class CachedTemplate{
	private $template;
	private $backendKey;
	private $lifetime;
	
	/**
	 * Constructor
	 * @param array $context
	 * @param string $backendKey
	 * @param number $lifetime
	 */
	public function CachedTemplate(array $context=array(), $backendKey="default", $lifetime=120)
	{
		$this->template = new Template($context);
		$this->backendKey = $backendKey;
		$this->lifetime = $lifetime;
	}
	
	/**
	 * Alias of load for the template
	 * Its useful as it also saves the filename
	 * within $this->template for later use 
	 * @param string $id
	 * @param string $filename
	 */
	public function load_file($id, $filename)
	{
		$this->template->load_file($id, $filename);
	}
	
	/**
	 * Alias of load html for the template
	 * useful too for render function
	 * @param string $id
	 * @param string $html
	 */
	public function load_html($id, $html)
	{
		$this->template->load_html($id, $html);
	}
	
	/**
	 * Check in the cache for a template
	 * @param string $id
	 * @param string $file
	 * @param array $vars
	 * @param array $params
	 * @param string $url
	 * @return NULL|string
	 */
	public function check_cache($id=null, $file=null, array $vars=array(), array $params=array(), $url=null)
	{
		if(Cmless::$config['app']['cache_enabled']):
			$cachedTemplateModel = null;
			$conditions = array();
			
			if($file !== null) $conditions['file']=$file;
			if($id !== null) $conditions['template_id']=$id;
			if($url !== null) $conditions['url']=$url;
			
			if(count($params)>0) $conditions['params']=md5(print_r($params, true));
			elseif(count($vars)>0) $conditions['vars']=md5(print_r($vars, true));
			
			$cachedTemplateQuerySet = CachedTemplateModel::objects($this->backendKey)->filter($conditions);
			
			foreach($cachedTemplateQuerySet as $cachedTemplate):
				$now = new DateTime('NOW', new DateTimeZone(Cmless::$config['datetime']['save_as']));
				$cachedTemplateDateTime = new DateTime($cachedTemplate->datetime, new DateTimeZone(Cmless::$config['datetime']['save_as']));
					
				if($now->format("U")-$cachedTemplateDateTime->format("U") <= $cachedTemplate->lifetime) $cachedTemplateModel = $cachedTemplate;
				else $cachedTemplate->delete();
			endforeach;
			
			if($cachedTemplateModel !== null) return $cachedTemplateModel->html;
			else return null;
			
		else:
			return null;
		
		endif;
	}
	
	/**
	 * Saves the template into the cache
	 * @param string $id
	 * @param string|null $file
	 * @param array $vars
	 * @param array $params
	 * @param string|null $url
	 * @param string $html
	 */
	private function save_cache($id, $file, $vars, $params, $url, $html)
	{
		CachedTemplateModel::objects($this->backendKey)->create(array(
			'file'=>$file,
			'template_id'=>$id,
			'html'=>$html,
			'lifetime'=>$this->lifetime,
			'vars'=>(count($vars)>0)? md5(print_r($vars, true)) : null,
			'params'=>(count($params)>0)? md5(print_r($params, true)) : null,
			'url'=>$url,
		));
	}
	
	/**
	 * Get the cache or render
	 * @param string $id
	 * @param string $file
	 * @param array $vars
	 * @param array $params
	 * @param string|null $url
	 * @return string
	 */
	public function render_file($id, $file, array $vars=array(), array $params=array(), $url=null)
	{
		if(Cmless::$config['app']['cache_enabled']):
			$file = $this->template->add_path_aliases($file);
			$result = $this->check_cache($id, $file, $vars, $params, $url);
			if($result !== null) return $result;
			
			$rendered = $this->template->render_file($id, $file, $vars);
			$this->save_cache($id, (array_key_exists($id, $this->template->filenames))? $this->template->filenames[$id] : $file, $vars, $params, $url, $rendered);
			
			return $rendered;
			
		else:
			return $this->template->render_file($id, $file, $vars);
		
		endif;
	}
	
	/**
	 * Get the cache or render
	 * @param string $id
	 * @param array $vars
	 * @param array $params
	 * @param string|null $url
	 * @return string
	 */
	public function render($id, array $vars=array(), array $params=array(), $url=null)
	{
		if(Cmless::$config['app']['cache_enabled']):
			if(array_key_exists($id, $this->template->filenames)) $result = $this->check_cache($id, $this->template->filenames[$id], $vars, $params, $url);
			else $result = $this->check_cache($id, null, $vars, $params, $url);
			if($result !== null) return $result;
		
			$rendered = $this->template->render($id, $vars);
			$this->save_cache($id, (array_key_exists($id, $this->template->filenames))? $this->template->filenames[$id] : null, $vars, $params, $url, $rendered);
				
			return $rendered;
			
		else:
			return $this->template->render($id, $vars);
		
		endif;
	}
	
}

?>