<?php
	require_once './lean/constants.php';
	require_once _LEAN . '/Lean.php';
	$config = require_once _CONFIG . '/app.config.php';
	$config['dbConnections'] = require_once _CONFIG . '/db-connections.config.php';

	$app = Lean::init( $config )->run();
?>
