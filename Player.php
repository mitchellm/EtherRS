<?php
/**
 * @author Mitchell Murphy
 * @version 1.0.0
 */
require_once("Server.php");
class Player extends Server {
	protected $socket, $stream;

	public function __construct($sock) {
		$this->socket = $sock;
		$this->stream = new Stream();
	}

	public function process() {
		$debug = true;
		$returnCode = 2;
		$serverSessionKey = ((((mt_rand(1, 100)/100) * 99999999) << 32) + ((mt_rand(1, 100)/100) * 99999999));
		$clientSessionKey = 0;

		$this->log("SERVER SESSION KEY: " . $serverSessionKey, $debug);

		$data = socket_read($this->socket, 2, PHP_BINARY_READ);
		$byte_array = unpack('C*', $data);
		$this->stream->setStream($byte_array);

		if($this->stream->getUnsignedByte() != 14) {
			$this->log("Expected login Id 14 from client.");
			return;
		} else {
			$this->log("Login ID Validated!", $debug);
		}

		$namePart = $this->stream->getUnsignedByte();
		$this->log("namePart: " . $namePart, $debug);
		for($x = 0; $x < 8; $x++) {
			socket_write($this->socket, chr(0));
		}
		socket_write($this->socket, chr(0));

		$this->stream->clear();
		$this->stream->putLong($serverSessionKey);

		$stream = $this->stream->getStream();
		$string = $this->stream->packData($stream);

		$ssk = socket_write($this->socket, $string);
		$this->log("WRITING SESSION KEY: " . $ssk, $debug);

		$this->stream->setCurrentOffset(1);

		$data = socket_read($this->socket, 2, PHP_BINARY_READ);
		$byte_array = unpack('C*', $data);
		$this->stream->setStream($byte_array);
		
		$loginType = $this->stream->getUnsignedByte();
		
		$this->log("LoginType: " . $loginType, $debug);
		if($loginType != 16 && $loginType != 18) {
			$this->log("Unexpected login type " . $loginType);
			return;
		} 

		$loginPacketSize = $this->stream->getUnsignedByte();
		$loginEncryptPacketSize = $loginPacketSize - (36 + 1 + 1 + 2);
		if($loginEncryptPacketSize <= 0) {
			$this->log("Zero RSA packet size", $debug);
			return;
		}

		$this->log("LPKSIZE: " . $loginPacketSize . " _ " . $loginEncryptPacketSize, $debug);

		$data = socket_read($this->socket, $loginPacketSize, PHP_BINARY_READ);
		$byte_array = unpack('C*', $data);
		$this->stream->setStream($byte_array);
		$this->stream->setCurrentOffset(1);

		$m1 = $this->stream->getUnsignedByte();
		$m2 = $this->stream->getUnsignedShort();

		if($m1 != 255 || $m2 != 317) {
			$this->log("Wrong login packet magic ID (expected 255, 317)" . $m1 . " _ " . $m2);
			return;
		}	
		$lowMemVersion = $this->stream->getUnsignedByte();
		for($x = 0; $x < 9; $x++) {
			$this->stream->getInt();
		}
		$loginEncryptPacketSize--;

		$encryptSize = $this->stream->getUnsignedByte();
		if($loginEncryptPacketSize != $encryptSize) {
			$this->log($this->stream->getCurrentOffset());
			$this->log("Encrypted size mismatch! It's: " . $encryptSize);
			return;
		}

		$tmp = $this->stream->getUnsignedByte();
		if($tmp != 10) {
			$this->log("Encrypt packet Id was " . $tmp . " but expected 10");
		}

		$clientSessionKey = $this->stream->getLong();
		$serverSessionKey = $this->stream->getLong();
		$uid = $this->stream->getInt();

		$username = strtolower($this->stream->getString());
		$password = $this->stream->getString();

		$this->stream->clear();
		$this->stream->putByte(2);
		$this->stream->putByte(0);
		$this->stream->putByte(0);

		$stream = $this->stream->getStream();
		$string = $this->stream->packData($stream);

		$this->log("Stream STR: " . $string, $debug);

		$this->log("WRITING SOMETHING: " . socket_write($this->socket, $string), $debug);

		$this->log("Username: " . $username, $debug);
		$this->log("Password: " . $password, $debug);
		$this->log("UID: " . $uid, $debug);
		$this->log("=========================================", $debug);

	}
}