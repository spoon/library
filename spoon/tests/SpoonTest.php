<?php

// includes
require_once 'spoon/spoon.php';
require_once 'PHPUnit/Framework/TestCase.php';

class SpoonTest extends PHPUnit_Framework_TestCase
{
	public function testGet()
	{
		// set value
		$value = 'Speed spelled backwards is deeps.';
		$this->assertEquals(Spoon::set('stored_value', $value), $value);
		$this->assertEquals(Spoon::get('stored_value'), $value);

		// attempt to fetch non existing value
		try
		{
			$this->assertEquals('I have no idea what I am doing.', Spoon::get('my_custom_value'));
		}

		catch(SpoonException $e)
		{
			$this->assertEquals($e->getMessage(), 'An item with reference name "my_custom_value" doesn\'t exist in the registry.');
		}
	}

	public function testExists()
	{
		// set value
		Spoon::set('spoon', new stdClass());

		// check value
		$this->assertTrue(Spoon::exists('spoon'));
		$this->assertFalse(spoon::exists('foobar'));
	}

	public function testSet()
	{
		// set value
		$value = array('Davy Hellemans', 'Tijs Verkoyen', 'Dave Lens', 'Matthias Mullie');
		$this->assertEquals(Spoon::set('salad_fingers', $value), $value);

		// get rid of value
		Spoon::set('salad_fingers');
		$this->assertFalse(Spoon::exists('salad_fingers'));
	}
}

?>