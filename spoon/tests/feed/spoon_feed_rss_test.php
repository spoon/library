<?php

// includes
require_once 'spoon/spoon.php';
require_once 'PHPUnit/Framework/TestCase.php';

class SpoonFeedRSSTest extends PHPUnit_Framework_TestCase
{
	public function testMain()
	{
		$rss = new SpoonFeedRSS('Spoon Library', 'http://feeds2.feedburner.com/spoonlibrary', 'Spoon Library - RSS feed.');
	}
}

?>