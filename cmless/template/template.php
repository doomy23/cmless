<?php

class TemplateException extends Exception { }

class Template{
	private $files = array();
	public $filenames = array();
	private $vars = array();
	private $context = array();
	private $blocks = array();
	private $extends = array();
	
	/**
	 * Constructor
	 * @param array $context
	 */
	function Template(array $context=array())
	{
		$this->context = $context;
		
		if(array_key_exists('tags', $this->context)):
			foreach($this->context['tags'] as $tagClass):
				if(!is_subclass_of($tagClass, 'TemplateTag'))
					throw new TemplateException(sprintf("Class '%s' is not a subclass of TemplateTag", $tagClass));
				
			endforeach;
		endif;
		
		if(array_key_exists('filters', $this->context)):
			foreach($this->context['filters'] as $tagClass):
				if(!is_subclass_of($tagClass, 'TemplateFilter'))
					throw new TemplateException(sprintf("Class '%s' is not a subclass of TemplateFilter", $tagClass));
			
			endforeach;
		endif;
	}
	
	/**
	 * Replace alias in path if
	 * 'templates_aliases' is in context
	 * @param string $filename
	 * @return string
	 */
	public function add_path_aliases($filename)
	{
		if(array_key_exists("templates_aliases", $this->context)):
			$tbl = explode("/", $filename);
			$alias = $tbl[0];
			
			if(count($tbl)>1 && array_key_exists($alias, $this->context['templates_aliases'])):
				$tbl[0] = $this->context['templates_aliases'][$alias];
				return implode("/", $tbl);
			
			endif;
		endif;
		
		return $filename;
	}
	
	/**
	 * Load a file content
	 * @param string $id
	 * @param string $filename
	 * @throws TemplateException
	 */
	public function load_file($id, $filename)
	{
		if(file_exists($filename)):
        	$this->files[$id] = file_get_contents($filename);
			$this->filenames[$id] = $filename;
		
		else:
			$filename = $this->add_path_aliases($filename);
			
			if(file_exists($filename)):
				$this->files[$id] = file_get_contents($filename);
				$this->filenames[$id] = $filename;
				
			else:
				throw new TemplateException(sprintf("Template file does not exists : '%s'", $filename));
			
			endif;
		endif;
	}
	
	/**
	 * Load a htmlk content
	 * @param string $id
	 * @param string $html
	 * @throws TemplateException
	 */
	public function load_html($id, $html)
	{
		$this->files[$id] = $html;
	}
	
	/**
	 * Parse and render a file
	 * @param string $id
	 * @param array $vars
	 */
	public function render($id, array $vars=array())
	{
		$this->vars[$id] = array();
		
		if(count($vars)>0)
			$this->vars[$id] = $vars;
		
		if(array_key_exists("vars", $this->context))
			$this->vars[$id] = array_merge($this->vars[$id], $this->context['vars']);
		
		$this->files[$id] = $this->functions_parse($id);
		$this->files[$id] = $this->parse($id);
		
		if(array_key_exists($id, $this->extends)):
			$extends_id = $id."_extends";
			$this->load_file($extends_id, $this->extends[$id]);
			
			if(array_key_exists($id, $this->blocks))
				$this->blocks[$extends_id] = $this->blocks[$id];
			
			return $this->render($extends_id, $vars);
			
		else:
			return $this->files[$id];
			
		endif;
	}
	
	/**
	 * Shortcut function to load and render
	 * @param string $id
	 * @param string $file
	 * @param array $vars
	 * @return string
	 */
	public function render_file($id, $file, array $vars=array())
	{
		$this->load_file($id, $file);
		return $this->render($id, $vars);
	}
	
	/**
	 * Return a string literal content
	 * @param string $quoted
	 * @return string|NULL
	 */
	private function get_string_literal($quoted)
	{
		foreach(array("\"", "'") as $quote):
			if(preg_match('~'.$quote.'(.*?)'.$quote.'~', $quoted, $string))
				return $string[1];
			
		endforeach;
			
		return null;
	}
	
	/**
	 * Builtin filters and customs parsing
	 * @param unknown $value
	 * @param string $filter
	 * @return string
	 */
	private function var_filter($value, $filter, array $params=array())
	{
		if(in_array($filter, array(
			'htmlentities', 'htmlspecialchars', 'nl2br', 
		   	'strtolower', 'strtoupper',
		   	'ucfirst', 'ucwords', 'trim',
		   	'count', 'sizeof', 'strlen',
		   	))):
		   	
		   	if(count($params)>0)
		   		throw new TemplateException(sprintf("The following filter does not support any parameters : '%s'", $filter));
		   	
		   	// Convert to string if it's an object
		   	if(is_object($value)):
		   		if(method_exists($value, "__toString"))
		   			$value = (string) $value;
		   		else 
		   			$value = get_class($value);
		   		
			endif;

			if(!is_string($value) && in_array($filter, array('htmlentities', 'htmlspecialchars', 'nl2br', 
				'strtolower', 'strtoupper', 'ucfirst', 'ucwords', 'trim', 'strlen')))
				$value = "";
			
			return eval("return ".$filter."(\$value);");
			
		elseif($filter=="length"):
			if(count($params)>0)
				throw new TemplateException(sprintf("The following filter does not support any parameters : '%s'", $filter));
		
			if(is_string($value)):
				return strlen($value);
			
			else:
				return count($value);
				
			endif;
			
		elseif($filter=="truncatewords"):
			if(count($params)==0)
				throw new TemplateException(sprintf("The '%s' filter expected at least one parameter : int", $filter));
			
			$chars = $params[0];
			$suffix = (count($params)>1)? $params[1] : "";
			
			return substr($value, 0, strrpos(substr($value, 0, $chars-strlen($suffix)), ' ')).$suffix;
			
		else:
			if(array_key_exists('filters', $this->context)):
				foreach($this->context['filters'] as $filterClass):
					if($filter==$filterClass::filter):
						$customFilter = new $filterClass();
						return $customFilter->filter($value, $params);
					
					endif;
				endforeach;
			endif;
			
			throw new TemplateException(sprintf("Unknown template filter : '%s'", $filter));
		
		endif;
		
		return $value;
	}
	
	/**
	 * Get value of a variable
	 * @param string $id
	 * @param string $selector
	 * @param array $array
	 * @throws TemplateException
	 * @return unknown
	 */
	private function var_select($id, $selector, array $array=array())
	{
		if(count($array)==0)
			$array = $this->vars[$id];
		
		$value = null;
		$filters = explode("|", $selector);
		
		if(count($filters)>1):
			$selector = trim($filters[0]);
		endif;
		
		$pos = strpos($selector, '.', 0);
		
		if($pos!==false):
			$array_selector = substr($selector, 0, $pos);
			
			if(array_key_exists($array_selector, $array)):
				if(is_array($array[$array_selector])):
					$array_selector_child = substr($selector, $pos+1);
					$value = $this->var_select($id, $array_selector_child, $array[$array_selector]);
				
				elseif(is_object($array[$array_selector])):
					$object = $array[$array_selector];
					$object_selector = substr($selector, $pos+1);
					
					$pos2 = strpos($object_selector, '.', 0);
					
					if($pos2!==false):
						// Complex object->multi prop/func/array
						
						while($pos2!==false):
							$object_sub_selector = substr($object_selector, 0, $pos2);
							$object_selector = substr($object_selector, $pos2+1);
							
							if(is_array($object)):
								if(array_key_exists($object_sub_selector, $object)):
									$value = $this->var_select($id, $object_selector, $object[$object_sub_selector]);
									break;
									
								endif;
							
							elseif(property_exists($object, $object_sub_selector)):
								$object = $object->$object_sub_selector;
							
							elseif(method_exists($object, $object_sub_selector)):
								$object = call_user_func(array($object, $object_sub_selector));
							
							endif;
							
							$pos2=strpos($object_selector, '.', 0);
	
						endwhile;
						
						if(is_null($value) && is_array($object)):
							// The last selector was an array...
							$value = $this->var_select($id, $object_selector, $object);
						
						elseif(is_null($value) && is_object($object)):
							if(property_exists($object, $object_selector)):
								$value = $object->$object_selector;
								
							elseif(method_exists($object, $object_selector)):
								$value = call_user_func(array($object, $object_selector));
							
							endif;
						endif;
						
					else:
						// Simple object->prop or func selector...
						
						if(property_exists($object, $object_selector)):
							$value = $object->$object_selector;
					
						elseif(method_exists($object, $object_selector)):
							$value = call_user_func(array($object, $object_selector));
						
						endif;
					endif;
				else:
					throw new TemplateException(sprintf("Bad selector, variable is not an array or an object : '%s'", $selector));
					
				endif;
			endif;
			
		else:
			if(array_key_exists($selector, $array)):
				$value = $array[$selector];
			
			endif;
		endif;
		
		// Add filters
		if(!is_null($value) && count($filters)>0):
			for($i=1;$i<count($filters);$i++):
				$filter_params = array();
		
				// Check for filter params
				$pos = strpos($filters[$i], ':', 0);
				
				if($pos!==false):
					$filter_params = explode(',', substr($filters[$i], $pos+1));
					$filters[$i] = substr($filters[$i], 0, $pos);
					
					foreach($filter_params as $j => $param):
						$filter_params[$j] = trim($param);
						$filter_params[$j] = $this->condition_value($id, $filter_params[$j], $array);
					
					endforeach;
				endif;
				
				$value = $this->var_filter($value, $filters[$i], $filter_params);
				
			endfor;
		endif;

		return $value;
	}
	
	/**
	 * Vars parsing
	 * @param string $id
	 * @param string $html
	 * @param array $array
	 * @return string
	 */
	private function parse($id, $html=null, array $array=array())
	{
		if(count($array)==0)
			$array = $this->vars[$id];
		
		if($html===null)
			$html = $this->files[$id];
		
		// Get variables
		$vars = array();
		preg_match_all('#\{{(.*?)\}}#s', $html, $matches);
		foreach($matches[1] as $key => $var):
			$value = $this->var_select($id, trim($var), $array);
			
			if(!is_null($value)):
				if(is_array($value)):
					$value = print_r($value, true);
				
			   	elseif(is_object($value)):
			   		if(method_exists($value, "__toString"))
			   			$value = (string) $value;
			   		else 
			   			$value = get_class($value);
			   		
				endif;
				
				$html = str_replace($matches[0][$key], $value, $html);
				
			else:
				$html = str_replace($matches[0][$key], "", $html);
				
			endif;
		endforeach;
		
		return $html;
	}
	
	/**
	 * Replace templatetags and
	 * add an offset to the next tags
	 * start and end
	 * @param string $html
	 * @param string $result
	 * @param integer $pos
	 * @param array $tags
	 */
	private function functions_parse_execute(&$html, &$result, &$pos, &$tags)
	{
		if(!is_null($tags[$pos]['end_tag']))
			$end = $tags[$pos]['end_tag']['end'];
		else
			$end = $tags[$pos]['end'];
		
		$length = $end - $tags[$pos]['start'];
		$diff = strlen($result) - $length;
		
		foreach($tags as $i => $tag):
			if($i>$pos):
				$tags[$i]['start'] += $diff;
				$tags[$i]['end'] += $diff;
				
				if(!is_null($tags[$i]['end_tag'])):
					$tags[$i]['end_tag']['start'] += $diff;
					$tags[$i]['end_tag']['end'] += $diff;
					
				endif;
			endif;
		endforeach;
		
		$html = substr_replace($html, $result, $tags[$pos]['start'], $end-$tags[$pos]['start']);
	}
	
	/**
	 * Parse the builtin and custom templatetags
	 * @param string $id
	 * @param string $html
	 * @param array $local_vars
	 * @return string
	 */
	private function functions_parse($id, $html=null, array $local_vars=array())
	{
		if($html===null)
			$html = $this->files[$id];
		
		if(count($local_vars)==0)
			$local_vars = $this->vars[$id];
		
		// Get tags
		$tags = array();
		$exclusions = array();
		
		$pos = strpos($html, '{% ', 0);
		while($pos!==false)
		{
			$end = strpos($html, '%}', $pos);
			
			if($end!==false):
				$content = trim(substr($html, $pos+2, $end-$pos-2));
				$tag_words = explode(' ', $content);
				
				$tags[] = array(
					'start'=>$pos,
					'end'=>$end+2,
					'content'=>$content,
					'words'=>$tag_words,
					'end_tag'=>null,
				);
				
				if($tag_words[0]=='block'):
					$exclusions[count($tags)-1] = array('end_tag'=>'endblock', 'tag'=>'block');
					
				elseif($tag_words[0]=='if'):
					$exclusions[count($tags)-1] = array('end_tag'=>'endif', 'tag'=>'if');
				
				elseif($tag_words[0]=='for'):
					$exclusions[count($tags)-1] = array('end_tag'=>'endfor', 'tag'=>'for');
				
				else:
					if(array_key_exists('tags', $this->context)):
						foreach($this->context['tags'] as $tagClass):
							if($tag_words[0]==$tagClass::tag && !is_null($tagClass::end_tag)):
								$exclusions[count($tags)-1] = array('end_tag'=>$tagClass::end_tag, 'tag'=>$tagClass::tag);
								break;
								
							endif;
						endforeach;
					endif;
				endif;
			endif;
			
			$pos = strpos($html, '{% ', $pos+2);
		}
		
		// Deleting tags within exclusion
		$count = count($tags);
		
		foreach($exclusions as $pos => $exclusion):
			$pass_end_tag = 0;
			if(array_key_exists($pos, $tags)):
				for($i=$pos+1;$i<$count;$i++):
					if(array_key_exists($i, $tags)):
						if($tags[$i]['content']==$exclusion['end_tag'] && $pass_end_tag==0):
							$tags[$pos]['end_tag'] = $tags[$i];
							unset($tags[$i]);
							break;
							
						elseif($tags[$i]['words'][0]==$exclusion['tag']):
							$pass_end_tag++;
							unset($tags[$i]);
		
						elseif($tags[$i]['content']==$exclusion['end_tag'] && $pass_end_tag>0):
							$pass_end_tag--;
							unset($tags[$i]);
		
						elseif($tags[$i]['words'][0]!=$exclusion['tag'] && $tags[$i]['content']!=$exclusion['end_tag']):
							unset($tags[$i]);
							
						endif;
					endif;
				endfor;
			endif;
		endforeach;
		
		foreach($tags as $i => $tag):
			switch($tag['words'][0]):
				case 'extends':
					$result = $this->extending($id, $html, $i, $tags, $local_vars);
					$this->functions_parse_execute($html, $result, $i, $tags);
					break;
					
				case 'block':
					$result = $this->block_parse($id, $html, $i, $tags, $local_vars);
					$this->functions_parse_execute($html, $result, $i, $tags);
					break;
			
				case 'if':
					$result = $this->condition_parse($id, $html, $i, $tags, $local_vars);
					$this->functions_parse_execute($html, $result, $i, $tags);
					break;
					
				case 'for':
					$result = $this->forloop_parse($id, $html, $i, $tags, $local_vars);
					$this->functions_parse_execute($html, $result, $i, $tags);
					break;
					
				case 'include':
					$result = $this->include_parse($id, $html, $i, $tags, $local_vars);
					$this->functions_parse_execute($html, $result, $i, $tags);
					break;
					
				default:
					$is_custom = false;
					
					if(array_key_exists('tags', $this->context)):
						foreach($this->context['tags'] as $tagClass):
							if($tag['words'][0]==$tagClass::tag):
								$is_custom = true;
							
								if(is_null($tagClass::end_tag)):
									$tag_html = null;
								
								else:
									if($tag['end_tag']===null)
										throw new TemplateException(sprintf("Expecting %s tag after %s tag : '%s'", $tagClass::end_tag, $tagClass::tag, $tag['content']));
									
									$tag_html = substr($html, $tags[$i]['end'], $tags[$i]['end_tag']['start']-$tags[$i]['end']);
									
									if($tagClass::pre_parse_html) 
									{
										$tag_html = $this->functions_parse($id, $tag_html, $local_vars);
										$tag_html = $this->parse($id, $tag_html, $local_vars);
									}
									
								endif;
								
								$tag_params = array();
								for($j=1;$j<count($tag['words']);$j++):
									$tag_params[] = $this->condition_value($id, $tag['words'][$j], $local_vars);
								
								endfor;
							
								$templateTag = new $tagClass($tags[$i]);
								$result = $templateTag->parse($tag_params, $tag_html, 
										(!is_null($tagClass::end_tag) && !$tagClass::pre_parse_html)? $local_vars : array());
								$this->functions_parse_execute($html, $result, $i, $tags);
								break;
							
							endif;
						endforeach;
					endif;
					
					if(!$is_custom)
						throw new TemplateException(sprintf("Unknown template tag '%s'", $tag['words'][0]));
			endswitch;
		endforeach;
		
		return $html;
	}
	
	/**
	 * Extends tags parsing
	 * @param string $id
	 * @param string $html
	 * @param integer $tag_pos
	 * @param array $tags
	 * @param array $local_vars
	 * @throws TemplateException
	 * @return string
	 */
	private function extending($id, &$html, &$tag_pos, &$tags, &$local_vars)
	{
		$tag = &$tags[$tag_pos];

		if($tag_pos!=0)
			throw new TemplateException(sprintf("Extends tag should be the first tag of the template : '%s'", $tag['content']));
		
		if(count($tag['words'])!=2)
			throw new TemplateException(sprintf("Extends tag expecting one argument : '%s'", $tag['content']));
		
		if(array_key_exists($id, $this->extends))
			throw new TemplateException(sprintf("Template id '%s'. Already extends '%s'. Cannot extends more then one template : '%s'", $id, $this->extends[$id], $tag['content']));
		
		$string = $this->get_string_literal($tag['words'][1]);
		
		if(is_null($string))
			throw new TemplateException(sprintf("Extends tag '%s' parameter is not a string literal.", $tag['content']));
		
		$this->extends[$id] = $string;
		
		return "";
	}
	
	/**
	 * Block tags parsing
	 * @param string $id
	 * @param string $html
	 * @param integer $tag_pos
	 * @param array $tags
	 * @param array $local_vars
	 * @throws TemplateException
	 * @return string
	 */
	private function block_parse($id, &$html, &$tag_pos, &$tags, &$local_vars)
	{
		$tag = &$tags[$tag_pos];
		
		if($tag['end_tag']===null)
			throw new TemplateException(sprintf("Expecting endblock tag after block tag : '%s'", $tag['content']));
		
		if(count($tag['words'])!=2)
			throw new TemplateException(sprintf("Block tag expecting one argument : '%s'", $tag['content']));
		
		if(!array_key_exists($id, $this->blocks))
			$this->blocks[$id] = array();
		
		if(!array_key_exists($tag['words'][1], $this->blocks[$id])):
			$html_part = substr($html, $tag['end'], $tag['end_tag']['start']-$tag['end']);
			$html_part = $this->functions_parse($id, $html_part, $local_vars);
			$html_part = $this->parse($id, $html_part, $local_vars);
			$this->blocks[$id][$tag['words'][1]] = $html_part;
			return $html_part;
			
		else:
			return $this->blocks[$id][$tag['words'][1]];
			
		endif;
	}
	
	/**
	 * Get a condition value or string literal or object (for eval...)
	 * @param string $id
	 * @param string $selector
	 * @param array $local_vars
	 * @param boolean $eval
	 * @return unknown
	 */
	private function condition_value($id, $selector, &$local_vars, $eval=false)
	{
		$value = null;
		
		if($selector=='true'):
			$value = true;
			
		elseif($selector=='false'):
			$value = false;
		
		elseif($selector=='null'):
			$value = null;
		
		elseif(is_numeric($selector)):
			$value = $selector + 0; // returns int or float
		
		elseif(!is_null($this->get_string_literal($selector))):
			$value = $this->get_string_literal($selector);
		
		else:
			$value = $this->var_select($id, $selector, $local_vars);
		
		endif;
		
		if(!$eval):
			return $value;
		
		else:
			if(is_bool($value)):
				return ($value)? 'true' : 'false';
			
			elseif(is_null($value)):
				return 'null';
			
			elseif(is_numeric($value)):
				return ''.$value;
			
			elseif(is_string($value)):
				return '"'.addcslashes($value, '"').'"';
			
			else:
				return $value;
			
			endif;
		endif;
	}
	
	/**
	 * Check one condition
	 * @param string $id
	 * @param array $words
	 * @param array $local_vars
	 * @throws TemplateException
	 * @return boolean|NULL
	 */
	private function condition_result($id, &$words, &$local_vars)
	{
		$s = 0;
		
		if($words[0]=='not'):
			unset($words[0]);
			if(count($words)==0 || count($words)==2)
				throw new TemplateException(sprintf("IF condition with negation is incomplete."));
			
			$negation = true;
			$s++;
			
		else:
			$negation = false;
			
		endif;
		
		$return = null;
		
		if(count($words)==3):
			$value1 = $words[$s];
			$operator = $words[$s+1];
			$value2 = $words[$s+2];
			
			// Basic opperators can be checked with eval and value2
			if(in_array($operator, array('==', '!=', '<', '>', '<=', '>='))):
				$value1 = $this->condition_value($id, $value1, $local_vars, true);
				$value2 = $this->condition_value($id, $value2, $local_vars, true);
				
				if(is_object($value1)):
					$obj1 = $value1;
					$value1 = "\$obj1";
				endif;
				
				if(is_object($value2)):
					$obj2 = $value2;
					$value2 = "\$obj2";
				endif;
				
				$return = eval("return ".$value1." ".$operator." ".$value2.";");
			
			else:
				switch($operator):
					case 'is':
						$value1 = $this->condition_value($id, $value1, $local_vars);
						$check_function = 'is_'.$value2;
						
						if(function_exists($check_function))
							$return = $check_function($value1);
						
						elseif($value2 == 'empty')
							$return = empty($value1);
							
						else
							throw new TemplateException(sprintf("In IF condition operator '%s' use unknown check function '%s'", $operator, $value2));

						break;
						
					default:
						throw new TemplateException(sprintf("In IF condition operator '%s' is unknown", $operator));
				endswitch;
			endif;
		
		else:
			$value1 = $words[$s];
			$value1 = $this->condition_value($id, $value1, $local_vars);
			// if $value1 is not null (exist), is not a false boolean and is not 0, then the condition returns true
			$return = (!is_null($value1) && $value1!==false && $value1!==0)? true : false;
			
		endif;
		
		if(!is_null($return))
			return ($negation)? !$return : $return;
		else
			return null;
	}
	
	/**
	 * If tags parsing
	 * @param string $id
	 * @param string $html
	 * @param integer $tag_pos
	 * @param array $tags
	 * @param array $local_vars
	 * @throws TemplateException
	 * @return string
	 */
	private function condition_parse($id, &$html, &$tag_pos, &$tags, $local_vars)
	{
		$tag = &$tags[$tag_pos];
		
		if($tag['end_tag']===null)
			throw new TemplateException(sprintf("Expecting endif tag after if condition : '%s'", $tag['content']));
		
		// TODO : Add 'and' and 'or' logic
		
		// Checking condition
		$words = explode(' ', trim(substr($tag['content'], 3)));
		
		if(count($words)<1 || count($words)>4 || $words[0]=='')
			throw new TemplateException(sprintf("IF condition '%s' should countains between 1 and 4 words", $tag['content']));
		
		$result = $this->condition_result($id, $words, $local_vars);
		
		if($result===null)
			throw new TemplateException(sprintf("IF condition '%s' could not be parsed correctly.", $tag['content']));
		
		// HTML inside
		$html_part = substr($html, $tag['end'], $tag['end_tag']['start']-$tag['end']);

		// Parsing content for else
		$else = null;
		$pass_else = 0;
		$pos = strpos($html_part, '{% ', 0);
		while($pos!==false)
		{
			$end = strpos($html_part, '%}', $pos);
			
			if($end!==false):
				$content = trim(substr($html_part, $pos+2, $end-$pos-2));
				$tag_words = explode(' ', $content);
				
				if($content=='else' && $pass_else==0)
				{
					$else = array(
						'start'=>$pos,
						'end'=>$end+2,
					);
					break;
				}
				
				if($tag_words[0]=='if')
					$pass_else++;
				
				if($content=='else' && $pass_else>0)
					$pass_else--;
					
			endif;
			
			$pos = strpos($html_part, '{% ', $pos+2);
		}
		
		if($else===null):
			if($result):
				// No else, returned true
				$condition_html = $this->functions_parse($id, $html_part, $local_vars);
				$condition_html = $this->parse($id, $condition_html, $local_vars);

			else:
				// No else, returned false
				$condition_html = "";
			
			endif;
			
		else:
			if($result):
				$condition_html = substr($html_part, 0, $else['start']);
				$condition_html = $this->functions_parse($id, $condition_html, $local_vars);
				$condition_html = $this->parse($id, $condition_html, $local_vars);

			else:
				$condition_html = substr($html_part, $else['end']);
				$condition_html = $this->functions_parse($id, $condition_html, $local_vars);
				$condition_html = $this->parse($id, $condition_html, $local_vars);

			endif;
		endif;
		
		return $condition_html;
	}
	
	/**
	 * Include tags parsing
	 * @param string $id
	 * @param string $html
	 * @param integer $tag_pos
	 * @param array $tags
	 * @param array $local_vars
	 * @throws TemplateException
	 * @return string
	 */
	private function include_parse($id, &$html, &$tag_pos, &$tags, $local_vars)
	{
		$tag = &$tags[$tag_pos];
		
		if(count($tag['words'])!=2)
			throw new TemplateException(sprintf("Include tag '%s' should have one parameter.", $tag['content']));
		
		$string = $this->get_string_literal($tag['words'][1]);
		
		if(is_null($string))
			throw new TemplateException(sprintf("Include tag '%s' parameter is not a string literal.", $tag['content']));
		
		try
		{
			$included_html = "";
			$included_html_id = $id."_incl_".$string;
			$included_html = $this->render_file($included_html_id, $string, $local_vars);
			
			unset($this->files[$included_html_id]);
			unset($this->vars[$included_html_id]);
			if(array_key_exists($included_html_id, $this->extends)) unset($this->extends[$included_html_id]);
			if(array_key_exists($included_html_id, $this->blocks)) unset($this->blocks[$included_html_id]);
			
			return $included_html;
		}
		catch(Exception $e)
		{
			throw new TemplateException(sprintf("Include tag '%s' failed : %s", $tag['content'], $e->getMessage()));
		}
	}
	
	/**
	 * For tags parsing
	 * @param string $id
	 * @param string $html
	 * @param integer $tag_pos
	 * @param array $tags
	 * @param array $local_vars
	 * @throws TemplateException
	 * @return string
	 */
	private function forloop_parse($id, &$html, &$tag_pos, &$tags, $local_vars)
	{
		$tag = &$tags[$tag_pos];
		
		if($tag['end_tag']===null)
			throw new TemplateException(sprintf("Expecting endfor tag after for loop : '%s'", $tag['content']));
		
		// Parsing tag content
		$vars_in_array = explode(' in ', substr($tag['content'], 3));
		
		if(count($vars_in_array)!=2)
			throw new TemplateException(sprintf("For tag should contains one 'in' statement : '%s'", $tag['content']));
		
		$vars = explode(',', $vars_in_array[0]);
		if(count($vars)>2)
			throw new TemplateException(sprintf("Cannot unpack more then key, val from array : '%s'", $tag['content']));
			
		foreach ($vars as $k => $v) {
			if(trim($v)=="")
				throw new TemplateException(sprintf("Empty variable name : '%s'", $tag['content']));
			
			$vars[$k] = trim($v);
		}
		
		$in = trim($vars_in_array[1]);
		$value = $this->var_select($id, $in, $local_vars);

		if(!is_array($value) && !($value instanceof Traversable))
			throw new TemplateException(sprintf("For tag must loop over an array : '%s' selector returned %s.", $in, gettype($value)));
		
		// HTML inside
		$html_part = substr($html, $tag['end'], $tag['end_tag']['start']-$tag['end']);

		// Parsing content for empty content
		$empty = null;
		$pass_empty = 0;
		$pos = strpos($html_part, '{% ', 0);
		while($pos!==false)
		{
			$end = strpos($html_part, '%}', $pos);
			
			if($end!==false):
				$content = trim(substr($html_part, $pos+2, $end-$pos-2));
				$tag_words = explode(' ', $content);
				
				if($content=='empty' && $pass_empty==0)
				{
					$empty = array(
						'start'=>$pos,
						'end'=>$end+2,
					);
					break;
				}
				
				if($tag_words[0]=='for')
					$pass_empty++;
				
				if($content=='empty' && $pass_empty>0)
					$pass_empty--;
				
			endif;
			
			$pos = strpos($html_part, '{% ', $pos+2);
		}
		
		if(!is_null($empty) && count($value)==0):
			$forloop_html = substr($html_part, $empty['end']);
			$forloop_html = $this->functions_parse($id, $forloop_html, $local_vars);
			$forloop_html = $this->parse($id, $forloop_html, $local_vars);
			
			
		else:
			if(!is_null($empty)):
				$html_part = substr($html_part, 0, $empty['start']);
			endif;
			
			// Parsing html content
			$forloop_html = "";
			
			$i = 0;
			$count = count($value);
			foreach($value as $key => $val):
				$forloop_item_vars = array(
					'forloop'=>array(
						'counter'=>$i+1,
						'first'=> $i==0 ? true : false,
						'last'=> ($i+1)==$count ? true : false,
					),
				);
				
				if(count($vars)==1):
					$forloop_item_vars[$vars[0]] = $val;
				else:
					$forloop_item_vars[$vars[0]] = $key;
					$forloop_item_vars[$vars[1]] = $val;
				endif;
				
				$forloop_item_html = $this->functions_parse($id, $html_part, array_merge($local_vars, $forloop_item_vars));
				$forloop_item_html = $this->parse($id, $forloop_item_html, array_merge($local_vars, $forloop_item_vars));
				$forloop_html = $forloop_html.$forloop_item_html;
				$i++;
	
			endforeach;
		endif;
		
		return $forloop_html;
	}
	
}

?>