<?php

declare(strict_types=1);

namespace R3H6\Typo3BrowserkitTesting;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait BrowserKitTrait
{
    /**
     * @param array<int, string> $sites
     */
    protected function setUpSites(int $pageId, array $sites = []): void
    {
        if (empty($sites[$pageId])) {
            $sites[$pageId] = __DIR__ . '/../res/Fixtures/Frontend/site.yaml';
        }

        foreach ($sites as $identifier => $file) {
            $path = Environment::getConfigPath() . '/sites/' . $identifier . '/';
            $target = $path . 'config.yaml';
            if (!file_exists($target)) {
                GeneralUtility::mkdir_deep($path);
                if (!file_exists($file)) {
                    $file = GeneralUtility::getFileAbsFileName($file);
                }
                $fileContent = file_get_contents($file);
                $fileContent = str_replace('\'{rootPageId}\'', (string)$identifier, $fileContent);
                GeneralUtility::writeFile($target, $fileContent);
            }
        }
    }
}
