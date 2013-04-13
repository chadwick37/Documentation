<?php

// Setup Slim
require('Slim/Slim.php');
\Slim\Slim::registerAutoloader();

// Setup Twig
require_once 'Twig/lib/Twig/Autoloader.php';
Twig_Autoloader::register();

session_cache_limiter(false);
session_start();

// Setup Paris and Idiorm
require_once 'Paris/idiorm.php';
require_once 'Paris/paris.php';

// Config
require_once('config.php');
ORM::configure('mysql:host='.SQLHOST.';dbname='.SQLDB.'');
ORM::configure('username', SQLUSER);
ORM::configure('password', SQLPASS);
ORM::configure('logging', true);

require('classes/hys.class.php');

$app = new \Slim\Slim(array(
	'view' => new \Slim\Extras\Views\Twig()
));

$app->config('debug', true);

$app->get('/', function() use ($app) {

});

$app->run();