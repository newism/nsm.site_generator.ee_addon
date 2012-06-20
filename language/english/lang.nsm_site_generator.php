<?php

/**
 * NSM Site Generator Language File
 *
 * @package			NSMSiteGenerator
 * @version			0.0.1
 * @author			Leevi Graham <http://leevigraham.com>
 * @copyright 		Copyright (c) 2007-2010 Newism
 * @license 		Commercial - please see LICENSE file included with this distribution
 * @link			http://expressionengine-addons.com/nsm-site-generator
 * @see				http://expressionengine.com/public_beta/docs/development/modules.html#lang_file
 */
$lang = array(

	/* Module */
	'nsm_site_generator' => 'NSM Site Generator',
	'nsm_site_generator_module_name' => 'NSM Site Generator',
	'nsm_site_generator_module_description' => 'Generate scaffold templates, channels etc.',

	'index_page_title' => 'NSM Site Generator: Dashboard',
	'index_nav_title' => 'Import Theme',
	
	'configure_import_page_title' => '2. Preview Import',
	'import_page_title' => '3. Import Complete',

	'configure_export_page_title' => 'Theme: Export',
	'configure_export_nav_title' => 'Export Theme',
	'export_page_title' => 'Export Complete',

	/* Extension */
	'save_extension_settings' => 'Save extension settings',

	/* Messages / Alerts */
	'alert.warning.no_templates' => 'No site themes were found in <code>%s</code>. Please check the extension settings.',
	'alert.warning.truncate_db' => '<br /><strong>Caution:</strong> Checking this box will automatically remove all of the following in the database: 
	                                    channels, channel entries, template groups, templates, category groups, categories, custom field groups, custom fields, mailing lists.',

	/** ----------------------------------------------------------------------
	/** Log messages used by lib/Lg_site_generator
	/** ------------------------------------------------------------------- */

	"generatelog_info" 			=> "The site generation is complete. Here is a log of activity:",

	"log_ok_parsed_config"			=> "Parse configuration",
	"log_ok_truncate_db"			=> "Truncated database",
	"log_ok_extensions_enabled"		=> "Enabled extensions",

	// categories
	'log_warning_category_group_exist'		=> "Category group <strong>{group_name}</strong> already exists. Adding categories to the existing group.",
	'log_warning_category_exist'				=> "Category <strong>{cat_name}</strong> already exists in <strong>{group_name}</strong>.",
	"log_ok_category_group_imported"			=> "Created category group <strong>{group_name}</strong> with id <strong>{group_id}</strong>.",
	"log_ok_category_imported"					=> "Created category <strong>{cat_name}</strong> in category group <strong>{group_name}</strong> with id <strong>{cat_id}</strong>.",
	"log_notice_no_category_groups_imported"    => "No category groups were created.",

	// statuses
	"log_ok_status_group_imported"			=> "Created status group <strong>{group_name}</strong> with id <strong>{group_id}</strong>.",
	"log_ok_status_imported"					=> "Created status <strong>{status}</strong> in <strong>{group_name}</strong> with id <strong>{status_id}</strong>.",
	'log_warning_status_group_exist'		=> "Status group <strong>{group_name}</strong> already exists. Adding statuses to the existing group.",
	'log_error_status_exist'				=> "Status <strong>{status}</strong> already exists in <strong>{group_name}</strong>.",
	'log_notice_no_status_groups_imported'   => "No status groups were created.",

	// fieldgroups
    "log_ok_field_group_imported"			=> "Created field group <strong>{group_name}</strong> with id <strong>{group_id}</strong>.",
    "log_warning_field_group_exist" 		=> "Field group <strong>{group_name}</strong> already exists. Adding fields to existing group.",
	"log_ok_field_imported"			        => "Created field <strong>{field_name}</strong> with id <strong>{field_id}</strong>.",
	"log_error_field_exist" 		        => "Field <strong title='Field ID: {field_id}'>{field_name}</strong> already exists in <strong>{group_name}</strong>.",
	"log_notice_no_custom_field_groups_imported" => "No custom field groups were created.",

	// template groups
	"log_ok_template_group_imported"				    => "Created template group <strong>{group_name}</strong> with id <strong>{group_id}</strong>.",
	"log_ok_template_imported"		    => "Created template <strong>{group_name}/{template_name}</strong> in database with id <strong>{template_id}</strong>.",
	"log_warning_template_group_exist"	=> "Template group <strong>{group_name}</strong> already exists. Adding templates to the existing group.",
	"log_error_template_exists"		    => "Template <strong>{group_name}/{template_name}</strong> already exists.",
	"log_copy_attempt"				    => "Attempting to copy template file <strong>{group_name}/{template_name}</strong> from generator to themes.",
	"log_error_template_no_file"	    => "Template <strong>{template_name}</strong> could not be found in {directory}.",
	"log_error_cannot_write_to_destination" => "Cannot write <strong>{group_name}/{template_name}</strong> in <code>{directory}</code>.",
	"log_error_cannot_read_template_file" => "Cannot read <strong>{group_name}/{template_name}</strong> in <code>{directory}</code>.",
	"log_ok_copy_template"			    => "Copied template file <strong><code>{name}</code></strong> to <strong><code>{dest_filename}</code></strong>.",
	"log_error_dest_file_exists"	    => "<strong><code>{template_name}</code></strong> already exists in <strong><code>{directory}</code></strong>.",
	"log_error_copy_fail"			    => "Copy failed, not sure why.",

	// channels
	"log_ok_channel_imported"		 => "Created channel <strong>{channel_name}</strong> with id <strong>{channel_id}</strong>.",
	'log_notice_no_channels_imported' => "No channels were created.",
	"log_error_channel_exists" 		 => "Channel <strong title='Channel ID: {channel_id}'>{channel_name}</strong> already exists.",

	// custom field relationships
	"log_ok_relationship_imported"	=> "Created field relationship between {field_name} and {partner_field_name}.",
	"log_notice_no_field_relationships_imported" => "No field relationships were created.",
	
	// channel entries
	"log_ok_entry_imported"          => "Created entry: <strong>{title}</strong> <code>{url_title}</code>.",
	"log_error_creating_entry"      => "Error creating entry: <strong>{title}</strong>.",
	"log_notice_no_entries_imported" => "No entries were created.",
	
	// global variables
	"log_ok_global_variable_imported"        => "Created snippet: <strong>{variable_name}</strong>",
	"log_warning_global_variable_exists"    => "Global variable <strong>{variable_name}</strong> already exists",

	// snippets
	"log_ok_snippet_imported"        => "Created snippet: <strong>{snippet_name}</strong>",
	"log_warning_snippet_exists"    => "Snippet <strong>{snippet_name}</strong> already exists",
);