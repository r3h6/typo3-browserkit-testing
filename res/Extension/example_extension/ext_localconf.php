<?php

declare(strict_types=1);

defined('TYPO3') or die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'ExampleExtension',
    'Redirect',
    [\R3H6\ExampleExtension\Controller\ExampleController::class => 'redirect,show'],
    [\R3H6\ExampleExtension\Controller\ExampleController::class => 'redirect']
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'ExampleExtension',
    'Response',
    [\R3H6\ExampleExtension\Controller\ExampleController::class => 'response,show'],
    [\R3H6\ExampleExtension\Controller\ExampleController::class => 'response']
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'ExampleExtension',
    'Propagate',
    [\R3H6\ExampleExtension\Controller\ExampleController::class => 'propagateResponse,show'],
    [\R3H6\ExampleExtension\Controller\ExampleController::class => 'propagateResponse']
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'ExampleExtension',
    'Api',
    [\R3H6\ExampleExtension\Controller\ExampleController::class => 'api'],
    [\R3H6\ExampleExtension\Controller\ExampleController::class => 'api']
);
