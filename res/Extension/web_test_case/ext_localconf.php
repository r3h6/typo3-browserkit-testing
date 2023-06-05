<?php

declare(strict_types=1);

defined('TYPO3') or die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'WebTestCase',
    'Redirect',
    [\R3H6\WebTestCase\Controller\ExampleController::class => 'redirect,show'],
    [\R3H6\WebTestCase\Controller\ExampleController::class => 'redirect']
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'WebTestCase',
    'Response',
    [\R3H6\WebTestCase\Controller\ExampleController::class => 'response,show'],
    [\R3H6\WebTestCase\Controller\ExampleController::class => 'response']
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'WebTestCase',
    'Propagate',
    [\R3H6\WebTestCase\Controller\ExampleController::class => 'propagateResponse,show'],
    [\R3H6\WebTestCase\Controller\ExampleController::class => 'propagateResponse']
);
