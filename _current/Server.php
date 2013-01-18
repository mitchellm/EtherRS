<?php
require dirname(__FILE__) . '/networking/Stream.php';
require dirname(__FILE__) . '/player/Player.php';
$stream = new Stream();

set_time_limit (0);

$server = new Server('127.0.0.1', 43594);
$server->run();

class Server {
	private $client, $server, $addr, $port;

	private $player;

	public function __construct($addr, $port) {
		$this->server = socket_create(AF_INET, SOCK_STREAM, 0);
		$this->addr = $addr;
		$this->port = $port;
	}

	public function run() {
		socket_bind($this->server, 0, $this->port) or die('Could not bind to address');
		socket_listen($this->server);

		Server::out("Server listening on port " . $this->port . "...");

		while (true) {
			$this->client = socket_accept($this->server);
			if(socket_getpeername($this->client , $this->address , $this->port))
			{
			    Server::out("Client $this->address : $this->port is now connected to us.");
			}
			$this->player = new Player($this->client);
		}
		socket_close($this->client);
		socket_close($this->server);
	}

	public static function out($str) {
		printf("\n [SERVER] " . $str);
	}
}
