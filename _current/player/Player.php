<?php
require_once dirname(__FILE__) . '/../networking/Stream.php';
require_once dirname(__FILE__) . '/../Server.php';

class Player {
	private $socket;

	public function __construct($socket) {
		$this->socket = $socket;
		$this->stream = new Stream();
		$this->login();
	}

	public static function packData($resource) {
		$string = "";
		foreach ($resource as $chr) {
			$string .= chr($chr);
		}
		return $string;
	}

	private function login() {
		$returnCode = 2;
		$serverSessionKey = ((((mt_rand(1, 100)/100) * 99999999) << 32) + ((mt_rand(1, 100)/100) * 99999999));
		$clientSessionKey = 0;

		Server::out("SERVER SESSION KEY: " . $serverSessionKey);

		$data = socket_read($this->socket, 2, PHP_BINARY_READ);
		$byte_array = unpack('C*', $data);
		$this->stream->setStream($byte_array);

		if($this->stream->readUnsignedByte() != 14) {
			Server::out("Expected login Id 14 from client.");
			return;
		} else {
			Server::out("Login ID Validated!");
		}

		$namePart = $this->stream->readUnsignedByte();
		Server::out($namePart);
		for($x = 0; $x < 8; $x++) {
			socket_write($this->socket, chr(0));
		}
		socket_write($this->socket, chr(0));

		$this->stream->clear();
		$this->stream->writeQWord($serverSessionKey);

		$stream = $this->stream->getStream();
		$string = Player::packData($stream);

		Server::out("WRITING SESSION KEY: " . socket_write($this->socket, $string));

		$this->stream->setCurrentOffset(1);

		$data = socket_read($this->socket, 2, PHP_BINARY_READ);
		$byte_array = unpack('C*', $data);
		$this->stream->setStream($byte_array);
		
		$loginType = $this->stream->readUnsignedByte();
		
		Server::out("LoginType: " . $loginType);
		if($loginType != 16 && $loginType != 18) {
			Server::out("Unexpected login type " . $loginType);
			return;
		} 

		$loginPacketSize = $this->stream->readUnsignedByte();
		$loginEncryptPacketSize = $loginPacketSize - (36 + 1 + 1 + 2);
		if($loginEncryptPacketSize <= 0) {
			Server::out("Zero RSA packet size");
			return;
		}

		Server::out("LPKSIZE: " . $loginPacketSize . " _ " . $loginEncryptPacketSize);

		$data = socket_read($this->socket, $loginPacketSize, PHP_BINARY_READ);
		$byte_array = unpack('C*', $data);
		$this->stream->setStream($byte_array);
		$this->stream->setCurrentOffset(1);

		$m1 = $this->stream->readUnsignedByte();
		$m2 = $this->stream->readUnsignedWord();

		if($m1 != 255 || $m2 != 317) {
			Server::out("Wrong login packet magic ID (expected 255, 317)" . $m1 . " _ " . $m2);
			return;
		}	
		$lowMemVersion = $this->stream->readUnsignedByte();
		for($x = 0; $x < 9; $x++) {
			$this->stream->readDword();
		}
		$loginEncryptPacketSize--;

		$encryptSize = $this->stream->readUnsignedByte();
		if($loginEncryptPacketSize != $encryptSize) {
			Server::out($this->stream->getCurrentOffset());
			Server::out("Encrypted size mismatch! It's: " . $encryptSize);
			return;
		}

		$tmp = $this->stream->readUnsignedByte();
		if($tmp != 10) {
			Server::out("Encrypt packet Id was " . $tmp . " but expected 10");
		}

		$clientSessionKey = $this->stream->readQWord();
		$serverSessionKey = $this->stream->readQWord();
		$uid = $this->stream->readDWord();

		$username = strtolower($this->stream->readString());
		$password = $this->stream->readString();

		$this->stream->clear();
		$this->stream->writeByte(2);
		$this->stream->writeByte(0);
		$this->stream->writeByte(0);

		$stream = $this->stream->getStream();
		$string = Player::packData($stream);

		Server::out("Stream STR: " . $string);

		Server::out("WRITING SOMETHING: " . socket_write($this->socket, $string));


		Server::out("Username: " . $username);
		Server::out("Password: " . $password);
		Server::out("UID: " . $uid);
		Server::out("=========================================");
	}
}
?>