<?php

require_once "nsm_site_generator_exporter_class_base.php";

class Nsm_site_generator_exporter_class_status_group extends Nsm_site_generator_exporter_class_base
{
	public $name;
	public $id;
	public $statuses;
	
	
	public function _defaultAttributes()
	{
		return array('name', 'id');
	}
	
	
	public function __construct($data = array())
	{
		parent::__construct($data);
		
		$this->statuses = array();
	}
	
	
	public function asXML()
	{
		$statuses = '';
		foreach ($this->statuses as $status) {
			$statuses .= $status->asXML();
		}
		return <<<XML
<group group_name="{$this->_attributes->name}" id="{$this->_attributes->id}">
	{$statuses}
</group>
	
XML;
	}
	
	
	public function addStatus($node)
	{
		$this->statuses[] = $node;
	}
}