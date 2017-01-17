function displayInformationBlock(id) {
	/*
		id	description
		--	-----------
		0	Location not found
		1	Nothing found
	*/
	var _target = $("div.hfs-information");
	if(id == 0) 
		_target	.html($("#infoDirNotFound").tmpl())
			.css({display:'block'});
	else if(id == 1)
		_target	.html($("#infoNothingFound").tmpl())
			.css({display:'block'});
}

function hideInfromationBlock() {
	 $("div.hfs-information").css({display:'none'});
}