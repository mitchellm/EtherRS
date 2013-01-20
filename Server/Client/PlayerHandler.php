<?php
namespace Server\Client;

/**
 * @category RSPS
 * @package EtherRS
 * @author David Harris <lolidunno@live.co.uk>, Mitchell Murphy <mitchell@fl3x.co>
 * @copyright 2013 EtherRS
 * @version GIT: $Id:$
 * @link https://github.com/mitchellm/EtherRS/
 */

class PlayerHandler extends \Server\Server {
	protected $players = array();
	protected $active_sessions = 0;

	public function __construct() {}

	/**
	 * 
	 * Add a client to the handler
	 * 
	 */
	public function add($socket, \Server\Server $server) {
		$this->check();
		$player = new Player($socket, $this->active_sessions, $server);
		$this->active_sessions++;
		$this->players[] = $player;
		$server->handleModules('__onConnect', $socket, $this);
	}

	/**
	 *
	 * Remove all null players
	 * 
	 */
	protected function check() {
		foreach($this->players as $key => $player) {
			if(is_null($player)) 
				continue;

			@socket_send($player->connection, " ", 1, MSG_OOB);
			if(socket_last_error($player->connection) != 0) {
				$player = null;
				$this->active_sessions--;
			}
		}
		$this->players = array_filter($this->players);
	}
}
?>
