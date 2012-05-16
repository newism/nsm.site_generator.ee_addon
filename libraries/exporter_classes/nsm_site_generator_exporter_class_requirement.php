<?php

require_once "nsm_site_generator_exporter_class_base.php";

class Nsm_site_generator_exporter_class_requirement extends Nsm_site_generator_exporter_class_base
{
	public $title;
	public $type;
	public $download_url;
	public $target;
	
	
	public function _defaultAttributes()
	{
		return array('title', 'type', 'download_url', 'target');
	}
	
	public function asXML()
	{
		return <<<XML
<requirement {$this->_attributes} />

XML;
	}
	
}