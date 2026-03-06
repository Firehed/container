<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Firehed\Container\AutoDetect;
use Psr\Container\ContainerExceptionInterface;

chdir(__DIR__);
require __DIR__ . '/../../vendor/autoload.php';

// Hide deprecation warnings in "low" CI
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED);

if ($argc < 2) {
    throw new Exception('Argument required');
};
$mode = $argv[1];
if ($mode !== 'none') {
    $dotenv = match ($mode) {
        'mutable' => Dotenv::createMutable(__DIR__),
        'immutable' => Dotenv::createImmutable(__DIR__),
        'unsafe_mutable' => Dotenv::createUnsafeMutable(__DIR__),
        'unsafe_immutable' => Dotenv::createUnsafeImmutable(__DIR__),
    };
    $dotenv->load();
}

try {
    $container = AutoDetect::from('config');
} catch (Throwable $e) {
    echo $e;
    exit(1);
}

try {
    $foo = $container->get('FOO');
} catch (ContainerExceptionInterface $e) {
    echo $e;
    exit(2);
}

echo $foo;
