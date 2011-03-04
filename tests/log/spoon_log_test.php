<?php

// includes
require_once 'spoon/spoon.php';
require_once 'PHPUnit/Framework/TestCase.php';

// timezone
date_default_timezone_set('Europe/Brussels');

class SpoonLogTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var	SpoonLog
	 */
	private $log;

	public function setUp()
	{
		// create directory
		$directory = realpath(dirname(__FILE__) . '/..') . '/tmp/logging';
		SpoonDirectory::create($directory, 0777);

		// create instance
		$this->log = new SpoonLog('custom', $directory);
	}

	public function testGetMaxLogSize()
	{
		$this->assertEquals(10, $this->log->getMaxLogSize());
	}

	public function testSetMaxLogSize()
	{
		$this->log->setMaxLogSize(12);
		$this->assertEquals(12, $this->log->getMaxLogSize());
	}

	public function testSetPath()
	{
		$this->log->setPath('/Users/bauffman/Desktop');
		$this->assertEquals('/Users/bauffman/Desktop', $this->log->getPath());
	}

	public function testSetType()
	{
		$this->log->setType('myCustomLogging');
		$this->assertEquals('myCustomLogging', $this->log->getType());
		$this->log->setType('1337');
		$this->log->setType('my_underscores_logging');
		$this->log->setType('my-hyphen-logging');

		// attempt to set type
		try
		{
			$this->log->setType('No way hosé!');
		}

		// hopefully catch exception
		catch(Exception $e)
		{
			$this->assertType('SpoonLogException', $e);
			$this->assertObjectHasAttribute('message', $e);
			$this->assertEquals('The log type should only contain a-z, 0-9, underscores and hyphens. Your value "No way hosé!" is invalid.', $e->getMessage());
		}
	}

	public function testWrite()
	{
		$this->log->setMaxLogSize(1);
		for($i = 1; $i < 1000; $i++)
		{
			$this->log->write('We wants it, we needs it. Must have the precious. They stole it from us. Sneaky little hobbitses. Wicked, tricksy, false!');
		}
	}

	public function tearDown()
	{
		// remove directory
		$directory = realpath(dirname(__FILE__) . '/..') . '/tmp/logging';
		SpoonDirectory::delete($directory);
	}
}

?>