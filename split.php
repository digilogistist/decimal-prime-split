<?php

// the prime factoring suite
include "factor.php";
include "qfactor.php";
include "ffactor.php";

// the main unit test program
$override	=  $_POST ["test_override"];
$magnitude	=  $_POST ["magnitude"];
$iterations	=  $_POST ["iterations"];

// implement parameter limits
if ($magnitude	<   2)	$magnitude	=   2;
if ($magnitude	>  62)	$magnitude	=  62;
if ($iterations	<   1)	$iterations	=   1;
if ($iterations	> 999)	$iterations	= 999;

// check for test value override option
if ($override) {
	
	$magnitude  = $_POST ["test_value"];
	$iterations = 0;
	
}
// allocate the Prime object and allocate a prime list if bit 2 of $type_bitmap is set
$pPrime = new Prime (($_POST ["test2_en"] << 2	& 4) | ($_POST ["test1_en"] << 1 & 2) | ($_POST ["test0_en"] & 1));

// call the prime split test except for type 2
if ($override < 2)

	// call the functional test
	$result = $pPrime -> decimal_split_test ($magnitude, $iterations, $_POST ["show_proof"]);

// echo out the measured elapsed time values and error counts
for ($i = 0; $i < 3; $i ++) {
	
	if (!($pPrime -> type_bitmap >> $i & 1)) continue;
	switch ($override) {
	
	// print the random number prime split test results
	case 0:	echo	"Prime -> decimal_split (n = random_int (magnitude = " . $magnitude .
			", iterations = " . $iterations .
			"), decompose = 0, method_type = " . $i .
			"): <b>Time (mS)= " . (int) substr ($result [$i] ["time"], 0, -6) .
			"; Errors= " . $result [$i] ["errors"] .
			".</b>";
		break;

	// print the user-specified prime split results
	case 1: echo	"Prime -> decimal_split (n = " . $magnitude .
			", decompose = 0, method_type = " . $i .
			"): <b>Time (mS)= " . (int) substr ($result [$i] ["time"], 0, -6) .
			"; Errors= " . $result [$i] ["errors"] .
			".</b>";
		break;
				
	// print the factors of specified number
	case 2:	switch ($i) {
			
			case 0: foreach ( factor	   ((int) $magnitude) as $n) echo " * " . $n; break;
			case 1: foreach (qFactor	   ((int) $magnitude) as $n) echo " * " . $n; break;
			case 2: foreach ($pPrime -> factor ((int) $magnitude) as $n) echo " * " . $n;
			
		}
	}
	echo "<br/>";
	
}	
?>