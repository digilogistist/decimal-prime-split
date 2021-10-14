<?php

/*

qFactor breaks numbers down into their primes and builds a list of factors with exponents.
Moreover, it builds a list of primes up to the root of the test value, and is saved for later.
It also uses a hybrid Sieve of Eratosthenes algorithm combined with a prime modulus counting
system to significantly speed up the finding of primes while using a flexible amount of memory
for the Sieve render calculations.

The Sieve of Eratosthenes is a common algorithm for finding primes quickly.
Using a bitmap table representing the integer domain of potential primes starting
from 2 up to the root of the test value, the table is initially filled with 1s to
indicate all numbers are primes. The table is then scanned in a linear fashion for
any set bits, as this indicates a found prime number. The period of the found prime
number is then rendered out over the rest of the table and clears any set bits in the
bitmap table as it cycles.  This process continues as the table is scanned and eventually
all non-primes in said domain get rendered out.  What's left is set bits only where legit
primes live.

qFactor uses modulus counters to keep track of the prime periods so that a much smaller
Sieve bitmap can be used to calculate successive groups of primes at any given time.  This
requires an extra modulus value be associated with the prime number as the list of primes is
actively built.  Since the modulus field is for internal use only, it can simply be ignored by
the user.  As the primes found get larger the search for them slows down, but the larger the
bitmap table is the faster the algorithm will work.

When qFactor is not building primes it uses very little memory.  Thus, $bitmap_size is a
critical value to maximize when intentionally building a prime list.

The ratio of the bytes of memory consumed versus $bitmap_size is 4.52 : 1.

*/

const BUF_SIZE = 2048;

function qFactor ($n, $find_factors = 1) {

	// test for valid prime composites
	if (!is_int ($n) || $n <= 1)

		// return empty for undefined composites
		return [];

	// calculate the $bitmap_size based on amount of free memory available
	$bitmap_size = 132043520;
	for ($i = get_free_mem (); $i < $bitmap_size * 10; $bitmap_size >>= 1);

	// abort if the memory could not be allocated for some reason
	if ($bitmap_size <= 0) return [];	// bitmap_size can be as small as 1 and qFactor will still work

	// initialize a Sieve buffer
	$bitmap;

	// initialize a result buffer
	$result = [];

	// allocate the temporary prime buffers
	$primes;
	$modulo;

	// open the modulo and prime files
	$fPrimes = fopen ("primes.bin", "c+b");
	$fModulo = fopen ("modulo.bin", "c+b");

	// determine the size of the file using fseek()
	$file_size = filesize ("primes.bin");

	// allocate the top prime for the first time
	if (!filesize ("modulo.bin"))

		fwrite ($fModulo, pack ("V", 1), 4);

	// set the initial buffer size to resemble an approximation of a basic file allocation unit
	for (;;) {

		// SECTION 1: run the prime division loop until end of file
		while ($file_size !== ftell ($fPrimes)) {

			// pick the lesser of $buf_size or remainder at end of file
			$buffer_size = min (BUF_SIZE, ($file_size - ftell ($fPrimes)) >> 2);
			
			// load in a chunk of our primes
			foreach (unpack ("V*", fread ($fPrimes, $buffer_size << 2)) as $prime) {

				// if $n is smaller than the square of the test prime, then n is also prime and factoring is done
				if ($n < $prime * $prime) {

					// $n is prime when greater than 1
					if ($n > 1)

						// push the last prime on the result factor list
						$result [] = $n;

					// return found factors
					break 3;

				}
				// count how many times we can factor a prime composite
				while ($n % $prime === 0) {

					// push the prime on the result factor list
					$result [] = $prime;

					// exit if factors not required
					if ($find_factors === 0) {

						// push a dummy value to indicate not prime
						$result [] = 1;
						break 4;

					}
					// update the test value
					$n /= $prime;

				}
			}
		}
		// SECTION 2: build a prime table slice
		// step 1: clear the Sieve bitmap
		$bitmap = array_fill (0, ($bitmap_size + 63) >> 6, 0);
		
		// rewind the prime file pointers
		fseek ($fPrimes, 0);
		fseek ($fModulo, 0);
		
		// get the top prime value
		$top_prime = unpack ("V", fread ($fModulo, 4)) [1];

		// step 2: render out existing prime periods
		while ($file_size !== ftell ($fPrimes)) {

			// pick the lesser of $buf_size or remainder at end of file
			$buffer_size = min (BUF_SIZE, ($file_size - ftell ($fPrimes)) >> 2);
			
			// load in a chunk of our primes
			$primes = unpack ("V*", fread ($fPrimes, $buffer_size << 2));
			$modulo = unpack ("V*", fread ($fModulo, $buffer_size << 2));
			for ($m = 1; $m <= $buffer_size; $m ++) {
		
				// load the prime profile
				$j	= $modulo [$m];
				$prime	= $primes [$m];
				while ($j < $bitmap_size) {

					$bitmap [$j >> 6] |= 1 << ($j & 63);
					$j += $prime;

				}
				// update the modulus
				$modulo [$m] = $j - $bitmap_size;
				
			}
			// rewind the modulo pointer
			fseek ($fModulo, - ($buffer_size << 2), SEEK_CUR);
			
			// update the modulo values
			fwrite ($fModulo, call_user_func_array ("pack", array_merge (array ("V*"), $modulo)), $buffer_size << 2);

		}
		// reset the buffer size
		$buffer_size = 0;
		
		// step 3: find new primes in the Seive & render out the periods
		for ($k = 0; $k < $bitmap_size; $k ++) {

			// advance the top prime
			$top_prime ++;

			// scan for first clear bit in bitmap
			if (!($bitmap [$k >> 6] & 1 << ($k & 63))) {

				// allocate the prime
				$primes [++ $buffer_size] = $top_prime;

				// iterate out the bitmap
				$j = $k;
				do {

					$bitmap [$j >> 6] |= 1 << ($j & 63);
					$j += $top_prime;

				} while ($j < $bitmap_size);

				// allocate the modulus
				$modulo [$buffer_size] = $j - $bitmap_size;

			}
		}
		// step 4: allocate new data to primes & modulo
		// BUG: pack() will not use more than 1 array element as an argument; call_user_func_array is a workaround.
		fwrite ($fPrimes, call_user_func_array ("pack", array_merge (array ("V*"), $primes)), $buffer_size << 2);
		fwrite ($fModulo, call_user_func_array ("pack", array_merge (array ("V*"), $modulo)), $buffer_size << 2);
		
		// update top prime in first 32 bits
		fseek  ($fModulo, 0);
		fwrite ($fModulo, pack ("V", $top_prime), 4);
		
		// rewind the primes file to spot where new primes were just put
		fseek ($fPrimes, - ($buffer_size << 2), SEEK_CUR);
		
		// increase the file size value
		$file_size += $buffer_size << 2;

	}
	fclose ($fPrimes);
	fclose ($fModulo);
	return $result;

}

function qPrime ($n) {
	
	return (int) (count (qFactor ($n, 0)) === 1);
	
}

?>