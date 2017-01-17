var loginUser = {uid : 0, name : undefined};

function show_login_prompt() {
	$("div.login-background").css({display:'block'});
}

function try_to_login(name, passw) {
}

function logout() {
}

function checkAuth() {
	$.getJSON('https://' + location.hostname + '/login.php', function(data){
		parseLoginData(data);
	});
}

function signIn(login, passw) {
	$.post('https://' + location.hostname + '/login.php?do=in', {login:login,password:passw}, function(json){
		data = JSON.parse(json);
		parseLoginData(data);
	});
}

function signOut() {
	$.getJSON('https://' + location.hostname + '/login.php?do=out', function(data){
		parseLoginData(data);
	});
}

function parseLoginData(data) {
	if(data.status == 'SUCCESS') {
		loginUser = {uid : data.uid, name : data.name};
	} else if(data.status == 'NOAUTH') {
		loginUser = {uid : 0, name : undefined};
	} else if(data.status == 'WRONG') {
		console.log("Wrong login or password");
	}
	console.log("LOGIN: " + data.status);
}