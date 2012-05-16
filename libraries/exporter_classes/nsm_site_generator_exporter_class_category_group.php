<?php

require_once "nsm_site_generator_exporter_class_base.php";

class Nsm_site_generator_exporter_class_category_group extends Nsm_site_generator_exporter_class_base
{
	public $categories;
	
	public function _defaultAttributes()
	{
		return array('group_name', 'sort_order', 'exclude_group', 'field_html_formatting', 'can_edit_categories', 'can_delete_categories');
	}

	public function __construct($data = array())
	{
		parent::__construct($data);
		
		$this->categories = array();
	}
	
	
	public function asXML()
	{
		$categories = '';
		foreach ($this->categories as $category) {
			$categories .= $category->asXML();
		}
		return <<<XML
<group {$this->_attributes}>
	{$categories}
</group>

XML;
	}
	
	
	public function addCategory($node)
	{
		$this->categories[] = $node;
	}
}