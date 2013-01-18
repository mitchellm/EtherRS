<?php
require_once 'Stream.php';

class Player {
	private static $inst;
	private $socket;

	public function __construct($socket) {
		$this->socket = $socket;
		$this->stream = new Stream();
		$this->login();
	}

	public static function getInstance($socket) {
		if(!self::$inst) {
			self::$inst = new Player($socket);
		}
		return self::$inst;
	}

	public static function serverOutput($str) {
		printf("\n [SERVER] " . $str);
	}

	private function login() {
		$returnCode = 2; 
		$serverSessionKey = 1; 
		$clientSessionKey = 0;

		$data = socket_read($this->socket, 2, PHP_BINARY_READ);
		$byte_array = unpack('C*', $data);
		$this->stream->setStream($byte_array);

		if($this->stream->readUnsignedByte() != 14) {
			Player::serverOutput("Expected login Id 14 from client.");
			return;
		} else {
			Player::serverOutput("Login ID Validated!");
		}

		$namePart = $this->stream->readUnsignedByte();
		for($x = 0; $x < 8; $x++) {
			socket_write($this->socket, 0);
		}

		socket_write($this->socket, 255);
		socket_write($this->socket, $this->stream->writeQWord($serverSessionKey));

		$data = socket_read($this->socket, 2, PHP_BINARY_READ);
		$byte_array = unpack('C*', $data);
		$this->stream->setStream($byte_array);

		$loginType = $this->stream->readUnsignedByte();
		
		Player::serverOutput("LoginType: " . $loginType);
		if($loginType != 16 && $loginType != 18) {
			Player::serverOutput("Unexpected login type " . $loginType);
			return;
		} 
		$loginPacketSize = $this->stream->readUnsignedByte();
		$loginEncryptPacketSize = $loginPacketSize - (36 + 1 + 1 + 2);
		if($loginEncryptPacketSize <= 0) {
			Player::serverOutput("Zero RSA packet size");
			return;
		}
		$this->stream->setStream($loginPacketSize);
		if($this->stream->readUnsignedByte() != 255 || $this->stream->readUnsignedWord() != 317) {
			Player::serverOutput("Wrong login packet magic ID (expected 255, 317)");
			return;
		}	
		$lowMemVersion = $this->stream->readUnsignedByte();
		for($x = 0; $x < 9; $x++) {
			$this->stream->readDword();
		}
		$loginEncryptPacketSize--;
		$tmp = $this->stream->readUnsignedByte();
		if($tmp != 10) {
			Player::serverOutput("Encrypt packet Id was " . $tmp . " but expected 10");
		}

		$clientSessionKey = $this->stream->readQWord();
		$serverSessionKey = $this->stream->readQWord();
		$uid = $this->stream->readDWord();

		$username = strtolower($this->stream->readString());
		$password = $this->stream->readString();
		socket_write($this->socket, 2);
		socket_write($this->socket, 0);
		socket_write($this->socket, 0);
		fflush($this->socket);
	}
}
?>