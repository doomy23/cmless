<?php 

class LastNewsTag extends TemplateTag{
	const tag = "last_news_section";

	public function parse(array $params, $html)
	{
		if(count($params)!=0)
			throw new TemplateException(sprintf("Tag last_news_section does not expect any parameter : '%s'", $this->content));
		
		$last_articles = NewsArticle::objects()->all(array('datetime'=>'desc'), 3);

		return Cmless::Template()->render_file('last_news_section', 'News/last_news_section.html', array(
			'last_articles'=>$last_articles
		));
	}
}

class NewsCategoriesMenuTag extends TemplateTag{
	const tag = "news_categories_menu";

	public function parse(array $params, $html)
	{
		if(count($params)!=1)
			throw new TemplateException(sprintf("Tag news_categories_menu expected one parameter : '%s'", $this->content));

		$current_category = $params[0];
		$categories = NewsCategory::objects()->all(array('name'=>'asc'));

		return Cmless::Template()->render_file('categories_nav', 'News/categories_nav.html', array(
			'current_category'=>$current_category,
			'categories'=>$categories
		));
	}
}

class NewsCategoriesList extends TemplateTag{
	const tag = "news_categories_list";
	const end_tag = "end_news_categories_list";
	
	public function parse(array $params, $html)
	{
		if(count($params)!=1)
			throw new TemplateException(sprintf("Tag news_categories_list expected one parameter : '%s'", $this->content));
		
		// Optional param 3 is local template variables
		$categories_var = array($params[0]=>NewsCategory::objects()->all(array('name'=>'asc')));
		$vars = (func_num_args() >= 3)? array_merge(func_get_arg(2), $categories_var) : $categories_var; 
		
		$tpl = Cmless::Template();
		$tpl->load_html('news_categories_list', $html);
		return $tpl->render('news_categories_list', $vars);
	}
}

/**
 * This filter is totally useless 
 * and for educational purpose only...
 * @author Dominic Roberge
 */
class UselessTestFilter extends TemplateFilter{
	const filter = "uselessTestFilter";
	
	public function filter($value, array $params)
	{
		if(count($params)!=3)
			throw new TemplateException(sprintf("Filter uselessTestFilter expected three parameters"));
		
		return $value;
	}
}

?>