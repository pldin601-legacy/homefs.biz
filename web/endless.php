<?php

header('Content-Description: File Transfer');
header('Content-Disposition: filename="endless"');
header('Content-Type: application/octet-stream');

$data = str_repeat("\xFF", 4096);

set_time_limit(0);

while(1) {
	echo $data;
	flush();
}
