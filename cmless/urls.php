<?php

class UrlsException extends Exception { }

class Urls{
	private $urls;
	
	/**
	 * Constructor
	 * @param string $path
	 */
	function Urls($path=null)
	{
		$this->urls = array();
		
		// Load the first main URLs
		if(!is_null($path))
			$this->load_urls($path);
	}
	
	/**
	 * Get URL without parents
	 */
	public function route()
	{
		$this->get_url();
	}
	
	/**
	 * Get the base relative URL from the config
	 * @return string
	 */
	public function getBaseUrl()
	{
		$baseUrl = Cmless::$config['app']['base_uri'];
		if(strlen($baseUrl)==0) $baseUrl = "/";
		return $baseUrl;
	}
	
	/**
	 * Get the current path
	 */
	public function getCurrentPath()
	{
		$path = $_SERVER['REQUEST_URI'];
		
		if(strrpos($path, Cmless::$config['app']['base_uri'], -strlen($path)) === 0):
			$path = substr($path, strlen(Cmless::$config['app']['base_uri'])-1);
			if(strlen($path)==0) $path = "/";
		endif;
		
		return $path;
	}
	
	/**
	 * Load URLs
	 * @param string $path
	 * @param array $parent
	 * @throws UrlsException
	 */
	private function load_urls($path, $parent=null)
	{
		$imported = Cmless::getInstance()->import($path, 'urls');

		if(!is_array($imported['urls']))
			throw new UrlsException(sprintf("Imported \$urls from '%s' is not an array", $path));
		
		if(count($imported['urls'])==0)
			throw new UrlsException(sprintf("Imported \$urls from '%s' is empty", $path));
			
		foreach($imported['urls'] as $url_params):
			if(!is_array($url_params))
				throw new UrlsException(sprintf("Imported url from '%s' is not an array", $path));
			
			$count = count($url_params);
			if($count<2||$count>3)
				throw new UrlsException(sprintf("Imported url from '%s' must have 2 or 3 parameters, %d found", $path, $count));

			if($count==3):
				$url = array(
					'parent'=>$parent,
					'url'=>$url_params[0],
					'path'=>$url_params[1],
					'function'=>$url_params[2],
					'params'=>array()
				);
				
				if(!is_null($parent) && array_key_exists('include', $parent)):
					$include_path_tmp = explode(".", $parent['include']);
					unset($include_path_tmp[count($include_path_tmp)-1]);
					$url['path'] = implode(".", $include_path_tmp).".".$url['path'];
				
				endif;
			else:
				$lastchar = substr($url_params[0], strlen($url_params[0])-1, 1);
				
				if($lastchar=='*'):
					$url = array(
						'parent'=>$parent,
						'url'=>substr($url_params[0], 0, strlen($url_params[0])-1),
						'include'=>$url_params[1],
						'params'=>array(),
					);
					
				else:
					$file_path_table = explode('.', $url_params[1]);
					
					if(count($file_path_table)<2)
						throw new UrlsException(sprintf("Imported url '%s' does not have a function in the second parameter '%s'", $url_params[0], $url_params[1]));
					
					$function = $file_path_table[count($file_path_table)-1];
					unset($file_path_table[count($file_path_table)-1]);
					$path = implode('.', $file_path_table);
					
					$url = array(
						'parent'=>$parent,
						'url'=>$url_params[0],
						'path'=>$path,
						'function'=>$function,
						'params'=>array()
					);
					
					if(!is_null($parent) && array_key_exists('include', $parent)):
						$include_path_tmp = explode(".", $parent['include']);
						unset($include_path_tmp[count($include_path_tmp)-1]);
						$url['path'] = implode(".", $include_path_tmp).".".$url['path'];
					
					endif;
				endif;
			endif;
			
			foreach(explode('/', $url['url']) as $index => $content):
				$pos=strpos($content, "%");
				while($pos !== false):
					$param = substr($content, $pos, 2);
					if(in_array($param, array('%s', '%d'))):
						if(!isset($url['params'][$index]))
							$url['params'][$index] = array();
						
						$url['params'][$index][] = array(
							'pos'=>$pos,
							'param'=>$param
						);
					endif;
					$pos=strpos($content, "%", $pos+2);
				endwhile;
			endforeach;
			$this->urls[] = $url;
				
		endforeach;
	}
	
	/**
	 * Get the relative URL from the function path
	 * and params supplied
	 * @param string $functionPath
	 * @param array $params
	 * @param array $parent
	 */
	public function reverse($functionPath, $params=array(), $parent=null)
	{
		$functionPathParams = explode('.', $functionPath);
		$urlFound = null;

		foreach($this->urls as $url):
			if($parent==$url['parent']):
				if(array_key_exists('path', $url)):
					$urlFuncPath = $url['path'].".".$url['function'];
			
					if($functionPath == $urlFuncPath):
						$urlFound = $url;
						break;
					endif;
			
				elseif(array_key_exists('include', $url)):
					if(strpos($url['include'], $functionPath) == 0):
						$this->load_urls($url['include'], $url);
						try {
							return $this->reverse($functionPath, $params, $url);
						}
						catch(UrlsException $e){ 
							// No Url found in Include path
						 }
					endif;
				endif;
			endif;
		endforeach;
		
		if(is_null($urlFound)):
			throw new UrlsException(sprintf("Could not find the url for function path : %s", $functionPath));
		
		else:
			$builtUrl = array();
			$currentParrent = $parent;
			
			// Take the url part of the parents
			while(!is_null($currentParrent)):
				$builtUrl[] = $currentParrent['url'];
				$currentParrent = $currentParrent['parent'];
			
			endwhile;
			
			// Reverse the array and implode as a string
			rsort($builtUrl);
			$builtUrl = implode("/", $builtUrl);
			
			// Add curent URL
			$builtUrl .= "/".$urlFound['url'];
			
			// Add URI params if needed
			if(count($params)>0):
				$builtUrl = call_user_func_array("sprintf", array_merge(array($builtUrl), $params));
			endif;
			
			// Add base_url, as this function will be 
			// used most of the time in the templates
			$builtUrl = $this->getBaseUrl().$builtUrl;
			
			// Remove double / (do it twice to be sure!)
			$builtUrl = preg_replace("/\/\//", "/", $builtUrl);
			$builtUrl = preg_replace("/\/\//", "/", $builtUrl);
			
			return $builtUrl;
		endif;
	}
	
	/**
	 * Check in loaded URLs if any matches
	 * @param array $parent
	 */
	private function get_url($parent=null)
	{
		$URI = $this->getCurrentPath();
		$URL = null;
		$url_params = array();
		
		foreach($this->urls as $url):
			if($url['parent']==$parent):
				// Add full url and parameters from parent to child
				if(!is_null($parent)):
					$url_firstchat = substr($url['url'], 0, 1);
					$parent_lastchar = substr($parent['url'], strlen($parent['url'])-1, 1);
					$parent_table = explode('/', $parent['url']);
					
					if($url_firstchat=="/" && $parent_lastchar=="/"):
						$url['url'] = substr($parent['url'], 0, strlen($parent['url'])-1).$url['url'];
						$param_index_increased_by = count($parent_table)-2;
						
					elseif($url_firstchat!="/" && $parent_lastchar!="/"):
						$url['url'] = $parent['url']."/".$url['url'];
						$param_index_increased_by = count($parent_table);
						
					else:
						$url['url'] = $parent['url'].$url['url'];
						$param_index_increased_by = count($parent_table)-1;
					endif;
					
					$temp = array();
					foreach($parent['params'] as $index => $params):
						$temp[$index] = $params;
					endforeach;
					
					foreach($url['params'] as $index => $params):
						$temp[$index+$param_index_increased_by] = $params;
					endforeach;
					
					$url['params'] = $temp;
				endif;
				
				$url_lastchar = substr($url['url'], strlen($url['url'])-2, 1);
				$uri_lastchar = substr($URI, strlen($URI)-2, 1);
				
				if(count($url['params'])==0):
					if($url['url']==$URI || ($url_lastchar=='/' && $url['url']==$URI.'/')):
						$URL = $url;
						break;
					endif;
					
					if(isset($url['include'])):
						if($url['url']!=""):
							$pos = strpos($URI, $url['url']);
							
							if($pos===0):
								$URL = $url;
								break;
								
							endif;
							
						else:
							$URL = $url;
							break;
							
						endif;
					endif;
				else:
					$request_table = explode('/', $URI);
					$url_table = explode('/', $url['url']);
					
					if(isset($url['include']) && count($request_table)>=count($url_table)):
						// If include with parameters... cut the $request_table where $url_table ends
						$new_request_table = array();
						foreach ($url_table as $index => $content):
							if($content!="" || $index+1 < count($url_table)):
								$new_request_table[] = $request_table[$index];
								
							else:
								$new_request_table[] = '';
								break;
								
							endif;
						endforeach;

						$request_table = $new_request_table;
						
					elseif(count($request_table)<count($url_table) && $uri_lastchar != "/"):
						// if $request_table is -1 from $url_table and no slash, append one before testing
						$request_table = explode('/', $URI."/");
						$uri_lastchar = "/";
					
					endif;
					
					// validate each parameters and if they are all !null then $url is passed
					if(count($request_table)==count($url_table)):
						$valid = 0;
						$url_params = array();
						
						foreach($url_table as $index => $content):
							if(!isset($url['params'][$index])):
								if($content==$request_table[$index]):
									$valid++;
									
								endif;
							else:
								$parsed_params = sscanf($request_table[$index], $content);
								$invalid = false;
								
								if(!is_array($parsed_params))
									$parsed_params = array($parsed_params);

								foreach($parsed_params as $param):
									if(is_null($param)):
										$invalid = true;
										break;
										
									else:
										$url_params[] = $param;
										
									endif;
								endforeach;
								
								if(!$invalid):
									$valid++;
								else:
									break;
								endif;
							endif;
						endforeach;
						
						if($valid==count($url_table)):
							$URL = $url;
							break;
							
						endif;
					endif;
				endif;
			endif;
		endforeach;
		
		if(!is_null($URL))
			$this->load_url($URL, $url_params);
		else
			Cmless::getInstance()->Http404();
	}
	
	/**
	 * Load an URL
	 * @param array $url
	 * @param array $url_params
	 * @throws UrlsException
	 */
	private function load_url(array $url, array $url_params=array())
	{
		if(isset($url['function'])):
			$function_parts = explode('.', $url['function']);
			
			if(count($function_parts)==1):
				$imported = Cmless::getInstance()->import($url['path'], $url['function']);
				
				if(!is_callable($imported[$url['function']]))
					throw new UrlsException(sprintf("Cannot load url (%s) because '%s' is not callable", $url['path'], $url['function']));
				
				$response = call_user_func_array($imported[$url['function']], $url_params);
				
			elseif(count($function_parts)==2):
			
				$imported = Cmless::getInstance()->import($url['path'], $function_parts[0]);
				
				if(!is_subclass_of($function_parts[0], "Controller"))
					throw new UrlsException(sprintf("Cannot load url (%s) because '%s' is not a Controller class", $url['path'], $function_parts[0]));
				
				$controller = new $function_parts[0]($_SERVER['REQUEST_URI'], $url['path'], $url['function']);
				
				if(!method_exists($controller, $function_parts[1]))
					throw new UrlsException(sprintf("Cannot load url (%s) because '%s' is not a method of %s", $url['path'], $function_parts[1], $function_parts[0]));
			
				$response = call_user_func_array(array($controller, $function_parts[1]), $url_params);
			
			else:
				throw new UrlsException(sprintf("Cannot load url (%s) because '%s' is not a valid function or method", $url['path'], $url['function']));
				
			endif;
			
			if(!$response)
				throw new UrlsException(sprintf("Function '%s' in url (%s) returned false", $url['function'], $url['path']));
			
			print $response;
		
		elseif(isset($url['include'])):
			$this->load_urls($url['include'], $url);
			$this->get_url($url);
			
		endif;
	}
	
}

?>