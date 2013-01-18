<?php

Class Stream {
	public $currentOffset = 1;
	public $array = array();

    public function readUnsignedWord() {
        $this->currentOffset += 2;
        return (($this->array[$this->currentOffset - 2] & 0xff) << 8) + ($this->array[$this->currentOffset - 1] & 0xff);
    }

    public function printArray() {
        var_dump($this->array);
    }

    public function clear() {
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

    //write short
	public function writeWord($i) {
        $this->array[$this->currentOffset++] = $this->toByte($i >> 8);
        $this->array[$this->currentOffset++] = $this->toByte($i);
    }

    public function toByte($val) {
    	return ((($val+128) % 256) - 128);
    }

    public function setCurrentOffset($offset) {
    	$this->currentOffset = $offset;
    }

    public function writeByte($i) {
    	$this->array[$this->currentOffset++] = $this->toByte($i);
		}

    //int
    public function writeDWord($i) {
        $this->array[$this->currentOffset++] = $this->toByte($i >> 24);
        $this->array[$this->currentOffset++] = $this->toByte($i >> 16);
        $this->array[$this->currentOffset++] = $this->toByte($i >> 8);
        $this->array[$this->currentOffset++] = $this->toByte($i);
    }
 
    //long
    public function writeQWord($l) {
        $this->array[$this->currentOffset++] = $this->toByte($l >> 56);
        $this->array[$this->currentOffset++] = $this->toByte($l >> 48);
        $this->array[$this->currentOffset++] = $this->toByte($l >> 40);
        $this->array[$this->currentOffset++] = $this->toByte($l >> 32);
        $this->array[$this->currentOffset++] = $this->toByte($l >> 24);
        $this->array[$this->currentOffset++] = $this->toByte($l >> 16);
        $this->array[$this->currentOffset++] = $this->toByte($l >> 8);
        $this->array[$this->currentOffset++] = $this->toByte($l);
    }


    public function writeString($s) {
    	$max = currentOffset + strlen($s);
    	for($i = $this->currentOffset; $i < $max; $i++) {
    		$this->array[$this->currentOffset + $i] = $s[$i];
    	}
    	$this->currentOffset += strlen($s);
        $this->array[$this->currentOffset++] = 10;
    }

    public function readUnsignedByte() {
        return $this->array[$this->currentOffset++] & 0xff;
    }
 
    public function readSignedByte() {
        return $this->array[$this->currentOffset++];
    }

    //signed int
    public function readSignedWord() {
        $this->currentOffset += 2;
        $i = (($this->array[$this->currentOffset - 2] & 0xff) << 8) + ($this->array[$this->currentOffset - 1] & 0xff);
        if ($i > 32767) {
            $i -= 0x10000;
        }
        return $i;
    }
 
    //int
    public function readDWord() {
        $this->currentOffset += 4;

        return (($this->array[$this->currentOffset - 4] & 0xff) << 24) + 
        (($this->array[$this->currentOffset - 3] & 0xff) << 16) + (($this->array[$this->currentOffset - 2] & 0xff) << 8) + 
        ($this->array[$this->currentOffset - 1] & 0xff);
    }
 
    public function readQWord() {
        $l = $this->toLong($this->readDWord() & 0xffffffff);
        $l1 = $this->toLong($this->readDWord() & 0xfffffff);
        return ($l << 32) + $l1;
    }

    public function toLong($val) {
    	return (($val + 1) / 2);
    }
 
    public function readString() {
        $string = "";
        while($this->array[$this->currentOffset++] != 10) {
            $string .= chr($this->array[$this->currentOffset - 1]);
        }
        //var_dump($string);
        return $string;
    }

    public function readBytes($abyte0, $i, $j) {
        for ($k = $j; $k < $j + $i; $k++) {
            $abyte0[$k] = $this->array[$this->currentOffset++];
        }
    }
}
?>