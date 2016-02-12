<?php

// Automatic maintenance mode - while git deploy & composer install
if (file_exists('maintenanceON')) {
	require 'maintenance.php';
}

$container = require __DIR__ . '/nette/app/bootstrap.php';

$container->getByType('Nette\Application\Application')->run();
