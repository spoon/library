<?php

// includes
require_once 'spoon/spoon.php';
require_once 'PHPUnit/Framework/TestCase.php';

class SpoonFileTest extends PHPUnit_Framework_TestCase
{
	public function setup()
	{
		if(!defined('TMPPATH')) define('TMPPATH', dirname(realpath(dirname(__FILE__))) .'/tmp');

		$this->existingUrl = 'http://www.spoon-library.com/downloads/1.0.3/spoon-1.0.3.zip';
		$this->nonExistingUrl = 'http://ksdgg.com/'. time() .'.txt';
		$this->destinationFile = TMPPATH .'/spoon.zip';
	}

	public function testDownload()
	{
		// download
		$this->assertEquals(true, SpoonFile::download($this->existingUrl, $this->destinationFile));

		// download again, but do not overwrite
		$this->assertEquals(false, SpoonFile::download($this->existingUrl, $this->destinationFile, false));

		// attempt to download file
		try
		{
			$this->assertEquals(false, SpoonFile::download($this->nonExistingUrl, $this->destinationFile));
		}

		// hopefully catch exception
		catch (Exception $e)
		{
			$this->assertType('SpoonFileException', $e);
			$this->assertObjectHasAttribute('message', $e);
			$this->assertEquals('The file "'. $this->nonExistingUrl .'" isn\'t available for download.', $e->getMessage());
		}
	}
}

?>