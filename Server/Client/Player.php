<?php
namespace Server\Client;

require_once('Stream.php');
require(__DIR__ . "\..\Cryption\ISAAC.php");
/**
 * @category RSPS
 * @package EtherRS
 * @author David Harris <lolidunno@live.co.uk>, Mitchell Murphy <mitchell@fl3x.co>
 * @copyright 2013 EtherRS
 * @version GIT: $Id:$
 * @link https://github.com/mitchellm/EtherRS/
 */

class Player extends \Server\Server {
	protected $session, $server, $sql, $inStream, $outStream;
	protected $lastPacket;
	protected $username, $password;
	public $connection;

	protected $encryptor, $decryptor;

	public function __construct($socket, $active_session, \Server\Server $server, \Server\SQL $sql) {
		$this->connection = $socket;
		$this->session = $active_session;
		$this->server = $server;
		$this->sql = $sql;

		$this->outStream = new \Server\Stream();
		$this->inStream = new \Server\Stream();

		$this->run();
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
	 * Read data from client
	 * 
	 * @param int $bytes Amount of data to read to the buffer
	 * 
	 */
	private function read($bytes) {
		$this->inStream->clear();
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
		$this->outStream->clear();
		socket_write($this->connection, $s);
	}

	public function setUsername($s) {
		$this->username = $s;
	}

	public function setPassword($s) {
		$this->password = $s;
	}

	/**
	 * 
	 * Entire login method. Follows STD protocol.
	 * 
	 */
	private function run() {
		socket_set_block($this->connection);
		$serverHalf = ((((mt_rand(1, 100)/100) * 99999999) << 32) + ((mt_rand(1, 100)/100) * 99999999));
		$clientHalf = 0;

		$data = $this->read(2);
		$this->inStream->setStream($data);

		if($this->inStream->getUnsignedByte() != 14) {
			$this->log("Expected login Id 14 from client.");
			return;
		}


		$namePart = $this->inStream->getUnsignedByte();
		for($x = 0; $x < 8; $x++) {
			$this->write(chr(0));
		}
		$this->write(chr(0));

		$this->outStream->clear();
		$this->outStream->putLong($serverHalf);

		$stream = $this->outStream->getStream();

		$ssk = $this->write($stream);

		$data = $this->read(2);
		$this->inStream->setStream($data);
		
		$loginType = $this->inStream->getUnsignedByte();
		
		if($loginType != 16 && $loginType != 18) {
			$this->log("Unexpected login type " . $loginType);
			return;
		} 

		$loginPacketSize = $this->inStream->getUnsignedByte();
		$loginEncryptPacketSize = $loginPacketSize - (36 + 1 + 1 + 2);
		if($loginEncryptPacketSize <= 0) {
			$this->log("Zero RSA packet size", $debug);
			return;
		}

		$data = $this->read($loginPacketSize);
		$this->inStream->setStream($data);

		$m1 = $this->inStream->getUnsignedByte();
		$m2 = $this->inStream->getUnsignedShort();

		if($m1 != 255 || $m2 != 317) {
			$this->log("Wrong login packet magic ID (expected 255, 317)" . $m1 . " _ " . $m2);
			return;
		}	

		$lowMemVersion = $this->inStream->getUnsignedByte();
		for($x = 0; $x < 9; $x++) {
			$this->inStream->getInt();
		}
		$loginEncryptPacketSize--;

		$encryptSize = $this->inStream->getUnsignedByte();
		if($loginEncryptPacketSize != $encryptSize) {
			$this->log("Encrypted size mismatch! It's: " . $encryptSize);
			return;
		}

		$tmp = $this->inStream->getUnsignedByte();
		if($tmp != 10) {
			$this->log("Encrypt packet Id was " . $tmp . " but expected 10");
		}

		$clientHalf = 53955325; //$this->inStream->getLong();
		$this->inStream->getLong();
		$serverHalf = $this->inStream->getLong();
		$uid = $this->inStream->getInt();

		$username = strtolower($this->inStream->getString());
		$password = $this->inStream->getString();

		$this->setUsername($username);
		$this->setPassword($password);

		$this->outStream->clear();

		$isaacSeed = array(intval($clientHalf >> 32), intval($clientHalf), intval($serverHalf >> 32), intval($serverHalf));
		//$this->log($clientHalf . " " . $serverHalf);
		$this->setDecryptor(new \Server\Cryption\ISAAC($isaacSeed));
		for($i = 0; $i < count($isaacSeed); $i++) {
			$isaacSeed[$i] += 50;
		}
		$this->setEncryptor(new \Server\Cryption\ISAAC($isaacSeed));

		$this->login();
	}

	private function login() {
		$response = 0;

		$exists = $this->sql->getCount("players", array('username', 'password'), 
			array($this->getUsername(), $this->getPassword()));

		if($exists == 1) {
			$response = 2;
		} else {
			$response = 3;
		}

		$players = $this->server->playerHandler->getPlayers();
		foreach($players as $player) {
			if($player == null) {
				continue;
			}
			if($player->getUsername() == $this->getUsername()) {
				$response = 5;
				break;
			}
		}		

		$this->outStream->putByte($response)->putByte(0)->putByte(0);
		$stream = $this->outStream->getStream();
		$this->write($stream);

		$this->outStream->putHeader($this->getEncryptor(), 249)->putByteA(0)->putLEShortA(0);
		$stream = $this->outStream->getStream();
		$this->write($stream);

		$this->outStream->putHeader($this->getEncryptor(), 107);
		$stream = $this->outStream->getStream();
		$this->write($stream);

		$this->outStream->putHeader($this->getEncryptor(), 73)->putShortA(400)->putShort(400);
		$stream = $this->outStream->getStream();
		$this->write($stream);

			
		$this->server->handleModules('__onLogin', $this);
	}

	protected function setEncryptor($isaac) {
		$this->encryptor = $isaac;
	}

	protected function setDecryptor($isaac) {
		$this->decryptor = $isaac;
	}

	protected function getEncryptor() {
		return $this->encryptor;
	}

	protected function getDecryptor() {
		return $this->decryptor;
	}


	public function getUsername() {
		return $this->username;
	}

	public function getPassword() {
		return $this->password;
	}

	public function getIP() {
		socket_getpeername($this->connection, $ip, $port);
		return array('ip' => $ip, 'port' => $port);
	}
}
?>
