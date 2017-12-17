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

    $option_output['plugin_extensible_metadata_overwrite_enabled'] = '';
    $cancel_icon = cpg_fetch_icon('cancel');
    $reload_icon = cpg_fetch_icon('reload');
    echo <<<EOT
	<tr>
		<td class="tableb">
    		<input type="checkbox" name="plugin_extensible_metadata_overwrite" id="plugin_extensible_metadata_overwrite" class="checkbox" value="1" {$option_output['plugin_extensible_metadata_overwrite_enabled']} />
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

    echo '<br>' . $LINEBREAK;

    $plugin_help = $extensible_metadata->help_button('xmp_fields');
    starttable('100%', $lang_plugin_extensible_metadata['config_name'] . " - " . $lang_plugin_extensible_metadata['config_xmp_fields'] . $plugin_help, 4);

    echo <<<EOT
	<tr>
		<th class="tableh2">{$lang_plugin_extensible_metadata['config_field_name']}</th>
		<th class="tableh2">{$lang_plugin_extensible_metadata['config_display_name']}</th>
		<th class="tableh2">{$lang_plugin_extensible_metadata['config_displayed']}</th>
		<th class="tableh2">{$lang_plugin_extensible_metadata['config_indexed']}</th>
	</tr>
EOT;

    $xmp_fields = $extensible_metadata->xmp_fields();
    $alternate = false;
    foreach ($xmp_fields as $xmp_field) {
        $class = $alternate ? "tableb tableb_alternate" : "tableb";
        $alternate = !$alternate;
        $display_name = htmlspecialchars($xmp_field['display_name']);
        $displayed = ($xmp_field['displayed'] == 1) ? 'checked="checked"' : '';
        $indexed = ($xmp_field['indexed'] == 1) ? 'checked="checked"' : '';
        echo <<<EOT
		<tr>
			<td class="{$class}">
				{$xmp_field['name']}
			</td>
			<td class="{$class}">
				<div style="width: 100%; display: table;">
					<input type="text" name="" id="" value="{$display_name}" style="display: table-cell; width: 100%;" />
				</div>
			</td>
			<td class="{$class}" style="text-align: center;">
				<input type="checkbox" name="displayed" id="displayed" value="{$xmp_field['id']}" {$displayed} />
			</td>
			<td class="{$class}" style="text-align: center;">
				<input type="checkbox" name="indexed" id="indexed" value="{$xmp_field['id']}" {$indexed} />
			</td>
		</tr>
EOT;
    }
    $ok_icon = cpg_fetch_icon('ok');
    $class = $alternate ? "tableb tableb_alternate" : "tableb";
    echo <<<EOT
	<tr>
		<td class = "{$class}" colspan="4">
			<button type="button" id="xmp-save-fields">{$ok_icon}{$lang_plugin_extensible_metadata['config_apply']}</button>
		</td>
</td>
	</tr>
EOT;


    endtable();
}

xmp_config_form();
