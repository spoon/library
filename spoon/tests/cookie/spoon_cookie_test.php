<?php

require_once 'spoon/spoon.php';
require_once 'PHPUnit/Framework/TestCase.php';

class SpoonCookieTest extends PHPUnit_Framework_TestCase
{
	public function testExists()
	{
		$this->assertFalse(SpoonCookie::exists('honka_honka'));
	}

	public function testGet()
	{
		$this->assertFalse(SpoonCookie::get('why_does_my_neck_hurt'));
	}
}
