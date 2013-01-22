<?php
namespace Server\Modules;

class playerChat extends \Server\Server {
	protected $server;

	public function __construct(\Server\Server $server) {}
	
	public function __onLogin($method_name, $player) {
		$ip = $player->getIP();
		$this->log($player->getUsername() . ' has logged in from ' . $ip['ip'] . ':' . $ip['port']);
	}
}
?>
