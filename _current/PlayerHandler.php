<?php
/**
 * @author Mitchell Murphy
 * @version 1.0.0
 */
require_once("Server.php");
class PlayerHandler extends Server {

	public function processPlayers() {
		$clients = $this->clienthandler->getClients();
	}
}