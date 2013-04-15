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
require('Slim/extras/Views/Twig.php');

$app = new \Slim\Slim(array(
	'view' => new \Slim\Extras\Views\Twig()
));

$app->config('debug', true);


function getOrderedArticles($articles) {
	// build the parent array
	$parents = array();
	foreach ($articles as $article) {
		$article = $article->as_array();
		if ($article['parent_id'] == 0) {
			$parents[$article['id']] = $article;
		}
	}
	
	foreach ($parents as $k => $v) {
		foreach ($articles as $article) {
			$article = $article->as_array();
			if ($article['parent_id'] == $k) {
				$parents[$k]['children'][] = $article;
			}
		}
	}
	
	return $parents;	
}

function slug($title) {
	$slug = str_replace(" ", "_", strtolower($title));
	$testSlug = Model::factory('Articles')
		->where('slug', $slug)
		->find_one();
	$i = 0;
	if ($testSlug) {
		$i++;
		$newTitle = ''.$title.' '.$i.'';
		$slug = slug($newTitle);
	}
	
	return $slug;
}


// home
$app->get('/', function() use ($app) {
	$articles = Model::factory('Articles')
		->order_by_asc('order')
		->find_many();
	
	$parents = getOrderedArticles($articles);
	
	$home = Model::factory('Articles')
		->where('slug', 'home')
		->find_one();
	
	if ($home) {
		return $app->render('read.twig', array('doc_root' => BASEDIR, 'article' => $home, 'parents' => $parents));
	} else {
		return $app->render('home.twig', array('doc_root' => BASEDIR, 'parents' => $parents));
	}
});

// read individual article
$app->get('/read/:slug', function($slug) use ($app) {
	$articles = Model::factory('Articles')
		->order_by_asc('order')
		->find_many();
	
	$parents = getOrderedArticles($articles);

	$article = Model::factory('Articles')
		->where('slug', $slug)
		->find_one();
	
	if (! $article instanceof Articles) {
	   $app->notFound();
	}
	
	return $app->render('read.twig', array('doc_root' => BASEDIR, 'article' => $article, 'parents' => $parents));
});

// Admin Home.
$app->get('/admin', function() use ($app) {
	$articles = Model::factory('Articles')
		->order_by_asc('order')
		->find_many();
	
	$parents = getOrderedArticles($articles);
	
	return $app->render('admin_home.twig', array('doc_root' => BASEDIR, 'parents' => $parents));
});

$app->post('/admin/reorder', function() use ($app) {
	$ids = $app->request()->post('item');
	
	foreach($ids as $key=>$value) {
		echo "Key: $key, Value: $value <br>";
		$article = Model::factory('Articles')->find_one($value);
		$article->order	= $key;
		$article->save();
    }
});
 
// Admin Add.
$app->get('/admin/add/:parent_id', function($parent_id) use ($app) {
	$articles = Model::factory('Articles')
		->order_by_asc('order')
		->find_many();
	
	$parents = getOrderedArticles($articles);

	return $app->render('article_form.twig', array('doc_root' => BASEDIR, 'parents' => $parents, 'parent_id' => $parent_id));
});   
 
// Admin Add - POST.
$app->post('/admin/add/:parent_id', function($parent_id) use ($app) {
		
	$slug = slug($app->request()->post('title'));
	
	$article = Model::factory('Articles')->create();
	$article->title		= $app->request()->post('title');
	$article->slug		= $slug;
	$article->content	= trim($app->request()->post('content'));
	$article->timestamp = date('Y-m-d H:i:s');
	$article->parent_id = $parent_id;

	$article->save();
	
	$app->redirect(''.BASEDIR.'/admin');
});
 
// Admin Edit.
$app->get('/admin/edit/(:id)', function($id) use ($app) {
	$articles = Model::factory('Articles')
		->order_by_asc('order')
		->find_many();
	
	$parents = getOrderedArticles($articles);

	$article = Model::factory('Articles')
		->where('id', $id)
		->find_one();
		
	if (! $article instanceof Articles) {
	   $app->notFound();
	}
	return $app->render('edit.twig', array('doc_root' => BASEDIR, 'article' => $article, 'parents' => $parents));
});
 
// Admin Edit - POST.
$app->post('/admin/edit/(:id)', function($id) use ($app) {
	$article = Model::factory('Articles')->find_one($id);
	if (! $article instanceof Articles) {
	   $app->notFound();
	}
	
	$slug = $app->request()->post('slug'); 
	if (!empty($slug)) {
		$article->slug = $slug;
	}
	
	$article->title		= $app->request()->post('title');
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