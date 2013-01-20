<?php
namespace Server\Modules;

class playerChat extends \Server\Server {
	private $server;
	
	public function __construct(\Server\Server $server) {
		$this->log('Player Chat loaded');
	}
}
?>
