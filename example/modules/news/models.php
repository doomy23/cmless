<?php

class NewsArticle extends Model{
	public $id;
	public $title;
	public $slug;
	public $short_desc;
	public $content;
	public $image;
	public $datetime;
	public $author;
	public $category;
	
	/**
	 * Manager
	 * @param string $backendKey
	 * @param string $modelClassName = __CLASS__
	 */
	public static function objects($backendKey="default", $modelClassName=__CLASS__)
	{
		return parent::objects($backendKey, $modelClassName);
	}
	
	/**
	 * Table
	 * @return string
	 */
	public function table()
	{
		 return "news_newsarticle"; 
	}
	
	/**
	 * Structure
	 * @return array
	 */
	public function structure()
	{
		return array(
			'id'=>array('type'=>'pk', 'auto'),
			'title'=>array('type'=>'char', 'length'=>255, 'required'),
			'slug'=>array('type'=>'char', 'length'=>255, 'required'),
			'short_desc'=>array('type'=>'text', 'required'),
			'content'=>array('type'=>'text', 'required'),
			'image'=>array('type'=>'image', 'upload_to'=>'news/articles'),
			'datetime'=>array('type'=>'datetime', 'now'),
			'author'=>array('type'=>'fk', 'model'=>'NewsAuthor', 'required'),
			'category'=>array('type'=>'fk', 'model'=>'NewsCategory', 'required'),
		);
	}
	
	/**
	 * Get category model
	 */
	public function getCategory()
	{
		return NewsCategory::objects()->getPk($this->category);
	}
	
	/**
	 * Get author model
	 */
	public function getAuthor()
	{
		return NewsAuthor::objects()->getPk($this->author);
	}
	
	/**
	 * Make URL with it's slugs
	 */
	public function getUrl()
	{
		return Cmless::Urls()->reverse('News.controller.NewsController.details', array($this->getCategory()->slug, $this->slug));
	}
	
}

class NewsCategory extends Model{
	public $id;
	public $name;
	public $slug;
	
	/**
	 * Manager
	 * @param string $backendKey
	 * @param string $modelClassName = __CLASS__
	 */
	public static function objects($backendKey="default", $modelClassName=__CLASS__)
	{
		return parent::objects($backendKey, $modelClassName);
	}
	
	/**
	 * Table
	 * @return string
	 */
	public function table()
	{
		return "news_newscategory";
	}
	
	/**
	 * Structure
	 * @return array
	 */
	public function structure()
	{
		return array(
			'id'=>array('type'=>'pk', 'auto'),
			'name'=>array('type'=>'char', 'length'=>255, 'required'),
			'slug'=>array('type'=>'char', 'length'=>255, 'required'),
		);
	}
	
	/**
	 * Get category's articles
	 */
	public function articles()
	{
		$orderBy = (func_num_args() >= 1)? func_get_arg(0) : array('datetime'=>'desc');
		$limit_count = (func_num_args() >= 2)? func_get_arg(1) : null;
		$limit_from = (func_num_args() >= 3)? func_get_arg(2) : null;
		
		return NewsArticle::objects()->filter(array('category'=>$this->id), $orderBy, $limit_count, $limit_from);
	}
	
}

class NewsAuthor extends Model{
	public $id;
	public $name;
	public $email;
	public $image;
	public $datetime;
	
	/**
	 * Manager
	 * @param string $backendKey
	 * @param string $modelClassName = __CLASS__
	 */
	public static function objects($backendKey="default", $modelClassName=__CLASS__)
	{
		return parent::objects($backendKey, $modelClassName);
	}
	
	/**
	 * Table
	 * @return string
	 */
	public function table()
	{
		return "news_newsauthor";
	}
	
	/**
	 * Structure
	 * @return array
	 */
	public function structure()
	{
		return array(
			'id'=>array('type'=>'pk', 'auto'),
			'name'=>array('type'=>'char', 'length'=>255, 'required'),
			'email'=>array('type'=>'char', 'length'=>255, 'required'),
			'image'=>array('type'=>'image', 'upload_to'=>'news/authors', 'default'=>'news/authors/unknown.jpg'),
			'datetime'=>array('type'=>'datetime', 'now'),
		);
	}
	
	/**
	 * Get author's articles
	 */
	public function articles()
	{
		$orderBy = (func_num_args() >= 1)? func_get_arg(0) : array('datetime'=>'desc');
		$limit_count = (func_num_args() >= 2)? func_get_arg(1) : null;
		$limit_from = (func_num_args() >= 3)? func_get_arg(2) : null;
		
		return NewsArticle::objects()->filter(array('author'=>$this->id), $orderBy, $limit_count, $limit_from);
	}
}

?>