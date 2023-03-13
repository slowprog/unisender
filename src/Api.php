<?php

namespace SlowProg\UniSender;

/**
 * API UniSender
 *
 * @see http://www.unisender.com/ru/help/api/
 */
class Api 
{
	/**
	 * @var string
	 */
	protected $apiKey;

	/**
	 * @var string
	 */
	protected $encoding;

	/**
	 * @var integer
	 */
	protected $retryCount;

	/**
	 * @var integer
	 */
	protected $timeout;

	/**
	 * @var bool
	 */
	protected $compression;
	
	/**
	 * enable test mode
	 * 
	 * @var boolean 
	 */
	public $testMode;

	/**
	 * @param string $apiKey
	 * @param array $config
	 */
	function __construct($apiKey, array $config = []) 
	{
		$config = array_merge([
			'encoding' => 'UTF8',
			'retry_count' => 4,
			'timeout' => null,
			'compression' => false,
			'test_mode' => false,
		], $config); 
		
		$this->apiKey = $apiKey;
		$this->encoding = $config['encoding'];
		$this->retryCount = $config['retry_count'];
		$this->timeout = $config['timeout'];
		$this->compression = $config['compression'];
		$this->testMode = $config['test_mode'];
	}

	/**
	 * @param string $name
	 * @param array $arguments
	 * @return string
	 */
	public function __call($name, $arguments)
	{
		if (!is_array($arguments) || empty($arguments))
			$params = [];
		else
			$params = $arguments[0];

		return $this->callMethod($name, $params);
	}

	/**
	 * subscribe email or/and phone
	 * 
	 * @param string $listIds - code lists, separated by commas (getLists - get mailing lists)
	 * @param array|string $fields - email string or an associative array with the email or/and phone
	 * @param array $params - optional parameters
	 * @throws \Exception
	 * @return array
	 * @see http://www.unisender.com/ru/help/api/subscribe/
	 */
	public function subscribe($listIds, $fields, $params = [])
	{
		$params['list_ids'] = $listIds;

		if (is_array($fields)) {
		    if (!isset($fields['email']) && !isset($fields['phone']))
			throw new \Exception('email or phone keys are required in array $fields');
		} else {
		    if ($fields)
			$fields['email'] = $fields;
		    else
			throw new \Exception('email keys are required in $fields like a string');
		}

		$params['fields'] = $fields;

		return $this->callMethod('subscribe', $params);
	}

	/**
	 * @param string $methodName
	 * @param array $params
	 * @return array
	 */
	protected function callMethod($methodName, $params = [])
	{
		if ($this->encoding != 'UTF8') {
			if (function_exists('iconv')) {
				array_walk_recursive($params, [$this, 'iconv']);
			} else if (function_exists('mb_convert_encoding')) {
				array_walk_recursive($params, [$this, 'mb_convert_encoding']);
			}
		}

		$url = $methodName . '?format=json';

		if ($this->compression) {
			$Url .= '&api_key=' . $this->apiKey . '&request_compression=bzip2';
			$content = bzcompress(http_build_query($params));
		} else {
			$params = array_merge((array)$params, ['api_key' => $this->apiKey]);
			$content = http_build_query($params);
		}

		$contextOptions = [
			'http' => [
				'method'  => 'POST',
				'header'  => 'Content-type: application/x-www-form-urlencoded',
				'content' => $content,
			]
		];

		if ($this->timeout) {
			$contextOptions['http']['timeout'] = $this->timeout;
		}

		$retryCount = 0;
		$context = stream_context_create($contextOptions);

		do {
			$host = $this->getApiHost($retryCount);
			$result = file_get_contents($host . $url, false, $context);
			$retryCount++;
		} while ($result === false && $retryCount < $this->retryCount);

		return $result !== false ? json_decode($result, true) : null;
	}

	/**
	 * @param integer $retryCount
	 * @return string
	 */
	protected function getApiHost($retryCount = 0)
	{
		if ($retryCount % 2 == 0)
			return 'https://api.unisender.com/ru/api/';
		else
			return 'https://www.api.unisender.com/ru/api/';
	}

	/**
	 * @param string $value
	 * @param string $key
	 */
	protected function iconv(&$value, $key)
	{
		$value = iconv($this->encoding, 'UTF8//IGNORE', $value);
	}

	/**
	 * @param string $value
	 * @param string $key
	 */
	protected function mb_convert_encoding(&$value, $key)
	{
		$value = mb_convert_encoding($value, 'UTF8', $this->encoding);
	}
}
