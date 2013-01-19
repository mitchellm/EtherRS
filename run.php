<?php
chdir(__DIR__);
require_once('Server/Server.php');
try {
	$server = new Server\Server();
} catch(Exception $e) {
	exit($e->getMessage() . PHP_EOL); 
}