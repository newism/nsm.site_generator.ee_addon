<?php //if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require PATH_THIRD.'nsm_site_generator/config.php';
require 'libraries/nsm_site_generator_gen.php';


/**
 * NSM Site Generator CP
 *
 * @package			NSMSiteGenerator
 * @version			0.0.1
 * @author			Leevi Graham <http://leevigraham.com>
 * @copyright 		Copyright (c) 2007-2010 Newism
 * @license 		Commercial - please see LICENSE file included with this distribution
 * @link			http://expressionengine-addons.com/nsm-site-generator
 * @see				http://expressionengine.com/public_beta/docs/development/modules.html#control_panel_file
 */

/**
 * Require the base generator class
 */

class Nsm_site_generator_mcp{

    public static $addon_id = NSM_SITE_GENERATOR_ADDON_ID;

	private $pages = array("index", "configure_export");

    public $EE;

	public function __construct()
	{
		$this->EE =& get_instance();
		$this->addon_id = strtolower(substr(__CLASS__, 0, -4));
		$this->cp_url = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->addon_id.AMP;


		$this->EE->load->library("{$this->addon_id}_helper", null, $this->addon_id);

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
	public function configure_export()
	{

        $this->EE =& get_instance();
	    $this->EE->load->library($this->addon_id."_helper");

	    $this->EE->db->select('
	        channels.channel_id as channel_id,
	        channels.channel_title as channel_title,
	        channels.cat_group as channel_category_group,
	        status_groups.group_id as status_group_id,
	        status_groups.group_name as status_group_name,
	        field_groups.group_id as field_group_id,
	        field_groups.group_name as field_group_name
	    ');
        $this->EE->db->from('channels');
        $this->EE->db->join('status_groups', 'channels.status_group = status_groups.group_id', 'left');
        $this->EE->db->join('field_groups', 'channels.field_group = field_groups.group_id', 'left');

        $query = $this->EE->db->get();
        $channels = $query->result_array();

        $required_category_groups = array();
        foreach ($channels as &$channel) {
            $channel['entries'] = array();
            if(!empty($channel['channel_category_group'])) {
                $channel_categories = explode("|", $channel['channel_category_group']);
                $required_category_groups = array_merge($required_category_groups, $channel_categories);
                $channel['channel_category_group'] = $channel_categories;
            } else {
                $channel['channel_category_group'] = array();
            }
        }

        $this->EE->db->select('group_name, group_id');
        $this->EE->db->from('exp_category_groups');
        $this->EE->db->where_in('group_id', $required_category_groups);
        $query = $this->EE->db->get();
        $categories = $query->result_array();

        $indexed_categories = array();
        foreach ($categories as $category) {
            $indexed_categories[$category['group_id']] = $category;
        }

        foreach ($channels as &$channel) {
            if(!empty($channel['channel_category_group'])) {
                foreach ($channel['channel_category_group'] as $key => $category) {
                    $channel['channel_category_group'][$key] = $indexed_categories[$category];
                }
            }
        }

        // var_dump($categories);
        // var_dump($required_category_groups);
        // var_dump($channels);
        // exit;

		$view_data = array(
			'input_prefix' => __CLASS__,
            'channels' => $channels,
            'data' => array(
                'title' => false,
                'version' => false,
                'description' => false,
                'download_url' => false,
                'post_import_instructions' => false,
            )
		);

		$out = $this->EE->load->view("layouts/module/configure_export", $view_data, TRUE);
		$out = form_open($this->cp_url . "method=export", FALSE) . $out . "</form>";
		return $this->_renderLayout("configure_export", $out);
	}

    public function export()
    {
        var_dump($_POST);
        exit;
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
	* @param	string	$site_template	The site template name based on the folder
	* @return	Object					A SimpleXML object
	*/
	private function _loadXML($site_template)
	{
		return simplexml_load_file("{$this->template_dir}/{$site_template}/config.xml", 'SimpleXMLElement',  LIBXML_NOCDATA);
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