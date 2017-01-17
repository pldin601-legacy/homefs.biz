$(document).ready(function(){
	$(this).keyup(function(){
		if(event.altKey == true && event.ctrlKey == true) {
			// Global Keys Switch - Ctrl + Alt
			if(event.keyCode == 70) {
				console.log("Shuffle");
				$("div.hfs-filelist").randomize('div.file-item');
				reindex_current_page();
				index_links();
			}
		}
	});
});