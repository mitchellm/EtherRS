<?php
namespace Server\Modules;

class playerChat extends \Server\Server {
	private $server;

	public function __construct(\Server\Server $server) {}
	
	public function __onLogin(array $args) {
		$player = $args[1];
		$ip = $player->getIP();
		$this->log($player->getUsername() . ' has logged in from ' . $ip['ip'] . ':' . $ip['port']);
	}
}
?>
