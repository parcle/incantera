<?php

global $project;
$project = 'mysite';

global $databaseConfig;
$databaseConfig = array(
	'type' => 'MySQLDatabase',
	'server' => 'localhost',
	'username' => 'incantera',
	'password' => 'lsop$ywermw9384',
	'database' => 'incantera',
	'path' => ''
);

// Set the site locale
i18n::set_locale('it_IT');
