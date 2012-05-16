<?php

require_once "nsm_site_generator_exporter_class_base.php";

class Nsm_site_generator_exporter_class_author extends Nsm_site_generator_exporter_class_base
{
	public function _defaultAttributes()
	{
		return array('name', 'url');
	}
	
	public function asXML()
	{
		return <<<XML
<author {$this->_attributes} />

XML;
	}
	
}