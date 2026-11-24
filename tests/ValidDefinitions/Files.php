<?php
declare(strict_types=1);

use function Firehed\Container\fileContents;
use function Firehed\Container\fileOrEnv;

$prefix = 'CONTAINER_UNITTEST_FOE_';

return [
    'file_contents' => fileContents(__DIR__ . '/../Fixtures/secret.txt'),
    'file_contents_missing' => fileContents(__DIR__ . '/../Fixtures/does-not-exist.txt'),

    'file_or_env_env' => fileOrEnv($prefix . 'ENV'),
    'file_or_env_file' => fileOrEnv($prefix . 'FILE'),
    'file_or_env_default' => fileOrEnv($prefix . 'NOT_SET', 'default'),
    'file_or_env_null_default' => fileOrEnv($prefix . 'NOT_SET', null),
    'file_or_env_not_set' => fileOrEnv($prefix . 'NOT_SET'),
    'file_or_env_conflict' => fileOrEnv($prefix . 'CONFLICT'),
];
