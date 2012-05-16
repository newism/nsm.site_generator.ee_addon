<?php

require_once "nsm_site_generator_exporter_class_base.php";

class Nsm_site_generator_exporter_class_template extends Nsm_site_generator_exporter_class_base
{

	public function _defaultAttributes()
	{
		return array('template_name', 'template_type');
	}
	
	public function asXML()
	{
		return <<<XML
<template {$this->_attributes} />

XML;
	}
	
}