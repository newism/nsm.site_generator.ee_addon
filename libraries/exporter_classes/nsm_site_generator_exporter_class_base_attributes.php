<?php

class Nsm_site_generator_exporter_class_base_attributes
{
	private $_properties = array();

	public function __construct($attributes = array())
	{
		foreach ($attributes as $attribute) {
			$this->_properties[$attribute] = null;
		}
	}
	
	public function _setData($data)
	{
		foreach ($data as $key => $val) {
			if (array_key_exists($key, $this->_properties)) {
				$this->_properties[$key] = $val;
			}
		}
	}
	
	public function __get($name)
	{
		if (array_key_exists($name, $this->_properties)) {
			return $this->_properties[$name];
		}
		return null;
	}

	public function __toString()
	{
		$output = '';
		foreach ($this->_properties as $key => $val) {
			if (empty($val)) {
				continue;
			}
			$output .= <<<XML
 {$key}="{$val}"
XML;
		}
		return $output;
	}

}