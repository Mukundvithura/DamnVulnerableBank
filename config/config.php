<?php
// Central settings. Every other file gets these through database.php.
// Each value can be overridden by an environment variable (Docker sets them in docker-compose.yml);
// the defaults are for running locally with `php -S localhost:8080`.

function env(string $name, string $default): string
{
    $value = getenv($name);
    return $value === false || $value === '' ? $default : $value;
}

const APP_NAME = 'BankVWA';
date_default_timezone_set('Asia/Kolkata');   // an Indian bank shows Indian time
define('APP_URL', env('APP_URL', 'http://localhost:8080')); // used for links in emails; never build these from the Host header
define('BASE_URL', env('BASE_URL', ''));                     // '' when served from the web root; '/BankVWA' if inside XAMPP htdocs

define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_PORT', (int) env('DB_PORT', '3307'));             // 3307 = the standalone bankvwa-db container
define('DB_NAME', env('DB_NAME', 'bankvwa'));
define('DB_USER', env('DB_USER', 'bankvwa'));
define('DB_PASS', env('DB_PASS', 'bankvwa_pass'));           // local lab only, never reuse real passwords
