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

$thisplugin->add_action('page_start', 'xmp_page_start');

$thisplugin->add_filter('page_meta', 'xmp_page_meta');

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

function xmp_page_meta($var)
{
    global $LINEBREAK;
    $var = '<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/base/jquery-ui.css" type="text/css" />' . $LINEBREAK . $var;
    $var = $var . $LINEBREAK . '<link rel="stylesheet" href="plugins/extensible_metadata/css/config.css" type="text/css" />';
    return $var;
}