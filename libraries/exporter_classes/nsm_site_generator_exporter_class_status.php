<?php

require_once "nsm_site_generator_exporter_class_base.php";

class Nsm_site_generator_exporter_class_status extends Nsm_site_generator_exporter_class_base
{
	public $status;
	
	public function _defaultAttributes()
	{
		return array('status');
	}
	
	public function asXML()
	{
		return <<<XML
<status {$this->_attributes} />

XML;
	}
	
}