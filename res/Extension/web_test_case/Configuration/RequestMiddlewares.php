<?php

return [
    'frontend' => [
        'r3h6/web-test-case/middleware' => [
            'target' => \R3H6\WebTestCase\Middleware\WebTestCaseMiddleware::class,
            'before' => [
                'typo3/cms-frontend/timetracker',
            ],
        ],
    ],
];
