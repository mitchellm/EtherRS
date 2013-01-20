<?php
namespace Server\Cryption;

class ISAAC {
	protected $keyArrayIdx;
	protected $keySetArray = array();
	protected $cryptArray = array();
	protected $cryptVar1;
	protected $cryptVar2;
	protected $cryptVar3;


	public function __construct(array $ai) {
		$this->cryptArray = array();
		$this->keySetArray = array();
		$this->keySetArray = $ai;

		$this->initializeKeySet();
	}

	public function urshift($n, $s) {
		return ($n >= 0) ? ($n >> $s) :
			(($n & 0x7fffffff) >> $s) | 
				(0x40000000 >> ($s - 1));
	}  

	public function getNextKey() {
		if ($this->keyArrayIdx-- == 0) {
			$this->generateNextKeySet();
			$this->keyArrayIdx = 255;
		}

		return $this->keySetArray[$this->keyArrayIdx];
	}

	public function generateNextKeySet() {
		$this->cryptVar2 += ++$this->cryptVar3;
		for ($i = 0; $i < 256; $i++) {
			$j = $this->cryptArray[$i];
			if (($i & 3) == 0) {
				$this->cryptVar1 ^= $this->cryptVar1 << 13;
			} else if (($i & 3) == 1) {
				$this->cryptVar1 ^= $this->urshift($this->cryptVar1, 6);
			} else if (($i & 3) == 2) {
				$this->cryptVar1 ^= $this->cryptVar1 << 2;
			} else if (($i & 3) == 3) {
				$this->cryptVar1 ^= $this->urshift($this->cryptVar1, 16);
			}
			$this->cryptVar1 += $this->cryptArray[$i + 128 & 0xff];
			$k = 0;
			$this->cryptArray[$i] = $k = $this->cryptArray[($j & 0x3fc) >> 2] + $this->cryptVar1 + $this->cryptVar2;
			$this->keySetArray[$i] = $this->cryptVar2 = $this->cryptArray[($k >> 8 & 0x3fc) >> 2] + $j;
		}
	}

	public function initializeKeySet() {
		$l = $i1 = $j1 = $k1 = $l1 = $i2 = $j2 = $k2 = 0x9e3779b9;
		for ($i = 0; $i < 4; $i++) {
			$l ^= $i1 << 11;
			$k1 += $l;
			$i1 += $j1;
			$i1 ^= $this->urshift($j1, 2);
			$l1 += $i1;
			$j1 += $k1;
			$j1 ^= $k1 << 8;
			$i2 += $j1;
			$k1 += $l1;
			$k1 ^= $this->urshift($l1, 16);
			$j2 += $k1;
			$l1 += $i2;
			$l1 ^= $i2 << 10;
			$k2 += $l1;
			$i2 += $j2;
			$i2 ^= $this->urshift($j2, 4);
			$l += $i2;
			$j2 += $k2;
			$j2 ^= $k2 << 8;
			$i1 += $j2;
			$k2 += $l;
			$k2 ^= $this->urshift($l, 9);
			$j1 += $k2;
			$l += $i1;
		}

		for ($j = 0; $j < 256; $j += 8) {
			$l += $this->keySetArray[$j];
			$i1 += $this->keySetArray[$j + 1];
			$j1 += $this->keySetArray[$j + 2];
			$k1 += $this->keySetArray[$j + 3];
			$l1 += $this->keySetArray[$j + 4];
			$i2 += $this->keySetArray[$j + 5];
			$j2 += $this->keySetArray[$j + 6];
			$k2 += $this->keySetArray[$j + 7];
			$l ^= $i1 << 11;
			$k1 += $l;
			$i1 += $j1;
			$i1 ^= $this->urshift($j1, 2);
			$l1 += $i1;
			$j1 += $k1;
			$j1 ^= $k1 << 8;
			$i2 += $j1;
			$k1 += $l1;
			$k1 ^= $this->urshift($l1, 16);
			$j2 += $k1;
			$l1 += $i2;
			$l1 ^= $i2 << 10;
			$k2 += $l1;
			$i2 += $j2;
			$i2 ^= $this->urshift($j2, 4);
			$l += $i2;
			$j2 += $k2;
			$j2 ^= $k2 << 8;
			$i1 += $j2;
			$k2 += $l;
			$k2 ^= $this->urshift($l, 9);
			$j1 += $k2;
			$l += $i1;
			$this->cryptArray[$j] = $l;
			$this->cryptArray[$j + 1] = $i1;
			$this->cryptArray[$j + 2] = $j1;
			$this->cryptArray[$j + 3] = $k1;
			$this->cryptArray[$j + 4] = $l1;
			$this->cryptArray[$j + 5] = $i2;
			$this->cryptArray[$j + 6] = $j2;
			$this->cryptArray[$j + 7] = $k2;
		}

		for ($k = 0; $k < 256; $k += 8) {
			$l += $this->cryptArray[$k];
			$i1 += $this->cryptArray[$k + 1];
			$j1 += $this->cryptArray[$k + 2];
			$k1 += $this->cryptArray[$k + 3];
			$l1 += $this->cryptArray[$k + 4];
			$i2 += $this->cryptArray[$k + 5];
			$j2 += $this->cryptArray[$k + 6];
			$k2 += $this->cryptArray[$k + 7];
			$l ^= $i1 << 11;
			$k1 += $l;
			$i1 += $j1;
			$i1 ^= $this->urshift($j1, 2);
			$l1 += $i1;
			$j1 += $k1;
			$j1 ^= $k1 << 8;
			$i2 += $j1;
			$k1 += $l1;
			$k1 ^= $this->urshift($l1, 16);
			$j2 += $k1;
			$l1 += $i2;
			$l1 ^= $i2 << 10;
			$k2 += $l1;
			$i2 += $j2;
			$i2 ^= $this->urshift($j2, 4);
			$l += $i2;
			$j2 += $k2;
			$j2 ^= $k2 << 8;
			$i1 += $j2;
			$k2 += $l;
			$k2 ^= $this->urshift($l, 9);
			$j1 += $k2;
			$l += $i1;
			$this->cryptArray[$k] = $l;
			$this->cryptArray[$k + 1] = $i1;
			$this->cryptArray[$k + 2] = $j1;
			$this->cryptArray[$k + 3] = $k1;
			$this->cryptArray[$k + 4] = $l1;
			$this->cryptArray[$k + 5] = $i2;
			$this->cryptArray[$k + 6] = $j2;
			$this->cryptArray[$k + 7] = $k2;
		}

		$this->generateNextKeySet();
		$this->keyArrayIdx = 256;
	}
}