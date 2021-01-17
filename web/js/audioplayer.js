var track_list = [];
var track_curr = 0;
var track_curr_info = false;
var player_status = 0; /* 0 - stop, 1 - play, 2 - pause  */
var vu_meter = false;

$(document).ready(function(){
    $(".jplayer").jPlayer({
		ready: function(e) { initialize_player(); },
		ended: function(e) { play_completed(); },
		error: function(e) { player_error(e.jPlayer.error.message); },
		timeupdate: function(e) { playing_now(e); },
		progress: function(e) { loading_now(e); },
        swfPath: "/player",
        supplied: "mp3",
		solution: "flash,html",
		volume: 1
    });
});

function reindex_current_page() {
	var audio = getPageAudioList();
	for(i in audio) {
		var id = audio[i]['id'];
		$("div.file-item#file"+id+" div.list-icon")
			.attr('onclick', 'sw_player('+(i)+');')
			.attr('title', 'Play/Pause')
			.css({'cursor': 'pointer'});
	}
	update_page_status();
}

function update_page_status() {
	$("div.file-audio").removeClass('playing').removeClass('paused');
	$(".icon .folder").removeClass('folder-playing');
	if(player_status > 0) {
		var current_id = track_curr_info['id'];
		if(player_status == 1)
			$("div.file-audio#file"+current_id).addClass('playing');
		else if(player_status == 2)
			$("div.file-audio#file"+current_id).addClass('paused');

		var path = track_curr_info['path'].split(',');
		for (i in path) {
			$(".icon .folder#fi"+path[i]).addClass('folder-playing');
		}
	}

}

function sw_player(id) {
	var audio = getPageAudioList();
	if(track_curr == id && audio[id]['id'] == track_curr_info['id'] && player_status == 2) {
		unpause_player();
	} else if(track_curr == id && audio[id]['id'] == track_curr_info['id'] && player_status == 1) {
		pause_player();
	} else {
		load_playlist();
		track_curr = id;
		start_player(id);
	}
}

function play_pause() {
	if(player_status == 2) {
		unpause_player();
	} else if(player_status == 1) {
		pause_player();
	}
}

function load_playlist() {
	track_list = getPageAudioList();
	snapshotPlayingPage();
}

function set_player_title_new(info) {
	var title 	= info['title'] 	? info['title'] 										: info['name'];
	var artist 	= info['artist']	? '<a href="#find=#artist '+encodeURIComponent(info['artist'])+'">' + info['artist'] + '</a>'	: 'Unknown Artist';
	var album 	= info['album']		? '<a href="#find='+encodeURIComponent(info['album'])+'">' + info['album'] + '</a>'		: 'Unknown Album';

	$(".tt-title").html($("#navLink").tmpl({
		'url'	: '#playing', 
		'title'	: title
	}));

	$(".tt-artist").html(artist);
	$(".tt-time").html(album);

	$.getJSON('/api?action=checkicon&id=' + info['id'], function(data){
		if(data['cover_present'] === undefined) {
			$(".hfs-cover").toggleClass('hfs-cover-visible', false);
		} else if(data['cover_present'] == "1") {
			var cover = $(".hfs-cover");
			cover.toggleClass('hfs-cover-visible', true).html('<img width="46" height="46" align="middle" src="/api?action=icon&id=' + info['id']+'">');
		} else {
			$(".hfs-cover").toggleClass('hfs-cover-visible', false);
		}
	});

}

function make_audio_title(track) {
		if(track['metadata'] === undefined)
			return track['name'];
		if(track['metadata']['artist'] && track['metadata']['title']) {
			return track['metadata']['artist'] + " - " + track['metadata']['title'];
		} else if(!track['metadata']['artist'] && track['metadata']['title'])
			return track['metadata']['title'];
		else
			return track['name'];
}


function play_next() {
	if(track_list[track_curr + 1] !== undefined) {
		track_curr ++;
		start_player(track_curr);
	} else
		stop_player();
}

function play_previous() {
	if(track_list[track_curr - 1] !== undefined) {
		track_curr --;
		start_player(track_curr);
	} else
		stop_player();
}

function animate_scroll() {

//	if(track_curr_info) {
//		$("body,html").animate({scrollTop: (track_curr_info['scrolly'] - $(window).height() / 2) + 'px' });
//	}
}
