<?php

// spoon charset
if(!defined('SPOON_CHARSET')) define('SPOON_CHARSET', 'utf-8');

// includes
require_once 'spoon/spoon.php';
require_once 'PHPUnit/Framework/TestCase.php';

class SpoonFormDropdownTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var	SpoonForm
	 */
	private $frm;

	/**
	 * @var	SpoonFormDropdown
	 */
	private $ddmSingle, $ddmMultiple;

	public function setup()
	{
		$this->frm = new SpoonForm('dropdown');
		$this->ddmSingle = new SpoonFormDropdown('single', array(1 => 'Davy Hellemans', 'Tys Verkoyen', 'Dave Lens'));
		$this->ddmMultiple = new SpoonFormDropdown('multiple', array(1 => 'Swimming', 'Running', 'Cycling', 'Boxing', 'Slackin'), null, true);
		$this->frm->add($this->ddmSingle, $this->ddmMultiple);
	}

	public function testAttributes()
	{
		$this->ddmSingle->setAttribute('rel', 'bauffman.jpg');
		$this->assertEquals('bauffman.jpg', $this->ddmSingle->getAttribute('rel'));
		$this->ddmSingle->setAttributes(array('id' => 'specialID'));
		$this->assertEquals(array('id' => 'specialID', 'name' => 'single', 'class' => 'inputDropdown', 'size' => 1, 'rel' => 'bauffman.jpg'), $this->ddmSingle->getAttributes());
		$this->ddmMultiple->setAttribute('rel', 'bauffman.jpg');
		$this->assertEquals('bauffman.jpg', $this->ddmMultiple->getAttribute('rel'));
		$this->ddmMultiple->setAttributes(array('id' => 'specialID'));
		$this->assertEquals(array('id' => 'specialID', 'name' => 'multiple', 'class' => 'inputDropdown', 'size' => 1, 'rel' => 'bauffman.jpg'), $this->ddmMultiple->getAttributes());
	}

	public function testIsFilled()
	{
		$this->assertEquals(false, $this->ddmSingle->isFilled());
		$_POST['single'] = '2';
		$_POST['form'] = 'dropdown';
		$this->assertEquals(true, $this->ddmSingle->isFilled());
		$this->assertEquals(false, $this->ddmMultiple->isFilled());
		$_POST['multiple'] = array('1', '2');
		$this->assertEquals(true, $this->ddmMultiple->isFilled());
	}

	public function testGetValue()
	{
		$_POST['form'] = 'dropdown';
		$_POST['single'] = '1';
		$_POST['multiple'] = array('1', '2', '3');
		$this->assertEquals($_POST['single'], $this->ddmSingle->getValue());
		$this->assertEquals($_POST['multiple'], $this->ddmMultiple->getValue());
	}
}

?>