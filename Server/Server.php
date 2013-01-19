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

class Server {
	protected $socket, $bytes, $raw;
	protected $outStream;

	private $playerHandler;

	private $modules;

	public function __construct(array $args = null) {
		if(!extension_loaded('sockets')) {
			throw new \Exception('You need sockets enabled to use this!');
		}
		$this->log('Server initialized');
		$this->socket = @socket_create(AF_INET, SOCK_STREAM, 0);
		$bind = @socket_bind($this->socket, 0, SERVER_PORT);
		$listen = @socket_listen($this->socket);
		
		if(!$this->socket || !$bind || !$listen) {
			throw new \Exception('Could not bind to ' . SERVER_PORT);
		}

		$this->outStream = new Stream();
		$this->inStream = new Stream();
		$this->playerHandler = new Client\PlayerHandler();
		
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
			$this->modules[$module] = new $class();
		}

		$this->log('Finished loading modules');
	}

	/**
	 *
	 * Start the server
	 * 
	 */
	private function start() {
		socket_set_nonblock($this->socket);
		while($this->socket) {
			$secb4 = time();
			$client = @socket_accept($this->socket);
			if(!($client == false)) {
				$this->playerHandler->addClient($client, $this);
			}
			$diff = time() - $secb4;
			usleep(600000 - $diff);
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
	 * Get the current out stream
	 *
	 * @return Stream
	 *
	 */
	public function getOutstream() {
		return $this->outStream;
	}

	/**
	 *
	 * Get the current in stream
	 *
	 * @return Stream
	 *
	 */
	public function getInstream() {
		return $this->inStream;
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