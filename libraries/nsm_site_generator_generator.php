<?php

class NsmSiteGeneratorGenerator
{
    protected $siteTemplate;
    protected $siteId;
    protected $config;
    protected $excludeConfig;

    protected $categoryGroups = array();
    protected $statusGroups = array();
    protected $fieldGroups = array();
    protected $channels = array();
    protected $templateGroups = array();

    public function __construct($siteTemplate, $config = null, $excludeConfig = null)
    {
        $this->EE =& get_instance();
        $this->siteId = $this->EE->config->item("site_id");
        $this->setSiteTemplate($siteTemplate);
        $this->setConfig($config);
        $this->setExcludeConfig($excludeConfig);
    }

    /**
     * Parsing the config assigns it to the object while adding nessecary fields for db insertion
     */

    public function parseConfig()
    {
        $this->parseCategoryGroupConfig();
        $this->parseStatusGroupConfig();
        $this->parseFieldGroupConfig();
        $this->parseChannelConfig();
        $this->parseTemplateGroupConfig();
    }

    public function parseCategoryGroupConfig()
    {
        if(false === isset($this->config['category_groups'])) {
            return;
        }

        foreach ($this->config['category_groups'] as $key => $cg) {
            $cg = array_merge(array(
                'site_id'               => $this->siteId,
                'sort_order'            => 'a',
                'field_html_formatting' => 'all',
                'can_edit_categories'   => FALSE,
                'can_delete_categories' => FALSE
            ), $cg);

            $this->categoryGroups[$key] = $cg;
            $this->categoryGroups[$key]['categories'] = array();

            foreach ($cg['categories'] as $catKey => $catData) {
               $this->categoryGroups[$key]['categories'][$catKey] = array_merge(array(
                    'site_id'               => $this->siteId,
                    'parent_id'             => 0
                ), $catData);
            }
        }
    }

    public function parseStatusGroupConfig()
    {
        if(false === isset($this->config['status_groups'])) {
            return;
        }

        foreach ($this->config['status_groups'] as $key => $sg) {
            $sg = array_merge(array(
                'site_id' => $this->siteId
            ), $sg);
            $this->statusGroups[$key] = $sg;
            $this->statusGroups[$key]['statuses'] = array();

            foreach ($sg['statuses'] as $statusKey => $statusData) {
                $this->statusGroups[$key]['statuses'][$statusKey] = array_merge(array(
                    'site_id' => $this->siteId
                ), $statusData);
            }
        }
    }

    public function parseFieldGroupConfig()
    {
        if(false === isset($this->config['field_groups'])) {
            return;
        }

        foreach ($this->config['field_groups'] as $key => $fg) {
            $fg = array_merge(array(
                'site_id' => $this->siteId
            ), $fg);
            $this->fieldGroups[$key] = $fg;
            $this->fieldGroups[$key]['channel_fields'] = array();

            foreach ($fg['channel_fields'] as $fieldKey => $fieldData) {
                $this->fieldGroups[$key]['channel_fields'][$fieldKey] = array_merge(array(
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
            }
        }
    }

    public function parseChannelConfig()
    {
        if(false === isset($this->config['channels'])) {
            return;
        }

        foreach ($this->config['channels'] as $key => $ch) {
            $ch = array_merge(array(
                'site_id'               => $this->siteId,
                'channel_lang'          => $this->EE->config->item('xml_lang')
            ), $ch);
            $this->channels[$key] = $ch;
            $this->channels[$key]['entries'] = array();

            foreach ($ch['entries'] as $entryKey => $entryData) {
                $this->channels[$key]['entries'][] = array_merge(array(

                ), $entryData);
            }
        }
    }

    public function parseTemplateGroupConfig()
    {
        if(false === isset($this->config['template_groups'])) {
            return;
        }

        foreach ($this->config['template_groups'] as $key => $tg) {
            $tg = array_merge(array(
                'site_id'           => $this->siteId,
                'is_site_default'   => 'n',
                'is_user_blog'      => 'n'
            ), $tg);
            $this->templateGroups[$key] = $tg;
            $this->templateGroups[$key]['templates'] = array();
            foreach ($tg['templates'] as $templateKey => $templateData) {
                $this->templateGroups[$key]['templates'][$templateKey] = array_merge(array(
                    'site_id'               => $this->siteId,
                    'template_name'         => 'index',
                    'save_template_file'    => 'y',
                    'template_type'         => 'webpage',
                    'edit_date'             => 0,
                    'cache'                 => 'n',
                    'enable_http_auth'      => 'n',
                    'allow_php'             => 'n',
                    'php_parse_location'    => 'o'
                ), $templateData);
            }

        }
    }

    /**
     * Run the generator
     */

    public function generate()
    {
        $this->parseConfig();
        $this->generateCategories();
        $this->generateStatusGroups();
        $this->generateFieldGroups();
        $this->generateChannels();
        $this->generateChannelFields();
        $this->generateChannelEntries();
        $this->generateTemplateGroups();
    }

    protected function generateCategories()
    {
        $categoryGroupFields = array_flip(explode(' ', 'site_id sort_order field_html_formatting can_edit_categories can_delete_categories group_name'));

        $this->EE->load->model('category_model');
        $existingCategoryGroups = $this->hydrate($this->EE->category_model->get_category_groups()->result_array(), 'group_name');

        $categoryGroupsCreated = false;
        $this->logTitle("Generating categories");

        // Generate Category Groups
        foreach ($this->categoryGroups as $categoryGroupKey => $categoryGroupData) {

            if(array_key_exists($categoryGroupData['group_name'], $existingCategoryGroups)) {
                $this->logWarning('log_warning_category_group_exist', $categoryGroupData);
                $categoryGroupData = array_merge($categoryGroupData, $existingCategoryGroups[$categoryGroupData['group_name']]);
            } else {
                $this->EE->category_model->insert_category_group(array_intersect_key(
                    $categoryGroupData,
                    $categoryGroupFields
                ));
                $categoryGroupData['group_id'] = $this->EE->db->insert_id();
                $this->logSuccess("log_ok_category_group_created", $categoryGroupData, 'success');
            }

            // Re-assign back to the generator
            $this->categoryGroups[$categoryGroupKey] = $categoryGroupData;
        }
    }

    protected function renderBundleAuthors($tab_depth = 1)
    {
        $tab = str_repeat($this->tab, $tab_depth);
        $out = "<authors>";
        foreach ($this->authors as $author) {
            $out = "\n{$tab}<author ";
            $out .= $this->renderAttributes($author);
            $out .= "/>";
        }
        $out .= "</authors>";
        return $out;
    }

    protected function renderBundleRequirements()
    {
        $out = "<requirements>";
        foreach ($this->requirements as $requirement) {
            $out = "<requirement ";
            $out .= $this->renderAttributes($requirement);
            $out .= "/>";
        }
        $out .= "</requirements>";
        return $out;
    }

    protected function generateStatusGroups()
    {
        $statusGroupFields = array_flip(explode(' ', 'site_id group_name'));
        $statusFields = array_flip(explode(' ', 'site_id group_id, status, status_order, highlight'));

        $this->EE->load->model('status_model');
        $existingStatusGroups = $this->hydrate($this->EE->status_model->get_status_groups()->result_array(), 'group_name');

        $statusGroupsCreated = false;
        $this->logTitle("Generating statuses");

        // Generate Status Groups
        foreach ($this->statusGroups as $statusGroupKey => $statusGroupData) {

            if(array_key_exists($statusGroupData['group_name'], $existingStatusGroups)) {
                $statusGroupData = array_merge($statusGroupData, $existingStatusGroups[$statusGroupData['group_name']]);
                $this->logWarning('log_warning_status_group_exist', $statusGroupData);
            } else {
                $this->EE->db->insert('status_groups', array_intersect_key(
                    $statusGroupData,
                    $statusGroupFields
                ));
                $statusGroupData['group_id'] = $this->EE->db->insert_id();
                $this->logSuccess("log_ok_category_group_created", $statusGroupData, 'success');
            }

            // Generate Statuses
            foreach ($statusGroupData['statuses'] as $statusKey => $statusData) {

                $existingStatuses = $this->hydrate($this->EE->status_model->get_statuses($statusGroupData['group_id'])->result_array(), 'status');
                $statusData['group_id'] = $statusGroupData['group_id'];

                if(array_key_exists($statusData['status'], $existingStatuses)) {
                    $statusData = array_merge($statusData, $existingStatuses[$statusData['status']]);
                    $this->logError("log_error_status_exist", array_merge($statusData, array("group_name" => $statusGroupData['group_name'])));
                } else {
                    $this->EE->db->insert('statuses', $statusData);
                    $statusData['status_id'] = $this->EE->db->insert_id();
                    $statusGroupData['count']++;
                    $this->logSuccess("log_ok_status_created", $statusData);
                }

                $statusGroupData['statuses'][$statusKey] = $statusData;
            }

            // Reassign back into the generator
            $this->statusGroups[$statusGroupKey] = $statusGroupData;
        }
    }

    protected function generateFieldGroups()
    {
        $fieldGroupFields = array_flip(explode(' ', 'site_id group_name'));
        $fieldFields = array_flip(explode(' ', 'site_id group_id, field, field_order, highlight'));

        $this->EE->load->model('field_model');
        $this->EE->load->library('api');
        $this->EE->api->instantiate('channel_fields');

        $existingFieldGroups = $this->hydrate($this->EE->field_model->get_field_groups()->result_array(), 'group_name');

        $fieldGroupsCreated = false;
        $this->logTitle("Generating field groups");

        // Generate Field Groups
        foreach ($this->fieldGroups as $fieldGroupKey => $fieldGroupData) {

            if(array_key_exists($fieldGroupData['group_name'], $existingFieldGroups)) {
                $fieldGroupData = array_merge($fieldGroupData, $existingFieldGroups[$fieldGroupData['group_name']]);
                $this->logWarning('log_warning_field_group_exist', $fieldGroupData);
            } else {
                $this->EE->db->insert('field_groups', array_intersect_key(
                    $fieldGroupData,
                    $fieldGroupFields
                ));
                $fieldGroupData['group_id'] = $this->EE->db->insert_id();
                $this->logSuccess("log_ok_field_group_created", $fieldGroupData);
            }

            // Reassign back into the generator
            $this->fieldGroups[$fieldGroupKey] = $fieldGroupData;
        }
    }

    protected function generateChannelFields()
    {
        $this->logTitle("Generating fields");

        // Loop over the field groups
        foreach ($this->fieldGroups as $fieldGroupKey => $fieldGroupData) {

            // Generate Fields
            foreach ($fieldGroupData['channel_fields'] as $fieldKey => $fieldData) {

                $existingFields = $this->hydrate($this->EE->field_model->get_fields($fieldGroupData['group_id'])->result_array(), 'field_name');
                $fieldData['group_id'] = $fieldGroupData['group_id'];

                $dateOrRel = in_array($fieldData['field_type'], array('date', 'rel'));

                if(array_key_exists($fieldData['field_name'], $existingFields)) {
                    $fieldData = array_merge($fieldData, $existingFields[$fieldData['field_name']]);
                    $this->logError("log_error_field_exist", array_merge($fieldData, array("group_name" => $fieldGroupData["group_name"])));
                } else {

                    // Tweak the field related ID
                    $fieldData['field_related_id'] = (
                        isset($fieldData['field_related_id'])
                        && isset($this->channels[ $fieldData['field_related_id'] ]['channel_id'])
                    ) ? $this->channels[ $fieldData['field_related_id'] ]['channel_id'] : '';

                    // API Fuck Yeah.
                    $fieldData['field_id'] = $this->EE->api_channel_fields->update_field($fieldData);

                    if(false == $fieldData['field_id']) {
                        $this->logSuccess("log_error_field_create", array_merge($fieldData, array("group_name" => $fieldGroupData["group_name"])));
                    } else {
                        $this->logError("log_ok_field_created", array_merge($fieldData, array("group_name" => $fieldGroupData["group_name"])));
                    }
                }

                $fieldGroupData['channel_fields'][$fieldKey] = $fieldData;
            }
            $this->fieldGroups[$fieldGroupKey] = $fieldGroupData;
        }
    }

    protected function generateChannels()
    {
        $this->logTitle("Generating channels");
        $this->EE->load->model('channel_model');
        $this->EE->load->library('api');
        $this->EE->api->instantiate('channel_structure');

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

                if(empty($channelData['cat_group'])) {
                    unset($channelData['cat_group']);
                }

                // Set the status group
                $channelData['status_group'] = (
                    isset($channelData['status_group'])
                    && isset($this->statusGroups[$channelData['status_group']]['group_id'])
                ) ? $this->statusGroups[$channelData['status_group']]['group_id'] : '';

                if($channelData['channel_id'] = $this->EE->api_channel_structure->create_channel($channelData)) {
                    $this->logSuccess("log_ok_channel_created", $channelData, 'success');
                } else {
                    foreach($this->EE->api_channel_structure->errors as $error) {
                        $this->logError($error, $channelData);
                    }
                }
            }

            // Reassign back into the generator
            $this->channels[$channelKey] = $channelData;
        }
    }

    protected function generateChannelEntries()
    {
        $this->logTitle("Generating channel entries");
        $this->EE->load->library('api');
        $this->EE->api->instantiate('channel_entries');
        $this->EE->api->instantiate('channel_fields');
        
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
                    $this->logSuccess("log_ok_entry_created", $channelEntryData);
                    $savedEntries[$this->EE->api_channel_entries->entry_id] = $channelEntryData;
                } else {
                    foreach($this->EE->api_channel_entries->errors as $error) {
                        $this->logError("There was an error creating: {$channelEntryData['title']}. Here's the cryptic EE error: {$error}", $channelEntryData);
                    }
                }
            }

            $channelData['entries'] = $savedEntries;
            $this->channels[$channelKey] = $channelData;

        }
    }

    protected function generateTemplateGroups()
    {
        $this->logTitle("Generating template groups");
        $this->EE->load->library('api');
        $this->EE->api->instantiate('template_structure');
        
        $existingTemplateGroups = $this->hydrate($this->EE->template_model->get_template_groups()->result_array(), 'group_name');

        // Generate Template Groups
        foreach ($this->templateGroups as $templateGroupKey => $templateGroupData) {

            if(array_key_exists($templateGroupData['group_name'], $existingTemplateGroups)) {
                $templateGroupData = array_merge($templateGroupData, $existingTemplateGroups[$templateGroupData['group_name']]);
                $this->logWarning('log_warning_template_group_exist', $templateGroupData);
            } else {

                if($templateGroupData['group_id'] = @$this->EE->api_template_structure->create_template_group($templateGroupData)) {
                    $this->logSuccess("log_ok_template_created_group", $templateGroupData, 'success');
                } else {
                    foreach($this->EE->api_template_structure->errors as $error) {
                        $this->logError($error, $templateGroupData);
                    }
                }
            }

            // Generate templates
            foreach ($templateGroupData['templates'] as $templateKey => $templateData) {

                $existingTemplateQuery = $this->EE->db->get_where('templates', array('group_id' => $templateGroupData['group_id']));
                $existingTemplatees = $this->hydrate($existingTemplateQuery->result_array(), 'template_name');

                $templateData['group_id'] = $templateGroupData['group_id'];

                if(array_key_exists($templateData['template_name'], $existingTemplatees)) {
                    $templateData = array_merge($templateData, $existingTemplatees[$templateData['template_name']]);
                    $this->logError("log_error_template_exists", array_merge($templateData, array("group_name" => $templateGroupData['group_name'])));
                } else {
                    $this->EE->db->insert('templates', $templateData);
                    $templateData['template_id'] = $this->EE->db->insert_id();
                    $this->logSuccess("log_ok_template_created", $templateData);
                }

                $templateGroupData['templates'][$templateKey] = $templateData;
            }

            // Reassign back into the generator
            $this->templateGroups[$templateGroupKey] = $templateGroupData;
        }
    }

    /**
     * Getters and Setters
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function setExcludeConfig($excludeConfig)
    {
        $this->excludeConfig = $excludeConfig;
    }

    public function getExcludeConfig()
    {
        return $this->excludeConfig;
    }

    public function setSiteTemplate($siteTemplate)
    {
        $this->siteTemplate = $siteTemplate;
    }

    public function getSiteTemplate()
    {
        return $this->siteTemplate;
    }

    /**
    * getLog
    * public getter for log array
    */
    public function getLog() {
        return $this->log;
    }

    protected function log($text, $replacements = array(), $type='') {
        $text = lang($text);
        foreach ($replacements as $k => $v) {
            if(!is_array($v)) {
                $text = str_replace('{'.$k.'}', $v, $text);
            }
        }
        $this->log[] = array('type' => $type, 'text' => $text);
    }

    protected function logSuccess($lang_key, $replacements = array()){
        return $this->log($lang_key, $replacements, 'success');
    }

    protected function logError($lang_key, $replacements = array()){
        return $this->log($lang_key, $replacements, 'error');
    }

    protected function logWarning($lang_key, $replacements = array()){
        return $this->log($lang_key, $replacements, 'warning');
    }

    protected function logTitle($lang_key, $replacements = array()){
        return $this->log($lang_key, $replacements, 'title');
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