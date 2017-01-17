var ajaxBusyHandler = false;

$(document).ready(function(){
	$("div#busy-close").click(function(){
		resetAjax();
	});
});

function ajaxBusyShow() {
	if(ajaxBusyHandler != false) clearTimeout(ajaxBusyHandler);
		
	ajaxBusyHandler = setTimeout(function(){
		ajaxBusyHandler = false;
		$("div.ajax-busy").css({display:'block'}).stop().animate({opacity:1}, 250);
	}, 250);
}

function ajaxBusyHide() {
	if(ajaxBusyHandler != false) clearTimeout(ajaxBusyHandler);
	ajaxBusyHandler = false;
	$("div.ajax-busy").stop().css({opacity:0, display:'none'});
}

function resetAjax() {
	if(workerAjax != false) {
		workerAjax.abort();
		workerAjax = false;
	}
	ajaxBusyHide();
}