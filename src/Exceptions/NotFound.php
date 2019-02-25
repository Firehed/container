<?php
declare(strict_types=1);

namespace Firehed\Container\Exceptions;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class NotFound extends Exception implements NotFoundExceptionInterface
{
}
