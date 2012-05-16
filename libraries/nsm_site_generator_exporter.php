<?php

require PATH_THIRD.'nsm_site_generator/config.php';

/**
 * NSM Example Addon Display
 * 
 * Usage:
 * 
 * $this->EE->load->library("{$this->addon_id}_addon", null, $this->addon_id);
 *
 * #  Add the custom field stylesheet to the header 
 * $this->EE->nsm_example_addon_helper->addCSS('custom_field.css');
 * 
 * # Load the JS for the iframe
 * $this->EE->nsm_example_addon_helper->addJS('custom_field.js');
 * $this->EE->nsm_example_addon_helper->addJS('../lib/jquery.cookie.js');
 * 
 * @package			NsmExampleAddon
 * @version			0.0.1
 * @author			Leevi Graham <http://leevigraham.com>
 * @copyright 		Copyright (c) 2007-2010 Newism <http://newism.com.au>
 * @license 		Commercial - please see LICENSE file included with this distribution
 * @link			http://expressionengine-addons.com/nsm-example-addon
 */

class Nsm_site_generator_exporter
{

	/**
	 * The addon ID
	 *
	 * @access private
	 * @var string
	 */
	private $addon_id;

	/**
	 * Constructs the class and sets the addon id
	 */
	public function __construct()
	{
		$this->addon_id = NSM_SITE_GENERATOR_ADDON_ID;
	}
	
	public function buildBundleFromPostData($data)
	{
		// prep data to be added to bundle node
		$bundle_data	= array(
			'title'						=> $data['title'],
			'download_url'				=> $data['download_url'],
			'version'					=> $data['version'],
			'description'				=> $data['description'],
			'post_import_instructions'	=> $data['post_import_instructions']
		);
		// build bundle node and channel group node to contain channel nodes
		$bundle			= $this->createNodeByName('bundle', $bundle_data);
		$channelGroup	= $this->createNodeByName('channel_group');
		
		// loop over channels
			// loop over the exp_channel_fields create an array where the key is the field_id, value is field_name (hydrate)
			// get channel titles join custom fields (only fields required, can be pulled from keys of previous loop)
			// loop over each of the channel titles
				// build the standard fields (title, dates etc)
				// loop over the exp_channel_fields
					// add <custom field> by matching the data in channel titles

		// prep temporary data arrays for fields, statuses and categories
		$field_groups		= array();
		$status_groups		= array();
		$category_groups	= array();
		// iterate over channel export configuration and gather data
		foreach ($data['channels'] as $channel_id => $channel_config) {
			// if channel is disabled then move on to next channel
			if (empty($channel_config['enabled'])) {
				continue;
			}
			// get the database info for the channel and prep variables
			$channel_info		= $this->getChannelInfo($channel_id);
			$field_group_id		= $channel_info['field_group_id'];
			$status_group_id	= $channel_info['status_group_id'];
			$category_group_ids	= explode('|', $channel_info['cat_group_ids']);
			// get this channel's field group if not already prepared
			if (empty($field_groups[$field_group_id])) {
				$field_groups[$field_group_id] = array(
					'name'		=> $channel_info['field_group'],
					'ref'		=> $channel_info['field_group'],
					'fields'	=> $this->getFieldsArrayByGroupId($field_group_id)
				);
			}
			// get this channel's status group if not already prepared
			if (empty($status_groups[$status_group_id])) {
				$status_groups[$status_group_id] = array(
					'name'		=> $channel_info['status_group'],
					'ref'		=> $channel_info['status_group'],
					'statuses'	=> $this->getStatusesArrayByGroupId($status_group_id)
				);
			}
			// go through the channel's categories and prepare those needed
			$category_groups = $this->getAndMergeCategoriesGroups($category_groups, $category_group_ids);
			// build a channel node object to populate
			$channel = $this->buildChannelNode($channel_info);
			// if there are channel entries chosen get them and add them now
			if (!empty($channel_config['entries'])) {
				// pass the channel object, entry ids and channel fields to method
				$channel = $this->getAndMergeChannelEntries(
					$channel,
					$channel_config['entries'],
					$field_groups[$field_group_id]
				);
			}
			// add the populated channel node to the channel group
			$channelGroup->addChannel($channel);
			
			// get all custom fields
			
		}
		// add the populated channel group node to the bundle
		$bundle->addChannelGroup($channelGroup);
		// pass the bundle to some methods to build and attach additonal data
		$bundle = $this->buildAndMergeCategoryGroups($bundle, $category_groups);
		$bundle = $this->buildAndMergeFieldGroups($bundle, $field_groups);
		$bundle = $this->buildAndMergeStatusGroups($bundle, $status_groups);
		// add an author to our bounde
		$author = $this->createNodeByName('author', array('name' => 'Newism', 'url' => 'http://newism.com.au'));
		$bundle->addAuthor($author);
		// return the xml
		return $bundle->asXML();
	}
	
	// build node objects using incoming data and attach to the bundle
	public function buildAndMergeFieldGroups($bundle, $field_groups)
	{
		foreach ($field_groups as $field_group) {
			$customFieldGroup = $this->createNodeByName('custom_field_group', $field_group);
			foreach ($field_group['fields'] as $field) {
				$customField = $this->createNodeByName('custom_field', $field);
				$customFieldGroup->addCustomField($customField);
			}
			$bundle->addCustomFieldGroup($customFieldGroup);
		}
		return $bundle;
	}
	
	// build node objects using incoming data and attach to the bundle
	public function buildAndMergeCategoryGroups($bundle, $category_groups)
	{
		foreach ($category_groups as $category_group) {
			$categoryGroup = $this->createNodeByName('category_group', $category_group);
			foreach ($category_group['categories'] as $category_data) {
				$category = $this->createNodeByName('category', $category_data);
				$categoryGroup->addCategory($category);
			}
			$bundle->addCategoryGroup($categoryGroup);
		}
		return $bundle;
	}
	
	// build node objects using incoming data and attach to the bundle
	public function buildAndMergeStatusGroups($bundle, $status_groups)
	{
		foreach ($status_groups as $status_group) {
			$statusGroup = $this->createNodeByName('status_group', $status_group);
			foreach ($status_group['statuses'] as $status_data) {
				$status = $this->createNodeByName('status', $status_data);
				$statusGroup->addStatus($status);
			}
			$bundle->addStatusGroup($statusGroup);
		}
		return $bundle;
	}
	
	
	// get and merge categories to the category group
	public function getAndMergeCategoriesGroups($category_groups, $group_ids)
	{
		foreach ($group_ids as $group_id) {
			if (empty($category_groups[$group_id])) {
				$group = $this->getCategoryGroupById($group_id);
				$group = array_merge($group, array(
					'ref'			=> $group['group_name'],
					'categories'	=> $this->getCategoriesArrayByGroupId($group_id)
				));
				$category_groups[$group_id] = $group;
			}
		}
		return $category_groups;
	}
	
	// get categories from the database by the group id
	public function getCategoriesArrayByGroupId($group_id)
	{
		$db =& get_instance()->db;
		$db->from('categories');
		$db->where_in('group_id', $group_id);
		return $db->get()->result_array();
	}
	
	// get the category group from the database by the id
	public function getCategoryGroupById($id)
	{
		$db =& get_instance()->db;
		$db->from('category_groups');
		$db->where_in('group_id', $id);
		return $db->get()->row_array();
	}
	
	// get the statuses from the database by the group id
	public function getStatusesArrayByGroupId($group_id)
	{
		$db =& get_instance()->db;
		$db->from('statuses');
		$db->where_in('group_id', $group_id);
		return $db->get()->result_array();
	}
	
	// get the fields from the database by the group id
	public function getFieldsArrayByGroupId($group_id)
	{
		$db =& get_instance()->db;
		$db->from('channel_fields');
		$db->where_in('group_id', $group_id);
		return $db->get()->result_array();
	}
	
	// create a new channel node and populate object with the incoming data
	public function buildChannelNode($channel_data)
	{
		$channel = $this->createNodeByName('channel', $channel_data);
		return $channel;
	}
	
	// attach channel entries to the channel object using the incoming entry ids
	public function getAndMergeChannelEntries($channel, $channel_entry_ids, $fields)
	{
		$entries = $this->getChannelEntriesArrayByEntryIds($channel_entry_ids);
		foreach ($entries as $entry_data) {
			$channelEntry = $this->createNodeByName('channel_entry', $entry_data);
			$channelEntry = $this->buildChannelEntryData($channelEntry, $entry_data, $fields);
			$channel->addChannelEntry($channelEntry);
		}
		return $channel;
	}
	
	// find relevant data from the array bind data to channel as nodes
	public function buildChannelEntryData($channelEntry, $entry_data, $field_group)
	{
		// iterate over data and find entry data
		foreach ($field_group['fields'] as $field) {
			$field_data_column		= 'field_id_' . $field['field_id'];
			$field_format_column	= 'field_ft_' . $field['field_id'];
			$channel_data = array(
				'id' => $field['field_name'],
				'formatting' => $entry_data[$field_format_column],
				'data' => $entry_data[$field_data_column]
			);
			$channelEntryData = $this->createNewChannelEntryData($channel_data);
			$channelEntry->addData($channelEntryData);
		}
		return $channelEntry;
	}
	
	public function createNewChannelEntryData($data)
	{
		$entryData = $this->createNodeByName('channel_entry_data', $data);
		return $entryData;
	}
	
	// get the channel entries from the database by entry ids
	public function getChannelEntriesArrayByEntryIds($channel_entry_ids)
	{
		$db =& get_instance()->db;
		$db->from('channel_titles');
		$db->join(
			'channel_data',
			'channel_data.entry_id = channel_titles.entry_id',
			'left'
		);
		//$db->select('title, url_title, status, allow_comments, sticky');
		$db->where_in('channel_titles.entry_id', $channel_entry_ids);
		return $db->get()->result_array();
	}
	
	// get channel entry data from the database by entry id
	public function getChannelDataArrayByEntryIds($channel_entry_ids)
	{
		$db =& get_instance()->db;
		$db->from('channel_data');
		$db->select('title, url_title, status, allow_comments, sticky');
		$db->where_in('entry_id', $channel_entry_ids);
		return $db->get()->result_array();
	}
	
	// get the channel info from the database by id
	public function getChannelInfo($channel_id)
	{
		$db =& get_instance()->db;
		$db->from('channels');
		$db->join(
			'status_groups',
			'channels.status_group = status_groups.group_id',
			'left'
		);
		$db->join(
			'field_groups',
			'channels.field_group = field_groups.group_id',
			'left'
		);
		$db->select('channels.channel_name AS `name`');
		$db->select('channels.channel_title AS `title`');
		$db->select('channels.channel_url');
		$db->select('channels.channel_description');
		$db->select('channels.comment_url');
		$db->select('channels.cat_group AS `cat_group_ids`');
		//$db->select('channels.cat_group');
		$db->select('channels.url_title_prefix');
		$db->select('channels.status_group AS `status_group_id`');
		$db->select('channels.field_group AS `field_group_id`');
		$db->select('status_groups.group_name AS `status_group`');
		$db->select('field_groups.group_name AS `field_group`');
		$db->where('channel_id', $channel_id);
		return $db->get()->row_array();
	}
	
	// returns a node object from the factory and binds data before returning
	public function createNodeByName($node_type, $data = array())
	{
		$class_name = "Nsm_site_generator_exporter_class_{$node_type}";
		if (!class_exists($class_name)) {
			$class_dir = dirname(__FILE__) . '/exporter_classes/';
			$class_path = $class_dir.strtolower($class_name).'.php';
			include($class_path);
		}
		return new $class_name($data);
	}
	

}