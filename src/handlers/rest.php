<?php
namespace Handlers;

class Rest
{
	public $params = array();
	private $config = array();
	private $ch; // cURL Handler
	public $cookies = false;

	public function __construct($config = array()) {
		$this->config = array_merge(array(
			'cookies' => true,
			'cookie_file' => TMP . '/cookies/' . \Utilities\Random::simpleString(10) . '.txt',
			'root' => 'https://sis.hva.nl:8011/',
			'user_agent' => $this->getRandomUserAgent(),
			'use_cookie_class' => false,
			'referer' => '',
			'headers' => false
		), $config);
		$this->cookies = new Cookie();
	}

	public function setConfig($args = array()) {
		$this->config = array_merge($this->config, $args);
	}

	public function call($path, $requestType = 'post') {
		if (!$this->ch)
			$this->open();

		curl_setopt($this->ch, CURLOPT_URL, $this->config['root'] . $path);

		if ($requestType == 'post') {
			$fieldsString = '';
			foreach($this->params as $key=>$value) { $fieldsString .= $key.'='.$value.'&'; }
			rtrim($fieldsString, '&');

			curl_setopt($this->ch, CURLOPT_POST, count($this->params));
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $fieldsString);
		} else {
			curl_setopt($this->ch, CURLOPT_POST, 0);
		}

		if ($this->config['cookies']) {
			if($this->config['use_cookie_class']) {
				curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("Cookie: " . $this->cookies->getRules()));
			} else {
				curl_setopt($this->ch, CURLOPT_COOKIESESSION, true);
				curl_setopt($this->ch, CURLOPT_COOKIEJAR, $this->config['cookie_file']);
				curl_setopt($this->ch, CURLOPT_COOKIEFILE, $this->config['cookie_file']);
			}
		} else {
			curl_setopt($this->ch, CURLOPT_COOKIESESSION, false);
		}

		if($this->config['headers']) {
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->config['headers']);
		}

		curl_setopt($this->ch, CURLOPT_USERAGENT, $this->config['user_agent']);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true); // Follow the redirects if we get redirected.
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1); // Don't print output

		//curl_setopt($this->ch, CURLOPT_VERBOSE, true);
		//curl_setopt($this->ch, CURLOPT_STDERR, fopen('php://stderr', 'w'));

		curl_setopt($this->ch, CURLOPT_HEADER, 1);
		if($this->config['referer']) {
			curl_setopt($this->ch, CURLOPT_REFERER, $this->config['referer']);
			$this->setConfig(array('referer', ''));
		}

		$this->params = null;
		$content = curl_exec($this->ch);

		$this->cookies->parseData($content);

		if($this->config['cookies'])
			$this->cookies = new Cookie($this->config['cookie_file']);

		return $content;
	}

	public function close() {
		if ($this->ch)
			curl_close($this->ch);
		$this->ch = null;
	}

	public function open() {
		$this->ch = curl_init();
	}

	private function getRandomUserAgent() {
		$userAgents = array(
			'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US) AppleWebKit/527  (KHTML, like Gecko, Safari/419.3) Arora/0.6',
			'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/532.5 (KHTML, like Gecko) Chrome/4.0.249.0 Safari/532.5',
			'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:43.0) Gecko/20100101 Firefox/43.0',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/534.7 (KHTML, like Gecko) Chrome/7.0.514.0 Safari/534.7',
			'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0)',
			'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_4) AppleWebKit/534.57.2 (KHTML, like Gecko) Version/5.1.7 Safari/534.57.2',
		);

		return $userAgents[array_rand($userAgents)];
	}
}