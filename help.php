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

$styles = '../../themes/'.$_GET['t'].'/style.css';
$hpath = 'help/'.$_GET['g'].'/';
$lang = $_GET['l'];
$hfile = file_exists($hpath.$lang.'.html') ? $lang.'.html' : 'english.html';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title></title>
<link rel="stylesheet" type="text/css" href="<?=$styles?>">
</head>
<body class="nobgimage">
<?php readfile($hpath.$hfile); ?>
</body>
</html>