<?php

// functions prime() and factor() both use a slightly optimized version
// of the prime brute force test, where factors of 2 are checked for first,
// and then the test loop runs through all odd iterations of integer factors
// starting at 3.

// it is important to note here that these functions will return a result
// faster than qComposite or qFactor can on the first call.  Therefore,
// for handling a small amount of prime factoring these functions work
// well and can determine a 63-bit prime like 9223372036854775783 in about
// 60 seconds.  The largest allowed test integer is 2^63-1.

function prime ($n) {

	// test for invalid parameters
	if (!is_int ($n) || $n <= 1) return 0;

	// test for factors of 2
	if ( $n	     === 2) return 1;
	if (($n & 1) === 0) return 0;

	// test for odd factors greater than 2
	for ($i = 3; $i * $i <= $n; $i += 2)

		// divide into every value up to the square root of $n
		if ($n % $i === 0)

			// early exit if divisible
			return 0;

	// $n is prime
	return 1;

}

function factor ($n) {

	// initialize a return buffer for factors
	$result = [];

	// validate the parameters
	if (is_int ($n) && $n > 1) {

		// find factors of 2
		while ((1 & $n) === 0) {

			$n	>>= 1;
			$result []= 2;

		}

		// find prime factors greater than 2
		for ($i = 3; $i * $i <= $n; $i += 2)

			while ($n % $i === 0) {
	
				$n	 /= $i;
				$result []= $i;

			}

		// if $n is greater than 1, it is a prime factor
		if ($n > 1) $result [] = $n;

	}
	return $result;

}


?>