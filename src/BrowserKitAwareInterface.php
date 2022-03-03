<?php

namespace R3H6\Typo3BrowserkitTesting;

use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalResponse;

interface BrowserKitAwareInterface
{
    public function executeFrontendRequest(InternalRequest $request, InternalRequestContext $context = null, bool $followRedirects = false): InternalResponse;

    public function getClient(): Client;
}
