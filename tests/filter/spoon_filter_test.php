<?php

define('SPOON_CHARSET', 'utf-8');

// includes
require_once 'spoon/spoon.php';
require_once 'PHPUnit/Framework/TestCase.php';

class SpoonFilterTest extends PHPUnit_Framework_TestCase
{
	public function testArrayMapRecursive()
	{
		/* without allowedKeys parameter */
		// test array
		$testArray = array(0 => array('string1' => 'This%20is%20a%20string'), 1 => array('string2' => 'This%20is%20a%20string'));

		// expected result
		$testResult = array(0 => array('string1' => 'This is a string'), 1 => array('string2' => 'This is a string'));

		// perform test
		$this->assertEquals($testResult, SpoonFilter::arrayMapRecursive('urldecode', $testArray));


		/* with allowedKeys parameter */
		// test array
		$testArray = array(0 => serialize(array('string1' => 'spoon')), 1 => serialize(array('string2' => 'rocks')));

		// expected result
		$testResult= array(0 => 'a:1:{s:7:"string1";s:5:"spoon";}', 1 => array('string2' => 'rocks'));

		// perform test
		$this->assertEquals($testResult, SpoonFilter::arrayMapRecursive('unserialize', $testArray, '1'));


		/* with allowedKeys parameter, depth of 4 */
		// test array
		$testArray = array(0 => array('array1' => array(array('spoon' => serialize('kicks'), 'serious' => serialize('ass')))), 1 => serialize(array('string2' => 'ass')));

		// expected result
		$testResult = array(0 => array('array1' => array(array('spoon' => 's:5:"kicks";', 'serious' => 'ass'))), 1 => array('string2' => 'ass'));

		// perform test
		$this->assertEquals($testResult, SpoonFilter::arrayMapRecursive('unserialize', $testArray, array('serious', '1')));
	}

	public function testArraySortKeys()
	{
		// test array
		$testArray = array(-2 => 'Davy Hellemans', 1 => 'Tijs Verkoyen', 4 => 'Dave Lens');

		// expected result
		$expectedArray = array('Davy Hellemans', 'Tijs Verkoyen', 'Dave Lens');

		// perform test
		$this->assertEquals($expectedArray, SpoonFilter::arraySortKeys($testArray));
	}

	public function testDisableMagicQuotes()
	{
		// test input
		$_POST['name'] = "Erik\'s appetite for sex is NOT normal";

		// expected output
		$expectedOutput = 'Erik\'s appetite for sex is NOT normal';

		// disable magic quotes
		SpoonFilter::disableMagicQuotes();

		// perform test
		$this->assertEquals($expectedOutput, $_POST['name']);
	}

	public function testGetGetValue()
	{
		// setup
		$_GET['id'] = '1337';
		$_GET['type'] = 'web';
		$_GET['animal'] = 'donkey';

		// perform tests
		$this->assertEquals(0, SpoonFilter::getGetValue('category_id', null, 0, 'int'));
		$this->assertEquals(1337, SpoonFilter::getGetValue('id', null, 0, 'int'));
		$this->assertEquals('web', SpoonFilter::getGetValue('type', array('web', 'print'), 'print'));
		$this->assertEquals('whale', SpoonFilter::getGetValue('animal', array('whale', 'horse'), 'whale'));
		$this->assertEquals('donkey', SpoonFilter::getGetValue('animal', null, 'whale'));
	}

	public function testGetPostValue()
	{
		// setup
		$_POST['id'] = '1337';
		$_POST['type'] = 'web';
		$_POST['animal'] = 'donkey';

		// perform tests
		$this->assertEquals(0, SpoonFilter::getPostValue('category_id', null, 0, 'int'));
		$this->assertEquals(1337, SpoonFilter::getPostValue('id', null, 0, 'int'));
		$this->assertEquals('web', SpoonFilter::getPostValue('type', array('web', 'print'), 'print'));
		$this->assertEquals('whale', SpoonFilter::getPostValue('animal', array('whale', 'horse'), 'whale'));
		$this->assertEquals('donkey', SpoonFilter::getPostValue('animal', null, 'whale'));
	}

	public function testGetValue()
	{
		// setup
		$id = '1337';
		$type = 'web';
		$animal = 'donkey';
		$animals = array('1337', 'web', 'donkey');

		// perform tests
		$this->assertEquals(1337, SpoonFilter::getValue($id, null, 0, 'int'));
		$this->assertEquals('web', SpoonFilter::getValue($type, array('web', 'print'), 'print'));
		$this->assertEquals('whale', SpoonFilter::getValue($animal, array('whale', 'horse'), 'whale'));
		$this->assertEquals('donkey', SpoonFilter::getValue($animal, null, 'whale'));
		$this->assertEquals(array('1337', 'web', 'donkey'), SpoonFilter::getValue($animals, null, null, 'array'));
		$this->assertEquals(array('1337', 'web'), SpoonFilter::getValue($animals, array('1337', 'web'), array('soep'), 'array'));
		$this->assertEquals(array('soep'), SpoonFilter::getValue(array('blikken doos'), array('1337', 'web'), array('soep'), 'array'));
	}

	public function testHtmlentities()
	{
		// setup
		$input = 'Ik heb géén bananen vandaag';
		$expectedResult = 'Ik heb g&eacute;&eacute;n bananen vandaag';

		// perform test
		$this->assertEquals($expectedResult, SpoonFilter::htmlentities(utf8_decode($input), 'iso-8859-1'));
		$this->assertEquals($expectedResult, SpoonFilter::htmlentities($input, 'utf-8'));
	}

	public function testHtmlspecialchars()
	{
		// setup
		$input = '<a href="http://www.spoon-library.be">Ik heb géén bananen vandaag</a>';
		$expectedResult = '&lt;a href=&quot;http://www.spoon-library.be&quot;&gt;Ik heb géén bananen vandaag&lt;/a&gt;';

		// perform test
		$this->assertEquals($expectedResult, SpoonFilter::htmlspecialchars($input, 'utf-8'));
	}

	public function testHtmlentitiesDecode()
	{
		// setup
		$input = 'Ik heb g&eacute;&eacute;n bananen vandaag';
		$expectedResult = 'Ik heb géén bananen vandaag';

		// perform test
		$this->assertEquals(utf8_decode($expectedResult), SpoonFilter::htmlentitiesDecode(utf8_decode($input), 'iso-8859-1'));
		$this->assertEquals($expectedResult, SpoonFilter::htmlentitiesDecode($input, 'utf-8'));
	}

	public function testIsAlphabetical()
	{
		$this->assertEquals(true, SpoonFilter::isAlphabetical('geen'));
		$this->assertEquals(true, SpoonFilter::isAlphabetical('GeeN'));
		$this->assertEquals(false, SpoonFilter::isAlphabetical('géén'));
		$this->assertEquals(false, SpoonFilter::isAlphabetical('gééN'));
	}

	public function testIsAlphaNumeric()
	{
		$this->assertEquals(true, SpoonFilter::isAlphaNumeric('John09'));
		$this->assertEquals(false, SpoonFilter::isAlphaNumeric('Johan Mayer 007'));
	}

	public function testIsBetween()
	{
		$this->assertEquals(true, SpoonFilter::isBetween(1, 10, 5));
		$this->assertEquals(true, SpoonFilter::isBetween(1, 10, 1));
		$this->assertEquals(true, SpoonFilter::isBetween(1, 10, 10));
		$this->assertEquals(false, SpoonFilter::isBetween(1, 10, -1));
		$this->assertEquals(false, SpoonFilter::isBetween(1, 10, 0));
		$this->assertEquals(false, SpoonFilter::isBetween(1, 10, 12));
	}

	public function testIsBool()
	{
		$this->assertEquals(true, SpoonFilter::isBool('true'));
		$this->assertEquals(true, SpoonFilter::isBool(1));
		$this->assertEquals(true, SpoonFilter::isBool('on'));
		$this->assertEquals(true, SpoonFilter::isBool('yes'));
		$this->assertEquals(true, SpoonFilter::isBool('false'));
		$this->assertEquals(true, SpoonFilter::isBool(0));
		$this->assertEquals(false, SpoonFilter::isBool(100));
		$this->assertEquals(false, SpoonFilter::isBool(900));
		$this->assertEquals(true, SpoonFilter::isBool(090));
	}

	public function testIsDigital()
	{
		$this->assertEquals(true, SpoonFilter::isDigital('010192029'));
		$this->assertEquals(true, SpoonFilter::isDigital(1337));
		$this->assertEquals(false, SpoonFilter::isDigital('I can has cheezeburger'));
	}

	public function testIsEmail()
	{
		$this->assertEquals(true, SpoonFilter::isEmail('erik@spoon-library.be'));
		$this->assertEquals(true, SpoonFilter::isEmail('erik+bauffman@spoon-library.be'));
		$this->assertEquals(true, SpoonFilter::isEmail('erik-bauffman@spoon-library.be'));
		$this->assertEquals(true, SpoonFilter::isEmail('erik.bauffman@spoon-library.be'));
		$this->assertEquals(true, SpoonFilter::isEmail('a.osterhaus@erasmusnc.nl'));
		$this->assertEquals(true, SpoonFilter::isEmail('asmonto@umich.edu'));
	}

	public function testIsEven()
	{
		$this->assertEquals(true, SpoonFilter::isEven(0));
		$this->assertEquals(false, SpoonFilter::isEven(1));
		$this->assertEquals(true, SpoonFilter::isEven(10901920));
		$this->assertEquals(false, SpoonFilter::isEven(-1337));
	}

	public function testIsFilename()
	{
		$this->assertEquals(true, SpoonFilter::isFilename('test.tpl'));
		$this->assertEquals(true, SpoonFilter::isFilename('spoon_template.php'));
		$this->assertEquals(false, SpoonFilter::isFilename('/Users/bauffman/Desktop/test.txt'));
	}

	public function testIsFloat()
	{
		$this->assertEquals(true, SpoonFilter::isFloat(1));
		$this->assertEquals(false, SpoonFilter::isFloat('a'));
		$this->assertEquals(true, SpoonFilter::isFloat(1e10));
		$this->assertEquals(true, SpoonFilter::isFloat('1e10'));
		$this->assertEquals(false, SpoonFilter::isFloat('1a10'));
		$this->assertEquals(true, SpoonFilter::isFloat(1.337));
		$this->assertEquals(true, SpoonFilter::isFloat(-1.337));
		$this->assertEquals(true, SpoonFilter::isFloat(100));
		$this->assertEquals(true, SpoonFilter::isFloat(-100));
		$this->assertEquals(false, SpoonFilter::isFloat('1.,35'));
		$this->assertEquals(false, SpoonFilter::isFloat('1,.35'));
		$this->assertTrue(SpoonFilter::isFloat('1,35', true));
		$this->assertTrue(SpoonFilter::isFloat('-1,35', true));
	}

	public function testIsGreaterThan()
	{
		$this->assertEquals(true, SpoonFilter::isGreaterThan(1, 10));
		$this->assertEquals(true, SpoonFilter::isGreaterThan(-10, -1));
		$this->assertEquals(true, SpoonFilter::isGreaterThan(-1, 10));
		$this->assertEquals(false, SpoonFilter::isGreaterThan(1, -10));
		$this->assertEquals(false, SpoonFilter::isGreaterThan(0, 0));
	}

	public function testIsInteger()
	{
		$this->assertEquals(true, SpoonFilter::isInteger(0));
		$this->assertEquals(true, SpoonFilter::isInteger(1));
		$this->assertEquals(true, SpoonFilter::isInteger(1234567890));
		$this->assertEquals(true, SpoonFilter::isInteger(-1234567890));
		$this->assertEquals(false, SpoonFilter::isInteger(1.337));
		$this->assertEquals(false, SpoonFilter::isInteger(-1.337));
	}

	public function testIsInternalReferrer()
	{
		// reset referrer
		unset($_SERVER['HTTP_REFERER']);
		$this->assertEquals(true, SpoonFilter::isInternalReferrer());

		// new referrer
		$_SERVER['HTTP_REFERER'] = 'http://www.spoon-library.com/about-us';
		$_SERVER['HTTP_HOST'] = 'spoon-library.com';
		$this->assertEquals(true, SpoonFilter::isInternalReferrer(array('spoon-library.com')));

		// multiple domains
		$this->assertEquals(true, SpoonFilter::isInternalReferrer(array('docs.spoon-library.com', 'blog.spoon-library.com', 'spoon-library.com')));

		// incorrect!
		$this->assertEquals(false, SpoonFilter::isInternalReferrer(array('rotten.com')));
		$this->assertEquals(false, SpoonFilter::isInternalReferrer(array('rotten.com', 'rotn.com')));
	}

	public function testIsIP()
	{
		$this->assertEquals(true, SpoonFilter::isIp('127.0.0.1'));
		$this->assertEquals(true, SpoonFilter::isIp('192.168.1.101'));
	}

	public function testIsMaximum()
	{
		$this->assertEquals(true, SpoonFilter::isMaximum(10, 1));
		$this->assertEquals(true, SpoonFilter::isMaximum(10, 10));
		$this->assertEquals(true, SpoonFilter::isMaximum(-10, -10));
		$this->assertEquals(false, SpoonFilter::isMaximum(100, 101));
		$this->assertEquals(false, SpoonFilter::isMaximum(-100, -99));
	}

	public function testIsMaximumCharacters()
	{
		$string = 'Ik heb er géén gedacht van';
		$this->assertEquals(true, SpoonFilter::isMaximumCharacters(26, $string, 'utf-8'));
		$this->assertEquals(false, SpoonFilter::isMaximumCharacters(10, $string, 'utf-8'));
		$this->assertEquals(true, SpoonFilter::isMaximumCharacters(26, utf8_decode($string), 'iso-8859-1'));
	}

	public function testIsMinimum()
	{
		$this->assertEquals(false, SpoonFilter::isMinimum(10, 1));
		$this->assertEquals(true, SpoonFilter::isMinimum(10, 10));
		$this->assertEquals(true, SpoonFilter::isMinimum(-10, -10));
		$this->assertEquals(true, SpoonFilter::isMinimum(100, 101));
		$this->assertEquals(true, SpoonFilter::isMinimum(-100, -99));
	}

	public function testIsMinimumCharacters()
	{
		$string = 'Ik heb er géén gedacht van';
		$this->assertEquals(true, SpoonFilter::isMinimumCharacters(10, $string, 'utf-8'));
		$this->assertEquals(false, SpoonFilter::isMinimumCharacters(30, $string, 'utf-8'));
		$this->assertEquals(true, SpoonFilter::isMinimumCharacters(10, utf8_decode($string), 'iso-8859-1'));
	}

	public function testIsNumeric()
	{
		$this->assertEquals(true, SpoonFilter::isNumeric('010192029'));
		$this->assertEquals(true, SpoonFilter::isNumeric(1337));
		$this->assertEquals(false, SpoonFilter::isNumeric('I can has cheezeburger'));
	}

	public function testIsOdd()
	{
		$this->assertEquals(false, SpoonFilter::isOdd(0));
		$this->assertEquals(true, SpoonFilter::isOdd(1));
		$this->assertEquals(false, SpoonFilter::isOdd(10901920));
		$this->assertEquals(true, SpoonFilter::isOdd(-1337));
	}

	public function testIsSmallerThan()
	{
		$this->assertEquals(false, SpoonFilter::isSmallerThan(1, 10));
		$this->assertEquals(false, SpoonFilter::isSmallerThan(-10, -1));
		$this->assertEquals(false, SpoonFilter::isSmallerThan(-1, 10));
		$this->assertEquals(true, SpoonFilter::isSmallerThan(1, -10));
		$this->assertEquals(false, SpoonFilter::isSmallerThan(0, 0));
	}

	public function testIsString()
	{
		$this->assertEquals(true, SpoonFilter::isString('This should qualify as a string.'));
	}

	public function testIsValidAgainstRegexp()
	{
		$this->assertEquals(true, SpoonFilter::isValidAgainstRegexp('/([a-z]+)/', 'alphabet'));
		$this->assertEquals(false, SpoonFilter::isValidAgainstRegexp('/(boobies)/', 'I like babies'));
	}

	public function testIsValidRegexp()
	{
		$this->assertEquals(true, SpoonFilter::isValidRegexp('/boobies/'));
	}

	public function testToCamelCase()
	{
		$this->assertEquals('SpoonLibraryRocks', SpoonFilter::toCamelCase('Spoon library rocks', ' '));
		$this->assertEquals('SpoonLibraryRocks', SpoonFilter::toCamelCase('spoon_library_Rocks'));
		$this->assertEquals('SpoonLibraryRocks', SpoonFilter::toCamelCase('spoon_libraryRocks'));
	}

	public function testReplaceURLsWithAnchors()
	{
		$tlds = array(	'aero', 'asia', 'biz', 'cat', 'com', 'coop', 'edu', 'gov', 'info', 'int', 'jobs', 'mil', 'mobi',
									'museum', 'name', 'net', 'org', 'pro', 'tel', 'travel', 'ac', 'ad', 'ae', 'af', 'ag', 'ai', 'al',
									'am', 'an', 'ao', 'aq', 'ar', 'as', 'at', 'au', 'aw', 'ax', 'az', 'ba', 'bb', 'bd' ,'be', 'bf', 'bg',
									'bh', 'bi', 'bj', 'bm', 'bn', 'bo', 'br', 'bs', 'bt', 'bv', 'bw', 'by' ,'bz', 'ca', 'cc' ,'cd', 'cf',
									'cg', 'ch', 'ci', 'ck', 'cl', 'cm', 'cn', 'co', 'cr', 'cu', 'cv', 'cx', 'cy', 'cz', 'cz', 'de', 'dj', 'dk',
									'dm', 'do', 'dz', 'ec', 'ee', 'eg', 'er', 'es', 'et', 'eu', 'fi', 'fj', 'fk', 'fm', 'fo', 'fr', 'ga', 'gb',
									'gd', 'ge', 'gf', 'gg', 'gh', 'gi', 'gl', 'gm', 'gn', 'gp', 'gq', 'gr', 'gs', 'gt', 'gu', 'gw', 'gy', 'hk',
									'hm', 'hn', 'hr', 'ht', 'hu', 'id', 'ie', 'il', 'im', 'in', 'io', 'iq', 'ir', 'is', 'it', 'je', 'jm', 'jo',
									'jp', 'ke', 'kg', 'kh', 'ki', 'km', 'kn', 'kp', 'kr', 'kw', 'ky', 'kz', 'la', 'lb', 'lc', 'li', 'lk', 'lr',
									'ls', 'lt', 'lu', 'lv', 'ly', 'ma', 'mc', 'md', 'me', 'mg', 'mh', 'mk', 'ml', 'mn', 'mn', 'mo', 'mp', 'mr',
									'ms', 'mt', 'mu', 'mv', 'mw', 'mx', 'my', 'mz', 'na', 'nc', 'ne', 'nf', 'ng', 'ni', 'nl', 'no', 'np', 'nr',
									'nu', 'nz', 'nom', 'pa', 'pe', 'pf', 'pg', 'ph', 'pk', 'pl', 'pm', 'pn', 'pr', 'ps', 'pt', 'pw', 'py', 'qa',
									're', 'ra', 'rs', 'ru', 'rw', 'sa', 'sb', 'sc', 'sd', 'se', 'sg', 'sh', 'si', 'sj', 'sj', 'sk', 'sl', 'sm',
									'sn', 'so', 'sr', 'st', 'su', 'sv', 'sy', 'sz', 'tc', 'td', 'tf', 'tg', 'th', 'tj', 'tk', 'tl', 'tm', 'tn',
									'to', 'tp', 'tr', 'tt', 'tv', 'tw', 'tz', 'ua', 'ug', 'uk', 'us', 'uy', 'uz', 'va', 'vc', 've', 'vg', 'vi',
									'vn', 'vu', 'wf', 'ws', 'ye', 'yt', 'yu', 'za', 'zm', 'zw', 'arpa');

		foreach($tlds as $tld)
		{
			$this->assertEquals('verkeerde link: www.link.'. $tld .'l', SpoonFilter::replaceURLsWithAnchors('verkeerde link: www.link.'. $tld .'l', false));
			$this->assertEquals('zonder http: <a href="http://www.link.'. $tld .'">www.link.'. $tld .'</a>', SpoonFilter::replaceURLsWithAnchors('zonder http: www.link.'. $tld, false));
			$this->assertEquals('met http: <a href="http://www.link.'. $tld .'">http://www.link.'. $tld .'</a>', SpoonFilter::replaceURLsWithAnchors('met http: http://www.link.'. $tld, false));

			// port
			$this->assertEquals('verkeerde link: www.link.'. $tld .'l:80', SpoonFilter::replaceURLsWithAnchors('verkeerde link: www.link.'. $tld .'l:80', false));
			$this->assertEquals('zonder http: <a href="http://www.link.'. $tld .':80">www.link.'. $tld .':80</a>', SpoonFilter::replaceURLsWithAnchors('zonder http: www.link.'. $tld .':80', false));
			$this->assertEquals('met http: <a href="http://www.link.'. $tld .':80">http://www.link.'. $tld .':80</a>', SpoonFilter::replaceURLsWithAnchors('met http: http://www.link.'. $tld .':80', false));

			// querystring
			$this->assertEquals('verkeerde link: www.link.'. $tld .'l?m=12&b=0%20d', SpoonFilter::replaceURLsWithAnchors('verkeerde link: www.link.'. $tld .'l?m=12&b=0%20d', false));
			$this->assertEquals('zonder http: <a href="http://www.link.'. $tld .'?m=12&b=0%20d">www.link.'. $tld .'?m=12&b=0%20d</a>', SpoonFilter::replaceURLsWithAnchors('zonder http: www.link.'. $tld .'?m=12&b=0%20d', false));
			$this->assertEquals('met http: <a href="http://www.link.'. $tld .'?m=12&b=0%20d">http://www.link.'. $tld .'?m=12&b=0%20d</a>', SpoonFilter::replaceURLsWithAnchors('met http: http://www.link.'. $tld .'?m=12&b=0%20d', false));

			// folder
			$this->assertEquals('verkeerde link: www.link.'. $tld .'l/mekker', SpoonFilter::replaceURLsWithAnchors('verkeerde link: www.link.'. $tld .'l/mekker', false));
			$this->assertEquals('zonder http: <a href="http://www.link.'. $tld .'/mekker">www.link.'. $tld .'/mekker</a>', SpoonFilter::replaceURLsWithAnchors('zonder http: www.link.'. $tld .'/mekker', false));
			$this->assertEquals('met http: <a href="http://www.link.'. $tld .'/mekker">http://www.link.'. $tld .'/mekker</a>', SpoonFilter::replaceURLsWithAnchors('met http: http://www.link.'. $tld .'/mekker', false));
		}

		// no follow
		foreach($tlds as $tld)
		{
			$this->assertEquals('verkeerde link: www.link.'. $tld .'l', SpoonFilter::replaceURLsWithAnchors('verkeerde link: www.link.'. $tld .'l'));
			$this->assertEquals('zonder http: <a rel="nofollow" href="http://www.link.'. $tld .'">www.link.'. $tld .'</a>', SpoonFilter::replaceURLsWithAnchors('zonder http: www.link.'. $tld));
			$this->assertEquals('met http: <a rel="nofollow" href="http://www.link.'. $tld .'">http://www.link.'. $tld .'</a>', SpoonFilter::replaceURLsWithAnchors('met http: http://www.link.'. $tld));

			// port
			$this->assertEquals('verkeerde link: www.link.'. $tld .'l:80', SpoonFilter::replaceURLsWithAnchors('verkeerde link: www.link.'. $tld .'l:80'));
			$this->assertEquals('zonder http: <a rel="nofollow" href="http://www.link.'. $tld .':80">www.link.'. $tld .':80</a>', SpoonFilter::replaceURLsWithAnchors('zonder http: www.link.'. $tld .':80'));
			$this->assertEquals('met http: <a rel="nofollow" href="http://www.link.'. $tld .':80">http://www.link.'. $tld .':80</a>', SpoonFilter::replaceURLsWithAnchors('met http: http://www.link.'. $tld .':80'));

			// querystring
			$this->assertEquals('verkeerde link: www.link.'. $tld .'l?m=12&b=0%20d', SpoonFilter::replaceURLsWithAnchors('verkeerde link: www.link.'. $tld .'l?m=12&b=0%20d'));
			$this->assertEquals('zonder http: <a rel="nofollow" href="http://www.link.'. $tld .'?m=12&b=0%20d">www.link.'. $tld .'?m=12&b=0%20d</a>', SpoonFilter::replaceURLsWithAnchors('zonder http: www.link.'. $tld .'?m=12&b=0%20d'));
			$this->assertEquals('met http: <a rel="nofollow" href="http://www.link.'. $tld .'?m=12&b=0%20d">http://www.link.'. $tld .'?m=12&b=0%20d</a>', SpoonFilter::replaceURLsWithAnchors('met http: http://www.link.'. $tld .'?m=12&b=0%20d'));

			// folder
			$this->assertEquals('verkeerde link: www.link.'. $tld .'l/mekker', SpoonFilter::replaceURLsWithAnchors('verkeerde link: www.link.'. $tld .'l/mekker'));
			$this->assertEquals('zonder http: <a rel="nofollow" href="http://www.link.'. $tld .'/mekker">www.link.'. $tld .'/mekker</a>', SpoonFilter::replaceURLsWithAnchors('zonder http: www.link.'. $tld .'/mekker'));
			$this->assertEquals('met http: <a rel="nofollow" href="http://www.link.'. $tld .'/mekker">http://www.link.'. $tld .'/mekker</a>', SpoonFilter::replaceURLsWithAnchors('met http: http://www.link.'. $tld .'/mekker'));
		}	}

	public function testStripHTML()
	{
		$html = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>Fork CMS</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body>
	<p>
		<a href="http://www.spoon-library.be">Spoon Library</a>
	</p>
</body>
</html>';

		$this->assertEquals('Spoon Library', SpoonFilter::stripHTML($html));
		$this->assertEquals('<a href="http://www.spoon-library.be">Spoon Library</a>', SpoonFilter::stripHTML($html, '<a>'));
		$this->assertEquals('Spoon Library (http://www.spoon-library.be)', SpoonFilter::stripHTML($html, null, true));
	}


	public function testUrlise()
	{
		$this->assertEquals('geen-bananen', SpoonFilter::urlise('géén bananen'));
		$this->assertEquals('tom-and-jerry', SpoonFilter::urlise('Tom & Jerry'));
	}
}

?>