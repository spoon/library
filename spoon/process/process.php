<?php
/**
 * Spoon Library
 *
 * This source file is part of the Spoon Library. More information,
 * documentation and tutorials can be found @ http://www.spoon-library.com
 *
 * @package		spoon
 * @subpackage	process
 *
 *
 * @author		Davy Hellemans <davy@spoon-library.com>
 * @author 		Tijs Verkoyen <tijs@spoon-library.com>
 * @author		Dave Lens <dave@spoon-library.com>
 * @author		Matthias Mullie <matthias@spoon-library.com>
 * @since		1.2.1
 */


/**
 * This base class provides methods used to launch seperate PHP processes through the webserver.
 * PS: make sure that you don't exceed the time limit in the seperate process, take care of that yourself!
 * Note: I've opted to use fsockopen to call the new process. The big advantage is that it should work on most servers in 1 uniform way, whereas proc_open or popen do not.
 * 		The advantage of using fsockopen (= going through the webserver) is also its main drawback: website limitations (suck as time limit, execution time, ..) will also occur, so keep that in mind!
 *
 * @package		spoon
 * @subpackage	process
 *
 *
 * @author		Matthias Mullie <matthias@spoon-library.com>
 * @since		1.2.1
 */
class SpoonProcess
{
	/**
	 * The HTTP headers from our call
	 *
	 * @var		string
	 */
	private $headers;


	/**
	 * The POST data we'll be sending
	 *
	 * @var array
	 */
	private $post;



	/**
	 * Connection resource
	 *
	 * @var		resource
	 */
	private $resource;


	/**
	 * The result from our call
	 *
	 * @var		string
	 */
	private $result;


	/**
	 * The url we're calling
	 *
	 * @var		string
	 */
	private $url;


	/**
	 * Construct class
	 *
	 * @return	void
	 * @param	string $url					The target url
	 * @param	array[optional] $post
	 */
	public function __construct($url, $post = array())
	{
		// redefine variables
		$this->url = (string) $url;
		$this->post = (array) $post;

		// make the call
		$this->call();
	}


	/**
	 * Call
	 *
	 * @return	void		Make the call.
	 */
	private function call()
	{
		// parse url
		$scheme = parse_url($this->url, PHP_URL_SCHEME);
		$host = parse_url($this->url, PHP_URL_HOST);
		$port = parse_url($this->url, PHP_URL_PORT);
		$user = parse_url($this->url, PHP_URL_USER);
		$pass = parse_url($this->url, PHP_URL_PASS);
		$path = parse_url($this->url, PHP_URL_PATH);
		$query = parse_url($this->url, PHP_URL_QUERY);

		// only HTTP is supported
		if($scheme != 'http') throw new SpoonException('Scheme "'. $scheme .'" not allowed: only HTTP is supported.');

		// get current port
		if($port == '') $port = $_SERVER['SERVER_PORT'];

		// prepare url
		$file = $path . ($query ? '?'. $query : '');

		// well, this is our host..
		$hostname = $host;

		// gethostbyname caches host, fsockopen doesn't, so this is the better solution
		if(filter_var($host, FILTER_VALIDATE_IP) === false) $host = gethostbyname($host);

		// open resource (and thus create new process)
		$this->resource = fsockopen($host, (int) $port);

		// check if error
		if(!$this->resource) throw new SpoonException('Could not open url "'. $this->url .'".');
		else
		{
			// format POST data
			$post = http_build_query($this->post);

			// send data
			fwrite($this->resource, 'POST '. $file ." HTTP/1.0\r\n"); // 1.0 so we don't receive chunked data
	        fwrite($this->resource, 'Host: '. $hostname ."\r\n");
			fwrite($this->resource, 'User-Agent: Spoon Library '. SPOON_VERSION ."\r\n");
			if($user || $pass) fwrite($this->resource, 'Authorization: Basic '. base64_encode($user .':'. $pass) ."\r\n");
	        fwrite($this->resource, "Content-type: application/x-www-form-urlencoded\r\n");
	        fwrite($this->resource, 'Content-length: '. strlen($post) ."\r\n");
	        fwrite($this->resource, "Connection: close\r\n\r\n");
	        fwrite($this->resource, $post ."\r\n\r\n");

	        // set blocking off
			stream_set_blocking($this->resource, 0);

			// extend stream timeout (stream should not timeout because then feof - neccessary to determine if the stream is still going - will also return true)
			stream_set_timeout($this->resource, PHP_INT_MAX);
		}
	}


	/**
	 * Check if the call is initiated by another script on our server (and not someone
	 * trying to flood our webserver)
	 *
	 * @return bool
	 */
	public static function isAuthorized()
	{
		// protection against cross-site scripting
		if($_SERVER['REMOTE_ADDR'] != gethostbyname($_SERVER['SERVER_NAME'])) return false;

		return true;
	}


	/**
	 * Check if the process is still running (and gather the return data in the meantime ;) )
	 *
	 * Note: some webservers may abort scripts after X amount of time, or may display an internal
	 * server error (500) even though the script still continues
	 *
	 * @return	bool
	 */
	public function isRunning()
	{
		// no more resource = we're already done
		if(!$this->resource) return false;

		// extend time limit
		set_time_limit(0);

		// get the content
		$content = stream_get_contents($this->resource);

		// check if error
		if($content === false) throw new SpoonException('Could not read data from "'. $this->url .'".');
		else $this->result .= $content;

		// we've reached the end of our file
		if(feof($this->resource))
		{
			// clise handle
			fclose($this->resource);

			// parse the result
			return $this->parseResult();
		}

		// process must still be running
		return true;
	}


	/**
	 * Did we already receive the result from our process?
	 *
	 * @return	bool
	 */
	public function hasResult()
	{
		return $this->isRunning() === false && $this->result !== null;
	}


	/**
	 * Read what our process returned
	 *
	 * @return	string						The returned result from our process
	 */
	public function getResult()
	{
		// check if process is still running (and wait for it if it has not yet been completed)
		while($this->isRunning())
		{
			// we're waiting untill the process has completed
		}

		// return the result
		return $this->result;
	}


	/**
	 * Parse the received result
	 *
	 * @return	bool
	 */
	private function parseResult()
	{
		// reset resource
		$this->resource = null;

		// no result retrieved?
		if(!$this->result) throw new SpoonException('No result received from "'. $this->url .'".');

		// split header from content
		$split = strpos($this->result, "\r\n\r\n");
		$this->headers = substr($this->result, 0, $split);
		$this->result = substr($this->result, $split + strlen("\r\n\r\n"));

		// no headers?
		if(!$this->headers) throw new SpoonException('No headers received from "'. $this->url .'".');

		// reformat headers
		$this->headers = explode("\r\n", $this->headers);

		// get HTTP status
		preg_match('/HTTP\/1.[01] ([0-9]*)/', $this->headers[0], $httpStatus);

		// http status
		$httpStatus = $httpStatus[1];

		// interpret HTTP status code
		switch($httpStatus)
		{
			// 301, 302 = redirect
			case '301':
			case '302':
				// reset result
				$this->result = null;

				// set the new url
				foreach($this->headers as $header)
				{
					if(strpos($header, 'Location: ') === 0)
					{
						// get new location
						$this->url = substr($header, strlen('Location: '));

						// make the new call
						$this->call();

						// true = we're still running
						return true;
					}
				}

				// location not found?
				throw new SpoonException('HTTP status "301" redirect received without specified new location.');
			break;

			// actually, we should only process a 200 HTTP status (= OK), but let's try anyway
			default:
				// is this a serialized variable?
				if(strpos($this->result, 'SpoonProcess:') === 0)
				{
					// strip SpoonProcess identifier
					$this->result = substr($this->result, strlen('SpoonProcess:'));

					// get status
					$spoonStatus = substr($this->result, 0, strpos($this->result, ':'));

					// valid SpoonProcess status
					if($spoonStatus == 'ok')
					{
						// interpret result
						$this->result = unserialize(substr($this->result, strlen('ok:')));

						// false = we're done :)
						return false;
					}

					// invalid SpoonProcess status
					elseif($spoonStatus == 'nok')
					{
						// interpret result
						$this->result = unserialize(substr($this->result, strlen('nok:')));

						// but throw an exception
						throw new SpoonException('The target script producted an error: "'. $this->result .'"');

						// false = we're done :)
						return false;
					}

					// another status?
					else
					{
						// I guess this was no SpoonProcess-powered result after all, reset result
						$this->result = 'SpoonProcess:'. $this->result;
					}
				}

				// we did not receive a SpoonProcess-powered result, now check HTTP status code once again since this is our last indicator
				switch($httpStatus)
				{
					// 200 = ok
					case '200':
						// false = we're done :)
						return false;
					break;

					// 400 = fail
					case '400':
						// throw an exception
						throw new SpoonException('The target script producted an error: "'. $this->result .'"');

						// false = we're done
						return false;
					break;

					// don't know what to do with this status = complete fail
					default:
						throw new SpoonException('HTTP status "'. $httpStatus .'" received.');
					break;
				}
			break;
		}

		// something's wrong, we're not done yet
		return true;
	}


	/**
	 * SpoonProcess helper function: return success
	 *
	 * @return	void
	 * @param	mixed $return
	 */
	public static function returnSuccess($return)
	{
		// set valid headers
		if(headers_sent()) SpoonHTTP::setHeadersByCode(200);

		// serialize data and make sure our parent script will recognise it
		exit('SpoonProcess:ok:'. serialize($return));
	}


	/**
	 * SpoonProcess helper function: return fail
	 *
	 * @return	void
	 * @param	mixed $return
	 */
	public static function returnError($return)
	{
		// set valid headers
		if(headers_sent()) SpoonHTTP::setHeadersByCode(400);

		// serialize data and make sure our parent script will recognise it
		exit('SpoonProcess:nok:'. serialize($return));
	}
}
?>