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

function xmp_refresh()
{
    $gc = Inspekt::makeGetCage();

    switch ($gc->getAlpha('action')) {
        case 'start':
            xmp_refresh_start();
            break;

        case 'process':
            xmp_refresh_process();
            break;

        case 'status':
            xmp_refresh_status();
            break;

        case 'cancel':
            xmp_refresh_cancel();
            break;

        default:
            xmp_refresh_default();
            break;
    }
}

function xmp_refresh_process()
{
    global $CONFIG;
    global $extensible_metadata;

    $xmp_fields = $extensible_metadata->xmp_fields();
    $new_fields = array();

    $gc = Inspekt::makeGetCage();
    $overwrite = ($gc->getAlpha('overwrite') === 'true');

    // Get pictures from database
    $result = cpg_db_query(
        "SELECT `filepath`, `filename`
         FROM {$CONFIG['TABLE_PICTURES']}
         ORDER BY `pid` ASC
         LIMIT 0, 100"); // TODO: Range

    if (!$result) {
        $data = array(
            'status'       => 'error',
            'error_reason' => 'Database error');
        echo json_encode($data);
        return;
    }

    // Find new fields in XMP metadata
    while (($row = $result->fetchAssoc()) !== NULL) {
        $xmp_elements = $extensible_metadata->xmp_elements($row['filepath'], $row['filename'], $overwrite);
        foreach ($xmp_elements as $key => $value) {
            if (!$extensible_metadata->has_field($key, $xmp_fields) && !in_array($key, $new_fields)) {
                $new_fields[] = $key;
            }
        }
    }
    $result->free();

    // Insert new fields into database
    $num_fields = count($new_fields);
    if ($num_fields > 0) {
        $table_xmp_fields = $CONFIG['TABLE_PREFIX'] . 'plugin_xmp_fields';
        $sql = "INSERT IGNORE INTO {$table_xmp_fields} (`name`) VALUES";
        for ($i = 0; $i < $num_fields; $i++) {
            $field = cpg_db_real_escape_string($new_fields[$i]);
            $sql .= "('{$field}')";
            if ($i < $num_fields - 1) {
                $sql .= ",";
            }
        }
        cpg_db_query($sql);
    }

    $data = array('status' => 'success');
    echo json_encode($data);
}

function xmp_refresh_start()
{
    global $CONFIG;

    $gc = Inspekt::makeGetCage();
    $overwrite = ($gc->getAlpha('overwrite') === 'true');
    $table_xmp_status = $CONFIG['TABLE_PREFIX'] . 'plugin_xmp_status';
    $lock = 'plugins/extensible_metadata/.refresh_lock';

    $fp = fopen($lock, 'c');
    if (!flock($fp, LOCK_EX | LOCK_NB)) {
        fclose($fp);
        $data = array(
            'status'       => 'error',
            'error_reason' => 'Refresh already in progress');
        echo json_encode($data);
        return;
    }

    // TODO: Run in background, and return launch success

    $result = cpg_db_query(
        "UPDATE {$table_xmp_status}
         SET `refreshing` = '1',
             `last_refresh` = NOW()
         WHERE `id` = b'0'");

    if (!$result) {
        fclose($fp);
        $data = array(
            'status'       => 'error',
            'error_reason' => 'Database error');
        echo json_encode($data);
        return;
    }

    // TODO: Implement
    sleep(9);

    $result = cpg_db_query("SELECT COUNT(*) FROM {$CONFIG['TABLE_PICTURES']}");
    $row = $result->fetchRow(true);
    $total_images = $row[0];

    $result = cpg_db_query(
        "UPDATE {$table_xmp_status}
         SET `refreshing` = '0'
         WHERE `id` = b'0'");

    if (!$result) {
        fclose($fp);
        $data = array(
            'status'       => 'error',
            'error_reason' => 'Database error');
        echo json_encode($data);
        return;
    }

    $data = array('status' => 'success');
    echo json_encode($data);

    fclose($fp);
}

function xmp_refresh_status()
{
    global $CONFIG;

    $result = cpg_db_query("SELECT * FROM {$CONFIG['TABLE_PREFIX']}plugin_xmp_status WHERE id=0");
    $row = $result->fetchAssoc();

    if ($row !== NULL) {
        if ($row['refreshing'] === '1') {
            $refresh_status = 'in_progress';
        } else {
            $refresh_status = 'complete';
        }

        $data = array(
            'status'            => 'success',
            'refresh_status'    => $refresh_status,
            'last_refresh'      => $row['last_refresh'],
            'images_processed'  => $row['images_processed'],
            'total_images'      => $row['total_images'],
            'xmp_files_created' => $row['xmp_files_created'],
            'xmp_files_skipped' => $row['xmp_files_skipped']);
    } else {
        $data = array(
            'status'       => 'error',
            'error_reason' => 'Could not read from database');
    }
    echo json_encode($data);
}

function xmp_refresh_cancel()
{
    // TODO: Implement
    $data = array(
        'status'       => 'success',
        'error_reason' => 'Not implemented');
    echo json_encode($data);
}

function xmp_refresh_default()
{
    $data = array(
        'status'       => 'error',
        'error_reason' => 'Unknown action');

    echo json_encode($data);
}

xmp_refresh();
