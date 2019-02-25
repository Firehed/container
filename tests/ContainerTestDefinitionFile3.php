<?php
declare(strict_types=1);

use function Firehed\Container\env;

return [
    // This definition is a counterexample - do not follow it!
    'env_pwd' => getenv('PWD'),

    // Various env var examples
    'env_set' => env('CONTAINER_UNITTEST_SET'),
    'env_set_with_default' => env('CONTAINER_UNITTEST_SET', 'default'),
    'env_set_with_null_default' => env('CONTAINER_UNITTEST_SET', null),

    'env_not_set' => env('CONTAINER_UNITTEST_NOT_SET'),
    'env_not_set_with_default' => env('CONTAINER_UNITTEST_NOT_SET', 'default'),
    'env_not_set_with_null_default' => env('CONTAINER_UNITTEST_NOT_SET', null),
];
