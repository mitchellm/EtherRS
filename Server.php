<?php
/**
 * @author Mitchell Murphy
 * @version 1.0.0
 */
function __autoload($class_name) {
	require_once $class_name . '.php';
}
$server = new Server();
$server->run();

class Server {
	private $server_sock, $client_sock, $port = 43594, $host = '127.0.0.1';
	protected $clienthandler, $stream;

	public function __construct() {
		$this->stream = new Stream();
	}

	public function run() {
		$this->clienthandler = new ClientHandler();
		while(true) {
			try {
				$this->server_sock = socket_create(AF_INET, SOCK_STREAM, 0);
				$bind = socket_bind($this->server_sock, 0, $this->port);
				$listener = socket_listen($this->server_sock);
				$this->client_sock = socket_accept($this->server_sock);
				$index = $this->clienthandler->addClient($this->client_sock);
				$this->clienthandler->process($index);
			} catch(Exception $e) {
				$this->log($e->getMessage());
			}
		}
	}

	protected function log($s, $b = true) {
		if($b)
			echo "\n [SERVER] " . $s;
	}
}