<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Firehed\Container\AutoDetect;

chdir(__DIR__);
require __DIR__ . '/../../vendor/autoload.php';

// load based on $dotenv

$container = AutoDetect::from('config');
// var_dump(get_debug_type($container));

echo $container->get('FOO');
