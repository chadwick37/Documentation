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

require('models/Articles.php');

$app = new \Slim\Slim(array(
	'view' => new \Slim\Extras\Views\Twig()
));

$app->config('debug', true);

// home
$app->get('/', function() use ($app) {

});

// read individual article
$app->get('read/:slug', function($slug) use ($app) {
	
});

// Admin Home.
$app->get('/admin', function() use ($app) {
 
});
 
// Admin Add.
$app->get('/admin/add', function() use ($app) {
 
});   
 
// Admin Add - POST.
$app->post('/admin/add', function() use ($app) {
 
});
 
// Admin Edit.
$app->get('/admin/edit/(:id)', function($id) use ($app) {
 
});
 
// Admin Edit - POST.
$app->post('/admin/edit/(:id)', function($id) use ($app) {
 
});
 
// Admin Delete.
$app->get('/admin/delete/(:id)', function($id) use ($app) {
 
});

$app->run();