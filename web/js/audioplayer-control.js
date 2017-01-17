function start_player(id) {
	if(track_list[id] !== undefined) {
		player_status = 1;
		track_curr_info = track_list[id];
		$(".jplayer").jPlayer("setMedia", {  mp3: "/listen.php?format=mp3&id=" + track_curr_info['id'] + "&uid=" + loginUser.uid }).jPlayer("play");
		$("div.player-play").toggleClass("ctrl-pause", true).toggleClass("ctrl-play", false);
		set_player_title_new(track_curr_info);
		animate_scroll();
		update_page_status();
		show_audio_player();

		unloadPeakData();
		loadPeakData(track_curr_info['id']);

		$.post('http://jabbo.homefs.biz/lastfm.php', { 
			nowplaying: 1, 
			artist: track_curr_info.artist, 
			song: track_curr_info.title, 
			duration: track_curr_info.duration
		});
		
		$("#timeDuration").html(secondsToTime(track_curr_info['duration']));
		/*
		if(vu_meter == false)
			vu_meter = setInterval(function(){
				vu_iteration();
			}, 100);
		*/
	}
}

function pause_player() {
	$(".jplayer").jPlayer("pause");
	$("div.player-play").toggleClass("ctrl-pause", false).toggleClass("ctrl-play", true);
	player_status = 2;
	update_page_status();
}

function unpause_player() {
	$(".jplayer").jPlayer("play");
	$("div.player-play").toggleClass("ctrl-pause", true).toggleClass("ctrl-play", false);
	player_status = 1;
	update_page_status();
}

function stop_player() {
	$(".jplayer").jPlayer("stop").jPlayer("clearMedia");
	$("div.player-play").toggleClass("ctrl-pause", false).toggleClass("ctrl-play", true);
	player_status = 0;
	update_page_status();
	track_curr_info = false;
	hide_audio_player();
	unloadPeakData();
	$(".hfs-cover").toggleClass('hfs-cover-visible', false).html('');
	playingPage = false;
}

function player_seek(e, obj) {
	if(track_curr_info['duration'] == "0") return false;
	var x = e.pageX - $(obj).offset().left;
	if(player_status == 0) return false;
	$(".jplayer").jPlayer("play", track_curr_info['duration'] / $(obj).width() * x);
}

