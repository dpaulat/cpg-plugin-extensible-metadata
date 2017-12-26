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
        case 'process':
            xmp_refresh_process();
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
    global $lang_plugin_extensible_metadata;
    global $lang_date;

    $input = new XmpProcessPictureParams();
    $output = new XmpProcessPictureOutput();

    $input->xmp_fields = $extensible_metadata->xmp_fields();

    $gc = Inspekt::makeGetCage();
    $input->overwrite = ($gc->getAlpha('overwrite') === 'true');
    $page = $gc->getInt('page');
    if ($page === false) {
        $page = 0;
    }
    $limit = 10;
    $start = $page * $limit;
    $end = $start + $limit;
    $total_images = $extensible_metadata->total_images();

    // If the requested start image is beyond the number of total images, the process is complete
    if ($start >= $total_images) {
        $data = array(
            'status'      => 'success',
            'more_images' => 'false');
        echo json_encode($data);
        return;
    }

    // Get pictures from database
    $pictures = cpg_db_query(
        "SELECT `pid`, `filepath`, `filename`
         FROM {$CONFIG['TABLE_PICTURES']}
         ORDER BY `pid` ASC
         LIMIT {$start}, {$limit}");

    // If there are no pictures returned, there is nothing to process
    if (!$pictures) {
        $data = array(
            'status'       => 'error',
            'error_reason' => 'Database error');
        echo json_encode($data);
        return;
    }

    // Get status
    $xmp_status = $extensible_metadata->xmp_status();
    $input->index_dirty = ($xmp_status['index_dirty'] === '1');

    // Process image for metadata
    while (($picture = $pictures->fetchAssoc()) !== NULL) {
        $input->picture = $picture;
        $extensible_metadata->process_picture($input, $output);
    }
    $pictures->free();

    // Populate index with metadata
    $extensible_metadata->populate_index($output->search_insert_values);

    // Insert new fields into database
    $new_xmp_fields = $extensible_metadata->populate_new_fields($output->new_fields);

    // Determine if there are more images to process
    // If not, complete the refresh process
    $more_images = ($end < $total_images);
    if ($more_images === FALSE) {
        xmp_refresh_end();
    }

    // Retrieve updated last refresh information
    $xmp_status = $extensible_metadata->xmp_status();
    if ($xmp_status['last_refresh'] === NULL) {
        $last_refresh = $lang_plugin_extensible_metadata['config_never'];
    } else {
        $last_refresh = localised_date($xmp_status['last_refresh_time'], $lang_date['log']);
    }

    // Return status in a JSON array
    $data = array(
        'status'            => 'success',
        'new_fields'        => $new_xmp_fields,
        'images_processed'  => $output->images_processed,
        'total_images'      => $total_images,
        'xmp_files_created' => $output->xmp_files_created,
        'xmp_files_skipped' => $output->xmp_files_skipped,
        'last_refresh'      => $last_refresh,
        'index_dirty'       => $xmp_status['index_dirty'],
        'more_images'       => $more_images);

    echo json_encode($data);
}

function xmp_refresh_end()
{
    global $CONFIG;

    $table_xmp_status = $CONFIG['TABLE_PREFIX'] . 'plugin_xmp_status';

    cpg_db_query(
        "UPDATE {$table_xmp_status}
         SET `last_refresh` = NOW(),
             `index_dirty` = '0'
         WHERE `id` = b'0'");
}

function xmp_refresh_default()
{
    $data = array(
        'status'       => 'error',
        'error_reason' => 'Unknown action');

    echo json_encode($data);
}

xmp_refresh();
