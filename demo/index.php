<?php
use Roolith\Configuration\Config;

require_once __DIR__. '/../vendor/autoload.php';

define('ROOLITH_CONFIG_ROOT', __DIR__. '/config');
define('ROOLITH_ENV', 'development');

function dd($d) {
    echo '<pre>';
    print_r($d);
    echo '</pre>';
}

//Config::setEnv('development');
dd(Config::get('development.database'));
dd(Config::env());
