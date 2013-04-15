doc.umentation
=============

A simple application for building documentation for your app. Uses [Slim](http://www.slimframework.com/), [Paris/Idiorm](http://j4mie.github.io/idiormandparis/), [Twig](http://twig.sensiolabs.org/), and [Bootstrap v2.3.1](http://twitter.github.com/bootstrap).

Why? There are other applications but I wanted something that can be setup and used within 5 minutes. Use doc.umentation when you don't need a full blown wiki or auto documentation.

## Setup

Most of the setup is already done for you.

* Clone or download the repository and place on your webserver.
* Use the base MySQL database to setup your database
* Update config.php with your database settings
* Go to http://yourwebsite.com/admin/add to create content
* To create a home page edit the slug of an existing page to 'home' or create a new page with the title 'Home'

## Database

You can use a different data type (such as TEXT) for the content field. We use longblob to be able to save images to the database without issue.

	CREATE TABLE `articles` (
	  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	  `parent_id` int(11) NOT NULL,
	  `title` varchar(96) NOT NULL,
	  `slug` varchar(48) NOT NULL,
	  `content` longblob NOT NULL,
	  `timestamp` datetime NOT NULL,
	  `order` int(11) NOT NULL,
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;

## To Do

* Add user authentication for admin area access
