<?php
	require __DIR__ . '/PicoJade.php';
	
	$jade = new PicoJade;

	$template = file_get_contents(__DIR__ . '/index.jade');
	echo $jade->compile($template);
?>
