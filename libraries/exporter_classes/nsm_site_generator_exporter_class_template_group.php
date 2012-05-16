<?php

require_once "nsm_site_generator_exporter_class_base.php";

class Nsm_site_generator_exporter_class_template_group extends Nsm_site_generator_exporter_class_base
{
	public $name;
	public $is_site_default;
	public $templates;
	
	
	public function _defaultAttributes()
	{
		return array('name', 'is_site_default');
	}
	
	public function __construct($data = array())
	{
		parent::__construct($data);
		
		$this->templates = array();
	}
	
	
	public function asXML()
	{
		$templates = '';
		foreach ($this->templates as $template) {
			$templates .= $template->asXML();
		}
		return <<<XML	
<group group_name="{$this->_attributes->name}" is_site_default="{$this->_attributes->is_site_default}">
	{$templates}
</group>
	
XML;
	}
	
}