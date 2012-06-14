<?php //if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require PATH_THIRD.'nsm_site_generator/config.php';
require 'libraries/nsm_site_generator_generator.php';


/**
 * NSM Site Generator CP
 *
 * @package         NSMSiteGenerator
 * @version         0.0.1
 * @author          Leevi Graham <http://leevigraham.com>
 * @copyright       Copyright (c) 2007-2010 Newism
 * @license         Commercial - please see LICENSE file included with this distribution
 * @link            http://expressionengine-addons.com/nsm-site-generator
 * @see             http://expressionengine.com/public_beta/docs/development/modules.html#control_panel_file
 */

/**
 * Require the base generator class
 */

class Nsm_site_generator_mcp{

    public $EE;
    public static $addon_id = NSM_SITE_GENERATOR_ADDON_ID;

    private $pages = array("index", "configure_export");
    private $tab = "  ";

    public function __construct()
    {
        $this->EE =& get_instance();
        $this->addon_id = strtolower(substr(__CLASS__, 0, -4));
        $this->cp_url = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->addon_id.AMP;

        $this->EE->load->library("{$this->addon_id}_helper");

        if (class_exists('Nsm_site_generator_ext') == FALSE)
            include(PATH_THIRD. 'nsm_site_generator/ext.nsm_site_generator.php');

        $this->ext = new Nsm_site_generator_ext;
        $this->themesDirectory = $this->ext->settings['bundle_server_path'];
    }

    /**
     * Displays the dashboard
     *
     * @access public
     * @return string The page layout
     */
    public function index()
    {
        $this->EE->load->helper('directory');

        $viewData = array(
            "input_prefix" => __CLASS__,
            "error" => FALSE,
            "themes" => FALSE
        );

        if(is_dir($this->themesDirectory))
        {
            $map = directory_map($this->themesDirectory, 2);
            
            foreach ($map as $key => $values) {
                if(is_array($values) && in_array("structure.xml", $values) && "~" != substr($key, 0, 1)) {
                    $theme = $this->_loadXML($key);
                    $theme['folder'] = $key;
                    $theme["theme_url"] = BASE.AMP.$this->cp_url . "method=configure_import". AMP . "theme=". $key;
                    $viewData['themes'][] = $theme;
                }
            }
        }

        if(true === empty($viewData['themes'])) {
            $viewData["error"] = sprintf(lang("alert.warning.no_templates"), $this->themesDirectory);
        }

        $out = $this->EE->load->view("layouts/module/index", $viewData, TRUE);
        return $this->_renderLayout("index", $out);
    }

    /**
     * Display configuration options for the current generator
     *
     * @access public
     * @return string The page layout
     */
    public function configure_import()
    {
        $theme = $this->EE->input->get('theme');
        $xmlConfig = $this->_loadXML($theme);

        $generator = new NsmSiteGeneratorGenerator();
        $generator->importDirectory = $this->themesDirectory . "/" . $theme;
        $generator->parseXmlConfig($xmlConfig);

        $viewData = array(
            'input_prefix' => __CLASS__,
            'generator' => $generator,
            'config' => $generator->toArray(),
        );

        $out = $this->EE->load->view("layouts/module/configure_import", $viewData, TRUE);
        $out = form_open($this->cp_url . "method=import", FALSE, array("theme" => $theme)) . $out . "</form>";
        return $this->_renderLayout("configure_import", $out);
    }

    /**
     * Import the channels, templates etc.
     */
    public function import()
    {
        $theme = $this->EE->input->post('theme');
        $xmlConfig = $this->_loadXML($theme);

        $generator = new NsmSiteGeneratorGenerator();
        $generator->importDirectory = $this->themesDirectory . "/" . $theme;
        $generator->parseXmlConfig($xmlConfig);
        $generator->import();

        $viewData = array(
            "log" => $generator->getLog(),
            "post_import_instructions" => (string)$xmlConfig->postImportInstructions
        );

        $out = $this->EE->load->view("layouts/module/import", $viewData, TRUE);
        return $this->_renderLayout("import", $out);
    }

    /**
     * Display configuration options for exporting XML
     *
     * @access public
     * @return string The page layout
     */
    public function configure_export() {

        $theme = $this->EE->input->get('theme');

        $channels = $this->hydrate($this->getExistingChannels(), 'channel_id');
        foreach ($channels as $key => $channel) {
            $channels[$key]['entries'] = $this->hydrate($channel['entries'], 'entry_id');
        }

        $categoryGroups = $this->getExistingCategoryGroups();
        $statusGroups = $this->getExistingStatusGroups();
        $fieldGroups = $this->getExistingFieldGroups();
        $templateGroups = $this->getExistingTemplateGroups();
        $globalVariables = $this->getExistingGlobalVariables();
        $snippets = $this->getExistingSnippets();

        // Are there settings posted from the form?
        if($postData = $this->EE->input->post(__CLASS__)) {

            if(true == empty($postData['title'])) {
                die('Add a title…');
            }

            $arrayConfig = array(
                'channels' => array(),
                'template_groups' => array(),
                'title' => $postData['title'],
                'description' => $postData['description'],
                'version' => $postData['version'],
                'downloadUrl' => $postData['download_url'],
                'postImportInstructions' => $postData['post_import_instructions'],
            );

            if(true === isset($postData['channels'])) {

                $channelFields = array();
                foreach ($fieldGroups as $fieldGroupId => $fieldGroupData) {
                    $channelFields = array_merge($channelFields, $fieldGroupData['channel_fields']);
                }
                $channelFields = $this->hydrate($channelFields, 'field_id');

                foreach ($postData['channels'] as $channelId => $channelOptions) {

                    if(false == $channelOptions) {
                        continue;
                    }

                    $arrayChannelConfig = array_merge($channels[$channelId], array('entries' => array()));

                    if(true == isset($channelOptions['entries'])) {
                        foreach($channelOptions['entries'] as $channelEntryId){
                            $arrayChannelEntryConfig = $channels[$channelId]['entries'][$channelEntryId];
                            $arrayChannelEntryConfig['channel_fields'] = array();
                            foreach ($arrayChannelEntryConfig as $key => $data) {
                                
                                if(substr($key,0,6) == "field_") {

                                    $field_id = substr($key,9);
                                    $fieldAttribute = substr($key,6,2);
                                    $channelField = $channelFields[$field_id];
                                    $channelFieldName = $channelField['field_name'];

                                    if($fieldAttribute == "id") {
                                        $arrayChannelEntryConfig['channel_fields'][$channelFieldName]['name'] = $channelField['field_name'];
                                        $arrayChannelEntryConfig['channel_fields'][$channelFieldName]['type'] = $channelField['field_type'];
                                        $arrayChannelEntryConfig['channel_fields'][$channelFieldName]['data'] = $data;
                                    } else {
                                        $arrayChannelEntryConfig['channel_fields'][$channelFieldName][$fieldAttribute] = $data;
                                    }
                                    unset($arrayChannelEntryConfig[$key]);
                                }
                            }
                            $arrayChannelConfig['entries'][$channelEntryId] = $arrayChannelEntryConfig;
                        }
                    }

                    $arrayConfig['channels'][$channelId] = $arrayChannelConfig;

                    // Add the channel status group
                    if($statusGroupId = $arrayChannelConfig['status_group']) {
                        $arrayConfig['status_groups'][$statusGroupId] = $statusGroups[$statusGroupId];
                    }
                    
                    // Add the channel field group
                    if($fieldGroupId = $arrayChannelConfig['field_group']) {
                        $arrayConfig['field_groups'][$fieldGroupId] = $fieldGroups[$fieldGroupId];
                    }

                    // Add the channel category groups
                    $categoryGroupIds = (empty($arrayChannelConfig['cat_group'])) ? array() : explode("|",$arrayChannelConfig['cat_group']);
                    foreach ($categoryGroupIds as $categoryGroupId) {
                        $arrayConfig['category_groups'][$categoryGroupId] = $categoryGroups[$categoryGroupId];
                    }
                }
            }

            if(true === isset($postData['template_groups'])) {
                foreach ($postData['template_groups'] as $templateGroupId => $templateGroupOptions) {
                     $existingTemplateGroupData = $templateGroups[$templateGroupId];
                     $xmlTemplateGroupConfig = array_merge($existingTemplateGroupData, array('templates' => array()));

                     if(true == isset($templateGroupOptions['templates'])) {
                         foreach($templateGroupOptions['templates'] as $templateId){
                             $xmlTemplateGroupConfig['templates'][$templateId] = $existingTemplateGroupData['templates'][$templateId];
                         }
                     }

                     $arrayConfig['template_groups'][$templateGroupId] = $xmlTemplateGroupConfig;
                }
            }

            if(true === isset($postData['global_variables'])) {
                $globalVariables = $this->hydrate($globalVariables, 'variable_id');
                foreach ($postData['global_variables'] as $variableId) {
                     $existingVariableData = $globalVariables[$variableId];
                     $arrayConfig['global_variables'][$existingVariableData['variable_name']] = $existingVariableData;
                }
            }

            if(true === isset($postData['snippets'])) {
                $snippets = $this->hydrate($snippets, 'snippet_id');
                foreach ($postData['snippets'] as $snippetId) {
                     $existingSnippetData = $snippets[$snippetId];
                     $arrayConfig['snippets'][$existingSnippetData['snippet_name']] = $existingSnippetData;
                }
            }

            $generator = new NsmSiteGeneratorGenerator();
            $generator->outputTemplateData = false;
            $generator->exportDirectory = $this->themesDirectory;
            $generator->parseArrayConfig($arrayConfig);
            $generator->export();

            $viewData = array(
               'xml' => htmlentities($generator->toXmlString(), ENT_QUOTES, false),
               "log" => $generator->getLog(),
            );
            $out = $this->EE->load->view("layouts/module/export", $viewData, TRUE);
            return $this->_renderLayout("configure_export", $out);

        } else {

            $data = array(
                'title' => false,
                'version' => false,
                'description' => false,
                'download_url' => false,
                'post_import_instructions' => false,
                'channels' => array(),
                'templates' => array(),
                'snippets' => array(),
                'global_variables' => array()
            );

            foreach ($channels as $count => $channel) {
                $data['channels'][$count]['enabled'] = false;
                $data['channels'][$count]['entries'] = array();
            }

            foreach ($templateGroups as $count => $templateGroup) {
                $data['template_groups'][$count]['templates'] = array();
            }
        }

        $viewData = array(
            'input_prefix' => __CLASS__,
            'channels' => $this->_mergeChannelData($channels, $categoryGroups, $statusGroups, $fieldGroups),
            'template_groups' => $templateGroups,
            'global_variables' => $globalVariables,
            'snippets' => $snippets,
            'data' => $data
        );
        
        $out = $this->EE->load->view("layouts/module/configure_export", $viewData, TRUE);
        $out = form_open($this->cp_url . "method=configure_export", FALSE) . $out . "</form>";
        return $this->_renderLayout("configure_export", $out);
    }

    private function getExistingChannels($getChannelEntries = true, $getEntryData = true) {

        $this->EE->db->from('channels');
        $channelQuery = $this->EE->db->get();

        if($channelQuery->num_rows == 0) {
            return array();
        }

        $channels = $this->hydrate($channelQuery->result_array(), 'channel_id');

        if(true === $getChannelEntries) {

            $this->EE->db->from('channel_titles');
            
            if(true === $getEntryData) {
                $this->EE->db->join('channel_data', 'channel_titles.entry_id = channel_data.entry_id');
            }

            $this->EE->db->where_in('channel_titles.channel_id', array_keys($channels));
            $channelEntriesQuery = $this->EE->db->get();
            $channelEntries = $this->hydrate($channelEntriesQuery->result_array(), 'entry_id');

            if(true == $getEntryData) {
                $this->EE->db->select('field_id, group_id, field_name, field_type');
                $this->EE->db->from('channel_fields');
                $fieldQuery = $this->EE->db->get();
                $fields = $this->hydrate($fieldQuery->result_array(), 'field_id');
            }

            // Add entries to channels
            foreach ($channels as $channelId => &$channel) {
                $channel['entries'] = array();
                foreach ($channelEntries as $entryId => $entryData) {
                    if($entryData['channel_id'] == $channelId) {

                        if(true == $getEntryData) {
                            foreach ($entryData as $key => $value) {
                                if(substr($key, 0,6) == "field_") {
                                    $fieldId = substr($key,9);
                                    if(false === isset($fields[$fieldId]) || $fields[$fieldId]['group_id'] != $channel['field_group']) {
                                        unset($entryData[$key]);
                                    }
                                }
                            }
                        }
                        $channel['entries'][] = $entryData;
                    }
                }
            }
        }

        return $channels;
    }

    private function getExistingCategoryGroups($getCategories = true) {

        // Get the category groups
        $categoryGroupQuery = $this->EE->db->get('category_groups');

        if($categoryGroupQuery->num_rows == 0) {
            return array();
        }

        $categoryGroups = $this->hydrate($categoryGroupQuery->result_array(), 'group_id');

        foreach ($categoryGroups as $groupId => &$group) {
            $group['group_ref_id'] = "category_group_" . $groupId;
        }

        if(true === $getCategories) {
            // Get the categories
            $this->EE->db->from('categories');
            $this->EE->db->where_in('group_id', array_keys($categoryGroups));

            $category_query = $this->EE->db->get();
            $categories = $this->hydrate($category_query->result_array(), 'cat_id');

            // Add categories to category group
            foreach($categoryGroups as $groupId => &$group) {
                $group['categories'] = array();
                foreach ($categories as $category_id => $category) {
                    if($groupId == $category['group_id']) {
                        $group['categories'][$category_id] = $category;
                    }
                }
                $nested = $this->_build_array_from_nodes($group['categories']);
                $group['categories'] = $nested['categories'];
            }
        }

        return $categoryGroups;
    }

    private function getExistingStatusGroups($getStatuses = true) {

        // Get status groups
        $status_group_query = $this->EE->db->get('status_groups');

        if($status_group_query->num_rows == 0) {
            return array();
        }

        $status_groups = $this->hydrate($status_group_query->result_array(), 'group_id');
        foreach ($status_groups as $groupId => &$group) {
            $group['group_ref_id'] = "status_group_" . $groupId;
        }

        if(true === $getStatuses) {
            // Get the statuses
            $this->EE->db->from('statuses');
            $this->EE->db->where_in('group_id', array_keys($status_groups));

            $status_query = $this->EE->db->get();
            $statuses = $this->hydrate($status_query->result_array(), 'status_id');

            // Add entries to channel
            foreach($status_groups as $groupId => &$status_group) {
                $status_group['statuses'] = array();
                foreach ($statuses as $status_id => $status) {
                    if($groupId == $status['group_id']) {
                        $status_group['statuses'][$status_id] = $status;
                    }
                }
            }
        }

        return $status_groups;
    }

    private function getExistingFieldGroups($getFields = true) {

        // Get field groups
        $field_group_query = $this->EE->db->get('field_groups');

        if($field_group_query->num_rows == 0) {
            return array();
        }

        $field_groups = $this->hydrate($field_group_query->result_array(), 'group_id');
        foreach ($field_groups as $groupId => &$group) {
            $group['group_ref_id'] = "field_group_" . $groupId;
        }

        if(true === $getFields) {

            // Get the fields
            $this->EE->db->from('channel_fields');
            $this->EE->db->where_in('group_id', array_keys($field_groups));

            $channelFieldsQuery = $this->EE->db->get();
            $channelFields = $this->hydrate($channelFieldsQuery->result_array(), 'field_id');

            // Add entries to channel
            foreach($field_groups as $groupId => &$field_group) {
                $field_group['channel_fields'] = array();
                foreach ($channelFields as $channelField_id => $channelField) {
                    if($groupId == $channelField['group_id']) {
                        $field_group['channel_fields'][$channelField_id] = $channelField;
                    }
                }
            }
        }

        return $field_groups;
    }

    private function getExistingTemplateGroups($getTemplates = true) {
        // Get template groups
        $this->EE->db->order_by('group_order');
        $templateGroupQuery = $this->EE->db->get('template_groups');

        if($templateGroupQuery->num_rows == 0) {
            return array();
        }

        $templateGroups = $this->hydrate($templateGroupQuery->result_array(), 'group_id');

        foreach ($templateGroups as $groupId => &$group) {
            $group['group_ref_id'] = "template_group_" . $groupId;
        }

        if(true === $getTemplates) {

            // Get the templates
            $this->EE->db->from('templates');
            $this->EE->db->where_in('group_id', array_keys($templateGroups));
            $this->EE->db->order_by('template_name');
            $templatesQuery  = $this->EE->db->get();
            $templates = $this->hydrate($templatesQuery->result_array(), 'template_id');

            // Add entries to channel
            foreach($templateGroups as $templateGroupId => &$templateGroup) {
                $templateGroup['templates'] = array();
                foreach ($templates as $templateId => $templateData) {
                    if($templateGroupId == $templateData['group_id']) {
                        $templateGroup['templates'][$templateId] = $templateData;
                    }
                }
            }
        }
        return $templateGroups;
    }

    private function getExistingSnippets() {

        $snippetsQuery = $this->EE->db->get('snippets');

        if($snippetsQuery->num_rows == 0) {
            return array();
        }

        $snippets = $this->hydrate($snippetsQuery->result_array(), 'snippet_id');

        return $snippets;
    }

    private function getExistingGlobalVariables() {

        $globalVariablesQuery = $this->EE->db->get('global_variables');

        if($globalVariablesQuery->num_rows == 0) {
            return array();
        }

        $globalVariables = $this->hydrate($globalVariablesQuery->result_array(), 'variable_id');

        return $globalVariables;
    }

    private function _mergeChannelData(array $channels, $categoryGroups = array(), $statusGroups = array(), $fieldGroups = array()) {

        foreach ($channels as $channelId => &$channel) {

            // Merge category groups
            $cat_group = $channel['cat_group'];
            $channel['cat_group'] = array();
            if(false != $cat_group) {
                $cat_group_ids = explode("|", $cat_group);
                foreach ($cat_group_ids as $groupId) {
                    if(array_key_exists($groupId, $categoryGroups)) {
                        $channel['cat_group'][$groupId] = $categoryGroups[$groupId];
                    }
                }
            }

            // Merge status group
             $channel['status_group'] = (array_key_exists($channel['status_group'], $statusGroups)) 
                                            ? $statusGroups[$channel['status_group']]
                                            : false;

            // Merge field group
            $channel['field_group'] = (array_key_exists($channel['field_group'], $fieldGroups))
                                            ? $fieldGroups[$channel['field_group']]
                                            : false;

            // Set an empty array for entries
            if(false == isset($channel['entries'])) {
                $channel['entries'] = array();
            }
        }

        return $channels;
    }

    /**
     * Take a flat array and nest it
     **/
    private function _build_array_from_nodes($categories, $parent_id = false, $level = 1)
    {
        $return = false;
        $children = false;

        $return = array();

        foreach($categories as $category) {
            if($category['parent_id'] == $parent_id) {
                $category_id = $category['cat_id'];
                $return[$category_id] = $category;
                $return[$category_id]['parent_id'] = $parent_id;

                $children = $this->_build_array_from_nodes($categories, $category_id, $level + 1);

                if(!empty($children['categories'])) {
                    $return[$category_id]['categories'] = $children['categories'];
                } else {
                    $return[$category_id]['categories'] = array();
                }
            }
        }

        return array(
            'categories' => $return
        );
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

    /**
     * Render the layout for the specified page
     *
     * @access private
     * @return string The page layout wrapped with header and nav
     * @var $page string The page to render
     * @var $out string The page contents
     */
    private function _renderLayout($page, $out = FALSE)
    {
        $this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line("{$page}_page_title"));
        $this->EE->cp->set_breadcrumb(BASE.AMP.$this->cp_url, $this->EE->lang->line('nsm_site_generator_module_name'));

        $nav = array();
        foreach ($this->pages as $page) {
            $nav[lang("{$page}_nav_title")] = BASE.AMP.$this->cp_url . "method=" . $page;
        }
        $this->EE->cp->set_right_nav($nav);
        return "<div class='mor'>{$out}</div>";
    }

    /**
    * Load the xml configuration
    * 
    * @param    string  $theme  The site template name based on the folder
    * @return   Object                  A SimpleXML object
    */
    private function _loadXML($theme)
    {
        return simplexml_load_file("{$this->themesDirectory}/{$theme}/structure.xml", 'SimpleXMLElement',  LIBXML_NOCDATA);
        // var_dump(simplexml_load_file("{$this->themesDirectory}/{$theme}/structure.xml", 'SimpleXMLElement',  LIBXML_NOCDATA));
        // exit;
    }

    /**
    * Instantiate and return a new Generator for $template.
    *
    * Templates can subclass @see Lg_site_generator, using a filename of the 'generator.php' and the class name
    * must be the folder name + "_gen" with the first letter uppercased.
    *
    * For example, filename would be _nsm_site_generator/basic_blog/generator.php and class would be
    * Basic_blog_gen (extends Nsm_site_generator_gen).
    *
    * @param string $template template name, e.g. 'basic_blog'
    * @param SimpleXMLElement $params parsed configuration XML of template. @see Lg_site_generator_CP::_loadXML()
    * @return Lg_site_generator
    */
    private function _getGenerator($theme)
    {
        $filename = $this->themesDirectory . $theme . "/generator.php";
        $classname = ucfirst($theme . '_generator');

        // try to include the file if it's there
        if (file_exists($filename)) {
            include_once($filename);
        }

        // default to the base class if we can't find the subclass
        if (!class_exists($classname)) {
            $classname = "Nsm_site_generator_gen";
        }

        return new $classname($theme);
    }
}