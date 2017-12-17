<?php
/**************************************************
  Coppermine Plugin - Extensible Metadata
 *************************************************
  Copyright (c) 2017 Dan Paulat
 *************************************************
  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License version 3
  as published by the Free Software Foundation.
 ***************************************************/

if (!defined('IN_COPPERMINE')) die('Not in Coppermine...');

require_once './plugins/extensible_metadata/include/xmp_processor.class.php';

class ExtensibleMetadata
{
    public function __construct()
    {

    }

    public function help_button($guid)
    {
        global $CONFIG;
        return '&nbsp;<a class="greybox" href="plugins/extensible_metadata/help.php?t=' . $CONFIG['theme'] . '&amp;l=' . $CONFIG['lang']
            . '&amp;g=' . $guid . '" title="Help"><img src="images/help.gif" width="13" height="11" border="0" alt="" /></a>';
    }

    public function has_field($field_name, $xmp_fields) {
        foreach ($xmp_fields as $xmp_field) {
            if ($xmp_field['name'] == $field_name) {
                return TRUE;
            }
        }
        return FALSE;
    }

    public function xmp_elements($filepath, $filename, $overwrite)
    {
        $xmp = new XmpProcessor($filepath, $filename);

        if ($overwrite || !$xmp->sidecarExists()) {
            $xmp->generateSidecar();
        } else {
            $xmp->readSidecar();
        }

        $xmp->parseXML();
        return $xmp->getElementText();
    }

    public function xmp_displayed_field_names()
    {
        global $CONFIG;

        $table_xmp_fields = $CONFIG['TABLE_PREFIX'] . 'plugin_xmp_fields';
        $result = cpg_db_query(
            "SELECT `name`
             FROM {$table_xmp_fields}
             WHERE `displayed` = 1
             ORDER BY `name` ASC");
        $xmp_fields = array();
        while (($row = $result->fetchAssoc()) !== NULL) {
            $xmp_fields[] = $row['name'];
        }
        $result->free();

        return $xmp_fields;
    }

    public function xmp_fields()
    {
        global $CONFIG;

        $table_xmp_fields = $CONFIG['TABLE_PREFIX'] . 'plugin_xmp_fields';
        $result = cpg_db_query(
            "SELECT *
             FROM {$table_xmp_fields}
             ORDER BY `name` ASC");
        $xmp_fields = array();
        while (($row = $result->fetchAssoc()) !== NULL) {
            $xmp_fields[] = $row;
        }
        $result->free();

        return $xmp_fields;
    }
}

global $extensible_metadata;
$extensible_metadata = new ExtensibleMetadata();
