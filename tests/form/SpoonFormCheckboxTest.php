<?php

// spoon charset
if(!defined('SPOON_CHARSET')) define('SPOON_CHARSET', 'utf-8');

// includes
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(dirname(dirname(__FILE__))));
require_once 'spoon/spoon.php';
require_once 'PHPUnit/Framework/TestCase.php';

class SpoonFormCheckboxTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var	SpoonForm
	 */
	protected $frm;

	/**
	 * @var	SpoonFormCheckbox
	 */
	protected $chkAgree;

	public function setup()
	{
		$this->frm = new SpoonForm('checkbox');
		$this->chkAgree = new SpoonFormCheckbox('agree', true);
		$this->frm->add($this->chkAgree);
	}

	public function testGetChecked()
	{
		$this->assertEquals(true, $this->chkAgree->getChecked());
		$this->chkAgree->setChecked(false);
		$this->assertEquals(false, $this->chkAgree->getChecked());
	}

	public function testAttributes()
	{
		$this->chkAgree->setAttribute('rel', 'bauffman.jpg');
		$this->assertEquals('bauffman.jpg', $this->chkAgree->getAttribute('rel'));
		$this->chkAgree->setAttributes(array('id' => 'specialID'));
		$this->assertEquals(array('id' => 'specialID', 'name' => 'agree', 'class' => 'inputCheckbox', 'rel' => 'bauffman.jpg'), $this->chkAgree->getAttributes());
	}

	public function testGetValue()
	{
		$this->assertEquals(false, $this->chkAgree->getValue());
		$_POST['form'] = 'checkbox';
		$_POST['agree'] = 'Y';
		$this->assertEquals(true, $this->chkAgree->getValue());
	}
}
