<?php

require_once "nsm_site_generator_exporter_class_base.php";

class Nsm_site_generator_exporter_class_channel extends Nsm_site_generator_exporter_class_base
{
	public $name;
	public $title;
	/*public $url;
	public $status_group;
	public $cat_group;
	public $field_group;
	public $comment_url;
	public $description;*/
	public $bundle_channel_description;
	public $requirements;
	public $channel_entries;
	
	public function _defaultAttributes()
	{
		return array('channel_url', 'status_group', 'cat_group', 'comment_url', 'field_group', 'channel_description', 'url_title_prefix');
	}
	
	public function __construct($data = array())
	{
		parent::__construct($data);
		
		$this->requirements = array();
		$this->channel_entries = array();
	}
	
	
	public function asXML()
	{
		return <<<XML

<channel
	channel_name="{$this->name}"
	channel_title="{$this->title}"
	{$this->_attributes}
>
	<bundle_channel_description>
	<![CDATA[
		{$this->bundle_channel_description}
	]]>
	</bundle_channel_description>
	<requirements>
		{$this->getRequirementsAsXML()}
		</requirements>
	
	{$this->getChannelEntriesAsXML()}
	
</channel>

XML;
	}
	
	
	
	public function addRequirements($node)
	{
		$this->requirements[] = $node;
	}
	
	public function addChannelEntry($node)
	{
		$this->channel_entries[] = $node;
	}
	
	//////////////////////////////////
	
	public function getRequirementsAsXML()
	{
		$output = '';
		foreach ($this->requirements as $requirement) {
			$output .= $requirement->asXML();
		}
		return $output;
	}
	
	public function getChannelEntriesAsXML()
	{
		$output = '';
		foreach ($this->channel_entries as $channel_entry) {
			$output .= $channel_entry->asXML();
		}
		return $output;
	}
	
}