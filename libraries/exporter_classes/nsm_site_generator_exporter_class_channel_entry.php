<?php

require_once "nsm_site_generator_exporter_class_base.php";

class Nsm_site_generator_exporter_class_channel_entry extends Nsm_site_generator_exporter_class_base
{
	public $title;
	public $url_title;
	public $fields;
	public $data;
	public $categories;
	
	
	public function _defaultAttributes()
	{
		return array('title', 'url_title', 'status', 'allow_comments', 'sticky');
	}
	
	public function __construct($data = array())
	{
		parent::__construct($data);
		
		$this->fields = array();
		$this->data = array();
		$this->custom_tabs = array();
		$this->categories = array();
	}
	
	
	public function asXML()
	{
		return <<<XML
<entry {$this->_attributes}>
	<field id="title" formatting="none">{$this->_attributes->title}</field>
	<field id="url_title">{$this->_attributes->url_title}</field>
    
	{$this->getFieldsAsXML()}

	{$this->getDataAsXML()}

	{$this->getCustomTabsAsXML()}
	
	{$this->getCategoriesAsXML()}
</entry>

XML;
	}
	
	//////////////////////////////////
	
	public function addField($node)
	{
		$this->fields[] = $node;
	}
	
	public function addData($node)
	{
		$this->data[] = $node;
	}
	
	public function addCustomTab($node)
	{
		$this->custom_tabs[] = $node;
	}
	
	public function addCategory($node)
	{
		$this->categories[] = $node;
	}
	
	//////////////////////////////////
	
	public function getFieldsAsXML()
	{
		$output = '';
		foreach ($this->fields as $field) {
			$output .= $field->asXML();
		}
		return $output;
	}
	
	public function getDataAsXML()
	{
		$output = '';
		foreach ($this->data as $custom_field) {
			$output .= $custom_field->asXML();
		}
		return $output;
	}
	
	public function getCustomTabsAsXML()
	{
		$output = '';
		foreach ($this->custom_tabs as $custom_tab) {
			$output .= $custom_tab->asXML();
		}
		return $output;
	}
	
	public function getCategoriesAsXML()
	{
		$output = '';
		foreach ($this->categories as $category) {
			$output .= $category->asXML();
		}
		return $output;
	}
	
}