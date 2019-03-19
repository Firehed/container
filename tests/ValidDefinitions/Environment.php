<?php
declare(strict_types=1);

use function Firehed\Container\env;

$prefix = 'CONTAINER_UNITTEST_';

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

    // Casting: boolean
    'env_asbool_one' => env($prefix . 'ONE')->asBool(),
    'env_asbool_true' => env($prefix . 'TRUE')->asBool(),
    'env_asbool_zero' => env($prefix . 'ZERO')->asBool(),
    'env_asbool_false' => env($prefix . 'FALSE')->asBool(),
    'env_asbool_empty' => env($prefix . 'EMPTY')->asBool(),
    'env_asbool_notset' => env($prefix . 'NOT_SET', 'true')->asBool(),

    // Casting: int
    'env_asint_one' => env($prefix . 'ONE')->asInt(),
    'env_asint_zero' => env($prefix . 'ZERO')->asInt(),
    'env_asint_notset' => env($prefix . 'NOT_SET', '3')->asInt(),

    // Casting: float
    'env_asfloat_one' => env($prefix . 'ONE')->asFloat(),
    'env_asfloat_one_point_five' => env($prefix . 'ONE_POINT_FIVE')->asFloat(),
    'env_asfloat_zero' => env($prefix . 'ZERO')->asFloat(),
    'env_asfloat_notset' => env($prefix . 'NOT_SET', '3.14')->asFloat(),
];
