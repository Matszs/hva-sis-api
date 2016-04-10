<?php
namespace Handlers;

class Cookie
{
	var $rules = array();

	function __construct() {
		return $this;
	}

	public function parseData($data) {
		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $data, $matches);
		foreach($matches[1] as $item) {
			$this->rules[] = $item;
		}
	}

	public function getRules() {
		return implode('; ', $this->rules);
	}

	public function addRule($rule) {
		$this->rules[] = $rule;
	}
}