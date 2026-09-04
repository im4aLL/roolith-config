# roolith-config
PHP config class

#### Install
```text
composer require roolith/config
```

Note: the library supports PHP `^8.0` (runtime and dev toolchain). Dev toolchain uses PHPUnit `^9.6`, which also runs on PHP 8.0. The committed lock is resolved against a PHP 8.0 platform, so `composer install` works on PHP 8.0 and up.

#### Doc
Project directory requires a folder (e.g `config`) where configuration varibles will be stored.

Default config filename `config.php` and environment specific file names are -

```text
development.config.php
production.config.php
```

Note: `default.config.php` is reserved and ignored, since `config.php` is already loaded under the `default` key. Note: `local` uses only `config.php`; a `local.config.php` file is ignored.

##### config.php
```php
<?php

return [
    'database' => 'generalDatabase',
    'username' => 'generalUsername',
    'password' => 'generalPassword',
    'test' => true,
    'nullable' => null,
    'log' => [
        'path' => 'generalLogPath',
    ],
];
```

Same keys are used in `demo/config/config.php` and `tests/config-test/config.php` (demo and test fixtures stay in sync; `production.config.php` in both adds `a.b => c`).

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

Note: with an active env, dotted keys first try `env.key`, then fall back to a literal lookup of the full dotted path. So `Config::get('staging.database')` under `production` still resolves the literal `staging.database` when `production.staging.database` is missing. Pass `true` as second arg to skip the env-prefixed attempt entirely.

#### Merge semantics

Per-key shadowing, no deep merge:

- `Config::get('database')` under `production` returns the production value when present, else the `config.php` value. The env file is checked before `config.php`, so a top-level `production` key in `config.php` never shadows the env file.
- `Config::get('log.path')` falls back to `config.php` when the env file has no `log` key (see `demo/index.php` and `ConfigFallbackTest`).
- Missing keys return silent `null`; stored `null` values are preserved (including an env `null` shadowing a non-null default).
- Fetching a parent array that exists in the env file returns that env array as-is; default children are not deep-merged into it.
- A bare env name returns its file array: `Config::get('staging', true)` resolves `staging.config.php`.
- When the first dotted segment names an env file, that file wins over `config.php` (e.g. `staging.database`).
- `demo/config/` and `tests/config-test/` use the same fixture keys so the fallback examples behave identically in both.

#### Environment precedence

Highest first:

1. `Config::setEnv('production')` (process env `ROOLITH_ENVIRONMENT`, takes effect immediately).
2. `ROOLITH_ENV` constant, read once on first init.
3. `local` default.

```php
Config::reset(); // clear singleton, loaded data, and env state (e.g. for tests)
Config::reset(false); // force re-init from the current root while preserving env
```

A different `ROOLITH_CONFIG_ROOT` requires a fresh process because PHP constants cannot be redefined in-process.

Note: `Config::reset()` is a test and reload utility on the concrete class only. Note: it is not part of the `ConfigInterface` consumer contract.

#### Upgrade note (breaking change)

The generic `environment` process env var is no longer read. If you set env via `putenv('environment=...')`, switch to one of these:

```php
Config::setEnv('production');
// or
define('ROOLITH_ENV', 'production');
// or
putenv('ROOLITH_ENVIRONMENT=production');
```
