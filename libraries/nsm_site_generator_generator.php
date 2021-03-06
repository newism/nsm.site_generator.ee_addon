<?php

class NsmSiteGeneratorGenerator
{
    protected $EE;
    protected $siteId;
    protected $tab = "    ";

    public $exportDirectory;
    public $importDirectory;

    protected $title = false;
    protected $version = false;
    protected $description = false;
    protected $downloadUrl = false;
    protected $postImportInstructions = false;
    protected $requirements = array();
    protected $authors = array();

    protected $categoryGroups = array();
    protected $statusGroups = array();
    protected $fieldGroups = array();
    protected $channels = array();
    protected $templateGroups = array();
    protected $globalVariables = array();
    protected $snippets = array();

    public function __construct()
    {
        $this->EE =& get_instance();
        $this->siteId = $this->EE->config->item("site_id");
    }

    /**
     * Parsing array config
     */

    public function parseArrayConfig($array)
    {
        $this->parseArrayThemeInfo($array);
        $this->parseArrayCategoryGroups($array);
        $this->parseArrayStatusGroups($array);
        $this->parseArrayFieldGroups($array);
        $this->parseArrayChannels($array);
        $this->parseArrayChannelFields($array);
        $this->parseArrayTemplateGroups($array);
        $this->parseArrayGlobalVariables($array);
        $this->parseArraySnippets($array);
    }

    public function parseArrayThemeInfo($array)
    {
        $themeInfoKeys = array("title", "version", "description", "downloadUrl", "postImportInstructions");
        foreach ($themeInfoKeys as $key) {
            $this->$key = false;
            if(array_key_exists($key, $array)) {
                $this->$key = $array[$key];
            }
        }

        $this->authors = array();
        if(in_array('authors', $array)) {
            foreach ($array['authors'] as $author) {
                $this->authors[] = array(
                    "name" => $author->name,
                    "url" => $author->url
                );
            }
        }

        $this->requirements = array();
        if(in_array('requirements', $array)) {
            foreach ($array['requirements'] as $requirement) {
                $this->requirements[] = array(
                    "name" => $requirement->name,
                    "version" => $requirement->version,
                    "url" => $requirement->url
                );
            }
        }
    }

    public function parseArrayCategoryGroups($array)
    {
        $this->categoryGroups = array();

        // Loop over all the category groups in the config

        if(array_key_exists('category_groups', $array)) {
            foreach ($array['category_groups'] as $count => $categoryGroup) {

                $key = 'category_group_' . $categoryGroup['group_id'];

                $categoryGroupConfig = array_merge($categoryGroup, array(
                    'categories' => array(),
                    'group_ref_id' => 'cat_group_' . $categoryGroup['group_id']
                ));

                // recursively build this category groups category config in a single depth array
                $categoryGroupConfig['categories'] = $this->parseArrayCategories($categoryGroup['categories']);

                $this->categoryGroups[$key] = $categoryGroupConfig;
            }
        }
    }

    public function parseArrayCategories($categories) {
        $newCategories = array();
        foreach ($categories as $categoryKey => $categoryData) {
            $newCategories['category_'.$categoryKey] = array_merge($categoryData,
                array(
                    'site_id'               => $this->siteId,
                    'parent_id'             => ($categoryData['parent_id']) ? 'category_' . $categoryData['parent_id'] : false,
                    'categories'            => $this->parseArrayCategories($categoryData['categories'])
            ));
        }
        return $newCategories;
    }

    public function parseArrayStatusGroups($array)
    {
        $this->statusGroups = array();

        // Loop over all the category groups in the config
        if(array_key_exists('status_groups', $array)) {

            foreach ($array['status_groups'] as $count => $sg) {

                $key = 'status_group_' . $sg['group_id'];

                $statusGroupConfig = array_merge($sg, array(
                    'statuses' => array(),
                    'group_ref_id' => $key
                ));

                foreach ($sg["statuses"] as $statusData) {
                    $statusGroupConfig['statuses'][$statusData['status']] = array_merge(array(
                    ), $statusData);;
                }

                $this->statusGroups[$key] = $statusGroupConfig;
            }
        }
    }

    public function parseArrayFieldGroups($array)
    {
        $this->fieldGroups = array();

        // Loop over all the category groups in the config
        if(array_key_exists('field_groups', $array)) {
            foreach ($array['field_groups'] as $count => $fg) {

                $key = 'field_group_' . $fg['group_id'];

                $fieldGroupConfig = array_merge($fg, array(
                    'channel_fields' => array(),
                    'group_ref_id' => $key
                ));

                $this->fieldGroups[$fieldGroupConfig['group_ref_id']] = $fieldGroupConfig;
            }
        }
    }

    public function parseArrayChannelFields($array)
    {
        if(array_key_exists('field_groups', $array)) {
            foreach ($array['field_groups'] as $count => $fg) {

                $channelFields = array();
                foreach ($fg['channel_fields'] as $fieldKey => $fieldData) {

                    $key = 'field_group_' . $fg['group_id'];

                    // Tweak relationships
                    if(false == empty($fieldData['field_related_id'])) {
                        $channelKey = 'channel_'.$fieldData['field_related_id'];
                        $fieldData['field_related_id'] = "channel_".$fieldData['field_related_id'];
                    }

                    $channelFields[$fieldData['field_name']] = array_merge(array(
                    ), $fieldData);
                }
                $this->fieldGroups[$key]['channel_fields'] = $channelFields;
            }
        }
    }

    public function parseArrayChannels($array)
    {
        // Loop over all the category groups in the config
        $this->channels = array();

        if(array_key_exists('channels', $array)) {

            foreach ($array['channels'] as $count => $channel) {

                $key = 'channel_' . $channel['channel_id'];

                $channelConfig = array_merge($channel, array(
                    'entries' => array(),
                    'group_ref_id' => $key,
                ));

                foreach ($channel['entries'] as $entry) {
                    $channelConfig['entries'][$entry['entry_id']] = array_merge(array(

                    ), $entry);
                    $channelConfig['total_entries']++;
                }

                if(false == empty($channelConfig['status_group'])) {
                    $channelConfig['status_group'] = "status_group_" . $channelConfig['status_group'];
                }
                
                if(false == empty($channelConfig['field_group'])) {
                    $channelConfig['field_group'] = "field_group_" . $channelConfig['field_group'];
                }

                if(false == empty($channelConfig['cat_group'])) {
                    $channelConfig['cat_group'] = str_replace("|","cat_group_", "cat_group_" . $channelConfig['cat_group']);
                }

                $this->channels[$channelConfig['group_ref_id']] = $channelConfig;
            }
        }
    }

    public function parseArrayTemplateGroups($array)
    {
        // Loop over all the category groups in the config
        $this->templateGroups = array();
        if(array_key_exists('template_groups', $array)) {
            foreach ($array['template_groups'] as $count => $tg) {

                $key = 'template_group_' . $tg['group_id'];

                $templateGroupConfig = array_merge($tg, array(
                    'templates' => array(),
                    'group_ref_id' => 'template_group_' . $tg['group_id']
                ));

                foreach ($tg['templates'] as $templateKey => $templateData) {
                    $templateGroupConfig['templates'][$templateData['template_name']] = array_merge(array(
                    ), $templateData);
                }

                $this->templateGroups[$templateGroupConfig['group_ref_id']] = $templateGroupConfig;
            }
        }
    }

    public function parseArrayGlobalVariables($array)
    {
        $this->globalVariables = array();
        if(array_key_exists('global_variables', $array)) {
            foreach ($array['global_variables'] as $variable) {
                $this->globalVariables[$variable['variable_name']] = $variable;
            }
        }
    }

    public function parseArraySnippets($array)
    {
        $this->snippets = array();
        if(array_key_exists('snippets', $array)) {
            foreach ($array['snippets'] as $snippet) {
                $this->snippets[$snippet['snippet_name']] = $snippet;
            }
        }
    }

    /**
     * Parse XML Config
     */
    public function parseXmlConfig($xmlConfig)
    {
        $this->parseXmlThemeInfo($xmlConfig);
        $this->parseXmlCategoryGroups($xmlConfig);
        $this->parseXmlStatusGroups($xmlConfig);
        $this->parseXmlCategoryGroups($xmlConfig);
        $this->parseXmlFieldGroups($xmlConfig);
        $this->parseXmlChannels($xmlConfig);
        $this->parseXmlChannelFields($xmlConfig);
        $this->parseXmlTemplateGroups($xmlConfig);
        $this->parseXmlGlobalVariables($xmlConfig);
        $this->parseXmlSnippets($xmlConfig);
    }

    protected function parseXmlThemeInfo($xmlConfig)
    {
        $themeInfoKeys = array("title", "version", "description", "downloadUrl", "postImportInstructions");
        foreach ($themeInfoKeys as $key) {
            $this->$key = false;
            if($node = $xmlConfig->xpath('/'.$key.'[1]')) {
                $this->$key = $node[0];
            }
        }

        $this->authors = array();
        if($authors = $xmlConfig->xpath('/authors')) {
            foreach ($authors as $author) {
                
                $this->authors[] = array(
                    "name" => $author->name,
                    "url" => $author->url
                );
            }
        }

        $this->requirements = array();
        if($requirements = $xmlConfig->xpath('/requirements[@type=addon]')) {
            foreach ($requirements as $requirement) {
                $this->requirements[] = array(
                    "name" => $requirement->name,
                    "version" => $requirement->version,
                    "url" => $requirement->url
                );
            }
        }
    }

    protected function parseXmlCategoryGroups($xmlConfig)
    {
        // Loop over all the category groups in the config
        foreach ($xmlConfig->xpath('//category_groups/category_group') as $cg)
        {
            $key = (string)$cg['group_ref_id'];
            $this->categoryGroups[$key] = $this->attributes($cg);
            $this->categoryGroups[$key]['categories'] = $this->parseXmlCategories($cg->category);
        }
    }

    public function parseXmlCategories($categories) {

        $newCategories = array();
        foreach ($categories as $categoryData) {
            $childCategories = $categoryData->category;
            $categoryData = $this->attributes($categoryData);
            $newCategories[] = array_merge(array(
                'site_id'               => $this->siteId,
                'parent_id'             => false,
                'categories'            => $this->parseXmlCategories($childCategories)
            ), $categoryData);
        }
        return $newCategories;
    }

    protected function parseXmlStatusGroups($xmlConfig)
    {
        // Loop over all the status groups in the config
        foreach ($xmlConfig->xpath('//status_groups/status_group') as $sg) {
            $key = (string)$sg['group_ref_id'];
            $this->statusGroups[$key] = $this->attributes($sg);
            $this->statusGroups[$key]['statuses'] = array();
            foreach ($sg->status as $status) {
                $statusKey = (string)$status['status'];
                $this->statusGroups[$key]['statuses'][$statusKey] = $this->attributes($status);
            }
        }
    }

    protected function parseXmlFieldGroups($xmlConfig)
    {
        foreach ($xmlConfig->xpath('//field_groups/field_group') as $cfg) {
            // get the custom field group
            $key = (string)$cfg['group_ref_id'];
            $this->fieldGroups[$key] = $this->attributes($cfg);
        }
    }

    protected function parseXmlChannels($xmlConfig)
    {
        // Loop over channels
        foreach ($xmlConfig->xpath('//channels/channel') as $channel) {
            $key = (string)$channel['group_ref_id'];
            $this->channels[$key] = $this->attributes($channel);
            $this->channels[$key]['entries'] = array();

            // Loop over channel entries
            foreach ($channel->entry as $count => $entry) {
                $new_entry = $this->attributes($entry);

                // Loop over channel entry fields
                foreach ($entry->channel_field as $field) {
                    $fieldKey = (string)$field['field_name'];
                    $fieldAttrs = $this->attributes($field);
                    $fieldAttrs['data'] = (string)$field;
                    $new_entry['channel_fields'][$fieldKey] = $fieldAttrs;
                }

                $this->channels[$key]['entries'][] = $new_entry;
            }
        }
    }

    public function parseXmlChannelFields($xmlConfig)
    {
        foreach ($xmlConfig->xpath('//field_groups/field_group') as $cfg) {
            // get the custom field group
            $key = (string)$cfg['group_ref_id'];
            $this->fieldGroups[$key]['channel_fields'] = array();
            foreach ($cfg->channel_field as $channelField) {

                $fieldKey = (string)$channelField['field_name'];
                $fieldAttrs = $this->attributes($channelField);

                $this->fieldGroups[$key]['channel_fields'][$fieldKey] = $fieldAttrs;
            }
        }
    }

    protected function parseXmlTemplateGroups($xmlConfig)
    {
        foreach ($xmlConfig->xpath('//template_groups/template_group') as $tg) {
            // get the custom template group
            $key = (string)$tg['group_name'];
            $this->templateGroups[$key] = $this->attributes($tg);
            $this->templateGroups[$key]['templates'] = array();
            foreach ($tg->template as $template) {
                $templateKey = (string)$template['template_name'];
                $this->templateGroups[$key]['templates'][$templateKey] = $this->attributes($template);
                $this->templateGroups[$key]['templates'][$templateKey]['template_data'] = (string)$template;
            }
        }
    }

    protected function parseXmlSnippets($xmlConfig)
    {
        foreach ($xmlConfig->xpath('//snippets/snippet') as $snippet) {
            $key = (string)$snippet['snippet_name'];
            $this->snippets[$key] = $this->attributes($snippet);
            $this->snippets[$key]['snippet_contents'] = read_file("{$this->importDirectory}/snippets/{$key}");
        }
    }

    protected function parseXmlGlobalVariables($xmlConfig)
    {
        foreach ($xmlConfig->xpath('//global_variables/variable') as $global_variable) {
            $key = (string)$global_variable['variable_name'];
            $this->globalVariables[$key] = $this->attributes($global_variable);
            $this->globalVariables[$key]['variable_data'] = read_file("{$this->importDirectory}/global_variables/{$key}");
        }
    }

    /**
     * Import
     */
    public function import()
    {
        $this->logTitle("Starting Import");

        $this->importCategoryGroups();
        $this->importStatusGroups();
        $this->importFieldGroups();
        $this->importChannels();
        $this->importChannelFields();
        $this->importChannelEntries();
        $this->importTemplateGroups();
        $this->importSnippets();
        $this->importGlobalVariables();
    }

    protected function importCategoryGroups()
    {
        $this->EE->load->model('category_model');

        $categoryGroupsCreated = false;
        $this->logTitle("Generating categories");

        $categoryGroupFields = array_flip(explode(' ', 'site_id sort_order field_html_formatting can_edit_categories can_delete_categories group_name'));

        $existingCategoryGroups = $this->hydrate($this->EE->category_model->get_category_groups()->result_array(), 'group_name');

        // Generate Category Groups
        foreach ($this->categoryGroups as $categoryGroupKey => $categoryGroupData) {

            if(array_key_exists($categoryGroupData['group_name'], $existingCategoryGroups)) {
                $this->logWarning('log_warning_category_group_exist', $categoryGroupData);
                $categoryGroupData = array_merge($categoryGroupData, $existingCategoryGroups[$categoryGroupData['group_name']]);
            } else {

                $categoryGroupData = array_merge(array(
                    'site_id'               => $this->siteId,
                    'sort_order'            => 'a',
                    'field_html_formatting' => 'all',
                    'can_edit_categories'   => FALSE,
                    'can_delete_categories' => FALSE
                ), $categoryGroupData);

                $this->EE->category_model->insert_category_group(array_intersect_key(
                    $categoryGroupData,
                    $categoryGroupFields
                ));

                $categoryGroupData['group_id'] = $this->EE->db->insert_id();
                $this->logSuccess("log_ok_category_group_imported", $categoryGroupData);
                $categoryGroupsCreated = true;

            }

            $result = $this->importCategories($categoryGroupData['categories'], 0, $categoryGroupData);
            $newCategoryCount = $result['new_category_count'];
            $categoryGroupData['categories'] = $result['categories'];

            if(false == $newCategoryCount) {
                $this->logWarning("No categories created", array(), 1);
            }


            // Re-assign back to the generator
            $this->categoryGroups[$categoryGroupKey] = $categoryGroupData;
        }

        if(false == $categoryGroupsCreated) {
            $this->logWarning("No category groups created");
        }

    }

    protected function importCategories($categories, $parentId, $categoryGroup)
    {
        $newCategories = array();
        $newCategoryCount = 0;

        $categoryFields = array_flip(explode(' ', 'site_id parent_id group_id cat_name cat_url_title cat_description cat_image cat_order'));

        $existingCategories = $this->hydrate($this->EE->category_model->get_channel_categories($categoryGroup["group_id"], "*")->result_array(), 'cat_url_title');

        foreach ($categories as $categoryData) {

            if(array_key_exists($categoryData['cat_url_title'], $existingCategories)) {

                $this->logWarning('log_warning_category_exist', 
                                    array_merge($categoryData, array('group_name' => $categoryGroup["group_name"])),
                                    1
                                );
                $categoryData = array_merge($categoryData, $existingCategories[$categoryData['cat_url_title']]);

            } else {

                $categoryData = array_merge($categoryData, array(
                    'site_id'               => $this->siteId,
                    'parent_id'             => $parentId,
                    'group_id'              => $categoryGroup['group_id']
                ));

                $this->EE->db->insert('categories', array_intersect_key(
                    $categoryData,
                    $categoryFields
                ));

                $categoryData['cat_id'] = $this->EE->db->insert_id();
                $newCategoryCount++;

                $this->logSuccess("log_ok_category_imported", 
                                    array_merge($categoryData, array('group_name' => $categoryGroup["group_name"])),
                                    1
                                );
            }

            if(false == empty($categoryData['categories'])) {
                $result = $this->importCategories($categoryData['categories'], $categoryData['cat_id'], $categoryGroup);
                $categoryData['categories'] = $result["categories"];
                $newCategoryCount = $newCategoryCount + $result["new_category_count"];
            }

            $newCategories[] = $categoryData;
        }

        return array(
            "categories" => $newCategories,
            "new_category_count" => $newCategoryCount
        );

    }

    protected function importStatusGroups()
    {
        $this->EE->load->model('status_model');

        $statusGroupsCreated = false;
        $this->logTitle("Generating statuses");

        $statusGroupFields = array_flip(explode(' ', 'site_id group_name'));
        $statusFields = array_flip(explode(' ', 'site_id group_id, status, status_order, highlight'));

        $existingStatusGroups = $this->hydrate($this->EE->status_model->get_status_groups()->result_array(), 'group_name');


        // Generate Status Groups
        foreach ($this->statusGroups as $statusGroupKey => $statusGroupData) {

            if(array_key_exists($statusGroupData['group_name'], $existingStatusGroups)) {
                $statusGroupData = array_merge($statusGroupData, $existingStatusGroups[$statusGroupData['group_name']]);
                $this->logWarning('log_warning_status_group_exist', $statusGroupData);
            } else {
                
                $statusGroupData = array_merge(array(
                    'site_id' => $this->siteId,
                ), $statusGroupData);
                
                $this->EE->db->insert('status_groups', array_intersect_key(
                    $statusGroupData,
                    $statusGroupFields
                ));
                $statusGroupData['group_id'] = $this->EE->db->insert_id();
                $this->logSuccess("log_ok_category_group_imported", $statusGroupData);
                $statusGroupsCreated = true;
            }

            $statusesCreated = false;

            // Generate Statuses
            foreach ($statusGroupData['statuses'] as $statusKey => $statusData) {

                $existingStatuses = $this->hydrate($this->EE->status_model->get_statuses($statusGroupData['group_id'])->result_array(), 'status');
                $statusData['group_id'] = $statusGroupData['group_id'];

                if(array_key_exists($statusData['status'], $existingStatuses)) {
                    $statusData = array_merge($statusData, $existingStatuses[$statusData['status']]);
                    $this->logWarning("log_error_status_exist", array_merge($statusData, array("group_name" => $statusGroupData['group_name'])), 1);
                } else {

                    $statusData = array_merge(array(
                        'site_id' => $this->siteId
                    ), $statusData);

                    $this->EE->db->insert('statuses', $statusData);
                    $statusData['status_id'] = $this->EE->db->insert_id();
                    $this->logSuccess("log_ok_status_imported", array_merge($statusData, array("group_name" => $statusGroupData['group_name'])), 1);
                    $statusesCreated = true;
                }

                $statusGroupData['statuses'][$statusKey] = $statusData;

            }

            if(false == $statusesCreated) {
                $this->logWarning("No statuses created", array(), 1);
            }

            // Reassign back into the generator
            $this->statusGroups[$statusGroupKey] = $statusGroupData;
        }

        if(false == $statusGroupsCreated) {
            $this->logWarning("No status groups created");
        }
    }

    protected function importFieldGroups()
    {
        $this->EE->load->model('field_model');
        $this->EE->load->library('api');
        $this->EE->api->instantiate('channel_fields');

        $fieldGroupsCreated = false;
        $this->logTitle("Generating field groups");

        $fieldGroupFields = array_flip(explode(' ', 'site_id group_name'));
        $fieldFields = array_flip(explode(' ', 'site_id group_id, field, field_order, highlight'));

        $existingFieldGroups = $this->hydrate($this->EE->field_model->get_field_groups()->result_array(), 'group_name');

        // Generate Field Groups
        foreach ($this->fieldGroups as $fieldGroupKey => $fieldGroupData) {

            if(array_key_exists($fieldGroupData['group_name'], $existingFieldGroups)) {
                $fieldGroupData = array_merge($fieldGroupData, $existingFieldGroups[$fieldGroupData['group_name']]);
                $this->logWarning('log_warning_field_group_exist', $fieldGroupData);
            } else {
                
                $fieldGroupData = array_merge(array(
                    'site_id' => $this->siteId
                ), $fieldGroupData);

                $this->EE->db->insert('field_groups', array_intersect_key(
                    $fieldGroupData,
                    $fieldGroupFields
                ));

                $fieldGroupData['group_id'] = $this->EE->db->insert_id();
                $this->logSuccess("log_ok_field_group_imported", $fieldGroupData);
                $fieldGroupsCreated = true;
            }

            // Reassign back into the generator
            $this->fieldGroups[$fieldGroupKey] = $fieldGroupData;
        }

        if(false == $fieldGroupsCreated) {
            $this->logWarning("No field groups created");
        }

    }

    protected function importChannelFields()
    {
        $fieldsCreated = false;
        $this->logTitle("Generating custom fields");

        // Loop over the field groups
        foreach ($this->fieldGroups as $fieldGroupKey => $fieldGroupData) {

            // Generate Fields
            foreach ($fieldGroupData['channel_fields'] as $fieldKey => $fieldData) {

                $existingFields = $this->hydrate($this->EE->field_model->get_fields($fieldGroupData['group_id'])->result_array(), 'field_name');
                $fieldData['group_id'] = $fieldGroupData['group_id'];

                $dateOrRel = in_array($fieldData['field_type'], array('date', 'rel'));

                if(array_key_exists($fieldData['field_name'], $existingFields)) {

                    $fieldData = array_merge($fieldData, $existingFields[$fieldData['field_name']]);
                    $this->logWarning("log_error_field_exist", array_merge($fieldData, array("group_name" => $fieldGroupData["group_name"])));

                } else {

                    var_dump($fieldData['field_related_id']);

                    // Tweak the field related ID
                    $fieldData['field_related_id'] = (
                        false == empty($fieldData['field_related_id'])
                        && isset($this->channels[ $fieldData['field_related_id'] ]['channel_id'])
                    ) ? $this->channels[ $fieldData['field_related_id'] ]['channel_id'] : '';

                    $fieldData = array_merge(array(
                        'field_instructions'        => '',
                        'field_pre_channel_id'      => 0,
                        'field_pre_field_id'        => 0,
                        'field_pre_populate'        => 'n',
                        'field_related_to'          => 'channel',
                        'field_maxl'                => 128,
                        'field_ta_rows'             => 6,
                        'field_related_id'          => '',
                        'field_related_orderby'     => 'title',
                        'field_related_sort'        => 'desc',
                        'field_related_max'         => '0',
                        'field_fmt'                 => 'none',
                        'field_show_fmt'            => 'n',
                        'field_text_direction'      => 'ltr',
                        'field_type'                => 'text',
                        'field_required'            => 'n',
                        'field_search'              => 'n',
                        'field_is_hidden'           => 'n',
                        'site_id'                   => $this->siteId,
                        'field_settings'            => array()
                    ), $fieldData);

                    // API Fuck Yeah.
                    $fieldData['field_id'] = $this->EE->api_channel_fields->update_field($fieldData);

                    if(false == $fieldData['field_id']) {
                        foreach($this->EE->api_channel_fields->errors as $error) {
                            $this->logError("There was an error creating: {$fieldData['field_name']}. Here's the cryptic EE error: ".lang($error), $fieldData);
                        }
                    } else {
                        $this->logSuccess("log_ok_field_imported", array_merge($fieldData, array("group_name" => $fieldGroupData["group_name"])));
                        $fieldsCreated = true;
                    }
                }

                $fieldGroupData['channel_fields'][$fieldKey] = $fieldData;
            }
            $this->fieldGroups[$fieldGroupKey] = $fieldGroupData;
        }

        if(false == $fieldsCreated) {
            $this->logWarning("No fields created");
        }

    }

    protected function importChannels()
    {
        $this->EE->load->model('channel_model');
        $this->EE->load->library('api');
        $this->EE->api->instantiate('channel_structure');

        $channelsCreated = false;
        $this->logTitle("Generating channels");

        $existingChannels = $this->hydrate($this->EE->channel_model->get_channels()->result_array(), 'channel_name');

        // Generate Channels
        foreach ($this->channels as $channelKey => $channelData) {

            if(array_key_exists($channelData['channel_name'], $existingChannels)) {

                $channelData = array_merge($channelData, $existingChannels[$channelData['channel_name']]);
                $this->logWarning('log_error_channel_exists', $channelData);

            } else {

                // Set the field group
                $channelData['field_group'] = (
                    isset($channelData['field_group'])
                    && isset($this->fieldGroups[ $channelData['field_group'] ]['group_id'])
                ) ? $this->fieldGroups[$channelData['field_group']]['group_id'] : '';

                // Set the category group
                if(isset($channelData['cat_group'])) {
                    $categoryGroupSlugs = explode("|", $channelData['cat_group']);
                    $tmp = array();
                    foreach($categoryGroupSlugs as $categoryGroupSlug) {
                        if(isset($this->categoryGroups[$categoryGroupSlug])) {
                            $tmp[] = $this->categoryGroups[$categoryGroupSlug]['group_id'];
                        }
                    }
                    $channelData['cat_group'] = implode("|", $tmp);
                }

                // Set the status group
                $channelData['status_group'] = (
                    isset($channelData['status_group'])
                    && isset($this->statusGroups[$channelData['status_group']]['group_id'])
                ) ? $this->statusGroups[$channelData['status_group']]['group_id'] : '';

                $channelData = array_merge(array(
                    'total_entries'         => count($channelData['entries']),
                    'site_id'               => $this->siteId,
                    'channel_lang'          => $this->EE->config->item('xml_lang')
                ), $channelData);

                if(empty($channelData['cat_group'])) {
                    unset($channelData['cat_group']);
                }

                if($channelData['channel_id'] = $this->EE->api_channel_structure->create_channel($channelData)) {
                    $this->logSuccess("log_ok_channel_imported", $channelData);
                    $channelsCreated = true;
                } else {
                    foreach($this->EE->api_channel_structure->errors as $error) {
                        $this->logError($error, $channelData);
                    }
                }
            }

            // Reassign back into the generator
            $this->channels[$channelKey] = $channelData;
        }
        
        if(false == $channelsCreated) {
            $this->logWarning("No channels created");
        }
        
    }

    protected function importChannelEntries()
    {
        $this->EE->load->library('api');
        $this->EE->api->instantiate('channel_entries');
        $this->EE->api->instantiate('channel_fields');

        $this->logTitle("Generating channel entries");
        $channelEntriesCreated = false;
        
        foreach ($this->channels as $channelKey => $channelData) {

            $channelFieldQuery = $this->EE->db->query("SELECT f.field_name, f.field_id 
                                            FROM exp_channel_fields as f 
                                            JOIN exp_channels as c 
                                            WHERE c.field_group = f.group_id
                                            AND c.channel_id = " . $channelData['channel_id']);
            $channelFields = $this->hydrate($channelFieldQuery->result_array(), 'field_name');

            $channelCatgeoryQuery = $this->EE->db->query("SELECT c.cat_url_title, c.cat_id 
                                            FROM exp_categories as c
                                            JOIN exp_channels as ch 
                                            WHERE ch.cat_group = c.group_id
                                            AND ch.channel_id = " . $channelData['channel_id']);
            $channelCategories = $this->hydrate($channelCatgeoryQuery->result_array(), 'cat_id');

            $savedEntries = array();

            foreach ($channelData['entries'] as $channelEntryKey => $channelEntryData) {

                $this->EE->db->where('url_title', $channelEntryData['url_title']);
                $this->EE->db->from('channel_titles');

                if($this->EE->db->count_all_results() > 0) {
                    $this->logWarning("Entry: <strong>{$channelEntryData['title']}</strong> already exists", $channelEntryData);
                    continue;
                }

                if(false === isset($channelEntryData['entry_date'])) {
                    $channelEntryData['entry_date'] = time();
                }

                if(false == empty($channelEntryData['channel_fields'])) {
                    foreach ($channelEntryData['channel_fields'] as $channelEntryFieldKey => $channelEntryFieldData) {

                        if(false == array_key_exists($channelEntryFieldData['field_name'], $channelFields)) {
                            unset($channelEntryData[$channelEntryFieldKey]);
                            continue;
                        }

                        $fieldId = $channelFields[$channelEntryFieldData['field_name']]['field_id'];

                        // if ($this->EE->extensions->active_hook('nsm_site_generator_process_field') === TRUE)
                        // {
                        //     $channelEntryFieldData = $this->EE->extensions->universal_call('nsm_site_generator_process_field', $channelEntryFieldData);
                        //     if ($this->EE->extensions->end_script === TRUE) return;
                        // }

                        // @todo: checkout what field_n_dt is all about

                        if(true === isset($channelEntryFieldData['ft'])) {
                            $channelEntryData["field_ft_".$fieldId] = $channelEntryFieldData['ft'];
                        }

                        $channelEntryData['field_id_'.$fieldId] = $channelEntryFieldData['data'];
                        unset($channelEntryData['channel_fields']);
                    }
                }


                $this->EE->api_channel_fields->setup_entry_settings($channelData['channel_id'], $channelEntryData);

                if ($channelEntryData['entry_id'] = $this->EE->api_channel_entries->submit_new_entry($channelData['channel_id'], $channelEntryData)) {
                    $channelEntryData = array_merge(
                        $channelEntryData,
                        array(
                            'entry_id' => $this->EE->api_channel_entries->entry_id,
                            'channel_id' => $this->EE->api_channel_entries->channel_id
                        ),
                        $this->EE->api_channel_entries->meta,
                        $this->EE->api_channel_entries->data
                    );
                    $this->logSuccess("log_ok_entry_imported", $channelEntryData);
                    $savedEntries[$this->EE->api_channel_entries->entry_id] = $channelEntryData;
                    $channelEntriesCreated = true;
                } else {
                    foreach($this->EE->api_channel_entries->errors as $error) {
                        $this->logError("There was an error creating: {$channelEntryData['title']}. Here's the cryptic EE error: ".lang($error), $channelEntryData);
                    }
                }
            }

            $channelData['entries'] = $savedEntries;
            $this->channels[$channelKey] = $channelData;

        }

        if(false == $channelEntriesCreated) {
            $this->logWarning("No channel entries created");
        }

    }

    protected function importTemplateGroups()
    {
        $this->EE->load->library('api');
        $this->EE->api->instantiate('template_structure');

        $this->logTitle("Generating template groups");
        $templateGroupsCreated = false;
        
        $existingTemplateGroups = $this->hydrate($this->EE->template_model->get_template_groups()->result_array(), 'group_name');

        // Generate Template Groups
        foreach ($this->templateGroups as $templateGroupKey => $templateGroupData) {

            if(array_key_exists($templateGroupData['group_name'], $existingTemplateGroups)) {
                $templateGroupData = array_merge($templateGroupData, $existingTemplateGroups[$templateGroupData['group_name']]);
                $this->logWarning('log_warning_template_group_exist', $templateGroupData);
            } else {

                $templateGroupData = array_merge(array(
                    'site_id'           => $this->siteId,
                    'is_site_default'   => 'n',
                    'is_user_blog'      => 'n',
                    'duplicate_group'   => false,
                ), $templateGroupData);

                if($templateGroupData['group_id'] = $this->EE->api_template_structure->create_template_group($templateGroupData)) {
                    $this->logSuccess("log_ok_template_group_imported", $templateGroupData);
                    $templateGroupsCreated = true;
                } else {
                    foreach($this->EE->api_template_structure->errors as $error) {
                        $this->logError($error, $templateGroupData);
                    }
                }
            }

            // Generate templates
            foreach ($templateGroupData['templates'] as $templateKey => $templateData) {
                
                $templatesCreated = false;

                $existingTemplateQuery = $this->EE->db->get_where('templates', array('group_id' => $templateGroupData['group_id']));
                $existingTemplatees = $this->hydrate($existingTemplateQuery->result_array(), 'template_name');

                $templateData = array_merge(array(
                    'group_id'              => $templateGroupData['group_id'],
                    'site_id'               => $this->siteId,
                    'template_name'         => 'index',
                    'save_template_file'    => 'y',
                    'template_type'         => 'webpage',
                    'edit_date'             => 0,
                    'cache'                 => 'n',
                    'enable_http_auth'      => 'n',
                    'allow_php'             => 'n',
                    'php_parse_location'    => 'o',
                ), $templateData);

                if(array_key_exists($templateData['template_name'], $existingTemplatees)) {
                    $templateData = array_merge($templateData, $existingTemplatees[$templateData['template_name']]);
                    $this->logWarning("log_error_template_exists", array_merge($templateData, array("group_name" => $templateGroupData['group_name'])), 1);
                } else {
                    $this->EE->db->insert('templates', $templateData);
                    $templateData['template_id'] = $this->EE->db->insert_id();
                    $this->logSuccess("log_ok_template_imported", array_merge($templateData, array("group_name" => $templateGroupData['group_name'])), 1);
                    $templatesCreated = true;
                }

                $templateGroupData['templates'][$templateKey] = $templateData;

            }

            // Reassign back into the generator
            $this->templateGroups[$templateGroupKey] = $templateGroupData;
        }

        if(false == $templateGroupsCreated) {
            $this->logWarning("No template groups created");
        }
    }

    protected function importGlobalVariables()
    {
        $this->logTitle("Generating global variables");
        $globalVariablesCreated = false;

        $globalVariableFields = array_flip(explode(' ', 'site_id variable_name variable_data'));
        $existingGlobalVariables = $this->hydrate(
                                        $this->EE->db->get('global_variables')->result_array(),
                                        'variable_name'
                                    );


        foreach ($this->globalVariables as $globalVariableKey => $globalVariableData) {

            if(array_key_exists($globalVariableData['variable_name'], $existingGlobalVariables)) {
                $this->logWarning('log_warning_global_variable_exists', $globalVariableData);
            } else {
                $this->EE->db->insert('global_variables', array_intersect_key(
                    $globalVariableData,
                    $globalVariableFields
                ));
                $globalVariableData['variable_id'] = $this->EE->db->insert_id();
                $this->logSuccess("log_ok_global_variable_imported", $globalVariableData);
                $globalVariablesCreated = true;
            }

            // Re-assign back to the generator
            $this->globalVariables[$globalVariableKey] = $globalVariableData;
        }
        
        if(false == $globalVariablesCreated) {
            $this->logWarning("No global variables created");
        }
        
    }

    protected function importSnippets()
    {
        $snippetsCreated = false;
        $this->logTitle("Generating snippets");

        $snippetFields = array_flip(explode(' ', 'site_id snippet_name snippet_contents'));
        $existingSnippets = $this->hydrate(
                                $this->EE->db->get('snippets')->result_array(),
                                'snippet_name'
                            );

        foreach ($this->snippets as $snippetKey => $snippetData) {

            if(array_key_exists($snippetData['snippet_name'], $existingSnippets)) {
                $this->logWarning('log_warning_snippet_exists', $snippetData);
            } else {
                $this->EE->db->insert('snippets', array_intersect_key(
                    $snippetData,
                    $snippetFields
                ));
                $snippetData['variable_id'] = $this->EE->db->insert_id();
                $this->logSuccess("log_ok_snippet_imported", $snippetData);
                $snippetsCreated = true;
            }

            // Re-assign back to the generator
            $this->snippets[$snippetKey] = $snippetData;
        }
        
        if(false == $snippetsCreated) {
            $this->logWarning("No snippets created");
        }
        
    }

    /**
     * Export
     */
     public function export()
     {
         $this->EE->load->library('api');
         $this->EE->api->instantiate('template_structure');

         $this->logTitle("Starting Export");

         $bundleDir = strtolower(url_title($this->title));
         $exportDirectory = $this->exportDirectory."/".$bundleDir;
         $backupDirectory = $this->exportDirectory."/~".$bundleDir."-".time();

         // Backup the old bundle
         if(true == is_dir($exportDirectory)) {
             rename($exportDirectory, $backupDirectory);
             $this->logSuccess("Backed up existing theme to: <code>" .$backupDirectory . "</code>");
         }

         $this->logSuccess("Exporting theme to: <code>" .$exportDirectory . "</code>");
         mkdir($exportDirectory);
         mkdir($exportDirectory . "/templates");
         mkdir($exportDirectory . "/assets");
         mkdir($exportDirectory . "/global_variables");
         mkdir($exportDirectory . "/snippets");


         $this->logTitle("Exporting templates as files");
         foreach ($this->templateGroups as $templateGroup) {
             $templateGroupPath = $exportDirectory . "/templates/".$templateGroup['group_name'].".group";
             mkdir($templateGroupPath);
             $this->logSuccess("Exported template group: <strong><code>{$templateGroup['group_name']}.group</code></strong>");
             foreach ($templateGroup['templates'] as $template) {
                 $ext = $this->EE->api_template_structure->file_extensions($template['template_type']);
                 write_file("{$templateGroupPath}/{$template['template_name']}.{$ext}", trim($template['template_data']));
                 $this->logSuccess("Exported template: <strong><code>{$templateGroup['group_name']}/{$template['template_name']}{$ext}</code></strong>");
             }
         }

         $this->logTitle("Exporting global variables as files");
         foreach ($this->globalVariables as $globalVariable) {
             $globalVariablePath = $exportDirectory . "/global_variables/".$globalVariable['variable_name'];
             write_file($globalVariablePath, trim($globalVariable['variable_data']));
             $this->logSuccess("Exported global variable: <strong><code>{$globalVariable['variable_name']}</code></strong>");
         }

         $this->logTitle("Exporting snippets as files");
         foreach ($this->snippets as $snippet) {
             $snippetPath = $exportDirectory . "/snippets/".$snippet['snippet_name'];
             write_file($snippetPath, trim($snippet['snippet_contents']));
             $this->logSuccess("Exported snippet: <strong><code>{$snippet['snippet_name']}</code></strong>");
         }

         $xmlConfig = $this->toXmlString();
         write_file($exportDirectory."/structure.xml", $xmlConfig);
     }

    /**
     * Output Array
     */
    public function toArray()
    {
        return array(
            "title"             => $this->title,
            "version"           => $this->version,
            "description"       => $this->description,
            "download_url"      => $this->downloadUrl,
            "requirements"      => $this->requirements,
            "authors"           => $this->authors,
            "channels"          => $this->channels,
            "category_groups"   => $this->categoryGroups,
            "status_groups"     => $this->statusGroups,
            "field_groups"      => $this->fieldGroups,
            "template_groups"   => $this->templateGroups,
            "global_variables"  => $this->globalVariables,
            "snippets"          => $this->snippets,
        );
    }

    /**
     * Output XML String
     */
    public function toXmlString()
    {
        $xmlConfig = '<?xml version="1.0" encoding="utf-8"?>';
        $xmlConfig .= "\n".'<!DOCTYPE xml>';
        $xmlConfig .= "\n\n<generator_template>\n";
        $xmlConfig .=   $this->themeInfoToXmlString() .
                        $this->categoryGroupsToXmlString() .
                        $this->statusGroupsToXmlString() .
                        $this->fieldGroupsToXmlString() .
                        $this->channelsToXmlString() .
                        $this->templateGroupsToXmlString() .
                        $this->globalVariablesToXmlString() .
                        $this->snippetsToXmlString();
        $xmlConfig .= "\n\n</generator_template>";
        return $xmlConfig;
    }

    protected function themeInfoToXmlString()
    {
        $themeInfoKeys = array("title", "version", "description", "postImportInstructions");
        $out = "";
        foreach ($themeInfoKeys as $key) {
            $out .= "\n<{$key}><![CDATA[{$this->$key}]]></{$key}>";
        }
        $out .= "\n<downloadUrl>{$this->downloadUrl}</downloadUrl>";
        $out .= $this->authorsToXmlString();
        $out .= $this->requirementsToXmlString();
        return $out;
    }

    protected function authorsToXmlString($tabDepth = 1) 
    {
        $tab = str_repeat($this->tab, $tabDepth);
        $out = "\n\n<authors>";
        foreach ($this->authors as $author) {
            $out = "\n{$tab}<author ";
            $out .= $this->renderAttributes($author);
            $out .= "/>";
        }
        $out .= "\n</authors>";
        return $out;
    }

    protected function requirementsToXmlString($tabDepth = 1) 
    {
        $tab = str_repeat($this->tab, $tabDepth);
        $out = "\n\n<requirements>";
        foreach ($this->requirements as $requirement) {
            $out = "\n{$tab}<requirement ";
            $out .= $this->renderAttributes($requirement);
            $out .= "/>";
        }
        $out .= "\n</requirements>";
        return $out;
    }

    protected function categoryGroupsToXmlString($tabDepth = 1)
    {
        $tab = str_repeat($this->tab, $tabDepth);

        $out = "\n\n<category_groups>";
        foreach ($this->categoryGroups as $category_group) {
            $out .= "\n{$tab}<category_group ";
            $out .= $this->renderAttributes($category_group, array('group_id','site_id','categories'));
            $out .= ">";
            $out .= $this->categoryToXmlString($category_group["categories"], 2);
            $out .= "\n{$tab}</category_group>";
        }
        $out .= "\n</category_groups>";

        return $out;
    }

    protected function categoryToXmlString($categories, $tabDepth = 1)
    {
        $tab = str_repeat($this->tab, $tabDepth);
        $out = "";
        foreach($categories as $category) {
            $out .= "\n".$tab."<category";
            $out .= " " . $this->renderAttributes($category, array('cat_id','parent_id','group_id','site_id',"categories"));
            if(false === empty($category['categories'])) {
                $out .= " >";
                $out .= " ".$this->categoryToXmlString($category['categories'], $tabDepth+1);
                $out .= "\n".$tab."</category>";
            } else {
                $out .= " />";
            }
        }
        return $out;
    }

    protected function statusGroupsToXmlString($tabDepth = 1)
    {
        $tab = str_repeat($this->tab, $tabDepth);
        $out = "\n\n<status_groups>";
        foreach ($this->statusGroups as $status_group) {
            $out .= "\n{$tab}<status_group ";
            $out .= $this->renderAttributes($status_group, array('group_id','site_id', 'statuses'));
            $out .= ">";
            $out .= $this->statusesToXmlString($status_group["statuses"]);
            $out .= "\n{$tab}</status_group>";
        }
        $out .= "\n</status_groups>";
        return $out;
    }

    protected function statusesToXmlString($statuses, $tabDepth = 2)
    {
        $tab = str_repeat($this->tab, $tabDepth);
        $out = "";
        foreach($statuses as $status) {
            $out .= "\n{$tab}<status";
            $out .= " " . $this->renderAttributes($status, array('status_id','group_id','site_id'));
            $out .= " />";
        }
        return $out;
    }

    protected function fieldGroupsToXmlString($tabDepth = 1)
    {
        $tab = str_repeat($this->tab, $tabDepth);
        $out = "\n\n<field_groups>";
        foreach ($this->fieldGroups as $field_group) {
            $out .= "\n{$tab}<field_group ";
            $out .= $this->renderAttributes($field_group, array('group_id','site_id','channel_fields'));
            $out .= ">";
            $out .= $this->channelFieldsToXmlString($field_group["channel_fields"]);
            $out .= "\n{$tab}</field_group>";
        }
        $out .= "\n</field_groups>";
        return $out;
    }

    protected function channelFieldsToXmlString($fields, $tabDepth = 2)
    {
        $tab = str_repeat($this->tab, $tabDepth);
        $out = "";
        foreach($fields as $field) {
            $out .= "\n{$tab}<channel_field";
            $out .= " " . $this->renderAttributes($field, array('field_id','group_id','site_id'));
            $out .= "/>";
        }
        return $out;
    }

    protected function channelsToXmlString($tabDepth = 1)
    {
        $tab = str_repeat($this->tab, $tabDepth);
        $out = "\n\n<channels>";
        foreach ($this->channels as $channel) {
            $out .= "\n{$tab}<channel ";
            $out .= $this->renderAttributes($channel, array(
                                                        'entries',
                                                        'channel_id',
                                                        'site_id',
                                                        'total_entries',
                                                        'total_comments',
                                                        'last_entry_date',
                                                        'last_comment_date',
                                                        'channel_notify_emails'));
            $out .= ">";
            $out .= $this->channelEntriesToXmlString($channel["entries"]);
            $out .= "\n{$tab}</channel>";
        }
        $out .= "\n</channels>";
        return $out;
    }

    protected function channelEntriesToXmlString($entries, $tabDepth = 2)
    {
        $tab = str_repeat($this->tab, $tabDepth);
        $out = "";
        foreach($entries as $entry) {

            $out .= "\n{$tab}<entry";
            $out .= " " . $this->renderAttributes($entry, array(
                                                            'channel_fields',
                                                            'channel_id',
                                                            'entry_id',
                                                            'site_id',
                                                            'pentry_id',
                                                            'author_id',
                                                            'forum_topic_id',
                                                            'ip_address',
                                                            'view_count_one',
                                                            'view_count_two',
                                                            'view_count_three',
                                                            'view_count_four',
                                                            'entry_date',
                                                            'dst_enabled',
                                                            'year',
                                                            'month',
                                                            'day',
                                                            'expiration_date',
                                                            'comment_expiration_date',
                                                            'edit_date',
                                                            'recent_comment_date',
                                                            'comment_total'
                                                            ));
            $out .= ">";

            $out .= $this->channelEntryFieldsToXmlString($entry["channel_fields"]);
            $out .= "\n{$tab}</entry>";
        }
        return $out;
    }

    public function channelEntryFieldsToXmlString($fields, $tabDepth = 3)
    {
        $tab = str_repeat($this->tab, $tabDepth);
        $out = "";
        foreach($fields as $field) {
            $out .= "\n{$tab}<field";
            $out .= " " . $this->renderAttributes($field, array('site_id, data'));
            $out .= ">";
            if(false == empty($field['data'])) {
                $out .= "<![CDATA[" . $field['data'] . "]]>";
            }
            $out .= "</field>";
        }
        return $out;
    }

    protected function templateGroupsToXmlString($tabDepth = 1)
    {
        $tab = str_repeat($this->tab, $tabDepth);
        $out = "\n\n<template_groups>";
        foreach ($this->templateGroups as $templateGroup) {
            $out .= "\n{$tab}<template_group ";
            $out .= $this->renderAttributes($templateGroup, array('group_id','site_id','templates'));
            $out .= ">";
            $out .= $this->templatesToXmlString($templateGroup["templates"]);
            $out .= "\n{$tab}</template_group>";
        }
        $out .= "\n</template_groups>";
        return $out;
    }

    protected function templatesToXmlString($templates, $tabDepth = 2)
    {
        $tab = str_repeat($this->tab, $tabDepth);
        $out = "";
        foreach($templates as $template) {
            $out .= "\n{$tab}<template";
            $out .= " " . $this->renderAttributes($template, array('template_id','group_id','site_id', 'template_data'));
            $out .= ">";
            if(true == $this->outputTemplateData) {
                $out .= "<![CDATA[{$template['template_data']}]]>";
            }
            $out .= "</template>";
        }
        return $out;
    }

    protected function globalVariablesToXmlString($tabDepth = 1) 
    {
        $tab = str_repeat($this->tab, $tabDepth);
        $out = "\n\n<global_variables>";
        foreach ($this->globalVariables as $variable) {
            $out .= "\n{$tab}<variable ";
            $out .= $this->renderAttributes($variable, array('site_id', 'variable_id', 'variable_data'));
            $out .= "/>";
        }
        $out .= "\n</global_variables>";
        return $out;
    }

    protected function snippetsToXmlString($tabDepth = 1) 
    {
        $tab = str_repeat($this->tab, $tabDepth);
        $out = "\n\n<snippets>";
        foreach ($this->snippets as $snippet) {
            $out .= "\n{$tab}<snippet ";
            $out .= $this->renderAttributes($snippet, array('site_id', 'snippet_id', 'snippet_contents'));
            $out .= "/>";
        }
        $out .= "\n</snippets>";
        return $out;
    }

    protected function renderAttributes($data, $exclude = array())
    {
        $attributes = array();
        foreach ($data as $key => $value) {
            if(in_array($key, $exclude)) {
                continue;
            }
            $attributes[] = $key.'="'.$value.'"';
        }
        return implode(" ", $attributes);
    }

    /**
    * Returns an array of attributes
    * 
    * @param    $obj    SimpleXML object
    * @return           Array               A simple array of element attributes
    */
    protected function attributes($node)
    {
        $xmlAttributes = $node->attributes();
        $array = array();
        foreach ($xmlAttributes as $key => $value) {
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

    protected function log($text, $replacements = array(), $depth = 0, $type='') {
        $text = lang($text);
        foreach ($replacements as $k => $v) {
            if(!is_array($v)) {
                $text = str_replace('{'.$k.'}', $v, $text);
            }
        }
        $this->log[] = array('type' => $type, 'text' => $text, 'depth' => $depth);
    }

    protected function logSuccess($lang_key, $replacements = array(), $depth = 0){
        return $this->log($lang_key, $replacements, $depth, 'success');
    }

    protected function logError($lang_key, $replacements = array(), $depth = 0){
        return $this->log($lang_key, $replacements, $depth, 'error');
    }

    protected function logWarning($lang_key, $replacements = array(), $depth = 0){
        return $this->log($lang_key, $replacements, $depth, 'warning');
    }

    protected function logTitle($lang_key, $replacements = array(), $depth = 0){
        return $this->log($lang_key, $replacements, $depth, 'title');
    }

    /**
     * Create a new array based on a primary key
     **/
    public function hydrate($array, $primary_key)
    {
        $tmp = array();
        foreach ($array as $value) {
            $tmp[$value[$primary_key]] = $value;
        }
        return $tmp;
    }

}