<?php //if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require PATH_THIRD.'nsm_site_generator/config.php';
require 'libraries/nsm_site_generator_gen.php';


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
        $this->template_dir = $this->ext->settings['bundle_server_path'];
    }


    /**
     * Displays the dashboard
     *
     * @access public
     * @return string The page layout
     */
    public function index()
    {
        $view_data = array(
            "input_prefix" => __CLASS__,
            "error" => FALSE,
            "generators" => FALSE
        );

        $generators = FALSE;

        if(is_dir($this->template_dir))
        {
            $dir_handle = opendir($this->template_dir);
            if($dir_handle)
            {
                /* This is the correct way to loop over the directory. */
                while (false !== ($f = readdir($dir_handle)))
                {
                    if ($f != "." && $f != ".." && $f != "Thumb.db" && substr($f, 0, 1) != '-')
                    {
                        if(is_dir("{$this->template_dir}/{$f}"))
                        {
                            $generator = $this->_loadXML($f);
                            $generator['folder'] = $f;
                            $generator["generator_url"] = BASE.AMP.$this->cp_url . "method=configure_import". AMP . "site_template=". $f;
                            $view_data["generators"][] = $generator;
                        }
                    }
                }
            }
        }

        if(!$view_data["generators"])
            $view_data["error"] = sprintf(lang("alert.warning.no_templates"), $this->template_dir);

        $out = $this->EE->load->view("layouts/module/index", $view_data, TRUE);
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
        $site_template = $this->EE->input->get('site_template');
        $generator_xml = $this->_loadXML($site_template);

        $view_data = array(
            'input_prefix' => __CLASS__,
            'channels' => $generator_xml->channels->channel,
            'existing_channels' => array(),
            'xml' => $generator_xml
        );

        foreach ($this->EE->channel_model->get_channels()->result() as $channel)
            $view_data['existing_channels'][] = $channel->channel_name;

        $out = $this->EE->load->view("layouts/module/configure_import", $view_data, TRUE);
        $out = form_open($this->cp_url . "method=import", FALSE, array("site_template" => $site_template)) . $out . "</form>";
        return $this->_renderLayout("configure_import", $out);
    }

    /**
     * Import the channels, templates etc.
     */
    public function import()
    {
        // What are we generating?
        $site_template = $this->EE->input->post('site_template');
        // Build the XML
        $generator_xml = $this->_loadXML($site_template);
        // Get the generator
        $generator = $this->_getGenerator($site_template, $generator_xml);
        // Generate
        $generator->generate($this->EE->input->post(__CLASS__));

        $post_import_instructions = $generator_xml->xpath("post_import_instructions");

        $view_data = array(
            "log" => $generator->getLog(),
            "post_import_instructions" => (string)$post_import_instructions[0]
        );

        $out = $this->EE->load->view("layouts/module/import", $view_data, TRUE);
        return $this->_renderLayout("import", $out);
    }

    /**
     * Display configuration options for exporting XML
     *
     * @access public
     * @return string The page layout
     */
    public function configure_export() {
        $this->EE =& get_instance();

        $this->EE->db->select('
            channels.channel_id as channel_id,
            channels.channel_title as channel_title,
            channels.cat_group as cat_group,
            status_groups.group_id as status_group_id,
            status_groups.group_name as status_group_name,
            field_groups.group_id as field_group_id,
            field_groups.group_name as field_group_name
        ');

        $this->EE->db->from('channels');
        $this->EE->db->join('status_groups', 'channels.status_group = status_groups.group_id', 'left');
        $this->EE->db->join('field_groups', 'channels.field_group = field_groups.group_id', 'left');

        $channel_query = $this->EE->db->get();
        $channels = $this->hydrate($channel_query->result_array(), 'channel_id');

        // Are there settings posted from the form?
        if($data = $this->EE->input->post(__CLASS__)) {

            foreach ($data['channels'] as &$channel) {
                if(empty($channel['entries'])) {
                    $channel['entries'] = array();
                }
            }

            $xml = $this->create_xml($data);
            $view_data = array(
               'xml' => htmlentities($xml, ENT_QUOTES, false)
            );
            $out = $this->EE->load->view("layouts/module/export", $view_data, TRUE);
            return $this->_renderLayout("configure_export", $out);

        } else {

            $data = array(
                'title' => false,
                'version' => false,
                'description' => false,
                'download_url' => false,
                'post_import_instructions' => false,
                'channels' => array()
            );

            foreach ($channels as $channel) {
                $data['channels'][$channel['channel_id']]['enabled'] = false;
                $data['channels'][$channel['channel_id']]['entries'] = array();
            }
        }

        $category_group_query = $this->EE->db->get('exp_category_groups');
        $category_groups = $this->hydrate($category_group_query->result_array(), 'group_id');

        $channel_entries_query = $this->EE->db->get('exp_channel_titles');
        $channel_entries = $this->hydrate($channel_entries_query->result_array(), 'entry_id');

        foreach ($channels as $channel_id => &$channel) {

            if(empty($channel['cat_group'])) {
                $channel['cat_group'] = array();
            } else {
                $channel_category_groups = explode("|", $channel['cat_group']);
                $tmp = array();
                foreach($channel_category_groups as $channel_category_group) {
                    $group_id = $channel_category_group['group_id'];
                    $tmp[$group_id] = $category_groups[$group_id];
                }
                $channel['cat_group'] = $tmp;
            }

            $channel['entries'] = array();
            foreach ($channel_entries as $entry_id => $entry_data) {
                if($entry_data['channel_id'] == $channel_id) {
                    $channel['entries'][] = $entry_data;
                }
            }
        }

        $view_data = array(
            'input_prefix' => __CLASS__,
            'channels' => $channels,
            'data' => $data
        );

        $out = $this->EE->load->view("layouts/module/configure_export", $view_data, TRUE);
        $out = form_open($this->cp_url . "method=configure_export", FALSE) . $out . "</form>";
        return $this->_renderLayout("configure_export", $out);
    }

    public function create_xml($config) {
        $bundle_info = array(
            'title' => $config['title'],
            'download_url' => $config['download_url'],
            'version' => $config['version'],
            'description' => $config['description'],
            'post_import_instructions' => $config['post_import_instructions'],
            'authors' => array(),
            'requirements' => array()
        );

        $required_channels = array();
        $required_entries = array();
        foreach ($config['channels'] as $channel_id => $channel_data) {
            if(empty($channel_data['enabled'])) {
                unset($config['channels'][$channel_id]);
            }
            $required_channels[] = $channel_id;
            $required_entries = array_merge($required_entries, $channel_data['entries']);
        }

        // Get the category groups
        $category_group_query = $this->EE->db->get('category_groups');
        $category_groups = $this->hydrate($category_group_query->result_array(), 'group_id');

        foreach ($category_groups as $group_id => &$group) {
            $group['group_ref_id'] = "category_group_" . $group_id;
        }

        // Get the categories
        $category_query = $this->EE->db->get('categories');
        $categories = $this->hydrate($category_query->result_array(), 'cat_id');

        // Add categories to category group
        foreach($category_groups as $group_id => &$group) {
            $group['categories'] = array();
            foreach ($categories as $category_id => $category) {
                if($group_id == $category['group_id']) {
                    $group['categories'][$category_id] = $category;
                }
            }
            $nested = $this->_build_array_from_nodes($group['categories']);
            $group['categories'] = $nested['categories'];
        }

        // Get the field groups
        $field_group_query = $this->EE->db->get('field_groups');
        $field_groups = $this->hydrate($field_group_query->result_array(), 'group_id');

        foreach ($field_groups as $group_id => &$group) {
            $group['group_ref_id'] = "field_group_" . $group_id;
        }

        // Get the fields
        $field_query = $this->EE->db->get('channel_fields');
        $fields = $this->hydrate($field_query->result_array(), 'field_id');

        // Add fields to field group
        foreach($field_groups as $group_id => &$group) {
            $group['fields'] = array();
            foreach ($fields as $field_id => $field) {

                // Modify the output of the field
                if ($this->EE->extensions->active_hook('nsm_site_generator_export_field') === TRUE) {
                    $field = $this->EE->extensions->universal_call('nsm_site_generator_export_field', $field);
                    if ($this->EE->extensions->end_script === TRUE) return;
                }

                if($group_id == $field['group_id']) {
                    $group['fields'][$field_id] = $field;
                }
            }
        }

        // Get the channels
        $this->EE->db->where_in('channel_id', $required_channels);
        $channel_query = $this->EE->db->get('channels');
        $channels = $this->hydrate($channel_query->result_array(), 'channel_id');

        // Get the entries
        $entry_query =  $this->EE->db->from('channel_titles')
                                    ->join('channel_data', 'channel_titles.entry_id = channel_data.entry_id')
                                    ->where_in('channel_titles.entry_id', $required_entries)
                                    ->get();

        $entries = $this->hydrate($entry_query->result_array(), 'entry_id');

        // For each of the entries build the custom fields
        foreach ($entries as $entry_id => &$entry_data) {
            $entry_data['fields'] = array();

            foreach ($entry_data as $key => $value) {

                // If $key is field_id_n or field_ft_n
                if(substr($key,0, 6) == 'field_') {

                    $field_id = substr($key,9);
                    $custom_field = $fields[$field_id];
                    $channel_field_group_id = $channels[$entry_data['channel_id']]['field_group'];

                    if($custom_field['group_id'] == $channel_field_group_id) {
                        $field_attr = (substr($key,6,2) == "id") ? "data" : "formatting";
                        if($field_attr == "data") {

                            $entry_data['fields'][$field_id]['field_name'] = $custom_field['field_name'];
                            $entry_data['fields'][$field_id]['type'] = $custom_field['field_type'];

                            // Modify the output of the field
                            if ($this->EE->extensions->active_hook('nsm_site_generator_export_field_data') === TRUE) {
                                $value = $this->EE->extensions->universal_call('nsm_site_generator_export_field_data', $value, $custom_field);
                                if ($this->EE->extensions->end_script === TRUE) return;
                            }
                        }
                        $entry_data['fields'][$field_id][$field_attr] = $value;
                    }

                    unset($entry_data[$key]);
                }
            }
        }

        // Add entries to channel
        foreach($channels as $channel_id => $channel) {
            $channel['channel_entries'] = array();
            foreach ($entries as $entry_id => $entry) {
                if($channel_id == $entry['channel_id']) {
                    $channel['channel_entries'][$entry_id] = $entry;
                }
            }
            $channels[$channel_id]=$channel;
        }

        // Get status groups
        $status_group_query = $this->EE->db->get('status_groups');
        $status_groups = $this->hydrate($status_group_query->result_array(), 'group_id');
        foreach ($status_groups as $group_id => &$group) {
            $group['group_ref_id'] = "status_group_" . $group_id;
        }

        // Get the statuses
        $status_query = $this->EE->db->get('statuses');
        $statuses = $this->hydrate($status_query->result_array(), 'status_id');

        // Add entries to channel
        foreach($status_groups as $group_id => &$status_group) {
            $status_group['statuses'] = array();
            foreach ($statuses as $status_id => $status) {
                if($group_id == $status['status_id']) {
                    $status_group['statuses'][$status_id] = $status;
                }
            }
            $channels[$channel_id]=$channel;
        }

        // Loop over the channels
        foreach ($channels as $channel_id => &$channel) {

            // replace the cat_group with a ref_id
           if(false === empty($channel['cat_group'])) {
               $channel_cat_group_refs = array();
               $channel_cat_groups = explode("|", $channel['cat_group']);
               foreach ($channel_cat_groups as $group_id) {
                   $channel_cat_group_refs[] = $category_groups[$group_id]['group_ref_id'];
               }
               $channel['cat_group'] = implode("|", $channel_cat_group_refs);
           }

           // replace the status_group with a ref_id
           if(false === empty($channel['status_group'])) {
               $channel['status_group'] = $status_groups[$channel['status_group']]['group_ref_id'];
           }

           // replace the field_group with a ref_id
           if(false === empty($channel['field_group'])) {
               $channel['field_group'] = $field_groups[$channel['field_group']]['group_ref_id'];
           }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= "\n<generator_bundle>";
        $xml .= "\n".$this->renderBundleInfo($bundle_info);
        $xml .= "\n".$this->renderCategoryGroups($category_groups);
        $xml .= "\n".$this->renderFieldGroups($field_groups);
        $xml .= "\n".$this->renderStatusGroups($status_groups);
        $xml .= "\n".$this->renderChannels($channels);
        $xml .= "\n</generator_bundle>";

        return $xml;
    }

    public function renderBundleInfo($bundle_info) {
        $out = "";
        foreach ($bundle_info as $key => $value) {
            if(in_array($key, array('authors', 'requirements'))) {
                continue;
            }
            $out .= "\n<{$key}><![CDATA[ {$value} ]]></{$key}>";
        }
        $out .= "\n".$this->renderBundleAuthors($bundle_info['authors']);
        $out .= "\n".$this->renderBundleRequirements($bundle_info['requirements']);
        return $out;
    }

    public function renderBundleAuthors($authors, $tab_depth = 1) {
        $tab = str_repeat($this->tab, $tab_depth);
        $out = "<authors>";
        foreach ($authors as $author) {
            $out = "\n{$tab}<author ";
            $out .= $this->renderAttributes($author);
            $out .= "/>";
        }
        $out .= "</authors>";
        return $out;
    }

    public function renderBundleRequirements($requirements) {
        $out = "<requirements>";
        foreach ($requirements as $requirement) {
            $out = "<requirement ";
            $out .= $this->renderAttributes($requirement);
            $out .= "/>";
        }
        $out .= "</requirements>";
        return $out;
    }

    public function renderCategoryGroups($category_groups, $tab_depth = 1) {
        $tab = str_repeat($this->tab, $tab_depth);
        $out = "\n<category_groups>";
        foreach ($category_groups as $category_group) {
            $out .= "\n{$tab}<group ";
            $out .= $this->renderAttributes($category_group, array('group_id','site_id','categories'));
            $out .= ">";
            $out .= $this->renderCategories($category_group["categories"], 2);
            $out .= "\n{$tab}</group>";
        }
        $out .= "\n</category_groups>";
        return $out;
    }

    public function renderCategories($categories, $tab_depth = 1) {
        $tab = str_repeat($this->tab, $tab_depth);
        $out = "";
        foreach($categories as $category) {
            $out .= "\n".$tab."<category";
            $out .= " " . $this->renderAttributes($category, array('cat_id','parent_id','group_id','site_id',"categories"));
            if(false === empty($category['categories'])) {
                $out .= " >";
                $out .= " ".$this->renderCategories($category['categories'], $tab_depth+1);
                $out .= "\n".$tab."</category>";
            } else {
                $out .= " />";
            }
        }
        return $out;
    }

    public function renderFieldGroups($field_groups, $tab_depth = 1) {
        $tab = str_repeat($this->tab, $tab_depth);
        $out = "\n<field_groups>";
        foreach ($field_groups as $field_group) {
            $out .= "\n{$tab}<group ";
            $out .= $this->renderAttributes($field_group, array('group_id','site_id','fields'));
            $out .= ">";
            $out .= $this->renderFields($field_group["fields"]);
            $out .= "\n{$tab}</group>";
        }
        $out .= "\n</field_groups>";
        return $out;
    }

    public function renderFields($fields, $tab_depth = 2) {
        $tab = str_repeat($this->tab, $tab_depth);
        $out = "";
        foreach($fields as $field) {
            $out .= "\n{$tab}<field";
            $out .= " " . $this->renderAttributes($field, array('field_id','group_id','site_id'));
            $out .= "/>";
        }
        return $out;
    }

    public function renderStatusGroups($status_groups, $tab_depth = 1) {
        $tab = str_repeat($this->tab, $tab_depth);
        $out = "\n<status_groups>";
        foreach ($status_groups as $status_group) {
            $out .= "\n{$tab}<group ";
            $out .= $this->renderAttributes($status_group, array('group_id','site_id', 'statuses'));
            $out .= ">";
            $out .= $this->renderStatuses($status_group["statuses"]);
            $out .= "\n{$tab}</group>";
        }
        $out .= "\n</status_groups>";
        return $out;
    }

    public function renderStatuses($statuses, $tab_depth = 2) {
        $tab = str_repeat($this->tab, $tab_depth);
        $out = "";
        foreach($statuses as $status) {
            $out .= "\n{$tab}<status";
            $out .= " " . $this->renderAttributes($status, array('status_id','status_order','group_id','site_id'));
            $out .= " />";
        }
        return $out;
    }

    public function renderChannels($channels, $tab_depth = 1) {
        $tab = str_repeat($this->tab, $tab_depth);
        $out = "\n<channels>";
        foreach ($channels as $channel) {
            $out .= "\n{$tab}<channel ";
            $out .= $this->renderAttributes($channel, array(
                                                        'channel_entries',
                                                        'channel_id',
                                                        'site_id',
                                                        'total_entries',
                                                        'total_comments',
                                                        'last_entry_date',
                                                        'last_comment_date',
                                                        'channel_notify_emails'));
            $out .= ">";
            $out .= $this->renderChannelEntries($channel["channel_entries"]);
            $out .= "\n{$tab}</channel>";
        }
        $out .= "\n</channels>";
        return $out;
    }

    public function renderChannelEntries($entries, $tab_depth = 2) {
        $tab = str_repeat($this->tab, $tab_depth);
        $out = "";
        foreach($entries as $entry) {
            $out .= "\n{$tab}<entry";
            $out .= " " . $this->renderAttributes($entry, array(
                                                            'fields',
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
            $out .= $this->renderEntryFields($entry["fields"]);
            $out .= "\n{$tab}</entry>";
        }
        return $out;
    }

    public function renderEntryFields($fields, $tab_depth = 3) {
        $tab = str_repeat($this->tab, $tab_depth);
        $out = "";
        foreach($fields as $field) {
            $out .= "\n{$tab}<field";
            $out .= " " . $this->renderAttributes($field, array('data'));
            $out .= ">";
            if(false == empty($field['data'])) {
                $out .= "<![CDATA[ " . $field['data'] . "]]>";
            }
            $out .= "</field>";
        }
        return $out;
    }

    public function renderAttributes($data, $exclude = array()) {
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
    * @param    string  $site_template  The site template name based on the folder
    * @return   Object                  A SimpleXML object
    */
    private function _loadXML($site_template)
    {
        return simplexml_load_file("{$this->template_dir}/{$site_template}/config.xml", 'SimpleXMLElement',  LIBXML_NOCDATA);
        // var_dump(simplexml_load_file("{$this->template_dir}/{$site_template}/config.xml", 'SimpleXMLElement',  LIBXML_NOCDATA));
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
    private function _getGenerator($site_template, $params)
    {
        $filename = $this->template_dir . $site_template . "/generator.php";
        $classname = ucfirst($site_template . '_generator');

        // try to include the file if it's there
        if (file_exists($filename))
            include_once($filename);

        // default to the base class if we can't find the subclass
        if (!class_exists($classname))
            $classname = "Nsm_site_generator_gen";

        return new $classname($site_template, $params);
    }
}