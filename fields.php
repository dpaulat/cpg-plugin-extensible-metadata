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

    $pc = Inspekt::makePostCage();
    $id = $pc->getInt('id');

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
    // TODO: Detect change in indexed field
    global $CONFIG;

    $pc = Inspekt::makePostCage();

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

    $columns = array(
        'display_name' => '`display_name` = CASE ',
        'displayed'    => '`displayed` = CASE ',
        'indexed'      => '`indexed` = CASE ');

    $keys = array_keys($display_name);
    foreach ($keys as $key) {
        $id = cpg_db_real_escape_string($key);
        $ids[] = $id;
        $display_name[$key] = cpg_db_real_escape_string($display_name[$key]);

        if (key_exists($key, $displayed)) {
            $displayed[$key] = '1';
        } else {
            $displayed[$key] = '0';
        }

        if (key_exists($key, $indexed)) {
            $indexed[$key] = '1';
        } else {
            $indexed[$key] = '0';
        }

        $columns['display_name'] .= "WHEN `id`='{$id}' THEN '{$display_name[$key]}' ";
        $columns['displayed'] .= "WHEN `id`='{$id}' THEN '{$displayed[$key]}' ";
        $columns['indexed'] .= "WHEN `id`='{$id}' THEN '{$indexed[$key]}' ";
    }

    foreach ($columns as $column_name => $query_part) {
        $columns[$column_name] .= "ELSE `$column_name` END";
    }

    $where = " WHERE `id` IN ('" . implode("','", $ids) . "')";

    $query = "UPDATE {$table_xmp_fields} SET " . implode(', ', $columns) . $where;
    $result = cpg_db_query($query);

    if ($result !== false) {
        $data = array('status' => 'success');
    } else {
        $data = array(
            'status'       => 'error',
            'error_reason' => 'Database error');
    }

    echo json_encode($data);
}

function xmp_fields_default()
{
    $data = array(
        'status'       => 'error',
        'error_reason' => 'Unknown action');

    echo json_encode($data);
}

xmp_fields();
