doc.umentation
=============

A super simple app for documenting your app. Think of doc.umentation as an easy way to generate the userguide you never have time to build. Now you do because doc.umentation makes it so easy.

Why build doc.umentation? Because sometimes you need something quick and simple. It takes less than 5 minutes to setup and is super easy to use. If you don't need a full blown wiki or want the overhead of something like Wordpress, then doc.umentation may be for you.

More information available on [doc.umentation.com](http://doc.umentation.com).

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
	  `publish` TINYINT( 1 ) NOT NULL,
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;
	
	CREATE TABLE `users` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `username` varchar(24) NOT NULL,
	  `email` varchar(48) NOT NULL,
	  `password` varchar(48) NOT NULL,
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;
	
This will create a default user with a username 'admin' and password 'password'
	
	INSERT INTO `users` (`id`, `username`, `email`, `password`) VALUES
	(1, 'admin', 'admin@doc.umentation.com', '9b374a523bb94e6eea48522632bd2d90a713dd0f')
	
## To Do

* Add composer for dependency management.
* Add server side image processing.
* Create a setup and configuration (probably via a flat json file) to control various aspects of the site (like language). For example, the word FEATURES over the navigation on the left. Potentially doc.umentation could be used for other purposes.
