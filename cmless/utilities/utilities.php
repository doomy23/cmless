<?php
 
class Utilities{
	
	/**
	 * Parse a php file path like "folder.folder2.file"
	 * Or a folder if $php=false
	 * @param string $path
	 * @param array $aliases
	 * @param boolean $php
	 * @return string
	 */
	public static function parse_path($path, array &$aliases, $php=true)
	{
		$file = "";
		$parts = explode('.', $path);
		
		for ($i=0; $i < count($parts); $i++)
		{
			if($i==0&&count($parts)>1)
			{
				$alias_found = false;
				foreach($aliases as $alias => $alias_path){
					if($alias==$parts[$i])
					{
						$file .= $alias_path;
						$alias_found = true;
						break;
					}
				}
				if(!$alias_found)
				{
					$file .= $parts[$i]."/";
				}
			}
			elseif ($i==count($parts)-1) {
				if($php)
					$file .= $parts[$i].".php";
				else
					$file .= $parts[$i]."/";
			}
			else{
				$file .= $parts[$i]."/";
			}
		}
		
		return $file;
	}
	
	/**
	 * Check if https is on
	 * @return boolean
	 */
	public static function is_secure() {
	  return
	    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
	    || $_SERVER['SERVER_PORT'] == 443;
	}
	
	/**
	 * Build an absolute URL
	 * @return string
	 */
	public static function domain_uri(){
		$url = 'http';
		if (self::is_secure()) $url .= "s";
		$url .= "://";
		if ($_SERVER["SERVER_PORT"] != "80" && $_SERVER["SERVER_PORT"] != 443) $url .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
		else $url .= $_SERVER["SERVER_NAME"];
		return $url;
	}
	
	/**
	 * Build an absolute URL
	 * @return string
	 */
	public static function absolute_request_uri(){
		$url = 'http';
		if (self::is_secure()) $url .= "s";
		$url .= "://";
		if ($_SERVER["SERVER_PORT"] != "80" && $_SERVER["SERVER_PORT"] != 443) $url .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		else $url .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		return $url;
	}

	/**
	 * Get the maximum file size for a upload in mb
	 */
	public static function max_upload_file_mb() {
		$max_upload = (int)(ini_get('upload_max_filesize'));
		$max_post = (int)(ini_get('post_max_size'));
		$memory_limit = (int)(ini_get('memory_limit'));
		return min($max_upload, $max_post, $memory_limit);
	}
	
}

?>