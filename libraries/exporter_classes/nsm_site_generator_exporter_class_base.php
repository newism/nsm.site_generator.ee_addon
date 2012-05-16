<?php

require_once "nsm_site_generator_exporter_class_base_attributes.php";

class Nsm_site_generator_exporter_class_base
{
	
	public $_attributes;
	
	
	public function _defaultAttributes()
	{
		return array();
	}
	
	public function __construct($data = array())
	{
		$data = $this->_mapData($data);
		$this->_attributes = $this->_getAttributesObject($this->_defaultAttributes());
		if (count($data) > 0) {
			$this->_setData($data);
		}
	}
	
	
	public function _getAttributesObject($attributes)
	{
		return new Nsm_site_generator_exporter_class_base_attributes($attributes);
	}
	
	public function _mapData($data = array())
	{
		$output = array();
		$attributes = $this->_defaultAttributes();
		foreach ($data as $key => $val) {
			if (in_array($key, $attributes)) {
				$output['_attributes'][$key] = $val;
			} else {
				$output[$key] = $val;
			}
		}
		return $output;
	}
	
	public function _setData($data)
	{
		foreach ($data as $key => $val) {
			if ($key == '_attributes') {
				$this->_attributes->_setData($val);
				continue;
			}
			if (property_exists($this, $key)) {
				$this->{$key} = $val;
			}
		}
	}
	
	public function asArray()
	{
		return (array) $this;
	}
	
	public function asXML()
	{
		return "";
	}
	
}