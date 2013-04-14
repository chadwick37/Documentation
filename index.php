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
	$articles = Model::factory('Articles')->find_many();
	
	return $app->render('home.twig', array('doc_root' => BASEDIR, 'articles' => $articles));
});

// read individual article
$app->get('/read/:slug', function($slug) use ($app) {
	$articles = Model::factory('Articles')->find_many();

	$article = Model::factory('Articles')
		->where('slug', $slug)
		->find_one();
	
	if (! $article instanceof Articles) {
	   $app->notFound();
	}
	
	return $app->render('read.twig', array('doc_root' => BASEDIR, 'article' => $article, 'articles' => $articles));
});

// Admin Home.
$app->get('/admin', function() use ($app) {
	$articles = Model::factory('Articles')
		->order_by_desc('order')
		->find_many();
	
	return $app->render('admin_home.twig', array('doc_root' => BASEDIR, 'articles' => $articles));
});
 
// Admin Add.
$app->get('/admin/add', function() use ($app) {
	$articles = Model::factory('Articles')->find_many();

	return $app->render('article_form.twig', array('doc_root' => BASEDIR, 'articles' => $articles));
});   
 
// Admin Add - POST.
$app->post('/admin/add', function() use ($app) {
	$article = Model::factory('Articles')->create();
	$slug = str_replace(" ", "_", strtolower($app->request()->post('title')));
	
	$article->title		= $app->request()->post('title');
	$article->slug		= $slug;
	$article->content	= trim($app->request()->post('content'));
	$article->timestamp = date('Y-m-d H:i:s');
	$article->save();
	
	$app->redirect(''.BASEDIR.'/admin');
});
 
// Admin Edit.
$app->get('/admin/edit/(:id)', function($id) use ($app) {
	$articles = Model::factory('Articles')->find_many();
	$article = Model::factory('Articles')
		->where('id', $id)
		->find_one();
		
	if (! $article instanceof Articles) {
	   $app->notFound();
	}
	return $app->render('edit.twig', array('doc_root' => BASEDIR, 'article' => $article, 'articles' => $articles));
});
 
// Admin Edit - POST.
$app->post('/admin/edit/(:id)', function($id) use ($app) {
	$article = Model::factory('Articles')->find_one($id);
	if (! $article instanceof Articles) {
	   $app->notFound();
	}
	$slug = str_replace(" ", "_", strtolower($app->request()->post('title')));
	
	$article->title		= $app->request()->post('title');
	$article->slug		= $slug;
	$article->content	= trim($app->request()->post('content'));
	$article->timestamp = date('Y-m-d H:i:s');
	$article->save();
	
	$app->redirect(''.BASEDIR.'/admin');
});
 
// Admin Delete.
$app->get('/admin/delete/(:id)', function($id) use ($app) {
	$article = Model::factory('Articles')->find_one($id);
	
	if ($article instanceof Articles) {
		$article->delete();
	}
	 
	$app->redirect(''.BASEDIR.'/admin');
});

$app->run();