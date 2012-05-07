<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

/**
* NSM Form class

Usage:

public function create_form(){

	$this->EE =& get_instance();

	if(!class_exists("NSM_Form"))
		include("libraries/NSM/Form.php");

	$form = new NSM_Form();

	return $form->build(
		$this->EE->TMPL->tagdata,
		"your_class::class_method",
		array(
			"secure" => array("secure_param" => "secure_param_value"),
			"hidden" => array()
		)
	);
}

public function process_form_submission(){

	$this->EE =& get_instance();

	if(!class_exists("NSM_Form"))
		include("libraries/NSM/Form.php");

	$form = new NSM_Form();

	// Process submission
	$form->processSubmitStart();
	
	// var_dump($form->secure_params);
	
	// Redirect submission
	$form->processSubmitEnd();
}



*/
class NSM_Form
{
	var $EE = FALSE;
	var $AJAX_REQUEST = FALSE;
	var $content = FALSE;

	// Form params
	var $opts = array(
		"action" => FALSE,
		"enctype" => FALSE,
		"secure" => TRUE
	);

	// Hidden params displayed in the form itself
	var $hidden_params = array(
		"return" => FALSE,
		"ajax_return" => FALSE,
		"ACT" => FALSE
	);

	// Secure params added to the DB
	var $secure_params = array();

	// Form attributes, parsed from tag params
	var $form_attrs = array();

	static $paramsTable = "nsm_form_params";
	static $paramsTableFields = array(
		"hash" => array(),
		"data" => array(),
		"created_at" => FALSE
	);


	/**
	 * Construct the Form object
	 */
	public function __construct(){
		$this->EE =& get_instance();
		if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
			$this->AJAX_REQUEST = TRUE;
		}
	}

	// ===============================
	// = FORM HTML BUILDER METHODS   =
	// ===============================

	/**
	 * Build the form HTML
	 * 
	 * @access public
	 * @param string $content The form content
	 * @param array $opts The form options
	 * @param array $params The form params
	 * @return string The form HTML
	 */
	public function build($content, $opts = array(), array $params = array())
	{
		$this->content = $content;

		// Build the form options
		if(is_string($opts))
			$opts = array("ACT" => $opts);

		$this->opts = array_merge($this->opts, $opts);

		if(isset($this->opts["ACT"]))
		{
			$parts = explode("::", $this->opts["ACT"]);
			$this->hidden_params["ACT"] = $this->EE->functions->fetch_action_id($parts[0], $parts[1]);
		}

		// Build the hidden params
		if(isset($params["hidden"]))
			$this->hidden_params = array_merge($this->hidden_params, $params["hidden"]);

		// Build the secure params
		if(isset($params["secure"]))
			$this->secure_params = array_merge($this->secure_params, $params["secure"]);

		if(!empty($this->secure_params))
			$this->hidden_params["params_id"] = $this->_saveParamsToDB();

		// parse the tag params
		$this->_parseTagParams();


		// build the form tag
		$data = $this->opts;
		$data["hidden_fields"] = $this->hidden_params;
		$r = $this->EE->functions->form_declaration($data);

		// add all the form params
		foreach ($this->form_attrs as $key => $value)
			$r = str_replace( "<form", '<form '.$key.'="'.htmlspecialchars($value).'"', $r );

		$r = $r . $this->content . "</form>";
		return $r;
	}

	/**
	 * Parse the template tag params
	 * 
	 * @access private
	 */
	private function _parseTagParams(){
		foreach ($this->EE->TMPL->tagparams as $key => $value)
		{
			switch ($key) {
				case ($key == 'return' || $key == 'ajax_return'):
					$this->hidden_params[$key] = $this->_buildReturnURL($value);
					break;
				case (strncmp($key, 'form:', 5) == 0):
					$this->form_attrs[substr($key, 5)] = $value;
					break;
				case(!array_key_exists($key, $this->secure_params)):
					$this->hidden_params[$key] = $value;
			}
		}
	}

	/**
	 * Save the forms secure params to the db and return a hash / key
	 * 
	 * @access private
	 * @return string The hash / key id for the DB row
	 */
	private function _saveParamsToDB()
	{
		$this->EE->db->query( "DELETE FROM exp_user_params WHERE entry_date < ". ($this->EE->localize->now-7200) );
		// insert params into DB
		$hash = $this->EE->functions->random('alpha', 25);
		$this->EE->db->insert(self::$paramsTable, 
								array(
									"data" => serialize($this->secure_params),
									"created_at" => $this->EE->localize->now,
									"hash" => $hash
								)
							);
		return $hash;
	}

	/**
	 * Build the return param.
	 * 
	 * The return param can accept template_group/template, whole URLS or EE {path=} variables.
	 * This method turns the URL into a fully qualified URL
	 * 
	 * @access private
	 * @param $str string The raw return string before processing
	 * @return string The fully qualified URL
	 */
	private function _buildReturnURL($str)
	{
		if ( preg_match( "/".LD."\s*path=(.*?)".RD."/", $str, $match ))
		{
			$str = $this->EE->functions->create_url( $match['1'] );
		}
		elseif ( ! preg_match( "#https?:\/\/#", $str ))
		{
			$str = $this->EE->functions->create_url( $str );
		}
		return $str;
	}

	// ===============================
	// = FORM SUBMISSION METHODS     =
	// ===============================

	/**
	 * Start the form submission processing
	 *
	 * 1. Checks for a secure form submission
	 * 2. Loads in the params from the db
	 * 
	 * @access public
	 */
	function processSubmitStart()
	{

		// secure forms
		if ($this->EE->config->item('secure_forms') == 'y')
		{
			$hash = $this->EE->input->post('XID');
			$query = $this->EE->db->get_where("security_hashes", array("hash" => $hash), 1);

			if ($query->num_rows == 0)
			{
				$this->EE->output->fatal_error($this->EE->lang->line('invalid_action'));
			}
		}

		// get the secure params from the db and load into the form object
		if($hash = $this->EE->input->post("params_id"))
		{
			$query = $this->EE->db->get_where(self::$paramsTable, array('hash' => $hash), 1);
			if($query->num_rows > 0)
			{
				$row = $query->row_array();
				$this->secure_params = unserialize($row["data"]);
			}
		}
	}

	/**
	 * End the form submission processing
	 *
	 * 1. Redirect the user based on the submission params
	 * 
	 * @access public
	 * @param $redirect bool Redirect the user or not
	 */
	function processSubmitEnd($redirect = TRUE)
	{
		if($redirect)
		{
			// get the return URL and redirect
			// Ajax, redirect straight away without deleting the hash?
			if($this->AJAX_REQUEST && $this->EE->input->get_post('ajax_return'))
				$this->EE->functions->redirect($this->EE->input->get_post('ajax_return'));
			elseif ($this->EE->input->get_post('return')) // Return param
				$return = $this->EE->input->get_post('return');
			elseif ($this->EE->input->get_post('RET')) // Default return param?
				$return = $this->EE->input->get_post('RET');
			else // site url
				$return = $this->EE->config->item('site_url');

			if ( preg_match( "/".LD."\s*path=(.*?)".RD."/", $return, $match ) )
				$return	= $this->EE->functions->create_url( $match['1'] );

			// If everything is successful then delete the submission hash
			if ($this->EE->config->item('secure_forms') == 'y')
			{
				$hash = $this->EE->input->post('XID');
				$this->EE->db->where('hash', $hash)->delete('security_hashes');
			}

			$this->EE->functions->redirect( $return );
		}
	}
}