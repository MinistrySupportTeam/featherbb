<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
// Start a session for flash messages
session_cache_limiter(false);
session_start();
error_reporting(E_ALL); // Let's report everything for development

// Load Slim Framework
require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();

// Instantiate Slim
$feather = new \Slim\Slim();
$feather_user_settings = array(
							'db_name' => 'featherbb',
							'db_host' => 'localhost',
							'db_user' => 'featherbb',
							'db_pass' => 'featherbb',
							'cookie_name' => 'feather_cookie_45ef0b',
							'cookie_seed' => '0f320ab07f4afbc5');

// Load middlewares
$feather->add(new \Slim\Extras\Middleware\CsrfGuard('featherbb_csrf')); // CSRF
$feather->add(new \Slim\Extras\Middleware\FeatherBB($feather_user_settings)); // FeatherBB

// Load the routes
require 'include/routes.php';

// Run it, baby!
$feather->run();
