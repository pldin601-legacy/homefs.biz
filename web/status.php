<?php

require_once($_SERVER['DOCUMENT_ROOT'] . "/core/application.class.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/core/functions.core.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/core/common.core.php");

$config = homefs::app('conf')->config();
$fs = homefs::app('filesystem');

ob_start("my_ghandler");
site_init();

$total_dirs = $fs->total_dirs();
$total_files = $fs->total_files();
$total_size = $fs->total_size();
$last_updated = $fs->fs_modified();
$duplicates = $fs->total_duplicates();

?>
<html>
	<head>
		<title>HomeFS Statistics</title>
		<LINK href="/status.css" rel="stylesheet" type="text/css">
		<meta http-equiv="refresh" content="10">
		<meta name="viewport" content="initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
	</head>
	<body>
		<table class="tblStatus">
			<caption>HomeFS Database Status</caption>
			<tr>
				<td>Total files</td><td><?= number_format($total_files) ?></td>
			</tr>
			<tr>
				<td>Total directories</td><td><?= number_format($total_dirs) ?></td>
			</tr>
			<tr>
				<td>Files having duplicates</td><td><?= number_format($duplicates) ?></td>
			</tr>
			<tr>
				<td>Total files size</td><td><?= number_format($total_size) ?> bytes</td>
			</tr>
			<tr>
				<td>DB last modified</td><td><?= $last_updated ?></td>
			</tr>
		</table>
	</body>
</html>