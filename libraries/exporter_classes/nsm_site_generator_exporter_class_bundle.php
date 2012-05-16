<?php

require_once "nsm_site_generator_exporter_class_base.php";

class Nsm_site_generator_exporter_class_bundle extends Nsm_site_generator_exporter_class_base
{
	public $title;
	public $download_url;
	public $version;
	public $description;
	public $authors;
	public $requirements;
	public $post_import_instructions;
	public $category_groups;
	public $template_groups;
	public $custom_field_groups;
	public $status_groups;
	public $channel_groups;
	
	
	public function _defaultAttributes()
	{
		return array('title', 'download_url', 'version');
	}
	
	public function __construct($data = array())
	{
		parent::__construct($data);
		
		$this->authors = array();
		$this->requirements = array();
		$this->category_groups = array();
		$this->template_groups = array();
		$this->custom_field_groups = array();
		$this->status_groups = array();
		$this->channel_groups = array();
	}
	
	
	public function asXML()
	{
		return <<<XML

<generator_template title="{$this->_attributes->title}" download_url="{$this->_attributes->download_url}" version="{$this->_attributes->version}">
	<description>
	<![CDATA[{$this->description}]]>
	</description>

	<authors>
		{$this->getAuthorsAsXML()}
	</authors>

	<requirements>
		{$this->getRequirementsAsXML()}
	</requirements>

    <post_import_instructions>
    <![CDATA[{$this->post_import_instructions}]]>
    </post_import_instructions>

	<category_groups>
		{$this->getCategoryGroupsAsXML()}
	</category_groups>

	<template_groups>
		{$this->getTemplateGroupsAsXML()}
	</template_groups>

	<custom_field_groups>
		{$this->getCustomFieldGroupsAsXML()}
	</custom_field_groups>

	<status_groups>
		{$this->getStatusGroupsAsXML()}
	</status_groups>

	{$this->getChannelGroupsAsXML()}

</generator_template>

XML;
	}
	
	//////////////////////////////////
	
	public function addAuthor($node)
	{
		$this->authors[] = $node;
	}
	
	public function addRequirement($node)
	{
		$this->requirements[] = $node;
	}
	
	public function addCategoryGroup($node)
	{
		$this->category_groups[] = $node;
	}
	
	public function addTemplateGroup($node)
	{
		$this->template_groups[] = $node;
	}
	
	public function addCustomFieldGroup($node)
	{
		$this->custom_field_groups[] = $node;
	}
	
	public function addStatusGroup($node)
	{
		$this->status_groups[] = $node;
	}
	
	public function addChannelGroup($node)
	{
		$this->channel_groups[] = $node;
	}
	
	//////////////////////////////////
	
	public function getAuthorsAsXML()
	{
		$output = '';
		foreach ($this->authors as $author) {
			$output .= $author->asXML();
		}
		return $output;
	}
	
	public function getRequirementsAsXML()
	{
		$output = '';
		foreach ($this->requirements as $requirement) {
			$output .= $requirement->asXML();
		}
		return $output;
	}
	
	public function getCategoryGroupsAsXML()
	{
		$output = '';
		foreach ($this->category_groups as $category_group) {
			$output .= $category_group->asXML();
		}
		return $output;
	}
	
	public function getTemplateGroupsAsXML()
	{
		$output = '';
		foreach ($this->template_groups as $template_group) {
			$output .= $template_group->asXML();
		}
		return $output;
	}
	
	public function getCustomFieldGroupsAsXML()
	{
		$output = '';
		foreach ($this->custom_field_groups as $custom_field_group) {
			$output .= $custom_field_group->asXML();
		}
		return $output;
	}
	
	public function getStatusGroupsAsXML()
	{
		$output = '';
		foreach ($this->status_groups as $status_group) {
			$output .= $status_group->asXML();
		}
		return $output;
	}
	
	public function getChannelGroupsAsXML()
	{
		$output = '';
		foreach ($this->channel_groups as $channel_group) {
			$output .= $channel_group->asXML();
		}
		return $output;
	}
	
}