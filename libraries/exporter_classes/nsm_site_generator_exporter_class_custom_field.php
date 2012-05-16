<?php

require_once "nsm_site_generator_exporter_class_base.php";

class Nsm_site_generator_exporter_class_custom_field extends Nsm_site_generator_exporter_class_base
{
	public $name;
	public $label;
	public $type;
	public $data;
	
	public function _defaultAttributes()
	{
		return array('field_name', 'field_label', 'field_instructions', 'field_type', 'field_list_items', 'field_pre_populate', 'field_pre_channel_id', 'field_pre_field_id', 'field_related_to', 'field_related_id', 'field_related_orderby', 'field_related_sort', 'field_related_max', 'field_ta_rows', 'field_maxl', 'field_required', 'field_text_direction', 'field_search', 'field_is_hidden', 'field_fmt', 'field_show_fmt', 'field_order', 'field_content_type', 'field_settings');
	}
	
	public function asXML()
	{
		if (!empty($this->data)) {
			return <<<XML
<field {$this->_attributes}>
	{$this->data}
</field>

XML;
		} else {
			return <<<XML
<field {$this->_attributes}/>

XML;
		}
		
	}
	
}