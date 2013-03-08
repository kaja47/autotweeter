<?php

use
  Nette\Diagnostics\Debugger,
  Nette\Application\Routers\Route,
  Nette\Database\Connection;


// Load Nette Framework
$params['libsDir'] = __DIR__ . '/../libs';
require $params['libsDir'] . '/Nette/loader.php';


// Enable Nette Debugger for error visualisation & logging
Debugger::$logDirectory = __DIR__ . '/../log';
Debugger::$strictMode = TRUE;
Debugger::enable();


// Load configuration from config.neon file
$configurator = new Nette\Configurator;
$configurator->container->params += $params;
$configurator->container->params['tempDir'] = __DIR__ . '/../temp';
$container = $configurator->loadConfig(__DIR__ . '/config.neon');

$container->session->setExpiration('+28 days');
$container->session->setSavePath(__DIR__ . '/../temp/sessions');

// Setup router
$router = $container->router;
$router[] = new Route('index.php', 'Homepage:default', Route::ONE_WAY);
$router[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default');

dibi::connect($container->params['database']);

// Configure and run the application!
$application = $container->application;
//$application->catchExceptions = TRUE;
$application->errorPresenter = 'Error';
$application->run();
