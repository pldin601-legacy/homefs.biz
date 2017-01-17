$.fn.randomize = function(selector){
    var $elems = selector ? $(this).find(selector) : $(this).children(),
        $parents = $elems.parent();

    $parents.each(function(){
        $(this).children(selector).sort(function(){
            return Math.round(Math.random()) - 0.5;
        }).remove().appendTo(this);
    });

    return this;
};

function file_size_humanize(bytes) {
	var integer = parseInt(bytes);
	var sfx = 'Bytes KiB MiB GiB TiB'.split(' ');
	for(var pw=0;integer>1024;pw++)
		integer /= 1024;
	var tmp = integer.toFixed(1).replace(".0", "");
	return tmp + ' ' + sfx[pw];
}

function date_delta_format(unix) {
	var date = parseInt(new Date().getTime() / 1000);
	var seconds = date - unix;

	var monArray = "jan feb mar apr may jun jul aug sep oct nov dec".split(" ");
	var myDate = new Date(unix * 1000);

	var sec = myDate.getSeconds() < 10 ? "0" + myDate.getSeconds() : myDate.getSeconds();
	var min = myDate.getMinutes() < 10 ? "0" + myDate.getMinutes() : myDate.getMinutes();
	var hr  = myDate.getHours()   < 10 ? "0" + myDate.getHours()   : myDate.getHours();

	if(seconds < 0)
		return 'in the future';
	if(seconds < 60)
		return seconds + ' sec. ago';
	else if(seconds < 3600)
		return parseInt(seconds / 60) + ' min. ago';
	else if(seconds < 86400)
		return parseInt(seconds / 3600) + ' hr. ago';
	else if(seconds > 86400 && seconds < 172800)
		return 'yesterday';
	else if(seconds < 86400 * 7)
		return parseInt(seconds / 86400) + ' day(s) ago';
	else {
		return myDate.getDate() + ' ' + monArray[myDate.getMonth()] + ' ' + (myDate.getYear()+1900) + ", " + hr + ":" + min + ":" + sec;
	}
}

function find_in_keys(arr, val) {
	for (key in arr)
		if (key == val)
			return true;
			
	return false;
}


function secondsToTime(secs) {

 	var hr = Math.floor(secs / 3600);
	var min = Math.floor((secs / 60) % 60);
	var sec = Math.floor(secs) % 60;

	if (min < 10) min = '0' + min;
	if (sec < 10) sec = '0' + sec;

	if (hr > 0)
		return hr + ":" + min + ':' + sec;
	else
		return min + ':' + sec;
}

function isDef(i) {
	return i !== undefined;
}