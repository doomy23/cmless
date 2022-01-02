<?php

class ImportException extends Exception { }
class FrameworkException extends Exception { }
class ConfigException extends Exception { }

final class Cmless{
	public static $config = null;
	
	private static $_instance;
	
	private $_modules = array();
	private $_app_paths = array();
	private $_templateTags = array();
	private $_templateFilters = array();
	private $_backends = array();
	
	/**
	 * Private constructor
	 */
	private function Cmless(){}
	
	/**
	 * Instance creator/getter
	 */
	public static function getInstance()
	{
		if(is_null(self::$_instance))
			self::$_instance = new Cmless();
		
		return self::$_instance;
	}
	
	/**
	 * Set the config during init
	 * @param array $CONFIG
	 */
	public function setConfig(array $CONFIG)
	{
		self::$config = $CONFIG;
	}
	
	/**
	 * Loads modules and route URL
	 */
	public function start()
	{
		self::sec_session_start();
		self::sec_headers();
		
		$this->_app_paths['cmless'] = self::$config['cmless_path'];
		$this->load_modules();
		
		if(!is_null(self::$config['app']['urls']))
			self::Urls()->route();
	}
	
	/**
	 * Do a safe session start using secure cookies if possible
	 */
	public static function sec_session_start()
	{
		session_name(self::$config['session']["name"]);
		session_start();
		if (!isset($_SESSION['started'])):
			session_destroy();
			if (ini_set('session.use_only_cookies', 1) !== FALSE):
				$cookieParams = session_get_cookie_params();
				session_set_cookie_params((self::$config['session']["lifetime"] != 0)? 
					self::$config['session']["lifetime"] : $cookieParams["lifetime"] ,
					$cookieParams["path"], $cookieParams["domain"], Utilities::is_secure(), self::$config['session']["httponly"]);
				session_start();
				session_regenerate_id(true);
			else:
				session_start();
			endif;
			$_SESSION['started'] = time();
		endif;
	}

	/**
	 * Set the security headers
	 */
	public static function sec_headers()
	{
		header("X-XSS-Protection: 1; mode=block");
		header("Access-Control-Allow-Origin: ".Utilities::domain_uri());
		header("X-Frame-Options: deny");
		header("X-Content-Type-Options: nosniff");
		if(Utilities::is_secure())
			header("Strict-Transport-Security: max-age=3600; includeSubDomains");
		header("Cache-Control no-cache");
		header("Expires: 0");
	}
	
	/**
	 * Load/Instanciate modules
	 */
	private function load_modules()
	{
		// Loading default templatetags and filters
		$this->_templateFilters[] = DatetimeFormatFilter::class;
		$this->_templateFilters[] = ResizeImageFilter::class;

		// Loading custom templatetags, filters, models, etc.
		foreach(self::$config['modules'] as $class => $path):
			$path = Utilities::parse_path($path, $this->_app_paths, false);
			$imported = $this->import($path."module.php", $class, true);
			
			if(!is_subclass_of($class, "Module"))
				throw new ConfigException(sprintf("%s is not a Module", $class));
			
			$this->_modules[$class] = new $class($path);
			$this->_app_paths[$class] = $path;
			
			if(count($this->_modules[$class]->getTemplatesAliases())>0)
				self::$config['templates_aliases'] = array_merge(self::$config['templates_aliases'], $this->_modules[$class]->getTemplatesAliases());
			
			if(count($this->_modules[$class]->getTemplatetags())>0):
				foreach($this->_modules[$class]->getTemplatetags() as $tagOrFilterClass):
					if(is_subclass_of($tagOrFilterClass, 'TemplateTag'))
						$this->_templateTags[] = $tagOrFilterClass;
					
					if(is_subclass_of($tagOrFilterClass, 'TemplateFilter'))
						$this->_templateFilters[] = $tagOrFilterClass;
			
				endforeach;
			endif;
				
		endforeach;
	}
	
	/**
	 * Import vars, functions or class
	 * be careful with classes though, 
	 * dont import them twice
	 * @param string $path
	 * @param array|string $get
	 * @throws ImportException
	 * @return array
	 */
	public function import($path, $get=array(), $parsed=false)
	{
		if(!$parsed)
			$file = Utilities::parse_path($path, $this->_app_paths);
		else
			$file = $path;
		
		if(file_exists($file))
			require $file;
		else
			throw new ImportException(sprintf("Path cannot be imported, file '%s' does not exist", $file));
		
		if(is_string($get))
			$get = explode(',', $get);
		
		$imported = array();
		
		foreach($get as $name):
			if(isset($$name)):
				$imported[$name] = &$$name;
				
			elseif(function_exists($name)):
				$imported[$name] = create_function('', "return call_user_func_array('{$name}', func_get_args());");
			
			elseif(class_exists($name)):
				$imported[$name] = true;
				
			else:
				throw new ImportException(sprintf("Imported path '%s' does not contain %s", $path, $name));
			
			endif;
		endforeach;
		
		return $imported;
	}
	
	/**
	 * Load file and check the classes in
	 * @param string $file
	 * @param array $motherclasses
	 * @param string $throwErrorOnWrongSubclass
	 * @throws ImportException
	 * @return array
	 */
	public function importSubclasses($file, array $motherclasses, $throwErrorOnWrongSubclass=true)
	{
		if(file_exists($file))
			require_once $file;
		else
			throw new ImportException(sprintf("Path cannot be imported, file '%s' does not exist", $file));
		
		$tokens = token_get_all(file_get_contents($file));
		$subClasses = array();
		$class_found = false;
			
		foreach($tokens as $token):
			if(is_array($token)):
				if($token[0] == T_CLASS):
					$class_found = true;
					
				elseif($class_found && $token[0] == T_STRING):
					$class_found = false;
					$subclass_of = false;
					
					foreach($motherclasses as $motherclass):
						if(is_subclass_of($token[1], $motherclass)):
							$subClasses[] = $token[1];
							$subclass_of = true;
							break;
					
						endif;
					endforeach;
					
					if($throwErrorOnWrongSubclass && !$subclass_of)
						throw new ImportException(sprintf("Imported class '%s' must be a subclass of : %s", $token[1], implode(', ', $motherclasses)));
					
				endif;
			endif;
		endforeach;
		
		return $subClasses;
	}
	
	/**
	 * Make an error header
	 * @param integer $error
	 */
	public function makeErrorHeader($error=500)
	{
		switch($error)
		{
			case 403:
				header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
				break;
				
			case 404:
				header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
				break;

			case 500:
				header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
				break;
				
			default:
				header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
		}
	}
	
	/**
	 * Call a controller for errors
	 * @param string $controllerPath
	 * @param integer $error
	 * @param array $details
	 * @throws FrameworkException
	 * @throws ImportException
	 */
	public function callErrorController($controllerPath, $error, array $details)
	{
		// Try 1 : laod a simple function in a file
		$controllerPathParts = explode('.', $controllerPath);
		$function = $controllerPathParts[count($controllerPathParts)-1];
		unset($controllerPathParts[count($controllerPathParts)-1]);
		
		try
		{
			$imported = $this->import(implode('.', $controllerPathParts), $function);

			if(!is_callable($imported[$function]))
				throw new FrameworkException(sprintf("Cannot call error controller (%s) because '%s' is not callable", $controllerPath, $function));
		
			return call_user_func_array($imported[$function], array($error, self::$config['error_templates'][$error], $details));
		}
		catch(ImportException $e)
		{
			if(count($controllerPathParts)<2)
				throw $e;
			
			// Try 2 : Load a function in a class if theres at least 3 parts in the path
			$class = $controllerPathParts[count($controllerPathParts)-1];
			unset($controllerPathParts[count($controllerPathParts)-1]);
			
			$controller = $this->import(implode('.', $controllerPathParts), $class);
			
			if(!is_subclass_of($class, "Controller"))
				throw new FrameworkException(sprintf("Cannot call error controller (%s) because '%s' is not a Controller class", $controllerPath, $class));
			
			$controller = new $class();
			
			if(!method_exists($controller, $function))
				throw new FrameworkException(sprintf("Cannot call error controller (%s) because '%s' is not a method of %s", $controllerPath, $function, $class));
			
			return call_user_func_array(array($controller, $function), array($error, self::$config['error_templates'][$error], $details));
		}
	}
	
	/**
	 * Make header and render 404 page
	 * or call the error controller
	 */
	public function Http404()
	{
		if(is_null(self::$config['error_controller'])):
			$this->makeErrorHeader(404);
			
			$tpl = self::Template();
			print $tpl->render_file(404, self::$config['error_templates'][404], array(
				'absolute_request_uri'=>Utilities::absolute_request_uri(),
			));
			
		else:
			$result = $this->callErrorController(self::$config['error_controller'], 404, array(
				'absolute_request_uri'=>Utilities::absolute_request_uri(),
			));
			
			if($result)
				print $result;
		
		endif;
			
		exit();
	}
	
	/**
	 * Make header and render 403 page
	 * or call the error controller
	 */
	public function Http403()
	{
		if(is_null(self::$config['error_controller'])):
			$this->makeErrorHeader(403);
			
			header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
			
			$tpl = self::Template();
			print $tpl->render_file(403, self::$config['error_templates'][403], array(
				'absolute_request_uri'=>Utilities::absolute_request_uri(),
			));
			
		else:
			$result = $this->callErrorController(self::$config['error_controller'], 403, array(
				'absolute_request_uri'=>Utilities::absolute_request_uri(),
			));
				
			if($result)
				print $result;
		
		endif;
		
		exit();
	}

	/**
	 * Make redirection header to the reversed URL
	 * Does not work for external redirections.
	 */
	public function Redirect($functionPath, $params=array(), $status=302)
	{
		$redirection = self::Urls()->reverse($functionPath, $params);
		header("Location: ".$redirection, true, $status);
		return true;
	}
	
	/**
	 * Public static classes constructor
	 * @return Template
	 */
	public static function Template()
	{
		return new Template(array(
			'templates_aliases'=>self::$config['templates_aliases'],
			'tags'=>self::getInstance()->_templateTags,
			'filters'=>self::getInstance()->_templateFilters,
			'vars'=>array(
				'BASE_URL'=>self::$config['app']['base_uri'],
				'MEDIA_URL'=>self::$config['media_url'],
				'STATIC_URL'=>self::$config['static_url'],
				'CURRENT_URL'=>self::Urls()->getCurrentPath(),
				'HTTP_HOST'=>$_SERVER['HTTP_HOST'],
				'HTTPS'=>Utilities::is_secure(),
				'AUTH'=>self::Auth(),
			),
		));
	}
	
	/**
	 * Public static classes constructor
	 * @return CachedTemplate
	 */
	public static function CachedTemplate()
	{
		$db_key = (func_num_args() >= 1 && !is_int(func_get_arg(0)))? func_get_arg(0) : self::$config['app']['default_cache_db_key'];
		$lifetime = (func_num_args() >= 2 || (func_num_args() >= 1 && is_int(func_get_arg(0))))? 
			((func_num_args() >= 2)? func_get_arg(1) : func_get_arg(0)) : 
			self::$config['app']['default_cache_lifetime'];
		
		return new CachedTemplate(array(
			'templates_aliases'=>self::$config['templates_aliases'],
			'tags'=>self::getInstance()->_templateTags,
			'filters'=>self::getInstance()->_templateFilters,
			'vars'=>array(
				'BASE_URL'=>self::$config['app']['base_uri'],
				'MEDIA_URL'=>self::$config['media_url'],
				'STATIC_URL'=>self::$config['static_url'],
				'CURRENT_URL'=>self::Urls()->getCurrentPath(),
				'HTTP_HOST'=>$_SERVER['HTTP_HOST'],
				'HTTPS'=>Utilities::is_secure(),
				'AUTH'=>self::Auth(),
			)), $db_key, $lifetime);
	}
	
	/**
	 * Public static classes constructor
	 * @param string $db_key
	 * @throws ConfigException
	 * @return Backend
	 */
	public static function Backend()
	{
		$db_key = (func_num_args() >= 1)? func_get_arg(0) : self::$config['app']['default_db_key'];
		
		if(!array_key_exists("db", self::$config)) 
			throw new ConfigException("Cannot init Backend class, missing 'db' in config");
		
		if(!array_key_exists($db_key, self::getInstance()->_backends)) 
			self::getInstance()->_backends[$db_key] = new Backend($db_key);
		
		return self::getInstance()->_backends[$db_key];
	}
	
	/**
	 * Public static classes constructor
	 * @return Urls
	 */
	public static function Urls()
	{
		return new Urls(self::$config['app']['urls']);
	}

	/**
	 * Public static classes constructor
	 * @return Auth
	 */
	public static function Auth()
	{
		return new Auth();
	}
}

?>