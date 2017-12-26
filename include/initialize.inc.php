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

class XmpProcessPictureParams
{
    public $index_dirty;
    public $overwrite;
    public $picture;
    public $xmp_fields;

    public function __construct()
    {
        $this->index_dirty = FALSE;
        $this->overwrite = FALSE;
        $this->picture = array();
        $this->xmp_fields = array();
    }
}

class XmpProcessPictureOutput
{
    public $images_processed;
    public $new_fields;
    public $search_insert_values;
    public $xmp_files_created;
    public $xmp_files_skipped;

    public function __construct()
    {
        $this->new_fields = array();
        $this->search_insert_values = array();
        $this->images_processed = 0;
        $this->xmp_files_created = 0;
        $this->xmp_files_skipped = 0;
    }
}

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

    public function get_field($field_name, $xmp_fields) {
        foreach ($xmp_fields as $xmp_field) {
            if ($xmp_field['name'] == $field_name) {
                return $xmp_field;
            }
        }
        return NULL;
    }

    public function total_images()
    {
        global $CONFIG;

        $result = cpg_db_query("SELECT COUNT(*) FROM {$CONFIG['TABLE_PICTURES']}");
        $row = $result->fetchRow(true);
        if ($row === false) {
            $total_images = 0;
        } else {
            $total_images = $row[0];
        }
        return $total_images;
    }

    public function xmp_elements($filepath, $filename, $overwrite, &$sidecar_generated)
    {
        $xmp = new XmpProcessor($filepath, $filename);

        if ($overwrite || !$xmp->sidecarExists()) {
            $xmp->generateSidecar();
            $sidecar_generated = true;
        } else {
            $xmp->readSidecar();
            $sidecar_generated = false;
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

    public function xmp_fields($where = '')
    {
        global $CONFIG;

        $table_xmp_fields = $CONFIG['TABLE_PREFIX'] . 'plugin_xmp_fields';
        $result = cpg_db_query(
            "SELECT *
             FROM {$table_xmp_fields}
             {$where}
             ORDER BY `name` ASC");
        $xmp_fields = array();
        while (($row = $result->fetchAssoc()) !== NULL) {
            $xmp_fields[$row['id']] = $row;
        }
        $result->free();

        return $xmp_fields;
    }

    public function xmp_status()
    {
        global $CONFIG;

        $table_xmp_status = $CONFIG['TABLE_PREFIX'] . 'plugin_xmp_status';
        $result = cpg_db_query(
            "SELECT *, UNIX_TIMESTAMP(`last_refresh`) AS last_refresh_time
             FROM {$table_xmp_status}");
        return $result->fetchAssoc(true);
    }

    public function process_picture($input, $output)
    {
        global $extensible_metadata;

        // Retrieve non-empty XMP elements from image
        $xmp_elements =
            $extensible_metadata->xmp_elements(
                $input->picture['filepath'], $input->picture['filename'], $input->overwrite, $sidecar_generated);

        $pid = cpg_db_real_escape_string($input->picture['pid']);

        foreach ($xmp_elements as $key => $values) {

            // Get field data for current element
            $xmp_field = $extensible_metadata->get_field($key, $input->xmp_fields);

            if ($xmp_field !== NULL) {
                // The field exists

                // If the field is indexed, we need to parse search terms
                if (($sidecar_generated || $input->index_dirty) && $xmp_field['indexed'] === '1') {
                    $search_terms = array();

                    // Parse each array in the list of values, adding each value to the index
                    foreach ($values as $value) {
                        $value = cpg_db_real_escape_string($value);
                        if (!empty($value) && !in_array($value, $search_terms)) {
                            $search_terms[] = $value;
                        }
                    }

                    // Prepare and execute insert query
                    $id = cpg_db_real_escape_string($xmp_field['id']);
                    foreach ($search_terms as $term) {
                        $output->search_insert_values[] = "('{$pid}','{$id}','{$term}')";
                    }
                }

            } else if (!in_array($key, $output->new_fields)) {
                // The field doesn't exist yet
                // Add it to the new fields array
                $output->new_fields[] = $key;
            }
        }

        $output->images_processed++;
        if ($sidecar_generated) {
            $output->xmp_files_created++;
        } else {
            $output->xmp_files_skipped++;
        }
    }

    function process_delete($pic)
    {
        // Delete XMP file
        $xmp = new XmpProcessor($pic['filepath'], $pic['filename']);
        if ($xmp->sidecarExists()) {
            $xmp->deleteSidecar();
        }

        // Delete from index
        cpg_db_query("DELETE FROM `cpg16x_plugin_xmp_index` WHERE `pid` = '{$pic['pid']}'");
    }

    function populate_index($search_insert_values)
    {
        global $CONFIG;

        if (count($search_insert_values) > 0) {
            $table_xmp_index = $CONFIG['TABLE_PREFIX'] . 'plugin_xmp_index';

            cpg_db_query(
                "INSERT IGNORE INTO {$table_xmp_index} (`pid`, `field`, `text`)
                 VALUES " . implode(',', $search_insert_values));
        }
    }

    function populate_new_fields($new_fields)
    {
        global $CONFIG;

        $num_fields = count($new_fields);
        if ($num_fields > 0) {
            $table_xmp_fields = $CONFIG['TABLE_PREFIX'] . 'plugin_xmp_fields';
            $sql = "INSERT IGNORE INTO {$table_xmp_fields} (`name`) VALUES";
            $where = "WHERE `name` IN (";

            for ($i = 0; $i < $num_fields; $i++) {
                $field = cpg_db_real_escape_string($new_fields[$i]);
                $sql .= "('{$field}')";
                $where .= "'{$field}'";
                if ($i < $num_fields - 1) {
                    $sql .= ",";
                    $where .= ",";
                }
            }

            $where .= ")";
            cpg_db_query($sql);

            $new_xmp_fields = $this->xmp_fields($where);
        } else {
            $new_xmp_fields = array();
        }

        return $new_xmp_fields;
    }
}

global $extensible_metadata;
$extensible_metadata = new ExtensibleMetadata();
