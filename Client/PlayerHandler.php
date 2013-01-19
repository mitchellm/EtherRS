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

class PlayerHandler extends \Server\Server {
	protected $players = array();

	public function __construct() {
		$this->log('Player handler loaded');
	}

	public function addClient($socket, \Server\Server $server) {
		$server->inStream->clear();
		$this->players[] = $socket;
		$index = count($this->players);
		$this->log("Client " . $index . " has connected!");

		$debug = false;
		$socket = $this->players[$index - 1];

		$returnCode = 2;
		$serverSessionKey = ((((mt_rand(1, 100)/100) * 99999999) << 32) + ((mt_rand(1, 100)/100) * 99999999));
		$clientSessionKey = 0;

		$data = socket_read($socket, 2, PHP_BINARY_READ);
		$byte_array = unpack('C*', $data);
		$server->inStream->setStream($byte_array);

		if($server->inStream->getUnsignedByte() != 14) {
			$this->log("Expected login Id 14 from client.");
			return;
		} else {
			$this->log("Login ID Validated!", $debug);
		}

		$namePart = $server->inStream->getUnsignedByte();
		for($x = 0; $x < 8; $x++) {
			socket_write($socket, chr(0));
		}
		socket_write($socket, chr(0));

		$server->inStream->clear();
		$server->inStream->putLong($serverSessionKey);

		$stream = $server->inStream->getStream();
		$string = $this->packData($stream);

		socket_write($socket, $string);

		$server->inStream->setCurrentOffset(1);

		$data = socket_read($socket, 2, PHP_BINARY_READ);
		$byte_array = unpack('C*', $data);
		$server->inStream->setStream($byte_array);

		$loginType = $server->inStream->getUnsignedByte();
		if($loginType != 16 && $loginType != 18) {
			$this->log("Unexpected login type " . $loginType);
			return;
		}

		$loginPacketSize = $server->inStream->getUnsignedByte();
		$loginEncryptPacketSize = $loginPacketSize - (36 + 1 + 1 + 2);
		if($loginEncryptPacketSize <= 0) {
			$this->log("Zero RSA packet size", $debug);
			return;
		}

		$data = socket_read($socket, $loginPacketSize, PHP_BINARY_READ);
		$byte_array = unpack('C*', $data);
		$server->inStream->setStream($byte_array);
		$server->inStream->setCurrentOffset(1);

		$m1 = $server->inStream->getUnsignedByte();
		$m2 = $server->inStream->getUnsignedShort();

		if($m1 != 255 || $m2 != 317) {
			$this->log("Wrong login packet magic ID (expected 255, 317)" . $m1 . " _ " . $m2);
			return;
		}	
		$lowMemVersion = $server->inStream->getUnsignedByte();
		for($x = 0; $x < 9; $x++) {
			$server->inStream->getInt();
		}
		$loginEncryptPacketSize--;

		$encryptSize = $server->inStream->getUnsignedByte();
		if($loginEncryptPacketSize != $encryptSize) {
			$this->log($server->inStream->getCurrentOffset());
			$this->log("Encrypted size mismatch! It's: " . $encryptSize);
			return;
		}

		$tmp = $server->inStream->getUnsignedByte();
		if($tmp != 10) {
			$this->log("Encrypt packet Id was " . $tmp . " but expected 10");
		}

		$clientSessionKey = $server->inStream->getLong();
		$serverSessionKey = $server->inStream->getLong();
		$uid = $server->inStream->getInt();

		$username = strtolower($server->inStream->getString());
		$password = $server->inStream->getString();

		$server->inStream->clear();
		$server->inStream->putByte(2);
		$server->inStream->putByte(0);
		$server->inStream->putByte(0);

		$stream = $server->inStream->getStream();
		$string = $this->packData($stream);

		socket_write($socket, $string);
	}

	public function packData($resource) {
		$string = "";
		foreach ($resource as $chr) {
			$string .= chr($chr);
		}
		return $string;
	}
}
?>