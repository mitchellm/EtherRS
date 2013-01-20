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

class Player extends \Server\Server {
	protected $session, $server;
	protected $lastPacket;
	protected $username;
	public $connection;

	public function __construct($socket, $active_session, \Server\Server $server) {
		$this->connection = $socket;
		$this->session = $active_session;
		$this->server = $server;
		$this->run();
	}

	/**
	 * 
	 * Read data from client
	 * 
	 * @param int $bytes Amount of data to read to the buffer
	 * 
	 */
	private function read($bytes) {
		$data = socket_read($this->connection, $bytes, PHP_BINARY_READ);
		if($data > 0 && $data != false) 
			$this->lastPacket = time();
		$data = unpack('C*', $data);
		return $data;
	}

	/**
	 *
	 * Send data to a socket
	 * 
	 * @param mixed $s Data to be sent
	 * 
	 */
	public function write($s) {
		socket_write($this->connection, $s);
	}

	/**
	 * 
	 * Entire login method. Follows STD protocol.
	 * 
	 */
	private function run() {
		socket_set_block($this->connection);
		$serverSessionKey = ((((mt_rand(1, 100)/100) * 99999999) << 32) + ((mt_rand(1, 100)/100) * 99999999));
		$clientSessionKey = 0;

		$data = $this->read(2);
		$this->server->inStream->setStream($data);

		if($this->server->inStream->getUnsignedByte() != 14) {
			$this->log("Expected login Id 14 from client.");
			return;
		}


		$namePart = $this->server->inStream->getUnsignedByte();
		for($x = 0; $x < 8; $x++) {
			$this->write(chr(0));
		}
		$this->write(chr(0));

		$this->server->outStream->clear();
		$this->server->outStream->putLong($serverSessionKey);

		$stream = $this->server->outStream->getStream();

		$ssk = $this->write($stream);

		$data = $this->read(2);
		$this->server->inStream->setStream($data);
		
		$loginType = $this->server->inStream->getUnsignedByte();
		
		if($loginType != 16 && $loginType != 18) {
			$this->log("Unexpected login type " . $loginType);
			return;
		} 

		$loginPacketSize = $this->server->inStream->getUnsignedByte();
		$loginEncryptPacketSize = $loginPacketSize - (36 + 1 + 1 + 2);
		if($loginEncryptPacketSize <= 0) {
			$this->log("Zero RSA packet size", $debug);
			return;
		}

		$data = $this->read($loginPacketSize);
		$this->server->inStream->setStream($data);

		$m1 = $this->server->inStream->getUnsignedByte();
		$m2 = $this->server->inStream->getUnsignedShort();

		if($m1 != 255 || $m2 != 317) {
			$this->log("Wrong login packet magic ID (expected 255, 317)" . $m1 . " _ " . $m2);
			return;
		}	

		$lowMemVersion = $this->server->inStream->getUnsignedByte();
		for($x = 0; $x < 9; $x++) {
			$this->server->inStream->getInt();
		}
		$loginEncryptPacketSize--;

		$encryptSize = $this->server->inStream->getUnsignedByte();
		if($loginEncryptPacketSize != $encryptSize) {
			$this->log($this->server->inStream->getCurrentOffset());
			$this->log("Encrypted size mismatch! It's: " . $encryptSize);
			return;
		}

		$tmp = $this->server->inStream->getUnsignedByte();
		if($tmp != 10) {
			$this->log("Encrypt packet Id was " . $tmp . " but expected 10");
		}

		$clientSessionKey = $this->server->inStream->getLong();
		$serverSessionKey = $this->server->inStream->getLong();
		$uid = $this->server->inStream->getInt();

		$this->username = strtolower($this->server->inStream->getString());
		$this->password = $this->server->inStream->getString();
		$this->server->outStream->clear();
		if(true) {
			$return = 2;
		} else {
			$return = 3;
		}

		$this->server->outStream->putByte($return);
		$this->server->outStream->putByte(0);
		$this->server->outStream->putByte(0);

		$stream = $this->server->outStream->getStream();

		$this->write($stream);
		$this->server->handleModules('__onLogin', $this);
	}

	public function getUsername() {
		return $this->username;
	}

	public function getIP() {
		socket_getpeername($this->connection, $ip, $port);
		return array('ip' => $ip, 'port' => $port);
	}
}
?>
