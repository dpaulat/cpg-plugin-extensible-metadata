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
        case 'save':
            xmp_fields_save();
            break;

        default:
            xmp_fields_default();
            break;
    }
}

function xmp_fields_save()
{
    $pc = Inspekt::makePostCage();

    $display_name = array();
    parse_str($pc->getRaw('display_name'), $display_name);

    $displayed = array();
    parse_str($pc->getRaw('displayed'), $displayed);

    $indexed = array();
    parse_str($pc->getRaw('indexed'), $indexed);

    $data = array(
        'status'       => 'success',
        'id'           => $pc->getRaw('id'),
        'display_name' => $display_name,
        'displayed'    => $displayed,
        'indexed'      => $indexed);

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
