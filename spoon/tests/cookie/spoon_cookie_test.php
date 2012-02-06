<?php

// includes
require_once 'spoon/spoon.php';
require_once 'PHPUnit/Framework/TestCase.php';

class SpoonCookieTest extends PHPUnit_Framework_TestCase
{
	public function testDelete()
	{
		SpoonCookie::set('key', 'value');
		SpoonCookie::delete('key');
		$this->assertFalse(false, SpoonCookie::exists('key'));
	}

	public function testExists()
	{
		$this->assertFalse(SpoonCookie::exists('honka_honka'));
	}

	public function testGet()
	{
		$this->assertFalse(SpoonCookie::get('why_does_my_neck_hurt'));
	}

	public function testSet()
	{
		$this->assertTrue(SpoonCookie::set('name', 'Spoon Library'));
	}
}

?>