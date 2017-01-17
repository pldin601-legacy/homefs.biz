function initialize_player() { /* Do this when the player is initialized */
	/* Control buttons events */
	$("div.player-prev").click(function(){ play_previous(); });
	$("div.player-next").click(function(){ play_next(); });
	$("div.player-play").click(function(){ play_pause(); });
	$("div.player-stop").click(function(){ stop_player(); });

	/* Seeker event */
	$("#cnvbg")
		.mousedown(function(e){ player_seek(e, this); })
}

function play_completed() { /* Default action when current track is complete */
	scrobble();
	play_next();
}

function playing_now(e) { /* Action when seeking */
	track_curr_info['position'] = e.jPlayer.status.currentTime;
	changeSeekPosition(track_curr_info['position']);
}

function loading_now(e) {
	track_curr_info['loaded'] = e.jPlayer.status.seekPercent;
	drawPeakLevels();
}

function player_error(msg) {
	console.log(msg);
	stop_player();
}

function scrobble() {
	$.post('/lastfm.php', { 
			submission: 1,
			artist: track_curr_info.artist, 
			song: track_curr_info.title, 
			duration: track_curr_info.duration
		}, function (data){
			console.log(data);
		});
}