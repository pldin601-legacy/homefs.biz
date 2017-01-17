/*
	Audio peaks Module
*/

var peakPatterns = ["110","1","11111110"];
var activePattern = ($.cookie("frontendPeakMeterMode") === undefined) ? 0 : (parseInt($.cookie("frontendPeakMeterMode")) % peakPatterns.length);

var peakPattern = peakPatterns[activePattern];
var peakDivider = peakPattern.length;

var peakData = {raw:"",	adapted:[]};
var peakLoaderFlag = false;
var peakDataPresent = false;

var peakColor = {
	active 		: 'rgba(95,117,165,1)',
	inactive 	: 'rgba(0,0,0,1)'
};

/* Module initialization */
$(document).ready(function(){
	$(window).resize(function(){
		peakDataResample();
		resizeCanvas();
		drawPeakLevels();
	});
	
	$("#modeSwitch").click(function(){
		activePattern ++;
		activePattern %= peakPatterns.length;
		changeActivePattern();
		peakDataResample();
		drawPeakLevels();
		$.cookie("frontendPeakMeterMode", activePattern, { expires : 30 });
	});
});

/* Peak Data Load/Unload */
function loadPeakData(id) {
	$(".wave #loading").html("Loading peak data...").css({display:'block'});
	$("canvas#canvas").stop().css({opacity:0});

	if(peakLoaderFlag != false)
		peakLoaderFlag.abort();

	peakLoaderFlag = $.getJSON("/api?action=wave&id=" + id, function(data){
		peakLoaderFlag = false;
		if(data['wavedata'] !== undefined) {
			$(".wave #loading").css({display:'none'});
			peakDataPresent = true;
			peakData.raw = atob(data['wavedata']);
			peakDataResample();
			resizeCanvas();
			drawPeakLevels();
			$("canvas#canvas").stop().animate({opacity:1}, 250);
		} else {
			$(".wave #loading").html("No peak data");
			peakDataPresent = false;
			peakDataResample();
			unloadPeakData();
		}
	});
}

function changeActivePattern() {
	peakPattern = peakPatterns[activePattern];
	peakDivider = peakPattern.length;
	
}

function unloadPeakData() {
	peakData = {raw:"", adapted:[]};
	peakDataPresent = false;
	$("canvas#canvas").stop().css({opacity:0});
	clearPeakLevels();
}

/* Display peak levels */
function resizeCanvas() {
	var canvas = $("#canvas")[0];
	var realWidth = $("#canvas").width();
	var realHeight = $("#canvas").height();
	canvas.width = realWidth;
	canvas.height = realHeight;
}

function drawPeakLevels() {

	var canvas = $("#canvas")[0];
	var ctx = canvas.getContext('2d');

	ctx.clearRect(0, 0, canvas.width, canvas.height);

	if(peakDataPresent == true) {
		var loadedInPixels = (track_curr_info['loaded'] !== undefined) ? Math.floor(canvas.width / 100 * track_curr_info['loaded']) : 0;
		drawPeaks(ctx, 0, loadedInPixels, peakColor.active, 1);
		drawPeaksShadow(ctx, 0, loadedInPixels, peakColor.active, 0.5);
		drawPeaks(ctx, loadedInPixels + 1, canvas.width, peakColor.inactive, 0.3);
		drawPeaksShadow(ctx, loadedInPixels + 1, canvas.width, peakColor.inactive, 0.15);
	}
}

function clearPeakLevels() {
	var canvas = $("#canvas")[0];
	var ctx = canvas.getContext('2d');
	ctx.clearRect(0, 0, canvas.width, canvas.height);
}

/* Drawers */
function drawPeaks(context, start, end, color, alpha) {
	context.beginPath();
	for(var n = start; n <= end; n ++) {
		if(peakPattern.substr(n % peakDivider, 1) == "1") {
			var peakDataIndex = Math.floor(n / peakDivider);
			context.moveTo(n + 0.5, 38);
			context.lineTo(n + 0.5, 37 - Math.floor(38 / 127 * peakData.adapted[peakDataIndex]));
		}
	}
	context.globalAlpha = alpha;
	context.strokeStyle = color;
	context.stroke();
}

function createGradient(ctx, color) {
    var lingrad = ctx.createLinearGradient(0,0,0,48);
    lingrad.addColorStop(0, 'rgba('+color+', 0.7)');
    lingrad.addColorStop(0.79, 'rgba('+color+', 1)');
    lingrad.addColorStop(1, 'rgba('+color+', 0.7)');
	return lingrad;
}

function drawPeaksShadow(context, start, end, color, alpha) {
	context.beginPath();
	for(var n = start; n <= end; n ++) {
		if(peakPattern.substr(n % peakDivider, 1) == "1") {
			var peakDataIndex = Math.floor(n / peakDivider);
			context.moveTo(n + 0.5, 38);
			context.lineTo(n + 0.5, 38 + Math.floor(10 / 127 * peakData.adapted[peakDataIndex]));
		}
	}
	context.globalAlpha = alpha;
	context.strokeStyle = color;
	context.stroke();
}

/* Functions / Helpers */
function getMaximalValueChar(str) {
	var chr = 0;
	var array = str.split("");
	for (i in array) {
		if(array[i].charCodeAt(0) > chr)
			chr = array[i].charCodeAt(0);
	}
	return chr;
}

function peakDataResample() {
	var canvasWidth = Math.floor($("#canvas").width() / peakDivider);
	var tmpAdaptedData = [];
	var samplesPerBlock = Math.ceil(peakData.raw.length / canvasWidth);
	for(var i=0;i<canvasWidth;i++) {
		tmpAdaptedData.push(getMaximalValueChar(peakData.raw.substr(peakData.raw.length / canvasWidth * i, samplesPerBlock)));
	}
	peakData.adapted = tmpAdaptedData;
}