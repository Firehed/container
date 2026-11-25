<?php
declare(strict_types=1);

use function Firehed\Container\fileContents;

return [
    'file_contents' => fileContents(__DIR__ . '/../Fixtures/fileContents/secret.txt'),
    'file_contents_missing' => fileContents(__DIR__ . '/../Fixtures/fileContents/does-not-exist.txt'),
];
