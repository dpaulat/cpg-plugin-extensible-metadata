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
    $data = array(
        'status'       => 'error',
        'error_reason' => 'Not implemented');
    echo json_encode($data);
}

function xmp_refresh_status()
{
    // TODO: Implement
    $data = array(
        'status'            => 'success',
        'refresh_status'    => 'in_progress',
        'last_refresh'      => -1,
        'images_processed'  => 3,
        'total_images'      => 10,
        'xmp_files_created' => 2,
        'xmp_files_skipped' => 1,
        'error_reason'      => 'Not implemented');
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
