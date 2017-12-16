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
require_once './plugins/extensible_metadata/include/xmp_processor.class.php';

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

    while (($row = $result->fetchAssoc()) !== NULL) {
        $xmp = new XmpProcessor($row['filepath'], $row['filename']);

        if (!$xmp->sidecarExists()) {
            $xmp->generateSidecar();
        } else {
            $xmp->readSidecar();
        }

        $xmp->parseXML();
        $nodes = $xmp->getElementText();

    }

    $result->free();

    $data = array('status'  => 'success',
                  'xmp'     => $nodes);
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
