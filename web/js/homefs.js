var site_suffix = "Home File Server";

var homefs_variables = {};
var wf_collision = false;
var workerAjax = false;

var location_moving = false;

var nav_root_path = [[{"id":0,"name":"Home File Server","parent_id":undefined,"modified":undefined}]];

homefs_variables['player_enabled'] = false;

$(document).ready(function(){
	checkAuth();
	doc_resize();
	$(".nav-search").click(function(){
		$(".nav-search-hider").css({'display':'none'});
		$(".nav-search-input").css({'display':'inline'}).focus().select();
		doc_resize();
	});
	$(".nav-search-input").focusout(function(){
		$(".nav-search-hider").css({'display':''});
		$(".nav-search-input").css({'display':''});
		doc_resize();
	});
	$(".nav-search-input").keypress(function(e){
		if (!e) e = window.event;
		var keyCode = e.keyCode || e.which;
		if (keyCode == '13'){
			// Enter pressed
			modifyHash("#find=" + encodeURIComponent($(".nav-search-input").attr("value")));
		}
	});
	$(window).resize(function(){
		doc_resize();
	});
	var ivID = window.setInterval(function(){
		$(".time").each(function(){
			var tm = $(this).attr("time");
			if(tm.match(/\d+/))
				$(this).html(date_delta_format(tm));
		});
	}, 5000);

	/* Initial location */
	reload_hash_data();
		
	/* Change initial opacity */
	$("body").css({opacity:1});
	
	
	/* Scroll page auto */
	$(document).scroll(function () {
			scrollEvent();
	});
	
	$("a").live('click', function(){
		var me = $(this);
		var re = /^#/i;
		var href = $(this).attr('href');
		if(href.match(re))
			if(window.location.hash == href)
				reload_hash_data();
	});
	
});

$(window).on('hashchange', function() {
	reload_hash_data();
});



function scrollEvent() {
	var SH = document.body.scrollHeight;
	var CH = document.body.clientHeight;
	if ($(document).scrollTop() >= (SH - CH - 400)) {
		$("a.next-page").click();
	}
}

function modifyHash(text) {
	if(window.location.hash != text)
		window.location.hash = text;
	else
		reload_hash_data();
}

/* Core Functions */
function homefs_get_hashtag() {
    var vars = [], hash;
    var hashes = window.location.hash.substr(1).split('&');
    for(var i = 0; i < hashes.length; i++) {
        hash = hashes[i].split('=');
        vars[hash[0]] = decodeURIComponent(hash[1]);
    }
    return vars;
}

/* Go to folder */
function homefs_url_navigate(id) {
	homefs_url_continue(id, 0);
}

/* Continue folder */
function homefs_url_continue(id, start) {

	if(start == 0) ajaxBusyShow();

	if(workerAjax != false) workerAjax.abort();
	
	workerAjax = $.getJSON("/api?action=go&id=" + id + "&start=" + start, function( data ) {
		workerAjax = false;
		ajaxBusyHide();
		
		$("div.hfs-smartloader").html('');


		if(data.error !== undefined) {
			$("div.hfs-information span").html(data.error);
			return false;
		}
		
		/* Check for errors */
		if(data.dir.length == 0) {
			reset_page();
			draw_navigation_path(nav_root_path)
			document.title = site_suffix;
			displayInformationBlock(0);
			reindex_current_page();
			index_links();
			hfsStats();
			return false;
		} 
		
		if(start == 0) {
			/* Variables */
			reset_page();
			/* Current location */
			document.title = data.dir[0].name + " - " + site_suffix;
			draw_navigation_path(data.path)
			$(".nav-search-input").attr('value', '');
		}


		/* Parse integer values */
		data['variables'].total_dirs = parseInt(data['variables'].total_dirs);
		data['variables'].total_files = parseInt(data['variables'].total_files);
		data['variables'].start_from = parseInt(data['variables'].start_from);
		data['variables'].files_max = parseInt(data['variables'].files_max);

		/* Read directories */
		draw_dirs_on_page(data.dirs, false);

		/* Read files */
		draw_files_on_page(data.files, data.meta, false);

		/* Next page label */
		var total_all = data['variables'].total_dirs + data['variables'].total_files;
		var next_id = data['variables'].start_from + data['variables'].files_max;

	
		if(total_all > next_id)
			next_page_label("return np_browse("+id+", "+next_id+");");
		else
			hfsStats();

		doc_resize();
		
		if(start == 0)
			$("html, body").scrollTop(0);
			
		reindex_current_page();
		index_links();
		scrollEvent();
		
	});
}

function homefs_search_wrap(query, start) {

	if(start == 0) ajaxBusyShow();

	if(workerAjax != false) workerAjax.abort();

	workerAjax = $.getJSON("/api?action=search&q=" + encodeURIComponent(query) + "&start=" + start, function( data, textStatus, request ) {
		workerAjax = false;
		ajaxBusyHide();
		$("div.hfs-smartloader").html('');

		/* Parse integer values */
		data['variables'].total_dirs = parseInt(data['variables'].total_dirs);
		data['variables'].total_files = parseInt(data['variables'].total_files);
		data['variables'].start_from = parseInt(data['variables'].start_from);
		data['variables'].files_max = parseInt(data['variables'].files_max);

		var origin = request.getResponseHeader('Data-Origin');
		var responseTime = (origin === 'Cache' ? "in cached results." : "in <b>" + data['variables'].benchmark + "</b> second(s).");

		$("span.loc-wrap").html("Were found <b>" + (data['variables'].total_files + data['variables'].total_dirs) + "</b> results for <b>" + query + "</b> " + responseTime);
		
		if(start == 0) {
			reset_page();
			homefs_variables['dir_mtime'] = 0;
			homefs_variables['dir_id'] = 0;
			homefs_variables['page_title'] = "Search results for \"" + data['variables'].query + "\" - " + site_suffix;
			document.title = homefs_variables['page_title'];
			$(".nav-search-input").attr('value', data['variables'].query);
			if(data['variables'].total_dirs == 0 && data['variables'].total_files == 0)
				displayInformationBlock(1);
		}
	
		/* Read directories */
		draw_dirs_on_page(data.dirs, true);

		/* Read files */
		draw_files_on_page(data.files, data.meta, true);

		/* Next page label */
		var total_all = data['variables'].total_files;
		var next_id = data['variables'].start_from + data['variables'].files_max;
		
		if(total_all > next_id)
			next_page_label("return np_search(\"" + query.split('"').join('\\"') + "\", " + next_id + ");");
		else
			hfsStats();

		doc_resize();
		if(start == 0)
			$("html, body").scrollTop(0);
		reindex_current_page();
		index_links();
		scrollEvent();
	});
}

function np_search(query, from) {
	$("a.next-page").removeAttr('onclick');
	homefs_search_wrap(query, from);
	return false;
}

function np_browse(id, from) {
	$("a.next-page").removeAttr('onclick');
	homefs_url_continue(id, from);
	return false;
}

function reload_hash_data() {

	var hash = homefs_get_hashtag();
	
	if(find_in_keys(hash, 'playing')) {
		restorePlayingPage();
	} else if(find_in_keys(hash, 'go')) {
		homefs_url_continue(hash[key], 0);
	} else if(find_in_keys(hash, 'find')) {
		homefs_search_wrap(hash[key], 0);
	} else {
		homefs_url_continue(0, 0);
	}
	
}

function index_links() {
	$("a.file").unbind('click').each(function(){
		var me = $(this);
		me.bind('click', function(){
			window.open(me.attr('href'), "_blank");
			return false;
		});
	});



}