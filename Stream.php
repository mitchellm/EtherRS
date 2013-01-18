<?php
Class Stream {
	public $currentOffset = 1;
	public $array = array(), $bit_mask_out = array();

    public function __construct() {
        for($x = 0; $x < 32; $x++) {
            $this->bit_mask_out[$x] = (1 << $x) - 1;
        }
    }

    public function getUnsignedShort() {
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

    public function putLEShort($val) {
        $this->array[$this->currentOffset++] = $this->toByte($val);
        $this->array[$this->currentOffset++] = $this->toByte($val >> 8);
    } 

    public function putBits($numBits, $val) {
        $bytes = ceil((double) $numBits / 8) + 1;
        $bytePos = $this->currentOffset >> 3;
        $bitOffset = 8 - ($this->currentOffset & 7);
        $this->currentOffset += $numBits;

        for(; $numBits < $this->currentOffset; $this->currentOffset = 8) {
            $this->array[$bytePos] &= ~$this->bit_mask_out[$bitOffset];
            $this->array[$bytePos++] |= ($val >>  ($numBits-$this->currentOffset)) & $this->bit_mask_out[$bitOffset];
            $numBits -= $bitOffset;
        }

        if($numBit == $bitOffset) {
            $this->array[$bytePos] &= ~$this->bit_mask_out[$bitOffset];
            $this->array[$bytePos] |= $val &  $this->bit_mask_out[$bitOffset];
        } else {
            $this->array[$bytePos] &= ~($this->bit_mask_out[$numBits] << ($bitOffset - $numBits));
            $this->array[$bytePos] |= ($val & $this->bit_mask_out[$numBits]) << ($bitOffset - $numBits);
        }
    }

    public function putByteC($val) {
        $this->array[$this->currentOffset++] = $this->toByte(-$val);
    }

    public function putInt1($val) {
        $this->array[$this->currentOffset++] = $this->toByte($val >> 8);
        $this->array[$this->currentOffset++] = $this->toByte($val);
        $this->array[$this->currentOffset++] = $this->toByte($val >> 24);
        $this->array[$this->currentOffset++] = $this->toByte($val >> 16);
    }

    public function putInt2($val) {
        $this->array[$this->currentOffset++] = $this->toByte($val >> 16);
        $this->array[$this->currentOffset++] = $this->toByte($val >> 24);
        $this->array[$this->currentOffset++] = $this->toByte($val);
        $this->array[$this->currentOffset++] = $this->toByte($val >> 8);
    }

    public function putLEInt($val) {
        $this->array[$this->currentOffset++] = $this->toByte($val);
        $this->array[$this->currentOffset++] = $this->toByte($val >> 8);
        $this->array[$this->currentOffset++] = $this->toByte($val >> 16);
        $this->array[$this->currentOffset++] = $this->toByte($val >> 24);
    }
    
    public function putByteS($val) {
        $this->array[$this->currentOffset++] = $this->toByte(128 - $val);
    }    

    public function putTriByte($val) {
        $this->array[$this->currentOffset++] = $this->toByte($val >> 16);
        $this->array[$this->currentOffset++] = $this->toByte($val >> 8);
        $this->array[$this->currentOffset++] = $this->toByte($val);
    }

    public function putSmart($val) {
        if($val >= 128) {
            putShort($val + 32768);
        } else {
            putByte(toByte($val));
        }
    }

    public function putSignedSmart($val) {
        if($val >= 128) {
            putShort($val + 49152);
        } else {
            putByte(toByte($val + 64));
        }
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