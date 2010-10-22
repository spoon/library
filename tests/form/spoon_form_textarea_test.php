<?php

// spoon charset
if(!defined('SPOON_CHARSET')) define('SPOON_CHARSET', 'utf-8');

// includes
require_once 'spoon/spoon.php';
require_once 'PHPUnit/Framework/TestCase.php';

class SpoonFormTextareaTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var	SpoonForm
	 */
	private $frm;

	/**
	 * @var	SpoonFormTextarea
	 */
	private $txtMessage;

	public function setup()
	{
		$this->frm = new SpoonForm('textarea');
		$this->txtMessage = new SpoonFormTextarea('message', 'I am the default value');
		$this->frm->add($this->txtMessage);
	}

	public function testGetDefaultValue()
	{
		$this->assertEquals('I am the default value', $this->txtMessage->getDefaultValue());
	}

	public function testErrors()
	{
		$this->txtMessage->setError('You suck');
		$this->assertEquals('You suck', $this->txtMessage->getErrors());
		$this->txtMessage->addError(' cock');
		$this->assertEquals('You suck cock', $this->txtMessage->getErrors());
		$this->txtMessage->setError('');
		$this->assertEquals('', $this->txtMessage->getErrors());
	}

	public function testAttributes()
	{
		$this->txtMessage->setAttribute('rel', 'bauffman.jpg');
		$this->assertEquals('bauffman.jpg', $this->txtMessage->getAttribute('rel'));
		$this->txtMessage->setAttributes(array('id' => 'specialID'));
		$this->assertEquals(array('id' => 'specialID', 'name' => 'message', 'cols' => 62, 'rows' => 5, 'class' => 'inputTextarea', 'rel' => 'bauffman.jpg'), $this->txtMessage->getAttributes());
	}

	public function testIsFilled()
	{
		$this->assertEquals(false, $this->txtMessage->isFilled());
		$_POST['message'] = 'I am not empty';
		$this->assertEquals(true, $this->txtMessage->isFilled());
	}

	public function testIsAlphabetical()
	{
		$this->assertEquals(false, $this->txtMessage->isAlphabetical());
		$_POST['message'] = 'Bauffman';
		$this->assertEquals(true, $this->txtMessage->isAlphabetical());
	}

	public function testIsAlphaNumeric()
	{
		$_POST['message'] = 'Spaces are not allowed?';
		$this->assertEquals(false, $this->txtMessage->isAlphaNumeric());
		$_POST['message'] = 'L33t';
		$this->assertEquals(true, $this->txtMessage->isAlphaNumeric());
	}

	public function testIsMaximumCharacters()
	{
		$_POST['message'] = 'Writing tests can be pretty frakkin boring';
		$this->assertEquals(true, $this->txtMessage->isMaximumCharacters(100));
		$this->assertEquals(false, $this->txtMessage->isMaximumCharacters(10));
	}

	public function testIsMinimumCharacters()
	{
		$_POST['message'] = 'Stil pretty bored';
		$this->assertEquals(true, $this->txtMessage->isMinimumCharacters(10));
		$this->assertEquals(true, $this->txtMessage->isMinimumCharacters(2));
		$this->assertEquals(false, $this->txtMessage->isMinimumCharacters(23));
	}

	public function testGetValue()
	{
		$_POST['form'] = 'textarea';
		$_POST['message'] = '<a href="http://www.spoon-library.be">Bobby Tables, my friends call mééé</a>';
		$this->assertEquals(SpoonFilter::htmlspecialchars($_POST['message']), $this->txtMessage->getValue());
		$this->assertEquals($_POST['message'], $this->txtMessage->getValue(true));
	}
}

?>