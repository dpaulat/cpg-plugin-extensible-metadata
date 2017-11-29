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

function xmp_refresh()
{
    $gc = Inspekt::makeGetCage();

    switch ($gc->getAlpha('action')) {
        case 'start':
            xmp_refresh_start();
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

function xmp_refresh_start()
{
    // TODO: Implement
    $gc = Inspekt::makeGetCage();

    $overwrite = ($gc->getAlpha('overwrite') === 'true');

    $data = array(
        'status'       => 'error',
        'error_reason' => 'Not implemented');
    echo json_encode($data);
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
