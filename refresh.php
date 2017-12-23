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

    $xmp_fields = $extensible_metadata->xmp_fields();
    $new_fields = array();

    $gc = Inspekt::makeGetCage();
    $overwrite = ($gc->getAlpha('overwrite') === 'true');
    $page = $gc->getInt('page');
    if ($page === false) {
        $page = 0;
    }
    $limit = 10;
    $start = $page * $limit;
    $end = $start + $limit;
    $total_images = $extensible_metadata->total_images();
    $images_processed = 0;
    $xmp_files_created = 0;
    $xmp_files_skipped = 0;

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

    // Process image for metadata
    while (($picture = $pictures->fetchAssoc()) !== NULL) {

        // Retrieve non-empty XMP elements from image
        $xmp_elements =
            $extensible_metadata->xmp_elements(
                $picture['filepath'], $picture['filename'], $overwrite, $sidecar_generated);

        foreach ($xmp_elements as $key => $values) {

            // Get field data for current element
            $xmp_field = $extensible_metadata->get_field($key, $xmp_fields);

            if ($xmp_field !== NULL) {
                // The field exists

                // If the field is indexed, we need to parse search terms
                if ($xmp_field['indexed'] === '1') {
                    $search_terms = array();
                    $insert_values = array();

                    // Parse each array in the list of values, treating each unique word as an individual search term
                    foreach ($values as $value) {
                        $value = cpg_db_real_escape_string($value);
                        $term_list = explode(' ', $value);
                        $term_list = array_filter($term_list, 'strlen');
                        $search_terms = array_merge($search_terms, $term_list);
                        $search_terms = array_unique($search_terms);
                    }

                    // Prepare and execute insert query
                    $table_xmp_index = $CONFIG['TABLE_PREFIX'] . 'plugin_xmp_index';
                    $id = cpg_db_real_escape_string($xmp_field['id']);
                    $pid = cpg_db_real_escape_string($picture['pid']);
                    foreach ($search_terms as $term) {
                        $insert_values[] = "('{$pid}','{$id}','{$term}')";
                    }
                    $term_query = "INSERT IGNORE INTO {$table_xmp_index} (`pid`, `field`, `text`) VALUES " . implode(',', $insert_values);
                    cpg_db_query($term_query);
                }

            } else if (!in_array($key, $new_fields)) {
                // The field doesn't exist yet
                // Add it to the new fields array
                $new_fields[] = $key;
            }
        }

        $images_processed++;
        if ($sidecar_generated) {
            $xmp_files_created++;
        } else {
            $xmp_files_skipped++;
        }
    }
    $pictures->free();

    // Insert new fields into database
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

        $new_xmp_fields = $extensible_metadata->xmp_fields($where);
    } else {
        $new_xmp_fields = array();
    }

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
        'images_processed'  => $images_processed,
        'total_images'      => $total_images,
        'xmp_files_created' => $xmp_files_created,
        'xmp_files_skipped' => $xmp_files_skipped,
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
