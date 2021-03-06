<?php
namespace Server\Client;

require 'PlayerUpdate.php';
require 'Cryption/ISAAC.php';

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
	private $player_handler;
	protected $updater;

	protected $socket;

	protected $encryptor, $decryptor;

	public function __construct($socket, $active_session, \Server\Server $server, \Server\Network\SQL $sql, PlayerHandler $player_handler) {
		$this->connection = $socket;
		$this->session = $active_session;
		$this->server = $server;
		$this->sql = $sql;

		$this->outStream = new \Server\Network\Stream();
		$this->inStream = new \Server\Network\Stream();

		$this->socket = $server->getSocket();
		$this->socket->addStream($this->inStream, $this->outStream, $active_session + 1, true);
		$this->socket->addSocket($socket, $active_session + 1, true);

		$this->playerHandler = $player_handler;
		$this->handleModules('__newPlayer', $this, $player_handler);
		$this->run();
	}

	/**
	 * 
	 * Entire login method. Follows STD protocol.
	 * 
	 */
	private function run() {
		socket_set_block($this->connection);
		$serverHalf = ((((mt_rand(1, 100)/100) * 99999999) << 32) + ((mt_rand(1, 100)/100) * 99999999));
		$data = $this->socket->read(2);
		$this->inStream->setStream($data);

		if($this->inStream->getUnsignedByte() != 14) {
			$this->log("Expected login Id 14 from client.");
			return;
		}


		$namePart = $this->inStream->getUnsignedByte();
		for($x = 0; $x < 8; $x++) {
			$this->socket->write(chr(0));
		}
		$this->socket->write(chr(0));

		$this->outStream->clear();

		$this->outStream->putLong($serverHalf);
		$this->socket->writeStream();

		$data = $this->socket->read(2);
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

		$data = $this->socket->read($loginPacketSize);
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

		$a1 = $this->inStream->getInt();
		$a2 = $this->inStream->getInt();
		$a3 = $this->inStream->getInt();
		$a4 = $this->inStream->getInt();
		$uid = $this->inStream->getInt();
		
		$username = strtolower($this->inStream->getString());
		$password = $this->inStream->getString();

		$this->setUsername($username);
		$this->setPassword($password);

		$this->outStream->clear();

		$isaacSeed = array($a1, $a2, $a3, $a4);

		$this->setDecryptor(new \Server\Cryption\ISAAC($isaacSeed));
		for($i = 0; $i < count($isaacSeed); $i++) {
			$isaacSeed[$i] += 50;
		}
		$this->setEncryptor(new \Server\Cryption\ISAAC($isaacSeed));
		$this->update = new PlayerUpdate($this);
		$this->login();
	}

	private function login() {
		$response = 0;

		try {
			$exists = $this->sql->getCount("players", array('username', 'password'), 
				array($this->getUsername(), $this->getPassword()));
		} catch(\PDOException $e) {
			$this->log($e->getMessage());
		}

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
			if( strtolower($player->getUsername()) == strtolower($this->getUsername()) ) {
				$response = 5;
				break;
			}
		}		

		$this->outStream->putByte($response)->putByte(0)->putByte(0);
		$this->socket->writeStream();

		$this->outStream->putHeader($this->getEncryptor(), 249)->putByteA(0)->putLEShortA(0);
		$this->socket->writeStream();

		$this->outStream->putHeader($this->getEncryptor(), 107);
		$this->socket->writeStream();

		$this->outStream->putHeader($this->getEncryptor(), 73)->putShortA(400)->putShort(400);
		$stream = $this->outStream->getStream();
		$this->socket->write($stream);

		if($response == 2) {
			$this->playerHandler->modActiveSessions(1);
			$this->playerHandler->addPlayer($this);
		}

		$this->socket->writeStream();

		$this->update->sendBlock();
		//$this->server->playerHandler->update($this, $this->outStream, $this->getEncryptor());
		
		$this->server->handleModules('__onLogin', $this);
	}

	public function getOutstream() {
		return $this->outStream;
	}

	public function getInstream() {
		return $this->inStream;
	}

	public function setUsername($s) {
		$this->username = $s;
	}

	public function setPassword($s) {
		$this->password = $s;
	}

	protected function setEncryptor($isaac) {
		$this->encryptor = $isaac;
	}

	protected function setDecryptor($isaac) {
		$this->decryptor = $isaac;
	}

	public function getEncryptor() {
		return $this->encryptor;
	}

	public function getDecryptor() {
		return $this->decryptor;
	}


	public function getUsername() {
		return $this->username;
	}

	public function getPassword() {
		return $this->password;
	}

	public function getLastPacket() {
		return $this->lastPacket;
	}

	public function getConnection() {
		return $this->connection;
	}

	public function getIP() {
		socket_getpeername($this->connection, $ip, $port);
		return array('ip' => $ip, 'port' => $port);
	}
}
?>
