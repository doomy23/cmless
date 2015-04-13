<?php

class NewsController extends Controller{
	const ELEMENTS_BY_PAGES = 2;
	
	/**
	 * Articles list with pagination
	 */
	public function index()
	{
		$articles = array();
		$current_category = null;
		
		// Set the parameters
		
		if(func_num_args() == 1):
			$category_slug = (!is_numeric(func_get_arg(0)))? func_get_arg(0) : null;
			$page = (is_null($category_slug))? func_get_arg(0) : 1;
		
		elseif(func_num_args() == 2):
			$category_slug = func_get_arg(0);
			$page = func_get_arg(1);
			
		else:
			$category_slug = null;
			$page = 1;
		
		endif;
		
		// Check parameters and load data
		
		if(!is_null($category_slug)):
			try
			{
				$current_category = NewsCategory::objects()->get(array('slug'=>$category_slug));
				$articles = $current_category->articles(array('datetime'=>'desc'), self::ELEMENTS_BY_PAGES, self::ELEMENTS_BY_PAGES*($page-1));
				$rows = NewsArticle::objects()->count(array('category'=>$current_category->id));
			}
			catch(ModelNotFoundQueryException $e)
			{
				Cmless::getInstance()->Http404();
			}
			
		else:
			$articles = NewsArticle::objects()->all(array('datetime'=>'desc'), self::ELEMENTS_BY_PAGES, self::ELEMENTS_BY_PAGES*($page-1));
			$rows = NewsArticle::objects()->count();
		
		endif;
		
		$max_pages = ($rows>0)? ceil($rows/self::ELEMENTS_BY_PAGES) : 1;
		
		if($page<1 || $page>$max_pages)
			Cmless::getInstance()->Http404();
		
		// Pages info
		
		$pages_array = array();
		
		for($i=1;$i<=$max_pages;$i++):
			$pages_array[] = array(
				'current'=>($page==$i)? true : false,
				'num'=>$i
			);
		
		endfor;
		
		// Render page
		
		return Cmless::CachedTemplate(300)->render_file('news_index', 'News/index.html', array(
			'current_category'=>$current_category,
			'previous_page'=>($page>1)? $page-1 : null,
			'current_page'=>$page,
			'next_page'=>($page<$max_pages)? $page+1 : null,
			'articles'=>$articles,
			'pages'=>$pages_array,
		), func_get_args(), $this->getCurrentUrl());
	}
	
	/**
	 * Article details
	 * @param string $category_slug
	 * @param string $article_slug
	 */
	public function details($category_slug, $article_slug)
	{
		try
		{
			$current_category = NewsCategory::objects()->get(array('slug'=>$category_slug));
			$article = NewsArticle::objects()->get(array('category'=>$current_category->id, 'slug'=>$article_slug));
		}
		catch(ModelNotFoundQueryException $e)
		{
			Cmless::getInstance()->Http404();
		}
		
		return Cmless::CachedTemplate(600)->render_file('news_details', 'News/details.html', array(
			'current_category'=>$current_category,
			'article'=>$article
		), func_get_args(), $this->getCurrentUrl());
	}
	
}

?>