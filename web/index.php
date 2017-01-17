<?php 

require_once("core/application.class.php");
require_once("core/functions.core.php");

ae_detect_ie(); 

?>
<!DOCTYPE html>
<HTML>
<HEAD>
<TITLE>Home File Server</TITLE>
<!-- styles -->
<?= list_modules_css() ?>

<!-- scripts -->
<?= list_modules_js() ?>

<META http-equiv="Content-Type" content="text/html; charset=utf-8" />
<LINK rel="icon" type="image/png" href="/images/server.png">
<meta name="viewport" content="initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<!-- js patterns -->
<?= list_templates() ?>

</HEAD>
<body>
	<?= list_html() ?>
	<div class="hfs-hlist-wrap">
		<div class="hfs-header hfs-fixed-width">
			<ul class="head-list">
				<li class="nav-home"><a href="#"><span class="nav-top-menu icon-common icon-home hoverable corner"><span class="nav_label">Navigation</span></span></a></li>
				<li class="nav-search">
					<span class="nav-search-hider nav-top-menu icon-common icon-search">
						<span class="nav_label">Search</span>
					</span>
					<input class="nav-search-input search" placeholder="Search..." name="query" value="" id="input-icon">
					<div class="nav-search-popup shadow"></div>
				</li>
				<li class="nav-login"><span class="nav-top-menu icon-common icon-login"><span class="nav_label login_wrap">guest</span></span></li>
			</ul>
		</div>
	</div>
	<div class="hfs-nav-wrap">
		<div class="hfs-nav-subwrap hfs-fixed-width">
			<div class="hfs-location table">
				<div class="row">
					<div class="location-left cell"><span class="button-left">&laquo;</span></div>
					<div class="location-body cell"><span class="loc-wrap">Loading...</span></div>
					<div class="location-right cell"><span class="button-right">&raquo;</span></div>
				</div>
			</div>
		</div>
	</div>
	<div class="hfs-filelist-wrap">
		<div class="hfs-filelist-subwrap hfs-small-width">
			<div class="hfs-information"></div>
			<div class="hfs-filelist"></div>
			<div class="hfs-smartloader"></div>
			<div class="hfs-filestats"></div>
		</div>
	</div>
	<div class="hfs-footer-wrap">
		<div class="hfs-footer"><b>homefs.biz</b> &copy; 2014 by Roman Gemini | <a target="_blank" href="/status">status</a></div>
	</div>
</body>
</HTML>