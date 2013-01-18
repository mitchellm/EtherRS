<?php
require 'Stream.php';
require 'Player.php';
$stream = new Stream();

set_time_limit (0);
$address = '127.0.0.1';
$port = 43594;
$sock = socket_create(AF_INET, SOCK_STREAM, 0);
socket_bind($sock, 0, $port) or die('Could not bind to address');
socket_listen($sock);
Player::serverOutput("Server listening on port " . $port . "...");

while (true) {
	$client = socket_accept($sock);
	//display information about the client who is connected
	if(socket_getpeername($client , $address , $port))
	{
	    Player::serverOutput("Client $address : $port is now connected to us.");
	}
	/*$data = socket_read($client, 1, PHP_BINARY_READ);
	$byte_array = unpack('C*', $data);
	$stream->setStream($byte_array);
	Player::serverOutput("Client sent byte: " . $byte_array[1]);
	flush()
	$str = "172\n";
	$len = strlen($str);*/
	socket_write($client, chr(1));
}
socket_close($client);
socket_close($sock);