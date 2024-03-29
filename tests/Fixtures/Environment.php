<?php

declare(strict_types=1);

// phpcs:ignoreFile

namespace Firehed\Container\Fixtures;

enum Environment: string
{
    case PRODUCTION = 'production';
    case STAGING = 'staging';
    case DEVELOPMENT = 'development';
    case TESTING = 'testing';
}
