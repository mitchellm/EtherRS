<?php
namespace Server\Network;

class Socket extends \Server\Server {
	protected $currentStream, $activeStreams = array();
	protected $currentSocket, $activeSockets = array();

	public function __construct(Stream $inStream = null, Stream $outStream = null, $conn = null, $name = null) {
		if($inStream != null && $outStream != null) {
			$this->addStream($inStream, $outStream, $name, true);
		}

		if(is_resource($conn)) {
			$this->addSocket($conn, $name, true);
		}
		$this->log("Socket server initialized");
	}


	/**
	 *
	 * Read data from a socket
	 *
	 * @param string $name  The name of the socket to read from
	 * @param int    $bytes How many bytes to read
	 *
	 */
	public function read($bytes, $name = null) {
		if($name === null) {
			$this->currentStream[0]->clear();
			$data = @socket_read($this->currentSocket, $bytes, PHP_BINARY_READ);
		} else {
			$this->activeStreams[$name][0]->clear();
			$data = @socket_read($this->activeSockets[$name], $bytes, PHP_BINARY_READ);
		}
		if(!$data) {
			$this->log($this->lastError($name === null ? $this->currentSocket : $name));
		}
		$data = unpack('C*', $data);
		return $data;
	}

	/**
	 *
	 * Write data to a socket
	 *
	 * @param string $name The name of the socket to write to
	 * @param mixed  $data The data to write
	 *
	 */
	public function write($data, $name = null) {
		if($name === null) {
			$this->currentStream[1]->clear();
			socket_write($this->currentSocket, $data);
		} else {
			if(!is_resource($this->activeSockets[$name])) {
				throw new Exception(__METHOD__ . ': Not a valid resource');
			}
			socket_write($this->activeSockets[$name], $data);
		}
	}

	/**
	 *
	 * Write data from a stream to a socket
	 *
	 * @param string $name The name of the stream to use
	 *
	 */
	public function writeStream($name = null) {
		if($name === null) {
			$stream = $this->currentStream[1]->getStream();
			$this->write($stream);
		} else {
			if(!isset($this->activeStreams[$name])) {
				throw new Exception(__METHOD__ . ': Not a valid stream');
			}
			$stream = $this->activeStreams[$name][1]->getStream();
			$this->write($stream);
		}
	}

	/**
	 *
	 * Add a socket to the current object
	 *
	 * @param Socket $socket    The socket to add
	 * @param string $name      The name to use
	 * @param bool   $setActive Should we set this socket to the current socket?
	 *
	 */
	public function addSocket($socket, $name = null, $setActive = false) {
		if(!is_resource($socket)) {
			throw new Exception(__METHOD__ . ': $socket is not a valid resource');
		}

		if($name !== null) {
			$this->activeSockets[$name] = $socket;
		} else {
			$this->activeSockets[] = $socket;
		}

		if($setActive === true) {
			$this->currentSocket = $socket;
		}
	}

	/**
	 *
	 * Add a stream to the current object
	 *
	 * @param Stream $stream    The stream to add
	 * @param string $name      The name to use
	 * @param bool   $setActive Should we use this stream as the current one?
	 *
	 */
	public function addStream(Stream $inStream, Stream $outStream, $name = null, $setActive = false) {
		if($name !== null) {
			$this->activeStreams[$name] = array($inStream, $outStream);
		} else {
			$this->activeStreams[] = array($inStream, $outStream);
		}

		if($setActive === true) {
			$this->currentStream = array($inStream, $outStream);
		}
	}

	/**
	 *
	 * Select a stream to use
	 *
	 * @param string $name The name of the stream
	 *
	 */
	public function selectStream($name) {
		if(!isset($this->activeStreams[$name]))
			return false;
		$this->currentStream = $this->activeStreams[$name];
	}

	/**
	 *
	 * Select a socket to use
	 *
	 * @param string $name The name of the socket
	 *
	 */
	public function selectSocket($name) {
		if(!isset($this->activeSockets[$name]))
			return false;
		$this->currentSocket = $this->activeSockets[$name];
	}

	/**
	 *
	 * Get the last error of the socket
	 *
	 * @param string $name The name of the socket
	 *
	 */
	public function lastError($name = null) {
		if($name === null) {
			return socket_last_error($this->currentSocket);
		} else {
			if(!isset($this->activeSockets[$name]))
				return false;
			return socket_last_error($this->activeSockets[$name]);
		}
	}
}
?>
