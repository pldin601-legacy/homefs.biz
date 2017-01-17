/*

	Dropdown results search hepler module

*/

var pop_collision = false;
var pop_delay = false;

$(document).ready(function(){
	$("body").click(function(){
		homefs_popup_hide();
	});
	$(".nav-search-input").keypress(function(e){
		if (!e) e = window.event;
		var keyCode = e.keyCode || e.which;
		if (keyCode == '13')
			homefs_popup_hide();
	})
	.bind('textchange', function(e){
		if(pop_delay != false) 
			clearTimeout(pop_delay);
			pop_delay = setTimeout(function(){
				pop_delay = false;
				homefs_popup_show($(".nav-search-input").attr("value"));
			}, 250);
	});
});

function homefs_popup_show(query) {
		if(query == '') {
				homefs_popup_hide();
				return false;
		}
		if(pop_collision != false)
				pop_collision.abort();
		
		pop_collision = $.getJSON("/api?action=popup&q=" + encodeURIComponent(query), function(data) {
				pop_collision = false;
				if(data[0].length > 0)
						draw_popup_items(data[0]);
				else
						homefs_popup_hide();
		});
}

function draw_popup_items(data) {
	$(".nav-search-popup").css({display:'block'}).html('');
	for(i in data) {
		var itm = data[i];
		var tmp = { index : i, query : itm['request'], encoded : encodeURIComponent(itm['request']) };
		$("#popupItemTmpl").tmpl(tmp).appendTo(".nav-search-popup");
	}
}

function homefs_popup_hide() {
		if(pop_delay != false) clearTimeout(pop_delay);
		pop_delay = false;
		$(".nav-search-popup").css({display:'none'});
}
