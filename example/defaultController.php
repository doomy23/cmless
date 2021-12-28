<?php

class DefaultController extends Controller{
	
	public function index()
	{
		return Cmless::CachedTemplate(300)->render_file('home', 'templates/index.html', array(
			'cats'=>array(
					array('img'=>'http://images.wisegeek.com/young-calico-cat.jpg', 'title'=>'Young Calico'),
					array('img'=>'http://media.moddb.com/images/downloads/1/66/65343/-_-cats-cats-22066039-1280-1024.jpg', 'title'=>'My cup of tea'),
					array('img'=>'http://www.foodiggity.com/wp-content/uploads/2013/05/sushi-cats-2.jpeg', 'title'=>'Delicious-looking sushi-cat'),
					array('img'=>'https://i.dailymail.co.uk/i/pix/2012/02/01/article-2094738-118A758B000005DC-950_634x436.jpg', 'title'=>'OH NOOOOOOOO!'),
					array('img'=>'http://rilm.files.wordpress.com/2012/10/cat-music.jpg', 'title'=>'Cat listening music'),
				)
			), array(), $this->getCurrentUrl());
	}
	
	/* Error controller for 'debug' = false
	 * It's just a test to see if it's possible!
	 * In this case this is useless as the template
	 * deosn't need any variable and we don't need
	 * to do any tweaky tweaks...
	 * 
	 * There is no header sent (in case you need a redirection) 
	 * so you need to make it! For this use... 
	 * Cmless::getInstance()->makeErrorHeader($error)
	 * 
	 * By default, without controller it does the same as the
	 * following lines...
	 */
	public function error($error, $template, array $details)
	{
		Cmless::getInstance()->makeErrorHeader($error);
		return Cmless::Template()->render_file($error, $template, $details);
	}
	
}

?>