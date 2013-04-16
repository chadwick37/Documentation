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
require('models/Users.php');
require('Slim/extras/Views/Twig.php');

$app = new \Slim\Slim(array(
	'view' => new \Slim\Extras\Views\Twig()
));

$app->config('debug', true);
define('SALT','doc');


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

$authenticate = function ($app) {
    return function () use ($app) {
        if (!isset($_SESSION['user'])) {
            $app->flash('error', 'Login required');
            $app->redirect(''.BASEDIR.'/admin/login');
        }
    };
};

$app->hook('slim.before.dispatch', function() use ($app) { 
   $user = null;
   if (isset($_SESSION['user'])) {
      $user = $_SESSION['user'];
   }
   $app->view()->setData('user', $user);
});

$app->view()->setData('doc_root', BASEDIR);

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
		return $app->render('read.twig', array('article' => $home, 'parents' => $parents));
	} else {
		return $app->render('home.twig', array('parents' => $parents));
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
	
	return $app->render('read.twig', array('article' => $article, 'parents' => $parents));
});

// Admin Home.
$app->get('/admin', $authenticate($app), function() use ($app) {
	$articles = Model::factory('Articles')
		->order_by_asc('order')
		->find_many();
	
	$parents = getOrderedArticles($articles);
	
	return $app->render('admin_home.twig', array('parents' => $parents));
});

$app->post('/admin/reorder', $authenticate($app), function() use ($app) {
	$ids = $app->request()->post('item');
	
	foreach($ids as $key=>$value) {
		echo "Key: $key, Value: $value <br>";
		$article = Model::factory('Articles')->find_one($value);
		$article->order	= $key;
		$article->save();
    }
});
 
// Admin Add.
$app->get('/admin/add/:parent_id', $authenticate($app), function($parent_id) use ($app) {
	$articles = Model::factory('Articles')
		->order_by_asc('order')
		->find_many();
	
	$parents = getOrderedArticles($articles);

	return $app->render('article_form.twig', array('parents' => $parents, 'parent_id' => $parent_id));
});   
 
// Admin Add - POST.
$app->post('/admin/add/:parent_id', $authenticate($app), function($parent_id) use ($app) {
		
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
$app->get('/admin/edit/(:id)', $authenticate($app), function($id) use ($app) {
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
	return $app->render('edit.twig', array('article' => $article, 'parents' => $parents));
});
 
// Admin Edit - POST.
$app->post('/admin/edit/(:id)', $authenticate($app), function($id) use ($app) {
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
$app->get('/admin/delete/(:id)', $authenticate($app), function($id) use ($app) {
	$article = Model::factory('Articles')->find_one($id);
	
	if ($article instanceof Articles) {
		$article->delete();
	}
	 
	$app->redirect(''.BASEDIR.'/admin');
});

$app->get('/admin/users', $authenticate($app), function() use ($app) {
	$articles = Model::factory('Articles')
		->order_by_asc('order')
		->find_many();
	
	$parents = getOrderedArticles($articles);
	
	$users = Model::factory('Users')->find_many();
	
	return $app->render('users.twig', array('users' => $users, 'parents' => $parents));
});

$app->get('/admin/users/add', $authenticate($app), function() use ($app) {
	$articles = Model::factory('Articles')
		->order_by_asc('order')
		->find_many();
	
	$parents = getOrderedArticles($articles);
		
	return $app->render('users_add.twig', array('parents' => $parents));	
});

$app->get('/admin/users/edit/(:id)', $authenticate($app), function($id) use ($app) {
	$articles = Model::factory('Articles')
		->order_by_asc('order')
		->find_many();
	
	$parents = getOrderedArticles($articles);
	
	$edituser = Model::factory('Users')->find_one($id);
	//$user = $user->as_array();
		
	return $app->render('users_edit.twig', array('edituser' => $edituser, 'parents' => $parents));		
});

$app->post('/admin/users/add', $authenticate($app), function() use ($app) {
	$password = $app->request()->post('password');
	$password = sha1($password.SALT);

	$user = Model::factory('Users')->create();
	$user->username		= $app->request()->post('username');
	$user->email			= $app->request()->post('email');
	$user->password		= $password;
	$user->save();
	
	$app->redirect(''.BASEDIR.'/admin/users');
});

$app->post('/admin/users/edit/(:id)', $authenticate($app), function($id) use ($app) {
	$user = Model::factory('Users')->find_one($id);
	
	$password = $app->request()->post('password');
	if (!empty($password)) {
		$password = sha1($password.SALT);
		$user->password = $password;
	}

	$user->username		= $app->request()->post('username');
	$user->email		= $app->request()->post('email');
	$user->save();
	
	$app->redirect(''.BASEDIR.'/admin/users');
});

$app->post('/admin/users/delete/(:id)', $authenticate($app), function($id) use ($app) {
	$user = Model::factory('Users')->find_one($id);
	
	if ($user instanceof Users) {
		$user->delete();
	}
	 
	$app->redirect(''.BASEDIR.'/admin/users');
});

$app->get('/admin/login', function() use ($app) {
	$articles = Model::factory('Articles')
		->order_by_asc('order')
		->find_many();
	
	$parents = getOrderedArticles($articles);	$flash = $app->view()->getData('flash');

	$error = '';
	if (isset($flash['error'])) {
	  $error = $flash['error'];
	}
		
	$username_value = $username_error = $password_error = '';
	
	if (isset($flash['username'])) {
	  $username_value = $flash['username'];
	}
	
	if (isset($flash['errors']['username'])) {
	  $username_error = $flash['errors']['username'];
	}
	
	if (isset($flash['errors']['password'])) {
	  $password_error = $flash['errors']['password'];
	}
	
	return $app->render('login.twig', array('error' => $error, 'username_value' => $username_value, 'username_error' => $username_error, 'password_error' => $password_error, 'parents' => $parents));	
});

$app->post("/admin/login", function () use ($app) {
    $username = $app->request()->post('username');
    $password = $app->request()->post('password');
    $password = sha1($password.SALT);
    
    $userlogin = Model::factory('Users')
    	->where('username', $username)
    	->find_one();
    
    $errors = array();

    if (empty($userlogin)) {
        $errors['username'] = "Username is not found.";
    } else if ($password != $userlogin->password) {
        $app->flash('username', $username);
        $errors['password'] = "Password does not match.";
    }

    if (count($errors) > 0) {
        $app->flash('errors', $errors);
        $app->redirect(''.BASEDIR.'/admin/login');
    }

    $_SESSION['user'] = $username;

    $app->redirect(''.BASEDIR.'/admin');
});

$app->get('/admin/logout', function() use ($app) {
	unset($_SESSION['user']);
	$app->view()->setData('user', null);
	
	$app->redirect(''.BASEDIR.'/');
});


$app->run();