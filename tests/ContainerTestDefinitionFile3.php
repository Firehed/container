<?php
declare(strict_types=1);

use function Firehed\Container\env;

return [
    'env_pwd' => getenv('PWD'),
    'env_container_unittest' => env('CONTAINER_UNITTEST'),
];
