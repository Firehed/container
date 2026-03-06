<?php

declare(strict_types=1);

use Dotenv\Dotenv;

// load based on $dotenv

$container = AutoDetect::from('config');

echo $container->get('FOO');
