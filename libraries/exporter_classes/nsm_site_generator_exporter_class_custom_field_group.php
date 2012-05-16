<?php

require_once "nsm_site_generator_exporter_class_base.php";

class Nsm_site_generator_exporter_class_custom_field_group extends Nsm_site_generator_exporter_class_base
{
	public $name;
	public $id;
	public $fields;
	
	
	public function _defaultAttributes()
	{
		return array('name', 'id');
	}
	
	public function __construct($data = array())
	{
		parent::__construct($data);
		
		$this->fields = array();
	}
	
	
	public function asXML()
	{
		$fields = '';
		foreach ($this->fields as $field) {
			$fields .= $field->asXML();
		}
		return <<<XML
		
<group group_name="{$this->_attributes->name}" id="{$this->_attributes->id}">
	{$fields}
</group>

XML;
	}
	
	public function addCustomField($node)
	{
		$this->fields[] = $node;
	}
}