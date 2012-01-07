<?php

// spoon charset
if(!defined('SPOON_CHARSET')) define('SPOON_CHARSET', 'utf-8');

// includes
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(dirname(dirname(__FILE__))));
require_once 'spoon/spoon.php';
require_once 'PHPUnit/Framework/TestCase.php';

class SpoonFormRadiobuttonTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var	SpoonForm
	 */
	protected $frm;

	/**
	 * @var	SpoonFormRadiobutton
	 */
	protected $rbtGender;

	public function setup()
	{
		$this->frm = new SpoonForm('radiobutton');
		$gender[] = array('label' => 'Female', 'value' => 'F');
		$gender[] = array('label' => 'Male', 'value' => 'M');
		$this->rbtGender = new SpoonFormRadiobutton('gender', $gender, 'M');
		$this->frm->add($this->rbtGender);
	}

	public function testGetChecked()
	{
		$this->assertEquals('M', $this->rbtGender->getChecked());
	}
}
