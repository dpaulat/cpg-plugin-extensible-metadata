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

class ExtensibleMetadata
{
    public function __construct()
    {

    }

    public function help_button($guid)
    {
        global $CONFIG;
        return '&nbsp;<a class="greybox" href="plugins/extensible_metadata/help.php?t=' . $CONFIG['theme'] . '&amp;l=' . $CONFIG['lang']
            . '&amp;g=' . $guid . '" title="Help"><img src="images/help.gif" width="13" height="11" border="0" alt="" /></a>';
    }
}

global $extensible_metadata;
$extensible_metadata = new ExtensibleMetadata();
