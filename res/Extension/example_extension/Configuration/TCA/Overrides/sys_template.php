<?php

defined('TYPO3') or die();

(static function (): void {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'example_extension',
        'Configuration/TypoScript',
        'Example Extension'
    );
})();
