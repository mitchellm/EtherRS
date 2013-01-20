<?php
namespace Server\Cryption\ISAAC;

class ISAAC {
	protected $memory = array(), $results = array();
	protected $count, $accumulator, $lastResult, $counter;

	public function __construct($seed)
	{
		//for(int x = 0; x < seed.length; x++)
			//System.out.println(seed[x]);
		$this->$memory = array_fill(0, 256, 0);
		$this->$results = array_fill(0, 256, 0);
		for($i = 0; $i < 4; $i++) {
			$this->$results[$i] = $seed[$i];
		}
		$this->initializeKeySet();
	}

		function urshift($x, $n){
			$mask = 0x40000000;
			if ($x < 0){
				$x &= 0x7FFFFFFF;
				$mask = $mask >> ($n-1);
				$ret = ($x >> $n) | $mask;
				$ret = str_pad(decbin($ret), 32, '0', STR_PAD_LEFT);
				$ret[0] = '1';
				$ret = bindec($ret);
			}
			else{
				$ret = (int)$x >> (int)$n;
			}
			return $ret;
		}

	public function getNextKey()
	{
		if($this->count-- == 0)
		{
			$this->isaac();
			$this->count = 255;
		}
		//System.out.println("Key "  + results[count]);
		return $this->results[$this->count];
		//return 1;
	}

	private function isaac()
	{
		$this->lastResult += ++$this->counter;
		for($i = 0; $i < 256; $i++)
		{
			$j = $this->memory[$i];
			if(($i & 3) == 0)
				$this->accumulator ^= $this->accumulator << 13;
			else
			if(($i & 3) == 1)
				$this->accumulator ^= $this->urshift($this->accumulator, 6);
			else
			if(($i & 3) == 2)
				$this->accumulator ^= $this->accumulator << 2;
			else
			if(($i & 3) == 3)
				$this->accumulator ^= $this->urshift($this->accumulator, 16);
			$accumulator += $this->memory[$i + 128 & 0xff];
			$k;
			$this->memory[i] = $k = $this->memory[($j & 0x3fc) >> 2] + $this->accumulator + $this->lastResult;
			$this->results[i] = $this->lastResult = $this->memory[($k >> 8 & 0x3fc) >> 2] + $j;
		}

	}

	private function initializeKeySet()
	{
		$golden_ratio = 0x9e3779b9;
		$l = $golden_ratio;
		$i1 = $golden_ratio;
		$j1 = $golden_ratio;
		$k1 = $golden_ratio;
		$l1 = $golden_ratio;
		$i2 = $golden_ratio;
		$j2 = $golden_ratio;
		$k2 = $golden_ratio;
		for($i = 0; $i < 4; $i++)
		{
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

		for($j = 0; $j < 256; $j += 8)
		{
			$l += $this->results[$j];
			$i1 += $this->results[$j + 1];
			$j1 += $this->results[$j + 2];
			$k1 += $this->results[$j + 3];
			$l1 += $this->results[$j + 4];
			$i2 += $this->results[$j + 5];
			$j2 += $this->results[$j + 6];
			$k2 += $this->results[$j + 7];
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
			$k2 ^= $this->urshift($l, 9);;
			$j1 += $k2;
			$l += $i1;
			$this->memory[$j] = $l;
			$this->memory[$j + 1] = $i1;
			$this->memory[$j + 2] = $j1;
			$this->memory[$j + 3] = $k1;
			$this->memory[$j + 4] = $l1;
			$this->memory[$j + 5] = $i2;
			$this->memory[$j + 6] = $j2;
			$this->memory[$j + 7] = $k2;
		}

		for($k = 0; $k < 256; $k += 8)
		{
			$l += $this->memory[$k];
			$i1 += $this->memory[$k + 1];
			$j1 += $this->memory[$k + 2];
			$k1 += $this->memory[$k + 3];
			$l1 += $this->memory[$k + 4];
			$i2 += $this->memory[$k + 5];
			$j2 += $this->memory[$k + 6];
			$k2 += $this->memory[$k + 7];
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
			$this->memory[$k] = $l;
			$this->memory[$k + 1] = $i1;
			$this->memory[$k + 2] = $j1;
			$this->memory[$k + 3] = $k1;
			$this->memory[$k + 4] = $l1;
			$this->memory[$k + 5] = $i2;
			$this->memory[$k + 6] = $j2;
			$this->memory[$k + 7] = $k2;
		}

		$this->isaac();
		$this->count = 256;
	}
}
