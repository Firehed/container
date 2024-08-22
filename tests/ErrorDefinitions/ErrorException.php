<?php

declare(strict_types=1);

/**
 * This is a fairly convoluted example; the purpose of this test is to validate
 * error handling from deep in the config/wiring stack (i.e. a bunch of
 * recursive $container->get() calls). Normally this would be services
 * depending on other services, etc.
 */
return [
    'api_host' => function ($c) {
        // Whoops! This would have come from an outside source and should have
        // contained a different value.
        $url = '/relativePath';
        $parts = parse_url($url);
        // Simulate array offset ($parts['host']) being converted to
        // ErrorException. Newer PHPUnit sometimes swallows error->exception
        // conversion, so we're explicitly throwing it.
        throw new ErrorException('Undefined array offset..', 0, E_WARNING);
    },

    'api_url' => function ($c) {
        return 'https://' . $c->get('api_host');
    },

    'api_service' => function ($c) {
        // Normally this would be passing some other config value to another
        // class. That's not relevant to the test - we just want to generate an
        // error inside the stack somewhere.
        return $c->get('api_url');
    },
];
