<?php

/**
 * Fake controller used by the real controller...
 */
class BenchtestControllerCeption extends CachedController{
	
	/**
	 * Controller Vs CachedController method
	 * @param array $params
	 * @param boolean $cache
	 */
	public function cachedController_test(array $params, $cache=false)
	{
		if($cache && $this->load_cache($params)) return $this->cache;
		
		return Cmless::CachedTemplate()->render_file('benchtest_cachedController', 'templates/benchtest_cachedtemplate_test.html', array(
					'news'=>NewsArticle::objects()->all()
				), $params, $this->getCurrentUrl());
	}
	
	/**
	 * CachedTemplate + QueriesSaver + CachedController Vs Nothing method
	 * @param array $params
	 * @param boolean $everything
	 */
	public function everything_test(array $params, $everything=false)
	{
		if($everything && $this->load_cache($params)) return $this->cache;
		
		if($everything):
			return Cmless::CachedTemplate()->render_file('benchtest_everything', 'templates/benchtest_cachedtemplate_test.html', array(
					'news'=>NewsArticle::objects()->all()
				), $params, $this->getCurrentUrl());
		
		else:
			return Cmless::Template()->render_file('benchtest_everything', 'templates/benchtest_cachedtemplate_test.html', array(
					'news'=>NewsArticle::objects()->all()));
		
		endif;
	}
	
}

class BenchtestController extends Controller{
	const TVSCT_ITERATIONS = 15;
	const QSONOFF_ITERATIONS = 15;
	const CVSCC_ITERATIONS = 15;
	const EVSN_ITERATIONS = 20;
	
	/**
	 * Template Vs CachedTemplate test
	 * @param array $params
	 * @param string $cached
	 * @return array (start, end, time, template)
	 */
	private function templateVsCachedTemplate_test(array $params, $cached=false)
	{
		Cmless::$config['app']['queries_saver'] = false;
		if($cached) Cmless::CachedTemplate(60)->render_file('benchtest_cachedtemplate', 'templates/benchtest_cachedtemplate_test.html', $params); // Pre-render in the cache if cached
		$start_time = microtime(true);
		$template_rendered = ($cached)? Cmless::CachedTemplate(60)->render_file('benchtest_cachedtemplate', 'templates/benchtest_cachedtemplate_test.html', $params) :
			Cmless::Template()->render_file('benchtest_template', 'templates/benchtest_cachedtemplate_test.html', $params);
		$end_time = microtime(true);
		$render_time = $end_time - $start_time;
		return array($start_time, $end_time, $render_time, $template_rendered);
	}
	
	/**
	 * QueriesSaver On/Off test
	 * @param boolean $on
	 * @return array(start, end, time, queries)
	 */
	private function queriesSaverOnOff_test($on=false)
	{
		Cmless::$config['app']['queries_saver'] = $on;
		if($on) QueriesSaver::getInstance()->clear();
		$queries_results = array();
		$start_time = microtime(true);
		for($i=0;$i<3;$i++):
			$newsArticles = NewsArticle::objects()->all();
			$queries_results[] = $newsArticles;
			foreach($newsArticles as $newsArticle):
				$queries_results[] = $newsArticle->getAuthor();
				$queries_results[] = $newsArticle->getCategory();
			endforeach;
		endfor;
		$end_time = microtime(true);
		$queries_time = $end_time - $start_time;
		return array($start_time, $end_time, $queries_time, $queries_results);
	}
	
	/**
	 * Controller Vs CachedController test
	 * @param boolean $cache
	 * @return array (start, end, time, template)
	 */
	private function controllerVsCachedController_test($cache=false)
	{
		Cmless::$config['app']['queries_saver'] = false;
		$controller = new BenchtestControllerCeption($this->getCurrentUrl());
		$params = array('cachedController'=>$cache);
		if($cache) $template_rendered = $controller->cachedController_test($params, $cache);
		$start_time = microtime(true);
		$template_rendered = $controller->cachedController_test($params, $cache);
		$end_time = microtime(true);
		$render_time = $end_time - $start_time;
		return array($start_time, $end_time, $render_time, $template_rendered);
	}
	
	/**
	 * CachedTemplate + QueriesSaver + CachedController Vs Nothing test
	 * @param string $everything
	 * @return multitype:unknown
	 */
	private function everything_test($everything=false)
	{
		Cmless::$config['app']['queries_saver'] = $everything;
		$controller = new BenchtestControllerCeption($this->getCurrentUrl());
		$params = array('everything'=>$everything);
		if($everything) $template_rendered = $controller->everything_test($params, $everything);
		QueriesSaver::getInstance()->clear();
		$start_time = microtime(true);
		$template_rendered = $controller->everything_test($params, $everything);
		$end_time = microtime(true);
		$render_time = $end_time - $start_time;
		return array($start_time, $end_time, $render_time, $template_rendered);
	}
	
	/**
	 * Some boring calculs put together
	 * @param float $time2
	 * @param string $string2
	 * @param float $time1
	 * @param string $string1
	 * @return array (diff, fastest, slowest
	 */
	private function getWinnerData($time2, $string2, $time1, $string1)
	{
		$diff = ($time2-$time1);
		if($diff<0) $diff = abs($diff);
		$slowest = ($time2 < $time1)? $time1 : $time2;
		$fastest = ($time2 < $time1)? $string2 : $string1;
		$gain = $diff/$slowest*100;
		return array($diff, $fastest, $slowest, $gain);
	}
	
	/**
	 * Test URL
	 */
	public function test()
	{
		//if($this->load_cache()) return $this->cache;
		
		$init_queries_saver = Cmless::$config['app']['queries_saver'];
		
		// CachedTemplate + QueriesSaver + CachedController Vs nothing
		$everythingVsNothing_moy= array(false=>0, true=>0);
		
		foreach(array(false, true) as $bool):
			for($i=0;$i<self::EVSN_ITERATIONS;$i++):
				list($start_time, $end_time, $render_time, $rendered) =
					$this->everything_test($bool);
				
				$everythingVsNothing_moy[$bool] += $render_time;
			endfor;
				
			$everythingVsNothing_moy[$bool] = $everythingVsNothing_moy[$bool]/self::EVSN_ITERATIONS;
			
			if($bool):
				$everything_start_time = $start_time;
				$everything_end_time = $end_time;
				$everything_render_time = $render_time;
				$everything_render_avg_time = $everythingVsNothing_moy[$bool];
			else:
				$nothing_start_time = $start_time;
				$nothing_end_time = $end_time;
				$nothing_render_time = $render_time;
				$nothing_render_time = $everythingVsNothing_moy[$bool];
			endif;
		endforeach;
		
		list($everythingVsNothingDiff, $everythingVsNothingFastest, $everythingVsNothingSlowest, $everythingVsNothingGain) =
			$this->getWinnerData($everything_render_avg_time, "CachedTemplate + QueriesSaver ON + CachedController", $nothing_render_time, "Nothing... it happens sometimes, refresh ;-)");
		
		// Controller Vs CachedController
		$controllerVsCachedController_moy= array(false=>0, true=>0);
		
		foreach(array(false, true) as $bool):
			for($i=0;$i<self::CVSCC_ITERATIONS;$i++):
				list($start_time, $end_time, $render_time, $rendered) =
					$this->controllerVsCachedController_test($bool);
				
				$controllerVsCachedController_moy[$bool] += $render_time;
			endfor;
				
			$controllerVsCachedController_moy[$bool] = $controllerVsCachedController_moy[$bool]/self::CVSCC_ITERATIONS;
			
			if($bool):
				$cachedController_start_time = $start_time;
				$cachedController_end_time = $end_time;
				$cachedController_render_time = $render_time;
				$cachedController_render_avg_time = $controllerVsCachedController_moy[$bool];
			else:
				$controller_start_time = $start_time;
				$controller_end_time = $end_time;
				$controller_render_time = $controllerVsCachedController_moy[$bool];
				$controller_render_avg_time = $controllerVsCachedController_moy[$bool];
			endif;
		endforeach;
		
		list($controllerVsCachedControllerDiff, $controllerVsCachedControllerFastest, $controllerVsCachedControllerSlowest, $controllerVsCachedControllerGain) =
			$this->getWinnerData($cachedController_render_avg_time, "CachedController", $controller_render_avg_time, "Controller");
		
		// QueriesSaver off VS QueriesSaver on
		$queriesSaverOff_moy= array(false=>0, true=>0);
		
		foreach(array(false, true) as $bool):
			for($i=0;$i<self::QSONOFF_ITERATIONS;$i++):
				list($start_time, $end_time, $queries_time, $queries) = $this->queriesSaverOnOff_test($bool);
			
				$queriesSaverOff_moy[$bool] += $queries_time;
			endfor;
				
			$queriesSaverOff_moy[$bool] = $queriesSaverOff_moy[$bool]/self::QSONOFF_ITERATIONS;
				
			if($bool):
				$queriesSaverOn_queries_results = $queries;
				$queriesSaverOn_start_time = $start_time;
				$queriesSaverOn_end_time = $end_time;
				$queriesSaverOn_queries_time = $queries_time;
				$queriesSaverOn_queries_avg_time = $queriesSaverOff_moy[$bool];
			else:
				$queriesSaverOff_queries_results = $queries;
				$queriesSaverOff_start_time = $start_time;
				$queriesSaverOff_end_time = $end_time;
				$queriesSaverOff_queries_time = $queries_time;
				$queriesSaverOff_queries_avg_time = $queriesSaverOff_moy[$bool];
			endif;
		endforeach;
		
		list($queriesSaverOnOffDiff, $queriesSaverOnOffFastest, $queriesSaverOnOffSlowest, $queriesSaverOnOffGain) =
			$this->getWinnerData($queriesSaverOn_queries_avg_time, "QueriesSaver ON", $queriesSaverOff_queries_avg_time, "QueriesSaver OFF");
		
		// Template VS CachedTemplate
		$params = array('news'=>NewsArticle::objects()->all(array(), 3)); // Get 3 news
		$templateVsCachedTemplate_moy= array(false=>0, true=>0);
		
		foreach(array(false, true) as $bool):
			for($i=0;$i<self::TVSCT_ITERATIONS;$i++):
				list($start_time, $end_time, $render_time, $rendered) =
					$this->templateVsCachedTemplate_test($params, $bool);
		
				$templateVsCachedTemplate_moy[$bool] += $render_time;
			endfor;
			
			$templateVsCachedTemplate_moy[$bool] = $templateVsCachedTemplate_moy[$bool]/self::TVSCT_ITERATIONS;

			if($bool): 
				$cachedTemplate_start_time = $start_time;
				$cachedTemplate_end_time = $end_time;
				$cachedTemplate_render_time = $render_time; 
				$cachedTemplate_render_avg_time = $templateVsCachedTemplate_moy[$bool];
			else:
				$template_start_time = $start_time;
				$template_end_time = $end_time;
				$template_render_time = $render_time;
				$template_render_avg_time = $templateVsCachedTemplate_moy[$bool];
			endif;
		endforeach;
		
		list($templateVsCachedTemplateDiff, $templateVsCachedTemplateFastest, $templateVsCachedTemplateSlowest, $templateVsCachedTemplateGain) = 
			$this->getWinnerData($cachedTemplate_render_avg_time, "CachedTemplate", $template_render_avg_time, "Template");
		
		// Put it back on original config ;-)
		Cmless::$config['app']['queries_saver'] = $init_queries_saver;
		
		return Cmless::Template()->render_file('benchtest', 'templates/benchtest.html', array(
			'templateVsCachedTemplate'=>array(
				'template'=>array(
					'start'=>$template_start_time,
					'end'=>$template_end_time,
					'time'=>$template_render_time
				),
				'cachedTemplate'=>array(
					'start'=>$cachedTemplate_start_time,
					'end'=>$cachedTemplate_end_time,
					'time'=>$cachedTemplate_render_time
				),
				'fastest'=>$templateVsCachedTemplateFastest,
				'diff'=>$templateVsCachedTemplateDiff,
				'gain'=>$templateVsCachedTemplateGain,
				'iterations'=>self::TVSCT_ITERATIONS
			),
			'queriesSaverOnOff'=>array(
				'off'=>array(
					'start'=>$queriesSaverOff_start_time,
					'end'=>$queriesSaverOff_end_time,
					'time'=>$queriesSaverOff_queries_time,
					'queries'=>count($queriesSaverOff_queries_results)
				),
				'on'=>array(
					'start'=>$queriesSaverOn_start_time,
					'end'=>$queriesSaverOn_end_time,
					'time'=>$queriesSaverOn_queries_time,
					'queries'=>count($queriesSaverOn_queries_results)
				),
				'fastest'=>($queriesSaverOn_queries_time < $queriesSaverOff_queries_time)? "QueriesSaver ON" : "QueriesSaver OFF",
				'diff'=>$queriesSaverOnOffDiff,
				'gain'=>$queriesSaverOnOffGain,
				'iterations'=>self::QSONOFF_ITERATIONS
			),
			'controllerVsCachedController'=>array(
				'controller'=>array(
					'start'=>$controller_start_time,
					'end'=>$controller_end_time,
					'time'=>$controller_render_time
				),
				'cachedController'=>array(
					'start'=>$cachedController_start_time,
					'end'=>$cachedController_end_time,
					'time'=>$cachedController_render_time
				),
				'fastest'=>$controllerVsCachedControllerFastest,
				'diff'=>$controllerVsCachedControllerDiff,
				'gain'=>$controllerVsCachedControllerGain,
				'iterations'=>self::CVSCC_ITERATIONS
			),
			'everythingVsNothing'=>array(
				'everything'=>array(
					'start'=>$everything_start_time,
					'end'=>$everything_end_time,
					'time'=>$everything_render_time
				),
				'nothing'=>array(
					'start'=>$nothing_start_time,
					'end'=>$nothing_end_time,
					'time'=>$nothing_render_time
				),
				'fastest'=>$everythingVsNothingFastest,
				'diff'=>$everythingVsNothingDiff,
				'gain'=>$everythingVsNothingGain,
				'iterations'=>self::EVSN_ITERATIONS
			),
			'code'=>file_get_contents(__FILE__)
		));
	}

}

?>