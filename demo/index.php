<?php
use Roolith\Configuration\Config;

require_once __DIR__. '/../vendor/autoload.php';

define('ROOLITH_CONFIG_ROOT', __DIR__. '/config');
// define('ROOLITH_ENV', 'development');

function dd($d) {
    echo '<pre>';
    print_r($d);
    echo '</pre>';
}

// Config::setEnv('development');
// Active env lookup: no env prefix in key (matches README usage).
// With env `production`, `get('database')` returns the production value,
// while keys missing from the env file fall back to `config.php`.
dd(Config::get('database'));
dd(Config::get('log.path')); // fallback to default when env file lacks the key
// Explicit cross-env lookup: skip auto env prefixing.
dd(Config::get('staging.database', true));
dd(Config::env());
