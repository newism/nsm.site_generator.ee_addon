<?php
/**
* Base generator class for site templates.
* Handles database truncation, and insertion of data into database.
*
* Your own generator class can extend this and overwrite almost any function in here.
*
* @package LgSiteGenerator
* @author Leevi Graham <http://leevigraham.com>
* @see http://leevigraham.com/cms-customisation/expressionengine/addon/lg-site-generator/
* @copyright Copyright (c) 2007-2008 Leevi Graham
* @license http://leevigraham.com/cms-customisation/commercial-license-agreement
*/
class Nsm_site_generator_gen
{
	/**
	 * Site template name
	 * @var string
	 */
	protected $site_template;

	/**
	 * Parsed configuration XML
	 * @var SimpleXmlElement
	 */
	protected $config;

	/**
	 * User's selected channels, themes, whether to truncate db etc.
	 * @var array array(string => array(..)|string)
	 */
	protected $options;

	/**
	 * Log of activities performed and errors encountered while processing. @see Nsm_site_generator_gen::log()
	 * @var array array(int => array('type' => (error|success|warning), 'text' => string))
	 */
	protected $log		= array();

	/**
	 * Required channels fetched from @see Nsm_site_generator_gen::$config
	 * @var array array(string => array(..)|string)
	 */
	protected $channels			= array();

	/**
	 * Required template_groups fetched from @see Nsm_site_generator_gen::$config. Dependencies figured out by selected channels' requires[@type=template]
	 * @var array array(string => array(..)|string)
	 */
	protected $template_groups	= array();

	/**
	 * Required category groups fetched from @see Nsm_site_generator_gen::$config. selected channels' cat_group attributes
	 * @var array array(string => array(..)|string)
	 */
	protected $category_groups	= array();

	/**
	 * Required status groups fetched from @see Nsm_site_generator_gen::$config. selected channels' status_group attributes
	 * @var array array(string => array(..)|string)
	 */
	protected $status_groups	= array();

	/**
	 * Required custom field groups fetched from @see Nsm_site_generator_gen::$config. selected channels' field_group attributes
	 * @var array array(string => array(..)|string)
	 */
	protected $custom_field_groups=array();

	/**
	* Nsm_site_generator_gen constructor
	* @param string $site_template name of template, as used in path. e.g. 'basic_blog'
	* @param SimpleXMLElement $params parsed configuration XML of template
	*/
	public function __construct($site_template, $config)
	{
		$this->EE =& get_instance(); 
		$this->db =& $this->EE->db;
		
		$this->site_template = $site_template;
		$this->config = $config;
		
		$this->EE->lang->loadfile('nsm_site_generator');
	}

	/**
	* Go through the config XML and the user's input and figure out what needs to be created, using dependencies etc
	* 
	* Sets $this->channels, $this->template_groups, $this->category_groups and $this->custom_field_groups
	* Uses $this->defaults()->channel(..) etc to set default values
	*/
	protected function parse_config()
	{

		$this->_logTitle("Parsing config");

		// an array of our template dependency globs e.g. array(0 => "blog/*", 1 => "*/category*")
		// applied to $group_name.'/'.$template_name
		$required_templates = array();
		$required_field_groups = array();
		$required_cat_groups = array();
		$required_status_groups = array();

		/* CHANNELS */
		$this->_log("Parsing channels config");
		// loop over each channel in the config
		foreach ($this->config->xpath('//channels/channel') as $channel)
		{
			// create a temp key
			$key = (string)$channel['channel_name'];

			// if this channel is in the submitted channel array
			if(in_array($key, $this->options['channels']))
			{
				// mix in with defaults
				$this->channels[$key]['attrs'] = $this->defaults()->channel($this->xmlAttributes($channel->attributes()));

				// set the cat, field, status dependencies
				if (isset($channel['field_group'])){ $required_field_groups[] = (string)$channel['field_group']; }
				if (isset($channel['cat_group'])){ $required_cat_groups[] = (string)$channel['cat_group']; }
				if (isset($channel['status_group'])){ $required_status_groups[] = (string)$channel['status_group']; }

                $this->channels[$key]['entries'] = array();
				foreach ($channel->entry as $entry) {
					$this->channels[$key]['entries'][] = $entry;
				}

				// collate all our template globs
				foreach ($channel->require as $dep) {
					$a = $dep->attributes();
					if ((string)$a->type == "template")
						$required_templates[] = (string)$a->target;
				}
			}
		}


		/* CATEGORY GROUPS */
		$this->_log("Parsing categories config");
		// Loop over all the category groups in the config
		foreach ($this->config->xpath('//category_groups/group') as $cg)
		{
			$key = (string)$cg['id'];
			// if this config category group is in our required category groups
			if(in_array($key, $required_cat_groups))
			{
				$this->category_groups[$key]['attrs'] = $this->defaults()->category_group($this->xmlAttributes($cg->attributes()));
				// recursively build this category groups category config in a single depth array
				$this->category_groups[$key]['cats'] = $this->_buildCatsConfig($cg, $key);
			}
		}


		/* STATUS GROUPS */
		$this->_log("Parsing statuses config");
		// Loop over all the status groups in the config
		foreach ($this->config->xpath('//status_groups/group') as $sg)
		{
			$key = (string)$sg['id'];
			// if this config status group is in our required status groups
			if(in_array($key, $required_status_groups))
			{
				$this->status_groups[$key]['attrs'] = $this->defaults()->status_group($this->xmlAttributes($sg->attributes()));
				$this->status_groups[$key]['statuses'] = array();
				foreach ($sg->status as $status)
				{
					$this->status_groups[$key]['statuses'][(string)$status['status']] = $this->defaults()->status($this->xmlAttributes($status->attributes()));
				}
			}
		}


		/* CUSTOM FIELD GROUPS */
		$this->_log("Parsing custom fields config");
		// Loop over all the custom field groups in the config
		foreach ($this->config->xpath('//custom_field_groups/group') as $cfg)
		{
			// get the custom field group
			$key = (string)$cfg['id'];
			// if this group is in the required groups
			if(in_array($key, $required_field_groups))
			{
				$this->custom_field_groups[$key]['attrs'] = $this->defaults()->custom_field_group($this->xmlAttributes($cfg->attributes()));
				$this->custom_field_groups[$key]['fields'] = array();
				foreach ($cfg->field as $field)
				{
					$field_key = (string)$field['field_name'];
					$this->custom_field_groups[$key]['fields'][$field_key] = $this->defaults()->custom_field($this->xmlAttributes($field->attributes()));
				}
			}
		}

		// /* TEMPLATES */
		// 
		// // only do 'global' dependencies if any channels are selected
		// if (count($this->channels))
		// {
		// 	// get all the global requirements for channels
		// 	foreach ($this->config->xpath("//channels/requirements/requirement[@type='template']") as $r)
		// 	{
		// 		$required_templates[] = (string)$r["target"];
		// 	}
		// 
		// 	foreach ($this->channels as $r)
		// 	{
		// 		foreach ($this->config->xpath("//channels/channel[@channel_name='".$r["attrs"]["channel_name"]."']/requirements/requirement[@type='template']") as $r)
		// 		{
		// 			$required_templates[] = (string)$r["target"];
		// 		}
		// 	}
		// }
		// 
		// // fix the globs up into a usable regex: quote (specifically slashes) and then replace wild '*' with more appropriate '.*'
		// foreach ($required_templates as $k => $req)
		// {
		// 	$required_templates[$k] = str_replace('*', '.*', $req);
		// }
		// 
		// // put all our templates into a form like array("blog/pages" => array(...)) 
		// // so that we can easily filter by keys matching the $required_templates regexes
		// $template_groups	= array();
		// $templates			= array();
		// // loop over each template group in the config
		// foreach ($this->config->xpath('//template_groups/group') as $tg)
		// {
		// 	// set a temp key
		// 	$key = (string)$tg['group_name'];
		// 	$template_groups[$key]['attrs'] = $this->xmlAttributes($tg->attributes());
		// 
		// 	// create a fake index template
		// 	$templates[$tg['group_name']."/index"]['attrs'] = array("template_name" => "index");
		// 	$templates[$tg['group_name']."/index"]['group'] = $key;
		// 
		// 	// for each template group template
		// 	foreach ($tg->template as $template)
		// 	{
		// 		$template_key = (string)$template['template_name'];
		// 		$templates[$tg['group_name']."/".$template_key]['attrs'] = $this->xmlAttributes($template->attributes());
		// 		$templates[$tg['group_name']."/".$template_key]['group'] = $key;
		// 	}
		// }
		// 
		// // now we loop over all of the templates
		// foreach ($templates as $k => $tpl)
		// {
		// 	// try to match against rxs
		// 	$found = FALSE;
		// 	foreach ($required_templates as $rx)
		// 	{
		// 		if (preg_match("#^".$rx."$#", $k))
		// 		{
		// 			$found = TRUE;
		// 			break;
		// 		}
		// 	}
		// 
		// 	// if success, add it to this->template_groups - and group too, if not already set
		// 	if ($found)
		// 	{
		// 		if (!isset($this->template_groups[$tpl['group']]))
		// 			$this->template_groups[$tpl['group']]['attrs'] = $this->defaults()->template_group($template_groups[$tpl['group']]['attrs']);
		// 
		// 		$this->template_groups[$tpl['group']]['templates'][$tpl['attrs']['template_name']] = $this->defaults()->template($tpl['attrs']);
		// 	}
		// }
		$this->_logSuccess("Parsing config complete");
	}

	/**
	* Build the category config into an array based on the xml
	* 
	* @param	object			$obj 		A simpleXML Object
	* @param	string			$group_id	The current category group id
	* @param	string			$parent_id	The parent category id
	* @return	array			A flat array of categories for a particular category group, with [parent_id] showing heirachy
	*/
	private function _buildCatsConfig($obj, $group_id, $parent_id = FALSE)
	{
		$categories = array();

		foreach ($obj->category as $category)
		{
			$key = (string)$category['cat_url_title'];
			$categories[$key] = $this->xmlAttributes($category->attributes());
			if($parent_id !== FALSE)
			{
				$categories[$key]['parent_id'] = $parent_id;
			}
			unset($categories[$key]['id']);

			// if we have children, recurse and append
			if($category->category)
			{
				$categories = $categories + $this->_buildCatsConfig($category, $group_id, (string)$category['cat_url_title']);
			}
		}
		return $categories;
	}

	/**
	* called before any generation starts, but after configuration and options are parsed.
	* base implementation does nothing, but allows subclasses to override and do potentially meaningful things.
	* @return bool continue processing? TRUE to continue generation, FALSE to forfeit.
	*/
	protected function generateBefore() { return TRUE; }

	/**
	* generate
	* perform entire generation for site template. delegates to generate*, copyTheme, manage*,..
	* 
	* @param array $options post data from form, used to decide whether to truncate database, which theme to use, and which channels etc
	* @return void
	*/
	public function generate($options)
	{
		$this->options = array_merge(
			array(
				"general"		=> array(),
				"channels"		=> array(),
				"categories"	=> array(),
			),
			$options
		);

		// parse the config xml and decide what we need to generate based on $options
		$this->parse_config();

		if (!$this->generateBefore()) return;

		if($this->options['general']['truncate_db'])
			$this->_truncateDB();

		$this->generateCategories();
		$this->generateStatuses();
		$this->generateCustomFields();
		$this->generateChannels();
		$this->generateRelationships();
		$this->generateEntries();
		$this->generateAfter();
	}

	/**
	* called after all generation is finished.
	* base implementation does nothing, but allows subclasses to override and do potentially meaningful things.
	* @return void
	*/
	protected function generateAfter() { return TRUE;  }

	/**
	* Generates the categories and sets the $this->saved_cat_groups array
	*/
	protected function generateCategoriesBefore() { return TRUE; }
	protected function generateCategories(){

        $category_groups_created = false;
		$this->_logTitle("Generating categories");

		if (!$this->generateCategoriesBefore()) return;

		// for each of our required cateories, those that were selected by the user in the config screen
		foreach ($this->category_groups as $cat_group_key => $cat_group)
		{
			if ($cat_group_id = $this->_checkExists(
										'category_groups',
										'group_id',
										array(
											'group_name' => $cat_group['attrs']['group_name'],
											'site_id' => $this->EE->config->item('site_id')
										)
								)
			)
			{
				$this->_logWarning('log_warning_category_group_exist', $cat_group['attrs']);
			}
			else
			{
				// build and execute an insert into the db for the category group
				$query = $this->db->insert('category_groups', $this->category_groups[$cat_group_key]['attrs']); 
				$cat_group['attrs']['group_id'] = $cat_group_id =  $this->db->insert_id();
				$this->_logSuccess("log_ok_created_cat_group", $cat_group['attrs'], 'success');
			}

			// set the group_id so we can use it the templates
			$this->category_groups[$cat_group_key]['attrs']['group_id'] = $cat_group_id;

			// if this category group has an array of categores
			// it could be just set to 'y' if the user doesn't choose any children
			// in which case we just skip to the next category group
			// the categories are not xml objects! they are a pure array
			if(is_array($cat_group['cats']))
			{
				// for each of the categories
				foreach ($cat_group['cats'] as $cat)
				{
					if ($cat_id = $this->_checkExists(
									'categories',
									'cat_id',
									array(
										'cat_url_title' => $cat['cat_url_title'],
										'group_id' => $cat_group_id
									)
								)
					)
					{
						$this->_logError('log_error_category_exist', array_merge(array("group_name" => $cat_group['attrs']['group_name']), $cat));
					}
					else
					{
						// assign the parent id for this category if it's a sub category
						if(
							empty($cat['parent_id']) === FALSE &&
							empty($this->category_groups[$cat_group_key]['cats'][ $cat['parent_id'] ]['cat_id']) === FALSE
						)
						{
							$cat['parent_id'] = $this->category_groups[$cat_group_key]['cats'][$cat['parent_id']]['cat_id'];
						}
						$cat 				= $this->defaults()->category($cat);
						$cat['group_id'] 	= $cat_group_id;
						
						// build and execute an insert into the db for the category group
						// $query 	= $this->db->query($this->db->insert_string('exp_categories', $cat));
						$query = $this->db->insert('categories', $cat);
						$cat_id = $cat["cat_id"] = $this->db->insert_id();
						
						$data['site_id'] = $this->EE->config->item('site_id');
            			$data['cat_id'] = $cat_id;
            			$data['group_id'] = $cat['group_id'];

            			$this->EE->db->insert('category_field_data', $data);
						$this->_logSuccess("log_ok_created_cat", array_merge(array("group_name" => $cat_group['attrs']['group_name']), $cat), 'success');
                        $category_groups_created = true;
					}
					$this->category_groups[$cat_group_key]['cats'][$cat['cat_url_title']] = $cat;
					$this->category_groups[$cat_group_key]['cats'][$cat['cat_url_title']]['cat_id'] = $cat_id;
				}
			}
		}
		$this->generateCategoriesAfter();

		if(!$category_groups_created) {
			$this->_log("log_notice_no_category_groups_created");
		}
	}
	protected function generateCategoriesAfter() { }


	/**
	* Generates the statuses and sets the $this->saved_status_groups array
	*/
	protected function generateStatusesBefore() { return TRUE; }
	protected function generateStatuses()
	{
        $status_groups_created = false;
		
		$this->_logTitle("Generating statuses");

		if (!$this->generateStatusesBefore()) return;

		// for each of our required cateories, those that were selected by the user in the config screen
		foreach ($this->status_groups as $status_group_key => $status_group)
		{

			// check if the group exists.
			if ($status_group_id = $this->_checkExists(
										'exp_status_groups',
										'group_id',
										array(
											'group_name' => $status_group['attrs']['group_name'],
											'site_id' => $this->EE->config->item('site_id')
										)
								)
			)
			{
				$this->_logWarning('log_warning_status_group_exist', $status_group['attrs']);
			}
			// else create it
			else
			{
				// build and execute an insert into the db for the category group
				// $query = $this->db->query( $this->db->insert_string('exp_status_groups', $this->status_groups[$status_group_key]['attrs']) );
				// $status_group['attrs']['group_id'] = $status_group_id =  $this->db->insert_id;
				$query = $this->db->insert('status_groups', $this->status_groups[$status_group_key]['attrs']);
				$status_group['attrs']['group_id'] = $status_group_id =  $this->db->insert_id();
				$this->_logSuccess("log_ok_created_status_group", $status_group['attrs']);
			}

			// set the group_id so we can use it the templates
			$this->status_groups[$status_group_key]['attrs']['group_id'] = $status_group_id;

			// process each status
			if(is_array($status_group['statuses']))
			{
				// for each of the custom fields
				foreach ($status_group['statuses'] as $status_key => $status)
				{
					if ($status_id = $this->_checkExists(
												'exp_statuses',
												'status_id',
												array(
													'status' 	=> $status['status'],
													'group_id'	=> $status_group_id
												)
										)
					)
					{
						$this->_logError("log_error_status_exist", array_merge(array("group_name" => $status_group['attrs']['group_name']), $status));
					}
					// else create it
					else
					{
						$status 					= $this->defaults()->status($status);
						$status['group_id'] 		= $status_group_id;
						// $query = $this->db->query( $this->db->insert_string('exp_statuses', $status) );
						// $status_id = $status['status_id'] = $this->db->insert_id;
						$query = $this->db->insert('exp_statuses', $status);
						$status_id = $status['status_id'] = $this->db->insert_id();
						$this->_logSuccess("log_ok_created_status", array_merge(array("group_name" => $status_group['attrs']['group_name']), $status));
						$status_groups_created = true;
					}

					// set the group_id so we can use it the templates
					$this->status_groups[$status_group_key]['statuses'][$status_id] = $status;
					$this->status_groups[$status_group_key]['statuses'][$status_id]['status_id'] = $status_id;
				}
			}
		}
		$this->generateStatusesAfter();
		
		if(!$status_groups_created) {
			$this->_log("log_notice_no_status_groups_created");
		}
		
	}
	protected function generateStatusesAfter() { }


	/**
	* Generates custom fields
	*/
	protected function generateCustomFieldsBefore() { return TRUE; }
	protected function generateCustomFields()
	{
        $custom_field_groups_created = false;
		
		$this->_logTitle("Generating custom fields");

		if (!$this->generateCustomFieldsBefore()) return;

		foreach ($this->custom_field_groups as $cfg_key => $cfg)
		{
			// check if the group exists.
			if ($cfg_id = $this->_checkExists(
										'exp_field_groups',
										'group_id',
										array(
											'group_name' => $cfg['attrs']['group_name']
										)
								)
			)
			{
				$this->_logWarning('log_warning_cfg_exist', $cfg['attrs']);
			}
			// else create it
			else
			{
				// build and execute an insert into the db for the category group
				$query = $this->db->insert('exp_field_groups',$this->custom_field_groups[$cfg_key]['attrs']);
				$cfg['attrs']['group_id'] = $cfg_id =  $this->db->insert_id();

				$this->_logSuccess("log_ok_created_cfg", $cfg['attrs']);
			}

			// set the group_id so we can use it the templates
			$this->custom_field_groups[$cfg_key]['attrs']['group_id'] = $cfg_id;

			if(is_array($cfg['fields']))
			{
				$count = 0;
				foreach ($cfg['fields'] as $f_key => $f)
				{
					if ($f_id = $this->_checkExists(
												'exp_channel_fields',
												'field_id',
												array(
													'field_name' => $f['field_name'],
													'group_id'	=> $cfg_id,
													'site_id' => $this->EE->config->item('site_id')
												)
										)
					)
					{
						$this->_logError("log_error_field_exist", 
											array_merge(
												array(
													"field_id" => $f_id,
													"group_name" => $cfg['attrs']['group_name']
												), $f
											));
					}
					// else create it
					else
					{
						$f = $this->defaults()->custom_field($f);

						// No settings
						$settings = FALSE;

						// Test the field settings, are they not an array
						if(!is_array($f['field_settings']))
							$settings = eval("return ".$f['field_settings'].";");

						// One more check
						if(!is_array($settings))
							$settings = array();

						$f['field_settings'] 	= base64_encode(serialize($settings));
						$f['field_order']		= $count;
						$f['group_id'] 			= $cfg_id;

						// Update the field if it's a date or REL
						if ($f['field_type'] == 'date' || $f['field_type'] == 'rel')
						{
							$f['field_fmt'] = 'none';
							$f['field_show_fmt'] = 'n';
						}

						$query = $this->db->insert('exp_channel_fields', $f);
						$f_id = $f['field_id'] = $this->db->insert_id();

						if ($f['field_type'] == 'date' OR $f['field_type'] == 'rel')
						{
							$this->db->query("ALTER TABLE exp_channel_data ADD COLUMN field_id_".$f_id." int(10) NOT NULL DEFAULT 0");
							$this->db->query("ALTER TABLE exp_channel_data ADD COLUMN field_ft_".$f_id." tinytext NULL");

							if ($f['field_type'] == 'date')
							{
								$this->db->query("ALTER TABLE exp_channel_data ADD COLUMN field_dt_".$f_id." varchar(8)");
							}
						}
						else
						{
							$this->db->query("ALTER TABLE exp_channel_data ADD COLUMN field_id_".$f_id." text");
							$this->db->query("ALTER TABLE exp_channel_data ADD COLUMN field_ft_".$f_id." tinytext NULL");
							$this->db->query("UPDATE exp_channel_data SET field_ft_".$f_id." = '".$f['field_fmt']."'");
						}

						foreach (array('none', 'br', 'xhtml') as $val)
						{
							$this->db->query("INSERT INTO exp_field_formatting (field_id, field_fmt) VALUES ('$f_id', '$val')");
						}

						$this->_logSuccess("log_ok_created_field", array_merge(array("group_name" => $cfg['attrs']['group_name']), $f));
                        $custom_field_groups_created = true;
						$count++;
					}

					// set the group_id so we can use it the templates
					// $this->custom_fields[$cfg_key]['fields'][$f_id] = $f;
					// $this->custom_fields[$cfg_key]['fields'][$f_id]['status_id'] = $f;
					$this->custom_field_groups[$cfg_key]['fields'][$f['field_name']] = $f;
					$this->custom_field_groups[$cfg_key]['fields'][$f['field_name']]['field_id'] = $f_id;
					$this->custom_field_groups[$cfg_key]['fields'][$f['field_name']]['group_id'] = $cfg_id;
				}
			}
		}

		if(!$custom_field_groups_created) {
			$this->_log("log_notice_no_custom_field_groups_created");
		}

		$this->generateCustomFieldsAfter();
	}
	protected function generateCustomFieldsAfter() { }


	/**
	* Generates the channels
	*/
	protected function generateChannelsBefore() { return TRUE; }
	protected function generateChannels(){

		$this->_logTitle("Generating channels");

        $channels_created = false;

		if (!$this->generateChannelsBefore()) return;

		foreach ($this->channels as $channel_name => &$channel)
		{
			// check if the group exists.
			if ($channel_id = $this->_checkExists(
										'channels',
										'channel_id',
										array(
											'channel_name' => $channel['attrs']['channel_name']
										)
								)
			)
			{
				$this->_logError('log_error_channel_exists', $channel['attrs']);
			}
			// else create it
			else
			{
				// Set the field group
				$channel['attrs']['field_group'] = (
					isset($channel['attrs']['field_group'])
					&& isset($this->custom_field_groups[ $channel['attrs']['field_group'] ]['attrs']['group_id'])
				) ? $this->custom_field_groups[$channel['attrs']['field_group']]['attrs']['group_id'] : '';

				// Set the category group
				$channel['attrs']['cat_group'] = (
					isset($channel['attrs']['cat_group'])
					&& isset($this->category_groups[$channel['attrs']['cat_group']]['attrs']['group_id'])
				) ? $this->category_groups[$channel['attrs']['cat_group']]['attrs']['group_id'] : '';

				// Set the status group
				$channel['attrs']['status_group'] = (
					isset($channel['attrs']['status_group'])
					&& isset($this->status_groups[$channel['attrs']['status_group']]['attrs']['group_id'])
				) ? $this->status_groups[$channel['attrs']['status_group']]['attrs']['group_id'] : '';

				$this->db->insert('channels', $channel['attrs']);
				$this->_logSuccess("log_ok_created_channel", array("channel_name" => $channel['attrs']['channel_name'], "channel_id" => $this->db->insert_id()));

				$channel_id = $this->db->insert_id();
				$channels_created = true;
			}

			$this->channels[$channel_name]['attrs']['channel_id'] = $channel_id;
		}
		
		if(!$channels_created) {
			$this->_log("log_notice_no_channels_created");
		}
		
		$this->generateChannelsAfter();

	}
	protected function generateChannelsAfter() { }


	/**
	* Generates the relationships
	*/
	protected function generateRelationshipsBefore() { return TRUE; }
	protected function generateRelationships(){

		$relationships_created = false;

		$this->_logTitle("Generating custom field relationships");
	
		if (!$this->generateRelationshipsBefore()) return;

		// if no channels have been created there is no need to check for relationships
		if(empty($this->channels)) return;

		// for each of the required custom field groups
		foreach ($this->custom_field_groups as $custom_field_group_name => $field_group)
		{
			// check for group_id; might not be set if the custom_field_group didn't end up being saved
			if(!isset($field_group['attrs']['group_id']))
				continue;

			// if there are fields
			// sometimes user might just generate the group and not the fields
			if(isset($field_group['fields']))
			{
				// loop through the fields
				foreach ($field_group['fields'] as $field)
				{
					if(isset($field['field_related_id']) && isset($this->channels[$field['field_related_id']]))
					{
						// update the channel_data table 
						$this->db->query("ALTER TABLE `exp_channel_data` CHANGE COLUMN field_id_" . $field['field_id'] . " field_id_" . $field['field_id'] . " int(10) NOT NULL;");
						$this->db->query("ALTER table `exp_channel_data` CHANGE COLUMN field_ft_" . $field['field_id'] . " field_ft_" . $field['field_id'] . " varchar(40) NOT NULL default 'none';");

						$this->db->update(
							'channel_fields',
							array(
								'field_type' => 'rel',
								'field_related_id' => $this->channels[$field['field_related_id']]['attrs']['channel_id']
							),
							array(
								"field_id" => $field['field_id']
							)
						);
						
						$relationships_created = true;
						$this->_logSuccess(lang("log_ok_created_relationship"),
							array(
								'field_name' => $field['field_name'],
								'partner_field_name' => $this->channels[$field['field_related_id']]['attrs']['field_name']
							)
						);
						
						
					}
					// clear old cache records from the relationships table
					// $this->db->query("UPDATE exp_relationships SET rel_data = '', reverse_rel_data = '';");
				}
			}
		}
		
		if(!$relationships_created) {
			$this->_log("log_notice_no_field_relationships_created");
		}
		
	}
	protected function generateRelationshipsAfter() { return TRUE; }


	/**
	* Generate entries
	*/
	protected function generateEntriesBefore() { return TRUE; }
	protected function generateEntries()
	{
	    $entries_created = false;
		$this->_logTitle("Generating channel entries");
	
		if (!$this->generateEntriesBefore()) return;

		// if no channels have been created there is no need to check for entris
		if(empty($this->channels)) return;

		$this->EE->load->library('api');
		$this->EE->api->instantiate('channel_entries');
		$this->EE->api->instantiate('channel_fields');

		foreach ($this->channels as $channel) {
		    
			$channel_id = $channel['attrs']['channel_id'];

			$channel_field_query = $this->EE->db->query("SELECT f.field_name, f.field_id 
			                                FROM exp_channel_fields as f 
			                                JOIN exp_channels as c 
			                                WHERE c.field_group = f.group_id
			                                AND c.channel_id = " . $channel_id);
            $channel_fields = array();
			foreach($channel_field_query->result_array() as $field) {
			    $channel_fields[$field['field_name']] = $field['field_id'];
			}

			$channel_field_query = $this->EE->db->query("SELECT f.field_name, f.field_id 
			                                FROM exp_channel_fields as f 
			                                JOIN exp_channels as c 
			                                WHERE c.field_group = f.group_id
			                                AND c.channel_id = " . $channel_id);
            $channel_fields = array();
			foreach($channel_field_query->result_array() as $field) {
			    $channel_fields[$field['field_name']] = $field['field_id'];
			}

			$catgeory_query = $this->EE->db->query("SELECT c.cat_url_title, c.cat_id 
			                                FROM exp_categories as c
			                                JOIN exp_channels as ch 
			                                WHERE ch.cat_group = c.group_id
			                                AND ch.channel_id = " . $channel_id);
            $channel_categories = array();
			foreach($catgeory_query->result_array() as $cat) {
			    $channel_categories[$cat['cat_url_title']] = $cat['cat_id'];
			}

			foreach($channel['entries'] as $entry) {
			    $data = array();

                foreach ($entry->field as $field) {
				    $field_attrs = $this->xmlAttributes($field->attributes());
				    $field_data = (string)$field;
				    $field_id = $field_attrs['id'];
                    $data[$field_id] = $field_data;
                }

				foreach ($entry->custom_field as $field) {
				    $field_attrs = $this->xmlAttributes($field->attributes());
				    $field_data = (string)$field;
				    if(false === isset($channel_fields[$field_attrs['id']])) {
				        continue;
				    }
				    $field_id = $channel_fields[$field_attrs['id']];
				    
					$data["field_id_".$field_id] = $field_data;
					if(true === isset($field_attrs['formatting'])) {
    					$data["field_ft_".$field_id] = $field_attrs['formatting'];
					}
				}

                foreach ($entry->category as $category) {
                    $cat_id = (string)$category['id'];
				    if(false === isset($channel_categories[$cat_id])) {
				        continue;
				    }
                    $data['category'][] = $channel_categories[$cat_id];
                }

                if(false === isset($data['entry_date'])) {
                    $data['entry_date'] = time();
                }

				$this->EE->api_channel_fields->setup_entry_settings($channel_id, $data);
				if ($this->EE->api_channel_entries->submit_new_entry($channel_id, $data) === FALSE)
                {
                    $this->_logError(lang("log_error_creating_entry"), array("entry_title" => $data["title"]));
                } else {
                    $this->_logSuccess(lang("log_ok_created_entry"), array("entry_title" => $data["title"]));
                    $entries_created = false;
                }
			}
		}

		$this->generateEntriesAfter();
		if(!$entries_created) {
			$this->_log("log_notice_no_entries_created");
		}
	}
	protected function generateEntriesAfter() { return TRUE; }


	/**
	* Returns an array of attributes
	* 
	* @param	$obj	SimpleXML object
	* @return			Array				A simple array of element attributes
	*/
	protected function xmlAttributes($obj)
	{
		$array = array();
		foreach ($obj as $key => $value) {
			$array[$key] = (string)$value;
		}
		return $array;
	} 

	/**
	* getLog
	* public getter for log array
	*/
	public function getLog() {
		return $this->log;
	}

	protected function _log($text, $replacements = array(), $type='') {
		$text = lang($text);
		foreach ($replacements as $k => $v) {
			if(!is_array($v)) {
				$text = str_replace('{'.$k.'}', $v, $text);
			}
		}
		$this->log[] = array('type' => $type, 'text' => $text);
	}

	protected function _logSuccess($lang_key, $replacements = array()){
		return $this->_log($lang_key, $replacements, 'success');
	}

	protected function _logError($lang_key, $replacements = array()){
		return $this->_log($lang_key, $replacements, 'error');
	}

	protected function _logWarning($lang_key, $replacements = array()){
		return $this->_log($lang_key, $replacements, 'warning');
	}

	protected function _logTitle($lang_key, $replacements = array()){
		return $this->_log($lang_key, $replacements, 'title');
	}

	/**
	 * Check if a record exists in the DB
	 */
	protected function _checkExists($table, $id, $search_arr){
		$query = $this->db->get_where($table, $search_arr)->row();
		return empty($query) ? FALSE : $query->$id;
	}

	/**
	 * Truncate the DB
	 */
	protected function _truncateDB()
	{
		$sql = array();

		// remove templates
		$sql[] = "TRUNCATE TABLE `exp_template_groups`";
		$sql[] = "TRUNCATE TABLE `exp_templates`";

		// remove categories
		$sql[] = "TRUNCATE TABLE `exp_category_posts`";
		$sql[] = "TRUNCATE TABLE `exp_categories`";
		$sql[] = "TRUNCATE TABLE `exp_category_groups`";
		$sql[] = "TRUNCATE TABLE `exp_category_field_data`";

		// remove categories
		$sql[] = "TRUNCATE TABLE `exp_statuses`";
		$sql[] = "TRUNCATE TABLE `exp_status_groups`";

		// remove channel entries
		$sql[] = "TRUNCATE TABLE `exp_channels`";
		$sql[] = "TRUNCATE TABLE `exp_channel_titles`";
		$sql[] = "TRUNCATE TABLE `exp_channel_data`";
		$sql[] = "TRUNCATE TABLE `exp_channel_fields`";
		$sql[] = "TRUNCATE TABLE `exp_field_groups`";
		$sql[] = "TRUNCATE TABLE `exp_field_formatting`";


		$sql[] = "TRUNCATE TABLE `exp_extensions`";
		$sql[] = "DELETE FROM  `exp_accessories` WHERE `class` <> 'Nsm_morphine_theme_acc'";
		$sql[] = "DELETE FROM `exp_modules` WHERE `module_name` <> 'Nsm_site_generator'";
		$sql[] = "TRUNCATE TABLE `exp_actions`";
		$sql[] = "TRUNCATE TABLE exp_html_buttons;";


		$query = $this->db->query("show columns from exp_channel_data");
		$sql_parts = array();
		if($query->num_rows > 0)
		{
			foreach ($query->result_array() as $result)
			{
				if(in_array($result['Field'], array("entry_id", "site_id", "channel_id")) === FALSE)
				{
					// remove the existing field data because we are going to add our own
					$sql_parts[] = "DROP " . $result['Field'];
				}
			}
			if(!empty($sql_parts))
			{
				$sql[] = "ALTER TABLE `exp_channel_data` " . implode(", ", $sql_parts);
			}
		}

		foreach ($sql as $sql_string)
			$this->db->query($sql_string);

		$this->_logSuccess("log_ok_truncate_db");
	}

	/**
	* defaults
	* return a new Nsm_site_generator_gen_defaults for use with config file inputs
	* override in subclasses if necessary, to return your own class
	* @see Nsm_site_generator_gen_defaults
	*/
	public function defaults()
	{
		return new Nsm_site_generator_gen_defaults();
	}
}


/**
* Nsm_site_generator_gen_defaults
*
* Provides a function for most types of data in the config xml. e.g. category_group(), channel(), custom_field().
* Each function takes a $data array: the attributes of the xml element, and returns an array with defaults set and unnecessary attributes unset.
*/
class Nsm_site_generator_gen_defaults
{
	protected $site_id;

	public function __construct()
	{
		$this->EE =& get_instance();
		$this->site_id = $this->EE->config->item("site_id");
	}

	public function category_group($data)
	{
		unset($data['id']);
		return array_merge(array(
			'site_id'				=> $this->site_id,
			'sort_order'			=> 'a',
			'field_html_formatting'	=> 'all',
			'can_edit_categories'	=> FALSE,
			'can_delete_categories'	=> FALSE
		), $data);
	}

	public function category($data)
	{
		unset($data['id']);
		return array_merge(array(
			'site_id'				=> $this->site_id,
			'parent_id'				=> 0
		), $data);
	}

	public function status_group($data)
	{
		unset($data['id']);
		return array_merge(array(
			'site_id'				=> $this->site_id
		), $data);
	}

	public function status($data)
	{
		unset($data['id']);
		return array_merge(array(
			'site_id'				=> $this->site_id
		), $data);
	}

	public function custom_field_group($data)
	{
		unset($data['id']);
		return array_merge(array(
			'site_id'			=> $this->site_id
		), $data);
	}

	public function custom_field($data)
	{
		unset($data['id']);
		return array_merge(array(
			'field_instructions'		=> '',
			'field_pre_channel_id'		=> 0,
			'field_pre_field_id'		=> 0,
			'field_pre_populate'		=> 'n',
			'field_related_to'			=> 'channel',
			'field_maxl'				=> 128,
			'field_ta_rows'				=> 6,
			'field_related_id'			=> '',
			'field_related_orderby'		=> 'title',
			'field_related_sort'		=> 'desc',
			'field_related_max'			=> '0',
			'field_fmt'					=> 'none',
			'field_show_fmt'			=> 'n',
			'field_text_direction'		=> 'ltr',
			'field_type'				=> 'text',
			'field_required'			=> 'n',
			'field_search'				=> 'n',
			'field_is_hidden'			=> 'n',
			'site_id'					=> $this->site_id,
			'field_settings'			=> array()
		), $data);
	}

	public function template_group($data)
	{
		unset($data['id']);
		return array_merge(array(
			'site_id'			=> $this->site_id,
			'is_site_default'	=> 'n',
			'is_user_blog'		=> 'n'
		), $data);
	}

	public function template($data)
	{
		unset($data['id']);
		return array_merge(array(
			'site_id'				=> $this->site_id,
			'template_name'			=> 'index',
			'save_template_file'	=> 'y',
			'template_type'			=> 'webpage',
			'edit_date'				=> 0,
			'cache'					=> 'n',
			'enable_http_auth'		=> 'n',
			'allow_php'				=> 'n',
			'php_parse_location'	=> 'o'
		), $data);
	}

	public function channel($data)
	{
		unset($data['id']);
		return array_merge(array(
			'site_id' 				=> $this->site_id,
			'channel_lang' 			=> $this->EE->config->item('xml_lang')
		), $data);
	}
}