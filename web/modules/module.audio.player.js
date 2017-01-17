/*
	Player Module Section 1
*/
$(document).ready(function(){
	$(window).resize(function(){
		if(player_status != 0) 
			changeSeekPosition(track_curr_info['position']);
	});
});

function changeSeekPosition(position) {
	$("div#cnvbg #time").html(secondsToTime(position));
	var cent = 100 / track_curr_info['duration'] * position;
	$("div#cnvbg div#cnvcursor").css({ left : Math.floor($("div#cnvbg").width() / 100 * cent) + 'px' });
		
	if( $("div#cnvbg").width() - $("div#cnvbg div#cnvcursor").position().left - $("div#cnvbg #timeDuration").outerWidth(true) > $("div#cnvbg #time").outerWidth(true) )
		$("div#cnvbg #time").css({ left : Math.floor($("div#cnvbg").width() / 100 * cent) + 'px' });
	else
		$("div#cnvbg #time").css({ left : ($("div#cnvbg").width() - $("div#cnvbg #time").outerWidth(true) - $("div#cnvbg #timeDuration").outerWidth(true)) + 'px' });

}