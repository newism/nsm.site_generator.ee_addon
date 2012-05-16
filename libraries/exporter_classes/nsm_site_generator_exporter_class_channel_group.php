<?php

require_once "nsm_site_generator_exporter_class_base.php";

class Nsm_site_generator_exporter_class_channel_group extends Nsm_site_generator_exporter_class_base
{
	public $requirements;
	public $channels;
	
	
	public function __construct($data = array())
	{
		parent::__construct($data);
		
		$this->requirements = array();
		$this->channels = array();
	}
	
	
	public function asXML()
	{
		return <<<XML

<channels>
	
	<requirements>
		{$this->getRequirementsAsXML()}
	</requirements>
	
	{$this->getChannelsAsXML()}
	
</channels>

XML;

	}
	
	//////////////////////////////////
	
	public function addRequirement($node)
	{
		$this->requirements[] = $node;
	}
	
	public function addChannel($node)
	{
		$this->channels[] = $node;
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
	
	public function getChannelsAsXML()
	{
		$output = '';
		foreach ($this->channels as $channel) {
			$output .= $channel->asXML();
		}
		return $output;
	}
	
}