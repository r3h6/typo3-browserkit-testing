<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use R3H6\ExampleExtension\Controller\ExampleController;
use Symfony\Component\DependencyInjection\Extension\Extension;

defined('TYPO3') or die();

ExtensionUtility::configurePlugin(
    'ExampleExtension',
    'Show',
    [ExampleController::class => 'show'],
    [],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);

ExtensionUtility::configurePlugin(
    'ExampleExtension',
    'Redirect',
    [ExampleController::class => 'redirect,show'],
    [ExampleController::class => 'redirect'],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);

ExtensionUtility::configurePlugin(
    'ExampleExtension',
    'Response',
    [ExampleController::class => 'response,show'],
    [ExampleController::class => 'response'],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);

ExtensionUtility::configurePlugin(
    'ExampleExtension',
    'Propagate',
    [ExampleController::class => 'propagateResponse,show'],
    [ExampleController::class => 'propagateResponse'],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);

ExtensionUtility::configurePlugin(
    'ExampleExtension',
    'Api',
    [ExampleController::class => 'api'],
    [ExampleController::class => 'api'],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);

ExtensionUtility::configurePlugin(
    'ExampleExtension',
    'Upload',
    [ExampleController::class => 'upload'],
    [ExampleController::class => 'upload'],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);
