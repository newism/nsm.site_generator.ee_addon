<?php

require_once "nsm_site_generator_exporter_class_base.php";

class Nsm_site_generator_exporter_class_channel_entry_data extends Nsm_site_generator_exporter_class_base
{
	public $data;
	
	public function _defaultAttributes()
	{
		return array('id', 'formatting');
	}
	
	public function __construct($data = array())
	{
		parent::__construct($data);
		
	}
	
	
	public function asXML()
	{
		return <<<XML
<custom_field {$this->_attributes}><![CDATA[{$this->data}]]></custom_field>

XML;
	}
		
}