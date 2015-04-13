<?php
if(!defined('CMLESS_PATH')) define('CMLESS_PATH', '');
if(!defined('RUN_PATH')) define('RUN_PATH', getcwd());
require_once CMLESS_PATH.'template/init.php';
require_once CMLESS_PATH.'utilities/init.php';

/**
 * Get the error level name
 * @param integer $level
 * @return integer|string
 */
function get_error_level_name($level)
{
	$ERROR_LEVELS = array(
		1 => 'E_ERROR',
		2 => 'E_WARNING',
		4 => 'E_PARSE',
		8 => 'E_NOTICE',
		16 => 'E_CORE_ERROR',
		32 => 'E_CORE_WARNING',
		64 => 'E_COMPILE_ERROR',
		128 => 'E_COMPILE_WARNING',
		256 => 'E_USER_ERROR',
		512 => 'E_USER_WARNING',
		1024 => 'E_USER_NOTICE',
		2048 => 'E_STRICT',
		4096 => 'E_RECOVERABLE_ERROR',
		8192 => 'E_DEPRECATED',
		16384 => 'E_USER_DEPRECATED',
	);
	
	if(array_key_exists($level, $ERROR_LEVELS)):
		return $ERROR_LEVELS[$level];
	
	else:
		return $level;
	
	endif;
}

/**
 * Error handler function
 * @param integer $level
 * @param string $message
 * @param string $file
 * @param integer $line
 * @param array $context
 * @param array $traceback
 */
function error_handler($level, $message, $file, $line, array $context, array $traceback=array())
{
	if(!in_array($level, Cmless::$config['errors_ignored'])):
	    if(CMLESS_DEBUG):
	    	header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
			
			if(array_key_exists('GLOBALS', $context))
				unset($context['GLOBALS']);
			
			if(empty($traceback)) 
				$traceback = debug_backtrace();
			
			krsort($traceback);
	
			$tpl = new Template(array(
				'templates_aliases'=>Cmless::$config['templates_aliases'],
				'vars'=>array('STATIC_URL'=>Cmless::$config['static_url'])
			));
			
			print $tpl->render_file('debug', 'cmless/debug.html', array(
				'level'=>get_error_level_name($level),
				'message'=>$message,
				'file'=>$file,
				'line'=>$line,
				'context'=>$context,
				'traceback'=>$traceback,
			));
			
	    	exit();
			
		else:
			if(is_null(Cmless::$config['error_controller'])):
				header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);

				$tpl = new Template(array(
					'templates_aliases'=>Cmless::$config['templates_aliases'],
					'vars'=>array('STATIC_URL'=>Cmless::$config['static_url'])
				));
				
				print $tpl->render_file('500', Cmless::$config['error_templates'][500], array(
					'absolute_request_uri'=>Utilities::absolute_request_uri(),
					'level'=>get_error_level_name($level),
					'message'=>$message
				));
				
			else:
				$result = Cmless::getInstance()->callErrorController(Cmless::$config['error_controller'], 500, array(
					'absolute_request_uri'=>Utilities::absolute_request_uri(),
					'level'=>get_error_level_name($level),
					'message'=>$message
				));
					
				if($result)
					print $result;
				
			endif;
			
	    	exit();
			
		endif;
	endif;
	
	return false;
}

/**
 * Fatal error handler
 */
function fatal_error_handler()
{
	$last_error = error_get_last();
	
	if(!is_null($last_error)):
		chdir(Cmless::$config['run_path']);
		return error_handler($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line'], $GLOBALS);
		
	endif;
}

/**
 * Exception handler
 * @param Exception $exception
 */
function exception_handler($exception)
{
	chdir(Cmless::$config['run_path']);
	$level = get_class($exception);
	
	return error_handler($level, $exception->getMessage(), $exception->getFile(), $exception->getLine(), $GLOBALS, $exception->getTrace());
}

ini_set('display_errors', 0);
error_reporting(E_ALL);
set_error_handler('error_handler');
register_shutdown_function('fatal_error_handler');
set_exception_handler('exception_handler');

?>