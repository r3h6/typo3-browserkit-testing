<?php

defined('TYPO3') or die();

(static function (): void {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'ExampleExtension',
        'Redirect',
        'Redirect plugin'
    );
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'ExampleExtension',
        'Response',
        'Response plugin'
    );
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'ExampleExtension',
        'Propagate',
        'Propagate plugin'
    );
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'ExampleExtension',
        'Api',
        'Api plugin'
    );
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'ExampleExtension',
        'Upload',
        'Upload plugin'
    );
})();
