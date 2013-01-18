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
	$plr = Player::getInstance($client);
}
socket_close($client);
socket_close($sock);