<!DOCTYPE html>
<html lang = "en" dir = "ltr">
 <head>
  <meta charset = "utf-8">
  <title>63-BIT DECIMAL PRIME SPLIT TEST</title>
 </head>
 <body>
  <b>Welcome to the Decimal Prime Split Test!</b><br/>
  By Brad Taylor<br/>
  10-10-2021<br/><br/><b>
  Specify a (2^n)-1 range and count of random values to test the split on, or specify a discrete<br/>
  value.  The program will show the result of the test along with elapsed time measurements,<br/>
  and count of any errors detected when reconstructing and verifying the decimal_prime_split()<br/>
  results.</b><br/><br/>
  
  There are <b>3 types of prime number algorithms</b> that can be tested simultaneously; each has a<br/>
  strength and weakness in prime determination: complexity, speed and memory usage are all different.<br/><br/>
  
  The measured time reported in the test is the time it takes for decimal_prime_split() to complete in<br/>
  a short-circuit mode ($decompose = 0), after all the primes required to test the number are rendered<br/>
  out to PRIMES.BIN and available.  This means that the test may be suspended while building up<br/>
  PRIMES.BIN in method_type modes 1 & 2, or factoring composite primes to verify the output<br/>
  can reconstruct the input string, but this extra time is not recorded by the test.<br/><br/>
  
  References:<br/>
  <a target="_blank" href = "https://www.dcode.fr/prime-factors-decomposition">Quickly factor very large numbers</a><br/>
  <a target="_blank" href = "https://en.wikipedia.org/wiki/List_of_prime_numbers">Listing of first 1000 prime numbers</a><br/>
  <a target="_blank" href = "https://onlinenumbertools.com/calculate-prime-numbers">Calculate a list of primes at a given starting point</a><br/>
  <a target="_blank" href = "https://primes.utm.edu/howmany.html">Relationship of number of primes to composite prime domain size</a><br/>
  <a target="_blank" href = "https://en.wikipedia.org/wiki/Sieve_of_Eratosthenes">The Sieve of Eratosthenes prime number production algorithm</a><br/><br/><br/>

  <form action = "split.php" method = "post">

   <b>RNG domain magnitude (2 - 62)?</b>
   <input type = "number" name = "magnitude" min = "2" max = "62" value = "48"><br/><br/>
   
   <b>Number of times to run split test (1 - 999)?</b>
   <input type = "number" name = "iterations" min = "1" max = "999" value = "20"><br/><br/>
   
   <b>Use basic factoring test (method_type = 0)?</b>
   <select name = "test0_en">
    <option value = "1">Yes</option>
    <option value = "0">No</option>
   </select><br/>
   Keep in mind very large primes may each take over 60 seconds to determine.<br/><br/>
  
   <b>Use quick factoring test (method_type = 1)?</b>
   <select name = "test1_en">
    <option value = "0">No</option>
    <option value = "1">Yes</option>
   </select><br/>
   Quick factoring works faster than basic on very large primes, but it may take a while<br/>
   for the test to finish the first time if PRIMES.BIN has to be significantly built up.<br/><br/>
  
   <b>Use fast factoring test (method_type = 2)?</b>
   <select name = "test2_en">
    <option value = "0">No</option>
    <option value = "1">Yes</option>
   </select><br/>
   NOTE: Set memory_limit=2400M minimum in PHP.INI to enable testing.<br/>
   The PRIMES.BIN file will load before testing starts; This will take about 20 seconds each time.<br/>
   If PRIMES.BIN needs to be generated, this will take almost 30 minutes the first time (you may need<br/>
   to set max_execution_time=2000 minimum in PHP.INI to allow the script enough time to finish).<br/><br/>
  
   <b>Display output test proof?</b>
   <select name = "show_proof">
    <option value = "1">Yes</option>
    <option value = "0">No</option>
   </select><br/><br/>
   
   <b>Test a discrete value?</b>
   <select name = "test_override">
    <option value = "0">No</option>
    <option value = "1">Prime Split</option>
    <option value = "2">Factorize</option>
   </select>
   <input type = "number" name = "test_value" value = "23571113"><br/><br/>
   
   <button type = "submit">Run Prime Split Test</button>
   
  </form>
</body>
</html>