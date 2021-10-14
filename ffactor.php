<?php

function get_value ($s) {
	
	switch (substr ($s, -1)) {
		
		case 'K': case 'k': return (int) $s * 1024;
		case 'M': case 'm': return (int) $s * 1048576;
		case 'G': case 'g': return (int) $s * 1073741824;
		default: return (int) $s;
		
	}
}

function get_free_mem () {
	
	// never return value less than zero
	return max (0, get_value (ini_get ("memory_limit")) - memory_get_usage ());
	
}

// index to the last prime
const END_PRIME_OFFSET	= 0x22D7F0FC;	//	end of meaningful part of prime number list
const TOP_PRIME		= 0xB504F33B;	//	valid throughout the root of the 63-bit positive integer domain
	
class Prime {

	// the prime list
	private $pList;
	public $type_bitmap;
	public function __construct ($test_bitmap) {
		
		// only push bits 0 and 1 thru
		$this -> type_bitmap = $test_bitmap & 3;
		
		// calculate the RAM available- determine if enough to load prime table
		if (($test_bitmap & 4) && (get_free_mem () >= 2411724800)) {

			// if primes.bin is absent, make one
			if (! is_file ("primes.bin") || (filesize ("primes.bin") < END_PRIME_OFFSET))
		
				// build the primes to the top
				qFactor (9223372036854775783, 0);

			// primes list is created
			$this -> pList =  new SplFixedArray (END_PRIME_OFFSET >> 2);
			$i = 0;		// list pointer

			// prime list file is opened
			$fPrimes = fopen ("primes.bin", "rb");

			// the for loop is necessary to break up the loading of the prime table due to its size.
			// 65536 iterations is tuned for best performance and least stack memory consumption
			for ($j = 0; $j < 65536; $j ++)
		
				foreach (unpack ("V*",  fread ($fPrimes, END_PRIME_OFFSET >> 16 & -4)) as $n)
		
					$this -> pList [$i ++] = $n;

			// this loop loads the remainder of the primes
			foreach (unpack ("V*",  fread ($fPrimes, END_PRIME_OFFSET & 262140)) as $n)
	
				$this -> pList [$i ++] = $n;	
	
			// close primes.bin
			fclose ($fPrimes);
			
			// push all bitmap bits thru
			$this -> type_bitmap = $test_bitmap & 7;
		
		}
		// sets $type_bitmap to 1 if it is 0
		$this -> type_bitmap |= (int) ! $this -> type_bitmap;
		
	}
	
	public function factor ($n, $find_factors = 1) {
	
		// test for invalid parameters
		if (!is_int ($n) || $n <= 1) return [];
	
		// run through entire prime list
		foreach ($this -> pList as $prime) {
		
			// if $n is smaller than the square of the test prime, then n is also prime and factoring is done
			if ($n < $prime * $prime) {

				// $n is prime when greater than 1
				if ($n > 1)

					// push the last prime on the result factor list
					$result [] = $n;

				// return found factors
				return $result;

			}
			// count how many times we can factor a prime composite
			while ($n % $prime === 0) {

				// push the prime on the result factor list
				$result [] = $prime;

				// exit if factors not required
				if ($find_factors === 0) {

					// push a dummy value to indicate not prime
					$result [] = 1;
					return $result;

				}
				// update the test value
				$n /= $prime;

			}	
		}
		return $result;

	}

	public function test ($n) {

	
		return (int) (count ($this -> factor ($n, 0)) === 1);
	
	}
	
	// returns the prime value for the given $index list position
	public function indexLookup ($index) {
		
		// bounds check
		if (is_int ($index) && (0 <= $index) && ($index < (END_PRIME_OFFSET >> 2)))

			return $this -> pList [$index];
			
		return -1;
			
	}
	
	// returns the first prime index position found with a value equal to or greater than for the given value.
	public function valueLookup ($value) {
		
		// bounds check
		if (is_int ($index) && (2 <= $value) && ($value <= TOP_PRIME))
			
			// scan through prime list
			foreach ($this -> pList as $prime)
			
				// is the prime greater than or equal to given value?
				if ($prime >= $value)
					
					// return the table index
					return key ($this -> pList);
		
		return -1;
			
	}
	// splits a base-10 number of left-justified prime numbers
	public function decimal_split ($n, $decompose = 0, $type = 0) {

		// allocate result array
		$result = [];

		// keep track of last prime number test
		$p = 0;

		// decimal values extracted from $n are stored in $test_value
		$test_value;

		// set the left string character index to the leftmost position
		$i = 0;

		// loop the right string index to find primes
		for ($j = 1; $j <= strlen ($n); $j ++) {

			// get the value of the selected string range
			$test_value = (int) substr ($n, $i, $j - $i);

			// use the method for factoring as specified by $type
			switch ($type) {
			
				case 0: $p = prime		($test_value); break;
				case 1: $p = qprime		($test_value); break;
				case 2: $p = $this -> test	($test_value);
			
			}
			// skip to add another digit if not prime
			if (!$p) continue;

			// record result
			$result [] = $test_value;

			// set the new starting string position
			$i = $j;

		}
		// if $p is prime the split was successful
		if ($p) return $result;

		// return a null array if factoring is not requested
		if (!$decompose) return [];

		// add the non-prime factors to result buffer
		switch ($type) {
			
			case 0: return array_merge ($result, factor		($test_value));
			case 1: return array_merge ($result, qFactor		($test_value));
			case 2: return array_merge ($result, $this -> factor	($test_value));
			
		}
	}
	// a unit and functional test for decimal_prime_split()
	public function decimal_split_test ($magnitude = 2, $iterations = 1, $show_proof = 1) {

		// clear out the $type_time array
		$type_time;
		for ($type = 0; $type < 3; $type ++) {

			$type_time [$type] ["time"  ] = 0;
			$type_time [$type] ["errors"] = 0;
	
		}
		// test_override mode if $iterations == 0
		$test_override = (int) (!$iterations);
		$iterations |= $test_override;		// set iterations = 1 in test_override mode
	
		// run through the iterations
		for ($i = 0; $i < $iterations; $i ++) {

			// determine if test running in discrete mode
			if ($test_override)
			
				// use magnitude as the test number
				$sav = $magnitude;
			
			else
				// get a random number
				$sav = random_int (2, (1 << $magnitude) - 1);
		
			// run through the types
			for ($type = 0; $type < 3; $type ++) {
			
				$r	= 1;
				$rem	= 0;
				$split	= 1;
				$src	= $sav;
				$output = $src . " => ";

			
				if (!($this -> type_bitmap >> $type & 1)) continue;
				foreach ($this -> decimal_split ($src, 1, $type) as $n) {

					if (!$split) {

						// multiply and accumulate
						$output .= " * " . $n;
						$r *= $n;
						continue;

					}
					// get the amount of significant digits in $n
					$size = strlen ($n);

					// compare the 2 numbers for equality
					if ($n == substr ($src, 0, $size)) {

						// echo out the result
						$output .= $n . ". ";

						// advance the test string
						$src = substr ($src, $size);

						// determine the remaining digits in source string
						$rem = strlen ($src);

						// delete leading zeros
						$src = ltrim ($src, "0");
						continue;

					}
					$output .= " (" . $n;
					$r	 = $n;
					$split	 =  0;

				}
				if (!$split) {

					// compare the accumulated product to the leftover string
					$output .= ") = " . $r;
					if ($r != substr ($src, 0, strlen ($r))) {

						$output .= "ERR0";
						$type_time [$type] ["errors"] ++;

					}
				}
				// if the split works, make sure the leftover string is empty
				elseif ($rem) {

					$output .= "can't factor last 0 or 1";
					$split	 = 0;

				}
				// test our result against the decimal split in no decomposition mode and measure the elapsed time
				$t = hrtime (true);
				$r = count ($this -> decimal_split ($sav, 0, $type));
				$t = hrtime (true) - $t;
				$type_time [$type] ["time"] += $t;
				$output .= "; time (ns): " . $t . "; ";
			
				// count any errors in comparing behaviour
				if ($r > 0 && $split === 0) {

					$output .= "ERR1";
					$type_time [$type] ["errors"] ++;

				} elseif ($r === 0 && $split === 1) {

					$output .= "ERR2";
					$type_time [$type] ["errors"] ++;

				} elseif ($split)

					$output .= "<b>perfect prime split!!</b>";

				$output .= "<br/>";
				if ($show_proof) echo $output;
			
			}
		}
		if ($show_proof) echo "<br/>";
		return $type_time;

	}
}
?>