# roolith-config
PHP config class

#### Install
```text
composer require roolith/config
```

#### Doc
Project directory requires a folder (e.g `config`) where configuration varibles will be stored.

Default config filename `config.php` and environment specific file names are - 

```text
development.config.php
production.config.php
```

##### config.php
```php
<?php

return [
    'database' => 'generalDatabase',
    'username' => 'generalUsername',
    'password' => 'generalPassword',
    'test' => true,
];
```

##### production.config.php
```php
<?php

return [
    'database' => 'productionDatabase',
    'username' => 'productionUsername',
    'password' => 'productionPassword',
    'a' => [
        'b' => 'c'
    ]
];
```

Note: Checkout `demo` folder more details.

#### Usage

```php
<?php
use Roolith\Configuration\Config;

define('ROOLITH_CONFIG_ROOT', __DIR__. '/config');

print_r(Config::get('database')); // generalDatabase
```

##### Once environment variable is set

```php
<?php
use Roolith\Configuration\Config;

require_once __DIR__. '/../vendor/autoload.php';

define('ROOLITH_CONFIG_ROOT', __DIR__. '/config');
define('ROOLITH_ENV', 'production'); // set environment varible

// Config::setEnv('development'); // another way to set env
var_dump(Config::get('database')); // result will be `productionDatabase`
var_dump(Config::env()); // production
```

#### More usage
```php
Config::setEnv('production');
Config::get('a.b'); // c

Config::get('staging.database', true); // true means it will skip auto set environment
```

#### Environment precedence

Highest first:

1. `Config::setEnv('production')` (process env `ROOLITH_ENVIRONMENT`, takes effect immediately).
2. `ROOLITH_ENV` constant, read once on first init.
3. `local` default.

```php
Config::reset(); // clear singleton, loaded data, and env state (e.g. for tests)
Config::reset(false); // reload from a new ROOLITH_CONFIG_ROOT while preserving env
```

Note: `Config::reset()` is a test and reload utility on the concrete class only.
Note: it is not part of the `ConfigInterface` consumer contract.

#### Upgrade note (breaking change)

The generic `environment` process env var is no longer read. If you set env
via `putenv('environment=...')`, switch to one of these:

```php
Config::setEnv('production');
// or
define('ROOLITH_ENV', 'production');
// or
putenv('ROOLITH_ENVIRONMENT=production');
```
