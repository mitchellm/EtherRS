<?php
/**
 * @author Mitchell Murphy
 * @version 1.0.0
 */
require_once("Server.php");
class ClientHandler extends Server {
	public $clients = array();

	public function addClient($socket) {
		$this->clients[] = new Player($socket);

		/*$socket;*/
		$idx = count($this->clients);
		$this->log("Client " . $idx . " has connected!");
		return $idx - 1;
	}
	
	public function getClient($index) {
		return $this->clients[$index];
	}

	public function getClients() {
		return count($this->clients);
	}
}
