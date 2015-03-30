<?php
	require __DIR__ . '/picojade.php';
	
	$jade = new PicoJade();
	echo $jade->compile(file_get_contents(__DIR__ . '/index.jade'));
?>
