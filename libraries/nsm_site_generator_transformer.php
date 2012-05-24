<?php

class NsmSiteGeneratorTransformer
{
    protected $tab = "  ";
    protected $title = false;
    protected $version = false;
    protected $description = false;
    protected $downloadUrl = false;
    protected $postImportInstructions = false;
    protected $requirements = array();
    protected $authors = array();

    protected $channels = array();
    protected $categoryGroups = array();
    protected $statusGroups = array();
    protected $fieldGroups = array();
    protected $templateGroups = array();

    public function __construct()
    {
    }

    /**
     * Parse XML Input
     */
    public function parseXmlConfig($xmlConfig)
    {
        $this->parseXmlThemeInfo($xmlConfig);
        $this->parseXmlCategoryGroups($xmlConfig);
        $this->parseXmlStatusGroups($xmlConfig);
        $this->parseXmlCategoryGroups($xmlConfig);
        $this->parseXmlFieldGroups($xmlConfig);
        $this->parseXmlChannels($xmlConfig);
        $this->parseXmlTemplateGroups($xmlConfig);
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
            $this->categoryGroups[$key]['categories'] = array();
            // recursively build this category groups category config in a single depth array
            // $this->categoryGroups[$key]['cats'] = $this->_buildCatsConfig($cg, $key);
        }
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
            $this->fieldGroups[$key]['channel_fields'] = array();
            foreach ($cfg->channel_field as $channel_field) {
                $fieldKey = (string)$channel_field['field_name'];
                $this->fieldGroups[$key]['channel_fields'][$fieldKey] = $this->attributes($channel_field);
            }
        }
    }

    protected function parseXmlChannels($xmlConfig)
    {
        // Loop over channels
        foreach ($xmlConfig->xpath('//channels/channel') as $channel) {
            $key = (string)$channel['channel_name'];
            $this->channels[$key] = $this->attributes($channel);
            $this->channels[$key]['entries'] = array();

            // Loop over channel entries
            foreach ($channel->entry as $count => $entry) {
                $new_entry = $this->attributes($entry);

                // Loop over channel entry fields
                foreach ($entry->channel_field as $field) {
                    $fieldKey = (string)$field['field_name'];
                    $new_entry['channel_fields'][$fieldKey] = $this->attributes($field);
                    $new_entry['channel_fields'][$fieldKey]['data'] = (string)$field;
                }

                $this->channels[$key]['entries'][] = $new_entry;
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

    /**
     * Parse Array Input
     */
    public function parseArrayConfig($array)
    {
        $this->parseArrayThemeInfo($array);
        $this->parseArrayCategoryGroups($array);
        $this->parseArrayStatusGroups($array);
        $this->parseArrayFieldGroups($array);
        $this->parseArrayChannels($array);
        $this->parseArrayTemplateGroups($array);
    }

    public function parseArrayThemeInfo($array)
    {
        $themeInfoKeys = array("title", "version", "description", "downloadUrl", "postImportInstructions");
        foreach ($themeInfoKeys as $key) {
            $this->$key = false;
            if(in_array($key, $array)) {
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
        // Loop over all the category groups in the config
        $this->categoryGroups = array();
        if(array_key_exists('category_groups', $array)) {
            foreach ($array['category_groups'] as $count => $cg) {
                $key = 'category_group_' . $cg['group_id'];
                
                $categoryGroupConfig = array_merge($cg, array(
                    'categories' => array(),
                    'group_ref_id' => 'cat_group_' . $cg['group_id']
                ));
                // recursively build this category groups category config in a single depth array
                // $this->categoryGroups[$key]['cats'] = $this->_buildCatsConfig($cg, $key);

                $this->categoryGroups[$key] = $categoryGroupConfig;
            }
        }

    }

    public function parseArrayStatusGroups($array)
    {
        // Loop over all the category groups in the config
        $this->statusGroups = array();
        if(array_key_exists('status_groups', $array)) {
            foreach ($array['status_groups'] as $count => $sg) {

                $key = 'status_group_' . $sg['group_id'];

                $statusGroupConfig = array_merge($sg, array(
                    'statuses' => array(),
                    'group_ref_id' => 'status_group_' . $sg['group_id']
                ));

                foreach ($sg["statuses"] as $status) {
                    $statusGroupConfig['statuses'][$status['status']] = $status;
                }
                
                $this->statusGroups[$statusGroupConfig['group_ref_id']] = $statusGroupConfig;
            }
        }
    }

    public function parseArrayFieldGroups($array)
    {
        // Loop over all the category groups in the config
        $this->fieldGroups = array();
        if(array_key_exists('field_groups', $array)) {
            foreach ($array['field_groups'] as $count => $fg) {

                $key = 'field_group_' . $fg['group_id'];

                $fieldGroupConfig = array_merge($fg, array(
                    'channel_fields' => array(),
                    'group_ref_id' => 'field_group_' . $fg['group_id']
                ));

                foreach ($fg["channel_fields"] as $field) {
                    $fieldGroupConfig['channel_fields'][$field['field_name']] = $field;
                }
                
                $this->fieldGroups[$fieldGroupConfig['group_ref_id']] = $fieldGroupConfig;
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
                    'total_entries' => 0,
                    'group_ref_id' => 'channel_' . $channel['channel_id']
                ));

                foreach ($channel['entries'] as $entry) {
                    $channelConfig['entries'][$entry['entry_id']] = $entry;
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

                foreach ($tg["templates"] as $template) {
                    $templateGroupConfig['templates'][$template['template_name']] = $template;
                }
                
                $this->templateGroups[$templateGroupConfig['group_ref_id']] = $templateGroupConfig;
            }
        }
    }

    /**
     * Output XML String
     */
    public function toXmlString()
    {
        $xmlConfig = '<?xml version="1.0" encoding="utf-8"?>';
        $xmlConfig .= "\n".'<!DOCTYPE xml>';
        $xmlConfig .= "\n<generator_template>";
        $xmlConfig .=    $this->themeInfoToXmlString() .
                        $this->categoryGroupsToXmlString() .
                        $this->statusGroupsToXmlString() .
                        $this->fieldGroupsToXmlString() .
                        $this->channelsToXmlString() .
                        $this->templateGroupsToXmlString();
        $xmlConfig .= "\n</generator_template>";
        return $xmlConfig;
    }

    protected function themeInfoToXmlString()
    {
        $themeInfoKeys = array("title", "version", "description", "postImportInstructions");
        $out = "";
        foreach ($themeInfoKeys as $key) {
            $out .= "\n<{$key}><![CDATA[ {$this->$key} ]]></{$key}>";
        }
        $out .= "\n<downloadUrl>{$this->downloadUrl}</downloadUrl>";
        $out .= "\n".$this->renderBundleAuthors();
        $out .= "\n".$this->renderBundleRequirements();
        return $out;
    }

    protected function renderBundleAuthors($tabDepth = 1) 
    {
        $tab = str_repeat($this->tab, $tabDepth);
        $out = "<authors>";
        foreach ($this->authors as $author) {
            $out = "\n{$tab}<author ";
            $out .= $this->renderAttributes($author);
            $out .= "/>";
        }
        $out .= "</authors>";
        return $out;
    }

    protected function renderBundleRequirements($tabDepth = 1) 
    {
        $tab = str_repeat($this->tab, $tabDepth);
        $out = "<requirements>";
        foreach ($this->requirements as $requirement) {
            $out = "\n{$tab}<requirement ";
            $out .= $this->renderAttributes($requirement);
            $out .= "/>";
        }
        $out .= "</requirements>";
        return $out;
    }

    protected function categoryGroupsToXmlString($tabDepth = 1)
    {
        $tab = str_repeat($this->tab, $tabDepth);
        $out = "\n<category_groups>";
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
        $out = "\n<status_groups>";
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
        $out = "\n<field_groups>";
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
        $out = "\n<channels>";
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
            $out .= " " . $this->renderAttributes($field, array('data'));
            $out .= ">";
            if(false == empty($field['data'])) {
                $out .= "<![CDATA[ " . $field['data'] . "]]>";
            }
            $out .= "</field>";
        }
        return $out;
    }

    protected function templateGroupsToXmlString($tabDepth = 1)
    {
        $tab = str_repeat($this->tab, $tabDepth);
        $out = "\n<template_groups>";
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
            $out .= "<![CDATA[ {$template['template_data']} ]]>";
            $out .= "</template>";
        }
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
        );
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

}
?>