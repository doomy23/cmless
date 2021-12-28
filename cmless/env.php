<?php

class Environment {
	private static $env = array();

	public static function setEnv(array $env) {
		self::$env = $env;
	}

	public static function getEnv() {
		return self::$env;
	}

	public static function setValue($value) {
		if(strtolower($value) == "false"):
			return false;
		elseif(strtolower($value) == "true"):
			return true;
		else:
			return trim($value);
		endif;
	}
}

if(file_exists(".env")):
	$env = array();
	$handle = fopen(".env", "r");
	$contents = stream_get_contents($handle);
	$lines = explode("\n", $contents);
	foreach($lines as $line):
		$key_value = explode("=", $line, 2);
		if(count($key_value)==2)
			$env[$key_value[0]] = Environment::setValue($key_value[1]);
	endforeach;
	Environment::setEnv($env);
	unset($key_value);
	unset($line);
	unset($handle);
	unset($contents);
	unset($lines);
	unset($env);
endif;

// Required function for .env local settings
function env($name, $default) {
	if(array_key_exists($name, Environment::getEnv()))
		return Environment::getEnv()[$name];
	return $default;
}

?>