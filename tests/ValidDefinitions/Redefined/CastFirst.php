<?php
declare(strict_types=1);

use function Firehed\Container\env;

return [
    'redefined' => env('CONTAINER_UNITTEST_NOT_SET', '5')->asInt(),
];
