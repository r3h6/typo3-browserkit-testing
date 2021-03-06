<?php

namespace R3H6\Typo3BrowserkitTesting;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalResponse;

trait BrowserKitTrait
{
    /**
     * @var Client
     */
    protected static $client;

    public function executeFrontendRequest(
        InternalRequest $request,
        InternalRequestContext $context = null,
        bool $followRedirects = false
    ): InternalResponse {
        return parent::executeFrontendRequest($request, $context, $followRedirects);
    }

    public function getClient(): Client
    {
        if (static::$client === null) {
            static::$client = new Client($this);
        }
        return static::$client;
    }

    protected function setUpSites($pageId, array $sites = [])
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
                $fileContent = str_replace('\'{rootPageId}\'', $pageId, $fileContent);
                GeneralUtility::writeFile($target, $fileContent);
            }
        }
    }
}
