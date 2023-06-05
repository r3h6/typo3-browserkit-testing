<?php

defined('TYPO3') or die();

(static function (): void {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'WebTestCase',
        'Redirect',
        'Redirect plugin'
    );
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'WebTestCase',
        'Response',
        'Response plugin'
    );
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'WebTestCase',
        'Propagate',
        'Propagate plugin'
    );
})();
