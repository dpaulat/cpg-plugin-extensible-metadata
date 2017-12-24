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

require_once './include/inspekt.php';
require_once './plugins/extensible_metadata/include/initialize.inc.php';

$thisplugin->add_action('page_start', 'xmp_page_start');
$thisplugin->add_action('add_file_data_success', 'xmp_file_upload');
$thisplugin->add_action('after_delete_file', 'xmp_file_delete');

$thisplugin->add_filter('page_meta', 'xmp_page_meta');
$thisplugin->add_filter('file_info', 'xmp_file_info');
$thisplugin->add_filter('search_form', 'xmp_search_form');
$thisplugin->add_filter('thumb_caption_search', 'xmp_search_results');

function xmp_plugin_id()
{
    global $CPG_PLUGINS;
    foreach ($CPG_PLUGINS as $plugin) {
        if ($plugin->path === 'extensible_metadata') {
            return $plugin->plugin_id;
        }
    }
    return false;
}

function xmp_page_start()
{
    global $CPG_PHP_SELF;
    global $JS;

    $gc = Inspekt::makeGetCage();

    $script = pathinfo($CPG_PHP_SELF, PATHINFO_BASENAME);
    $p = $gc->getInt('p');

    if ($script === 'pluginmgr.php' && $p == xmp_plugin_id()) {
        $JS['includes'][] = '//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js';
        $JS['includes'][] = 'plugins/extensible_metadata/js/config.js';
    }
}

function xmp_file_upload($current_pic_data)
{
    return $current_pic_data;
}

function xmp_file_delete($pic) { }

function xmp_page_meta($var)
{
    global $LINEBREAK;
    $var = '<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/base/jquery-ui.css" type="text/css" />' . $LINEBREAK . $var;
    $var = $var . $LINEBREAK . '<link rel="stylesheet" href="plugins/extensible_metadata/css/config.css" type="text/css" />';
    return $var;
}

function xmp_file_info($info)
{
    global $CURRENT_PIC_DATA;
    global $extensible_metadata;
    global $lang_common;

    $xmp_info = array();

    $overwrite = FALSE;
    $xmp_elements = $extensible_metadata->xmp_elements(
        $CURRENT_PIC_DATA['filepath'], $CURRENT_PIC_DATA['filename'], $overwrite, $sidecar_generated);
    $xmp_fields = $extensible_metadata->xmp_fields("WHERE `displayed` = '1'");

    foreach ($xmp_elements as $key => $elements) {
        $field = $extensible_metadata->get_field($key, $xmp_fields);
        if ($field !== NULL) {
            if (!empty($field['display_name'])) {
                $field_name = $field['display_name'];
            } else {
                $field_name = $field['name'];
            }
            foreach ($elements as $element) {
                $xmp_links[] = '<a href="thumbnails.php?album=search&amp;xmp=on&amp;search=' . urlencode($element) . '">' . htmlspecialchars($element) . '</a>';
            }
            $xmp_info[$field_name] = implode('<br/>', $xmp_links);
        }
    }

    $needle = $lang_common['filesize'];
    $offset = array_search($needle, array_keys($info));
    $length = count($info);
    if ($offset === FALSE) {
        $offset = $length;
    }
    $info = array_merge(array_slice($info, 0, $offset, true),
        $xmp_info,
        array_slice($info, $offset, $length - $offset, true));

    return $info;
}

function xmp_search_form($text)
{
    global $lang_plugin_extensible_metadata;

    // Search for the keyword checkbox
    $offset = strpos($text, 'id="keywords"');
    if ($offset === FALSE) {
        return $text;
    }

    // Search for the end of the keyword row
    $offset = strpos($text, '</tr>', $offset);
    if ($offset === FALSE) {
        return $text;
    }

    // Define the metadata checkbox
    $start = $offset + 5;
    $length = 0;
    $row = <<<EOT

                                        <tr>
                                                <td><input type="checkbox" name="xmp" id="xmp" class="checkbox" checked="checked" /><label for="xmp" class="clickable_option">{$lang_plugin_extensible_metadata['search_metadata']}</label></td>
                                                <td>&nbsp;</td>
                                        </tr>
EOT;

    // Insert the metadata checkbox
    $text = substr_replace($text, $row, $start, $length);

    return $text;
}

function xmp_search_results($rowset)
{
    return $rowset;
}
