<?php

require_once "nsm_site_generator_exporter_class_base.php";

class Nsm_site_generator_exporter_class_category extends Nsm_site_generator_exporter_class_base
{
	
	public $children;
	
	public function _defaultAttributes()
	{
		return array('cat_name', 'cat_url_title', 'cat_description', 'cat_image', 'cat_order');
	}
	
	public function __construct($data = array())
	{
		parent::__construct($data);
		
		$this->children = array();
	}
	
	
	public function asXML()
	{
		if (!empty($this->children)) {
	
			$children = '';
			foreach ($this->children as $child) {
				$children .= $child->asXML();
			}
			
			return <<<XML
<category {$this->_attributes}>
	{$children}
</category>

XML;
		} else {
			return <<<XML
<category {$this->_attributes} />

XML;
		}
	}
	
	
	public function addChild($node)
	{
		$this->children[] = $node;
	}
	
}