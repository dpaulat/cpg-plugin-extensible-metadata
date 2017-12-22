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

if (!GALLERY_ADMIN_MODE) {
    cpg_die(ERROR, $lang_errors['access_denied'], __FILE__, __LINE__);
}

require_once './include/inspekt.php';
require_once './plugins/extensible_metadata/include/initialize.inc.php';

function xmp_fields()
{
    $gc = Inspekt::makeGetCage();

    switch ($gc->getAlpha('action')) {
        case 'delete':
            xmp_fields_delete();
            break;

        case 'save':
            xmp_fields_save();
            break;

        default:
            xmp_fields_default();
            break;
    }
}

function xmp_fields_delete()
{
    global $CONFIG;
    global $extensible_metadata;

    $pc = Inspekt::makePostCage();
    $id = $pc->getInt('id');

    $xmp_fields = $extensible_metadata->xmp_fields();
    if (array_key_exists($id, $xmp_fields) && $xmp_fields[$id]['indexed'] === '1') {
        xmp_fields_delete_from_index($id);
    }

    if ($id !== false) {
        $table_xmp_fields = $CONFIG['TABLE_PREFIX'] . 'plugin_xmp_fields';
        $id = cpg_db_real_escape_string($id);
        $result = cpg_db_query("DELETE FROM {$table_xmp_fields} WHERE `id`='{$id}'");

        if ($result !== FALSE) {
            $data = array('status' => 'success');
        } else {
            $data = array(
                'status'       => 'error',
                'error_reason' => 'Database error');
        }
    } else {
        $data = array(
            'status'       => 'error',
            'error_reason' => 'ID not specified');
    }

    echo json_encode($data);
}

function xmp_fields_save()
{
    global $CONFIG;
    global $extensible_metadata;

    $pc = Inspekt::makePostCage();

    $xmp_fields = $extensible_metadata->xmp_fields();
    $index_additions = array();
    $index_removals = array();

    $display_name = array();
    parse_str($pc->getRaw('display_name'), $display_name);
    $display_name_field_name = 'xmp-field-display-name';
    if (array_key_exists($display_name_field_name, $display_name)) {
        $display_name = $display_name[$display_name_field_name];
    } else {
        $display_name = array();
    }

    $displayed = array();
    parse_str($pc->getRaw('displayed'), $displayed);
    $displayed_field_name = 'xmp-field-displayed';
    if (array_key_exists($displayed_field_name, $displayed)) {
        $displayed = $displayed[$displayed_field_name];
    } else {
        $displayed = array();
    }

    $indexed = array();
    parse_str($pc->getRaw('indexed'), $indexed);
    $indexed_field_name = 'xmp-field-indexed';
    if (array_key_exists($indexed_field_name, $indexed)) {
        $indexed = $indexed[$indexed_field_name];
    } else {
        $indexed = array();
    }

    $table_xmp_fields = $CONFIG['TABLE_PREFIX'] . 'plugin_xmp_fields';
    $table_xmp_status = $CONFIG['TABLE_PREFIX'] . 'plugin_xmp_status';

    $columns = array(
        'display_name' => '`display_name` = CASE ',
        'displayed'    => '`displayed` = CASE ',
        'indexed'      => '`indexed` = CASE ');

    foreach (array_keys($display_name) as $key) {
        $id = cpg_db_real_escape_string($key);
        $ids[] = $id;
        $display_name[$key] = cpg_db_real_escape_string($display_name[$key]);

        if (array_key_exists($key, $displayed)) {
            $displayed_value = '1';
        } else {
            $displayed_value = '0';
        }

        if (array_key_exists($key, $indexed)) {
            $indexed_value = '1';
        } else {
            $indexed_value = '0';
        }

        if (array_key_exists($key, $xmp_fields)) {
            if ($xmp_fields[$key]['indexed'] != $indexed_value) {
                if ($indexed_value === '0') {
                    $index_removals[] = $key;
                } else {
                    $index_additions[] = $key;
                }
            }
        }

        $columns['display_name'] .= "WHEN `id`='{$id}' THEN '{$display_name[$key]}' ";
        $columns['displayed'] .= "WHEN `id`='{$id}' THEN '{$displayed_value}' ";
        $columns['indexed'] .= "WHEN `id`='{$id}' THEN '{$indexed_value}' ";
    }

    foreach ($columns as $column_name => $query_part) {
        $columns[$column_name] .= "ELSE `$column_name` END";
    }

    $where = " WHERE `id` IN ('" . implode("','", $ids) . "')";

    $query = "UPDATE {$table_xmp_fields} SET " . implode(', ', $columns) . $where;
    $result = cpg_db_query($query);

    if ($result !== FALSE) {
        xmp_fields_delete_from_index($index_removals);

        $index_dirty = !empty($index_additions);
        if ($index_dirty == 1) {
            cpg_db_query("UPDATE ${table_xmp_status} SET `index_dirty` = 1 WHERE `id` = b'0'");
        }

        $data = array(
            'status'      => 'success',
            'index_dirty' => $index_dirty);
    } else {
        $data = array(
            'status'       => 'error',
            'error_reason' => 'Database error');
    }

    echo json_encode($data);
}

function xmp_fields_delete_from_index($ids)
{
    global $CONFIG;

    if (!is_array($ids)) {
        $ids[] = $ids;
    }

    if (count($ids) > 0) {
        $table_xmp_index = $CONFIG['TABLE_PREFIX'] . 'plugin_xmp_index';

        $fields = '';
        $num_ids = count($ids);
        for ($i = 0; $i < $num_ids; $i++) {
            $fields .= "'" . cpg_db_real_escape_string($ids[$i]) . "'";
            if ($i < $num_ids - 1) {
                $fields .= ",";
            }
        }

        cpg_db_query("DELETE FROM ${table_xmp_index} WHERE `field` IN ({$fields})");
    }
}

function xmp_fields_default()
{
    $data = array(
        'status'       => 'error',
        'error_reason' => 'Unknown action');

    echo json_encode($data);
}

xmp_fields();
