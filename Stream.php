<?php
namespace Server;

Class Stream {
	public $currentOffset = 1;
	public $array = array();

	public function getUnsignedShort() {
		$this->currentOffset += 2;
		return (($this->array[$this->currentOffset - 2] & 0xff) << 8) + ($this->array[$this->currentOffset - 1] & 0xff);
	}

	public function printArray() {
		var_dump($this->array);
	}

	public function clear() {
		$this->currentOffset = 1;
		$this->setStream(array());
	}

	public function getStream() {
		return $this->array;
	}

	public function getCurrentOffset() {
		return $this->currentOffset;
	}

	public function setStream($arr) {
		$this->array = $arr;
	}

	public function putShort($i) {
		$this->array[$this->currentOffset++] = $this->toByte($i >> 8);
		$this->array[$this->currentOffset++] = $this->toByte($i);
	}

	public function toByte($val) {
		return ((($val+128) % 256) - 128);
	}

	public function setCurrentOffset($offset) {
		$this->currentOffset = $offset;
	}

	public function putByte($i) {
		$this->array[$this->currentOffset++] = $this->toByte($i);
	}

	public function putInt($i) {
		$this->array[$this->currentOffset++] = $this->toByte($i >> 24);
		$this->array[$this->currentOffset++] = $this->toByte($i >> 16);
		$this->array[$this->currentOffset++] = $this->toByte($i >> 8);
		$this->array[$this->currentOffset++] = $this->toByte($i);
	}

	public function putLong($l) {
		$this->array[$this->currentOffset++] = $this->toByte($l >> 56);
		$this->array[$this->currentOffset++] = $this->toByte($l >> 48);
		$this->array[$this->currentOffset++] = $this->toByte($l >> 40);
		$this->array[$this->currentOffset++] = $this->toByte($l >> 32);
		$this->array[$this->currentOffset++] = $this->toByte($l >> 24);
		$this->array[$this->currentOffset++] = $this->toByte($l >> 16);
		$this->array[$this->currentOffset++] = $this->toByte($l >> 8);
		$this->array[$this->currentOffset++] = $this->toByte($l);
	}

	public function putShortA($val) {
		$this->array[$this->currentOffset++] = $this->toByte($val >> 8);
		$this->array[$this->currentOffset++] = $this->toByte($val + 128);
	}

	public function putByteA($val) {
		$this->array[$this->currentOffset++] = $this->toByte($val + 128);
	}

	public function putLEShortA($val) {
		$this->array[$this->currentOffset++] = $this->toByte($val + 128);
		$this->array[$this->currentOffset++] = $this->toByte($val >> 8);
	}

	public function putString($s) {
		$max = currentOffset + strlen($s);
		for($i = $this->currentOffset; $i < $max; $i++) {
			$this->array[$this->currentOffset + $i] = $s[$i];
		}
		$this->currentOffset += strlen($s);
		$this->array[$this->currentOffset++] = 10;
	}

	public function getUnsignedByte() {
		return $this->array[$this->currentOffset++] & 0xff;
	}

	public function get() {
		return $this->array[$this->currentOffset++];
	}

	public function getSignedInt() {
		$this->currentOffset += 2;
		$i = (($this->array[$this->currentOffset - 2] & 0xff) << 8) + ($this->array[$this->currentOffset - 1] & 0xff);
		if ($i > 32767) {
			$i -= 0x10000;
		}
		return $i;
	}

	public function getInt() {
		$this->currentOffset += 4;

		return (($this->array[$this->currentOffset - 4] & 0xff) << 24) +
		(($this->array[$this->currentOffset - 3] & 0xff) << 16) + (($this->array[$this->currentOffset - 2] & 0xff) << 8) +
		($this->array[$this->currentOffset - 1] & 0xff);
	}

	public function getLong() {
		$l = $this->toLong($this->getInt() & 0xffffffff);
		$l1 = $this->toLong($this->getInt() & 0xfffffff);
		return ($l << 32) + $l1;
	}

	public function toLong($val) {
		return (($val + 1) / 2);
	}

	public function getString() {
		$string = "";
		while($this->array[$this->currentOffset++] != 10) {
			$string .= chr($this->array[$this->currentOffset - 1]);
		}
//var_dump($string);
		return $string;
	}

	public function getBytes($abyte0, $i, $j) {
		for ($k = $j; $k < $j + $i; $k++) {
			$abyte0[$k] = $this->array[$this->currentOffset++];
		}
	}
}
?>