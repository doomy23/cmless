<?php 

abstract class TemplateTag{
	const tag = null;
	const end_tag = null;
	const pre_parse_html = false;
	
	public $content;
	
	/**
	 * Constructor called by the template
	 * @param array $tag
	 */
	public final function __construct(array $tag)
	{
		$this->content = $tag['content'];
	}

	/**
	 * Parse the tag
	 * @param array $params
	 * @param string $html
	 * @return string
	 */
	public abstract function parse(array $params, $html);
}

?>