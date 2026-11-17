<?php
declare(strict_types=1);

use function Firehed\Container\fileContents;
use function Firehed\Container\fileOrEnv;

$fixtures = dirname(__DIR__) . '/Fixtures';

return [
    'file_contents' => fileContents($fixtures . '/secret.txt'),
    'file_contents_asint' => fileContents($fixtures . '/port.txt')->asInt(),
    'file_contents_missing' => fileContents($fixtures . '/missing.txt'),

    'file_or_env' => fileOrEnv('CONTAINER_UNITTEST_SECRET'),
    'file_or_env_with_default' => fileOrEnv('CONTAINER_UNITTEST_SECRET', 'default'),
    'file_or_env_asint' => fileOrEnv('CONTAINER_UNITTEST_PORT')->asInt(),
];
