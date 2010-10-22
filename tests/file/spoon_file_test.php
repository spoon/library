<?php

// includes
require_once 'spoon/spoon.php';
require_once 'PHPUnit/Framework/TestCase.php';

class SpoonFileTest extends PHPUnit_Framework_TestCase
{
	public function setup()
	{
		if(!defined('TMPPATH')) define('TMPPATH', realpath(dirname(__FILE__) .'/../tmp'));

		$this->existingUrl = 'http://www.spoon-library.com/downloads/1.0.3/spoon-1.0.3.zip';
		$this->existingUrlFollowLocation = 'http://spoon-library.be/files/1.0.3/spoon-1.0.3.zip';
		$this->notExistingUrl = 'http://ksdgg.com/'. time() .'.txt';
		$this->destinationFile = TMPPATH .'/temp.zip';
	}

	public function testDownload()
	{
		// real download
		$this->assertEquals(true, SpoonFile::download($this->existingUrl, $this->destinationFile));

		// real download
		$this->assertEquals(true, SpoonFile::download($this->existingUrlFollowLocation, $this->destinationFile));

		// download again, but do not overwrite
		$this->assertEquals(false, SpoonFile::download($this->existingUrl, $this->destinationFile, false));

		// file isn't available
		try
		{
			$this->assertEquals(false, SpoonFile::download($this->notExistingUrl, $this->destinationFile));
		}
		catch (Exception $e)
		{
			$this->assertType('SpoonFileException', $e);
			$this->assertObjectHasAttribute('message', $e);
			$this->assertEquals('The file "'. $this->notExistingUrl .'" isn\'t available for download.', $e->getMessage());
		}
	}

	public function testMove()
	{
		// create file
		SpoonFile::setContent(TMPPATH .'/test.txt', 'TEST');

		// move a file within same folder
		$this->assertEquals(true, SpoonFile::move(TMPPATH .'/test.txt', TMPPATH .'/test.log'));

		// move it to a none existing folder
		$this->assertEquals(true, SpoonFile::move(TMPPATH .'/test.log', TMPPATH .'/logs/archived.log', true, false));
	}

	public function tearDown()
	{
		SpoonDirectory::delete(TMPPATH);
		SpoonDirectory::create(TMPPATH);
	}
}

?>