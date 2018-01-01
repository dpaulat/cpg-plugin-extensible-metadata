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

$thisplugin->add_action('plugin_install', 'xmp_plugin_install');
$thisplugin->add_action('plugin_uninstall', 'xmp_plugin_uninstall');

$thisplugin->add_action('page_start', 'xmp_page_start');
$thisplugin->add_action('add_file_data_success', 'xmp_file_upload');
$thisplugin->add_action('after_delete_file', 'xmp_file_delete');

$thisplugin->add_filter('page_meta', 'xmp_page_meta');
$thisplugin->add_filter('file_info', 'xmp_file_info');
$thisplugin->add_filter('custom_search_params_allowed', 'xmp_custom_search_params_allowed');
$thisplugin->add_filter('custom_search_param', 'xmp_custom_search_param');
$thisplugin->add_filter('custom_search_query_join', 'xmp_custom_search_query_join');
$thisplugin->add_filter('search_form', 'xmp_search_form');

$xmp_search_num_joins = 0;

function xmp_plugin_install()
{
    global $CONFIG;

    // Create field table
    $result = cpg_db_query(
        "CREATE TABLE IF NOT EXISTS `{$CONFIG['TABLE_PREFIX']}plugin_xmp_fields` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `display_name` varchar(255) DEFAULT NULL,
            `displayed` tinyint(1) NOT NULL DEFAULT '0',
            `indexed` tinyint(1) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            UNIQUE KEY `field` (`name`)
         ) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1");
    if ($result === FALSE) {
        return FALSE;
    }

    // Create index table
    $result = cpg_db_query(
        "CREATE TABLE IF NOT EXISTS `{$CONFIG['TABLE_PREFIX']}plugin_xmp_index` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `pid` int(11) NOT NULL,
            `field` int(11) NOT NULL,
            `text` varchar(255) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `pid_field_text` (`pid`,`field`,`text`) USING BTREE
         ) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=latin1");
    if ($result === FALSE) {
        return FALSE;
    }

    // Create status table
    $result = cpg_db_query(
        "CREATE TABLE IF NOT EXISTS `{$CONFIG['TABLE_PREFIX']}plugin_xmp_status` (
            `id` bit(1) NOT NULL DEFAULT b'0',
            `last_refresh` datetime DEFAULT NULL,
            `index_dirty` tinyint(1) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=latin1");
    if ($result === FALSE) {
        return FALSE;
    }

    // Populate default status
    $result = cpg_db_query(
        "INSERT IGNORE INTO `{$CONFIG['TABLE_PREFIX']}plugin_xmp_status`
            (`id`, `last_refresh`, `index_dirty`)
         VALUES (b'0', NULL, 0)");
    if ($result === FALSE) {
        return FALSE;
    }

    return TRUE;
}

function xmp_plugin_uninstall()
{
    global $CONFIG;

    cpg_db_query("DROP TABLE IF EXISTS `{$CONFIG['TABLE_PREFIX']}plugin_xmp_fields`");
    cpg_db_query("DROP TABLE IF EXISTS `{$CONFIG['TABLE_PREFIX']}plugin_xmp_index`");
    cpg_db_query("DROP TABLE IF EXISTS `{$CONFIG['TABLE_PREFIX']}plugin_xmp_status`");

    return TRUE;
}

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
    global $lang_plugin_extensible_metadata;

    $gc = Inspekt::makeGetCage();

    $script = pathinfo($CPG_PHP_SELF, PATHINFO_BASENAME);
    $p = $gc->getInt('p');

    if ($script === 'pluginmgr.php' && $p == xmp_plugin_id()) {
        $JS['vars']['lang_xmp_refreshing'] = $lang_plugin_extensible_metadata['config_refreshing'];
        $JS['vars']['lang_xmp_saved'] = $lang_plugin_extensible_metadata['config_saved'];

        $JS['includes'][] = 'plugins/extensible_metadata/js/config.js';
    }
}

function xmp_file_upload($current_pic_data)
{
    global $extensible_metadata;

    $input = new XmpProcessPictureParams();
    $output = new XmpProcessPictureOutput();

    $input->picture = $current_pic_data;
    $input->xmp_fields = $extensible_metadata->xmp_fields();

    $extensible_metadata->process_picture($input, $output);
    $extensible_metadata->populate_new_fields($output->new_fields);
    $extensible_metadata->populate_index($output->search_insert_values);

    return $current_pic_data;
}

function xmp_file_delete($pic)
{
    global $extensible_metadata;

    $extensible_metadata->process_delete($pic);

    return $pic;
}

function xmp_page_meta($var)
{
    global $LINEBREAK;
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
                $xmp_link = htmlspecialchars($element);

                // Add a search link if the field is indexed
                if ($field['indexed'] === '1') {
                    $xmp_link = '<a href="thumbnails.php?album=search&amp;xmp=on&amp;search=' . urlencode($element) . '">' . $xmp_link . '</a>';
                }

                $xmp_links[] = $xmp_link;
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

function xmp_custom_search_params_allowed($allowed)
{
    global $cpg_udb;
    if ($cpg_udb->can_join_tables) {
        $allowed[] = 'xmp';
    }
    return $allowed;
}

function xmp_custom_search_param($value)
{
    global $xmp_search_num_joins;
    global $cpg_udb;

    if ($cpg_udb->can_join_tables) {
        $fields = $value[0];
        $param = $value[1];
        $search = $value[2];
        $type = $value[3];

        if ($param === 'xmp') {
            if ($type == 'AND') {
                $xmp_search_num_joins++;
            } else {
                $xmp_search_num_joins = 1;
            }
            $table = "t{$xmp_search_num_joins}";
            $fields[] = "{$table}.text {$search}";
        }
    }

    return array($fields);
}

function xmp_custom_search_query_join($join)
{
    global $CONFIG;
    global $xmp_search_num_joins;

    $table_xmp_index = "{$CONFIG['TABLE_PREFIX']}plugin_xmp_index";

    for ($i = 1; $i <= $xmp_search_num_joins; $i++) {
        $join .= " LEFT JOIN $table_xmp_index AS t{$i} ON t{$i}.pid = p.pid ";
    }

    return $join;
}
