<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require PATH_THIRD.'nsm_site_generator/config.php';

/**
 * NSM Site Generator Extension
 *
 * @package			NsmSiteGenerator
 * @version			0.0.1
 * @author			Leevi Graham <http://leevigraham.com>
 * @copyright 		Copyright (c) 2007-2010 Newism <http://newism.com.au>
 * @license 		Commercial - please see LICENSE file included with this distribution
 * @link			http://expressionengine-addons.com/nsm-site-generator
 * @see 			http://expressionengine.com/public_beta/docs/development/extensions.html
 */

class Nsm_site_generator_ext
{
	public $addon_id		= NSM_SITE_GENERATOR_ADDON_ID;
	public $version			= NSM_SITE_GENERATOR_VERSION;
	public $name			= NSM_SITE_GENERATOR_NAME;
	public $description		= 'NSM Site Generator extension settings';
	public $docs_url		= '';
	public $settings_exist	= TRUE;
	public $settings		= array();

	// At leaset one hook is needed to install an extension
	// In some cases you may want settings but not actually use any hooks
	// In those cases we just use a dummy hook
	public $hooks = array('nsm_site_generator_process_field');

	public $default_site_settings = array(
		'enabled' => TRUE,
		'bundle_server_path' => ''
	);

	// ====================================
	// = Delegate & Constructor Functions =
	// ====================================

	/**
	 * PHP5 constructor function.
	 *
	 * @access public
	 * @return void
	 **/
	function __construct() {

		$EE =& get_instance();

		// define a constant for the current site_id rather than calling $PREFS->ini() all the time
		if (defined('SITE_ID') == FALSE) {
			define('SITE_ID', $EE->config->item('site_id'));
		}

		// Load the addons model and check if the the extension is installed
		// Get the settings if it's installed
		$EE->load->model('addons_model');
		if($EE->addons_model->extension_installed($this->addon_id)) {
			$this->settings = $this->_getSettings();
		}
		// Init the cache
		$this->_initCache();
	}


    function nsm_site_generator_process_field($field) {
        return $field;
    }

	/**
	 * Initialises a cache for the addon
	 * 
	 * @access private
	 * @return void
	 */
	private function _initCache() {

		$EE =& get_instance();

		// Sort out our cache
		// If the cache doesn't exist create it
		if (! isset($EE->session->cache[$this->addon_id])) {
			$EE->session->cache[$this->addon_id] = array();
		}

		// Assig the cache to a local class variable
		$this->cache =& $EE->session->cache[$this->addon_id];
	}






	// ===============================
	// = Hook Functions =
	// ===============================

	public function dummy_hook_function(){}






	// ===============================
	// = Setting Functions =
	// ===============================

	/**
	 * Render the custom settings form and processes post vars
	 *
	 * @access public
	 * @return The settings form HTML
	 */
	public	function settings_form() {

		$EE =& get_instance();
		$EE->lang->loadfile($this->addon_id);
		$EE->load->library($this->addon_id."_helper");
		$EE->load->helper('form');

		// Create the variable array
		$vars = array(
			'addon_id' => $this->addon_id,
			'error' => FALSE,
			'input_prefix' => __CLASS__,
			'message' => FALSE,
		);

		// Are there settings posted from the form?
		if($data = $EE->input->post(__CLASS__)) {

			if(!isset($data["enabled"])) {
				$data["enabled"] = TRUE;
			}

			// No errors ?
			if(! $vars['error'] = validation_errors()) {
				$this->settings = $this->_saveSettings($data);
				$EE->session->set_flashdata('message_success', $this->name . ": ". $EE->lang->line('alert.success.extension_settings_saved'));
				$EE->functions->redirect(BASE.AMP.'C=addons_extensions');
			}
		} else {
			// Sometimes we may need to parse the settings
			$data = $this->settings;
		}

		$vars["data"] = $data;

		// Return the view.
		return $EE->load->view('extension/settings', $vars, TRUE);
	}


	/**
	 * Builds default settings for the site
	 *
	 * @access private
	 * @param int $site_id The site id
	 * @param array The default site settings
	 */
	private function _buildDefaultSiteSettings($site_id = FALSE) {

		$EE =& get_instance();
		$site_settings = $this->default_site_settings;

		// No site id, use the current one.
		if(!$site_id) {
			$site_id = SITE_ID;
		}

		// return settings
		return $site_settings;
	}



	// ===============================
	// = Class and Private Functions =
	// ===============================

	/**
	 * Called by ExpressionEngine when the user activates the extension.
	 *
	 * @access		public
	 * @return		void
	 **/
	public function activate_extension() {
		$this->_createSettingsTable();
		$this->settings = $this->_getSettings();
		$this->_registerHooks();
	}

	/**
	 * Called by ExpressionEngine when the user disables the extension.
	 *
	 * @access		public
	 * @return		void
	 **/
	public function disable_extension() {
		$this->_unregisterHooks();
	}

	/**
	 * Called by ExpressionEngine updates the extension
	 *
	 * @access public
	 * @return void
	 **/
	public function update_extension($current=FALSE){}





	// ======================
	// = Settings Functions =
	// ======================

	/**
	 * The settings table
	 *
	 * @access		private
	 **/
	private static $settings_table = 'nsm_addon_settings';

	/**
	 * The settings table fields
	 *
	 * @access		private
	 **/
	private static $settings_table_fields = array(
		'id'						=> array(	'type'			 => 'int',
												'constraint'	 => '10',
												'unsigned'		 => TRUE,
												'auto_increment' => TRUE,
												'null'			 => FALSE),
		'site_id'					=> array(	'type'			 => 'int',
												'constraint'	 => '5',
												'unsigned'		 => TRUE,
												'default'		 => '1',
												'null'			 => FALSE),
		'addon_id'					=> array(	'type'			 => 'varchar',
												'constraint'	 => '255',
												'null'			 => FALSE),
		'settings'					=> array(	'type'			 => 'mediumtext',
												'null'			 => FALSE)
	);
	
	/**
	 * Creates the settings table table if it doesn't already exist.
	 *
	 * @access		protected
	 * @return		void
	 **/
	protected function _createSettingsTable() {
		$EE =& get_instance();
		$EE->load->dbforge();
		$EE->dbforge->add_field(self::$settings_table_fields);
		$EE->dbforge->add_key('id', TRUE);

		if (!$EE->dbforge->create_table(self::$settings_table, TRUE)) {
			show_error("Unable to create settings table for ".__CLASS__.": " . $EE->config->item('db_prefix') . self::$settings_table);
			log_message('error', "Unable to create settings table for ".__CLASS__.": " . $EE->config->item('db_prefix') . self::$settings_table);
		}
	}

	/**
	 * Get the addon settings
	 *
	 * 1. Load settings from the session
	 * 2. Load settings from the DB
	 * 3. Create new settings and save them to the DB
	 * 
	 * @access private
	 * @param boolean $refresh Load the settings from the DB not the session
	 * @return mixed The addon settings 
	 */
	private function _getSettings($refresh = FALSE) {

		$EE =& get_instance();
		$settings = FALSE;

		if (
			// if there are settings in the settings cache
			isset($this->cache[SITE_ID]['settings']) === TRUE 
			// and we are not forcing a refresh
			AND $refresh != TRUE
		) {
			// get the settings from the session cache
			$settings = $this->cache[SITE_ID]['settings'];
		} else {
			$settings_query = $EE->db->get_where(
									self::$settings_table,
									array(
										'addon_id' => $this->addon_id,
										'site_id' => SITE_ID
									)
								);
			// there are settings in the DB
			if ($settings_query->num_rows()) {

				if ( ! function_exists('json_decode')) {
					$$EE->load->library('Services_json');
				}

				$settings = json_decode($settings_query->row()->settings, TRUE);
				$this->_saveSettingsToSession($settings);
				log_message('info', __CLASS__ . " : " . __METHOD__ . ' getting settings from session');
			}
			// no settings for the site
			else {
				$settings = $this->_buildDefaultSiteSettings(SITE_ID);
				$this->_saveSettings($settings);
				log_message('info', __CLASS__ . " : " . __METHOD__ . ' creating new site settings');
			}
			
		}

		// Merge config settings
		foreach ($settings as $key => $value) {
			if($EE->config->item($this->addon_id . "_" . $key)) {
				$settings[$key] = $EE->config->item($this->addon_id . "_" . $key);
			}
		}

		return $settings;
	}

	/**
	 * Save settings to DB and to the session
	 *
	 * @access private
	 * @param array $settings
	 */
	private function _saveSettings($settings) {
		$this->_saveSettingsToDatabase($settings);
		$this->_saveSettingsToSession($settings);
		return $settings;
	}

	/**
	 * Save settings to DB
	 *
	 * @access private
	 * @param array $settings
	 * @return array The settings
	 */
	private function _saveSettingsToDatabase($settings) {

		$EE =& get_instance();
		$EE->load->library('javascript');

		$data = array(
			'settings'	=> $EE->javascript->generate_json($settings, true),
			'addon_id'	=> $this->addon_id,
			'site_id'	=> SITE_ID
		);
		$settings_query = $EE->db->get_where(
							'nsm_addon_settings',
							array(
								'addon_id' =>  $this->addon_id,
								'site_id' => SITE_ID
							), 1);

		if ($settings_query->num_rows() == 0) {
			$query = $EE->db->insert('exp_nsm_addon_settings', $data);
			log_message('info', __METHOD__ . ' Inserting settings: $query => ' . $query);
		} else {
			$query = $EE->db->update(
							'exp_nsm_addon_settings',
							$data,
							array(
								'addon_id' => $this->addon_id,
								'site_id' => SITE_ID
							));
			log_message('info', __METHOD__ . ' Updating settings: $query => ' . $query);
		}
		return $settings;
	}

	/**
	 * Save the settings to the session
	 *
	 * @access private
	 * @param array $settings The settings to push to the session
	 * @return array the settings unmodified
	 */
	private function _saveSettingsToSession($settings) {
		$this->cache[SITE_ID]['settings'] = $settings;
		return $settings;
	}




	// ======================
	// = Hook Functions     =
	// ======================

	/**
	 * Sets up and subscribes to the hooks specified by the $hooks array.
	 *
	 * @access private
	 * @param array $hooks A flat array containing the names of any hooks that this extension subscribes to. By default, this parameter is set to FALSE.
	 * @return void
	 * @see http://expressionengine.com/public_beta/docs/development/extension_hooks/index.html
	 **/
	private function _registerHooks($hooks = FALSE) {

		$EE =& get_instance();

		if($hooks == FALSE && isset($this->hooks) == FALSE) {
			return;
		}

		if (!$hooks) {
			$hooks = $this->hooks;
		}

		$hook_template = array(
			'class'    => __CLASS__,
			'settings' => "a:0:{}",
			'version'  => $this->version,
		);

		foreach ($hooks as $key => $hook) {
			if (is_array($hook)) {
				$data['hook'] = $key;
				$data['method'] = (isset($hook['method']) === TRUE) ? $hook['method'] : $key;
				$data = array_merge($data, $hook);
			} else {
				$data['hook'] = $data['method'] = $hook;
			}

			$hook = array_merge($hook_template, $data);
			$EE->db->insert('exp_extensions', $hook);
		}
	}

	/**
	 * Removes all subscribed hooks for the current extension.
	 * 
	 * @access private
	 * @return void
	 * @see http://expressionengine.com/public_beta/docs/development/extension_hooks/index.html
	 **/
	private function _unregisterHooks() {
		$EE =& get_instance();
		$EE->db->where('class', __CLASS__);
		$EE->db->delete('exp_extensions'); 
	}
}