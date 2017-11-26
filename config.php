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

require_once './plugins/extensible_metadata/include/initialize.inc.php';

function xmp_config_form()
{
    global $LINEBREAK;
    global $extensible_metadata;
    global $lang_gallery_admin_menu;
    global $lang_plugin_extensible_metadata;

    $plugin_help = $extensible_metadata->help_button('config');
    starttable('100%', $lang_plugin_extensible_metadata['config_name'] . " - " . $lang_gallery_admin_menu['admin_lnk'] . $plugin_help, 2);

    echo <<<EOT
	<tr>
		<td class="tableb" width="50%">
			Column 1
		</td>
		<td class="tableb">
			Column 2
		</td>
	</tr>
EOT;

    endtable();

    echo '<br>' . $LINEBREAK;

    $plugin_help = $extensible_metadata->help_button('refresh');
    starttable('100%', $lang_plugin_extensible_metadata['config_name'] . " - " . $lang_plugin_extensible_metadata['config_refresh_metadata'] . $plugin_help, 1);

    $option_output['plugin_extensible_metadata_overwrite_sidecar_enabled'] = '';
    $cancel_icon = cpg_fetch_icon('cancel');
    $reload_icon = cpg_fetch_icon('reload');
    echo <<<EOT
	<tr>
		<td class="tableb">
    		<input type="checkbox" name="plugin_extensible_metadata_overwrite_sidecar" id="plugin_extensible_metadata_overwrite_sidecar" class="checkbox" value="1" {$option_output['plugin_extensible_metadata_overwrite_sidecar_enabled']} />
			{$lang_plugin_extensible_metadata['config_overwrite_sidecar']}
		</td>
	</tr>
	<tr>
		<td class="tableb tableb_alternate">
		    Last refresh: <span id="xmp-last-refresh">Never</span><br><br>
		    Images processed: <span id="xmp-images-processed">0/0</span><br>
		    Sidecar files created: <span id="xmp-sidecar-files-created">0</span><br>
		    Sidecar files skipped: <span id="xmp-sidecar-files-skipped">0</span><br>
		    <br><div id="xmp-progress-bar" hidden="hidden"><div class="progress-label">Refreshing...</div></div>
        </td>
	</tr>
	<tr>
        <td class="tableb">
            <button type="button" id="xmp-refresh-metadata" onclick="">{$reload_icon}{$lang_plugin_extensible_metadata['config_refresh_metadata']}</button>
            <button type="button" id="xmp-cancel-refresh" onclick="" disabled="disabled">{$cancel_icon}{$lang_plugin_extensible_metadata['config_cancel_refresh']}</button>
        </td>
	</tr>
EOT;

    endtable();
}

xmp_config_form();
