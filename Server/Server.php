<?php
namespace Server;

/**
 * @category RSPS
 * @package EtherRS
 * @author David Harris <lolidunno@live.co.uk>, Mitchell Murphy <mitchell@fl3x.co>
 * @copyright 2013 EtherRS
 * @version GIT: $Id:$
 * @link https://github.com/mitchellm/EtherRS/
 */

chdir(__DIR__);
require_once('config.Server.php');
require_once('Stream.php');
require_once('Client/PlayerHandler.php');
require_once('Client/Player.php');
require_once("SQL.php");

class Server {
	protected $socket, $bytes, $raw;
	protected $sql;

	protected $playerHandler, $player;

	private $modules = array();

	public function __construct(array $args = null) {
		if(!extension_loaded('sockets')) {
			throw new \Exception('You need sockets enabled to use this!');
		}
		$this->log('EtherRS running and attempting to bind and listen on port ' . SERVER_PORT . '...');
		$this->socket = @socket_create(AF_INET, SOCK_STREAM, 0);
		$bind = @socket_bind($this->socket, 0, SERVER_PORT);
		$listen = @socket_listen($this->socket);
		socket_set_nonblock($this->socket);
		if(!$this->socket || !$bind || !$listen) {
			throw new \Exception('Could not bind to ' . SERVER_PORT);
		}

		$this->playerHandler = new Client\PlayerHandler();
		$this->sql = new SQL();

		$this->loadModules();
		$this->start();
	}

	/**
	 *
	 * Load all modules
	 * 
	 * @param string $dir The directory to load modules from
	 *
	 */
	private function loadModules($dir = null) {
		if($dir === null) {
			$dir = __DIR__;
		}

		$modules = glob($dir . '/Modules/mod.*.php');

		if(count($modules) <= 0) {
			return false;
		}

		foreach($modules as $module) {
			$module = basename($module);
			$module = substr($module, 4);
			$module = substr($module, 0, -4);
			require_once($dir . '/Modules/mod.' . $module . '.php');
			$class = '\Server\Modules\\' . $module; 
			if(!class_exists($class)) {
				throw new \Exception('Module ' . $class . ' failed to load -- Does the class name match the file name?');
			}
			$this->modules[$module] = new $class($this);
		}

		$this->log('Finished loading all server modules, the server will continue running!');
	}

	/**
	 *
	 * Handle all modules
	 *
	 * @param string $method_name Method name
	 *
	 */
	public function handleModules($handler) {
		$args = func_get_args();
		$modules = $this->getModules();

		foreach($modules as $module) {
			if(method_exists($module, $handler)) {
				$module->$handler($args);
			}
		}
	}

	/**
	 *
	 * Start the server
	 * 
	 */
	private function start() {
		$cycleTimed = 0;
		while($this->socket) {
			$cycleStart = time();

			$this->cycle();

			$cycleElapsed = time() - $cycleStart;
			usleep((CYCLE_TIME * 1000) - $cycleElapsed);
		}
	}

	/**
	 * 
	 * The sequence of a single server cycle
	 * 
	 */
	private function cycle() {
	 	//Listen for and process a new client, runs 10 times per cycle. Limited to prevent abuse 
		for($i = 0; $i < 10; $i++) {
			$client = @socket_accept($this->socket);
			if(!($client == false)) {
				$this->playerHandler->add($client, $this, $this->sql);
			}
			$this->playerHandler->cycleEvent();
		}
	}

	/**
	 *
	 * @return array
	 *
	 */
	public function getModules() {
		return $this->modules;
	}

	/**
	 *
	 * Get the current debug configuration
	 *
	 * @return Debug
	 *
	 */
	public function getDebug() {
		return DEBUG_CONFIG;
	}

	/**
	 *
	 * Write to STDOUT and append to log file
	 *
	 */
	public function log($msg, $log = true) {
		$msg = '[SERVER] ' . $msg . PHP_EOL;
		echo $msg;
		if($log) {
			file_put_contents('log/log-' . date('m-d-Y') .'.txt', $msg, FILE_APPEND);
		}
	}
}
?>
