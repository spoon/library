<?php

// for string replacement purposes
define('SPOON_CHARSET', 'utf-8');

// includes
require_once 'spoon/spoon.php';
require_once 'PHPUnit/Framework/TestCase.php';

class SpoonThumbnailTest extends PHPUnit_Framework_TestCase
{
	public function testIsSupportedFileType()
	{
		$this->assertEquals(true, SpoonThumbnail::isSupportedFileType(dirname(dirname(realpath(__FILE__))) . '/tmp/spoon.jpg'));
	}
}

?>