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
    global $lang_date;
    global $lang_gallery_admin_menu;
    global $lang_plugin_extensible_metadata;

    $cancel_icon = cpg_fetch_icon('cancel');
    $delete_icon = cpg_fetch_icon('delete');
    $ok_icon = cpg_fetch_icon('ok');
    $reload_icon = cpg_fetch_icon('reload');

    $xmp_status = $extensible_metadata->xmp_status();

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
    if ($xmp_status['index_dirty'] === '0') {
        $xmp_index_dirty_hidden = 'hidden="hidden"';
    } else {
        $xmp_index_dirty_hidden = '';
    }
    if ($xmp_status['last_refresh'] === NULL) {
        $last_refresh = $lang_plugin_extensible_metadata['config_never'];
    } else {
        $last_refresh = localised_date($xmp_status['last_refresh_time'], $lang_date['log']);
    }
    echo <<<EOT
	<tr>
		<td class="tableb">
    		<input type="checkbox" name="plugin_extensible_metadata_overwrite" id="plugin_extensible_metadata_overwrite" class="checkbox" value="1" {$option_output['plugin_extensible_metadata_overwrite_enabled']} />
			{$lang_plugin_extensible_metadata['config_overwrite_sidecar']}
		</td>
	</tr>
	<tr>
		<td class="tableb tableb_alternate">
			<div id="xmp-index-dirty" style="color:red;" {$xmp_index_dirty_hidden}>{$lang_plugin_extensible_metadata['config_index_dirty']}<br/><br/></div>
			<div id="xmp-refresh-error" style="color:red;" hidden="hidden">{$lang_plugin_extensible_metadata['config_refresh_error']}<br/><br/></div>
			{$lang_plugin_extensible_metadata['config_last_refresh']} <span id="xmp-last-refresh">{$last_refresh}</span>
			<div id="xmp-refresh-status" hidden="hidden">
				<br/>
				{$lang_plugin_extensible_metadata['config_images_processed']} <span id="xmp-images-processed">0</span><br/>
				{$lang_plugin_extensible_metadata['config_xmp_files_created']} <span id="xmp-sidecar-files-created">0</span><br/>
				{$lang_plugin_extensible_metadata['config_xmp_files_skipped']} <span id="xmp-sidecar-files-skipped">0</span>
			</div>
			<div id="xmp-refresh-spacer" hidden="hidden">&nbsp;</div>
			<div id="xmp-progress-bar" hidden="hidden"><div class="progress-label">Refreshing...</div></div>
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

    echo <<<EOT
	<div class="notification-bar" id="xmp-fields-notification-bar">
		<img src="images/message/ok.png" width="32" height="32" style="vertical-align: middle;" /> Saved
	</div>
EOT;


    $plugin_help = $extensible_metadata->help_button('xmp_fields');
    starttable('100%', $lang_plugin_extensible_metadata['config_name'] . " - " . $lang_plugin_extensible_metadata['config_xmp_fields'] . $plugin_help, 5);

    echo <<<EOT
	<tr>
		<th class="tableh2">{$lang_plugin_extensible_metadata['config_field_name']}</th>
		<th class="tableh2">{$lang_plugin_extensible_metadata['config_display_name']}</th>
		<th class="tableh2">{$lang_plugin_extensible_metadata['config_displayed']}</th>
		<th class="tableh2">{$lang_plugin_extensible_metadata['config_indexed']}</th>
		<th class="tableh2">{$lang_plugin_extensible_metadata['config_delete']}</th>
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
		<tr id="xmp-field-row[{$xmp_field['id']}]">
			<td class="{$class}">
				{$xmp_field['name']}
			</td>
			<td class="{$class}">
				<div style="width: 100%; display: table;">
					<input type="text" class="xmp-field-display-name" name="xmp-field-display-name[{$xmp_field['id']}]" value="{$display_name}" style="display: table-cell; width: 100%;" />
				</div>
			</td>
			<td class="{$class}" style="text-align: center;">
				<input type="checkbox" class="xmp-field-displayed" name="xmp-field-displayed[{$xmp_field['id']}]" value="{$xmp_field['id']}" {$displayed} />
			</td>
			<td class="{$class}" style="text-align: center;">
				<input type="checkbox" class="xmp-field-indexed" name="xmp-field-indexed[{$xmp_field['id']}]" value="{$xmp_field['id']}" {$indexed} />
			</td>
			<td class="{$class}" style="text-align: center;">
				<a class="xmp-field-delete" href="javascript:void(0);">
					<input type="hidden" value="{$xmp_field['id']}" />
					{$delete_icon}
				</a>
			</td>
		</tr>
EOT;
    }
    $class = $alternate ? "tableb tableb_alternate" : "tableb";
    echo <<<EOT
	<tr id="xmp-fields-apply-row">
		<td id="xmp-fields-apply-col" class="{$class}" colspan="5">
			<button type="button" id="xmp-fields-apply">{$ok_icon}{$lang_plugin_extensible_metadata['config_apply']}</button>
		</td>
</td>
	</tr>
EOT;


    endtable();
}

xmp_config_form();
