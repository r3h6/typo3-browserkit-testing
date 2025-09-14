<?php

namespace R3H6\Typo3BrowserkitTesting\Client;

use R3H6\Typo3BrowserkitTesting\WebTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

class AuthenticatedUser
{
    use PhpBrowserTrait;

    public function __construct(int $userId)
    {
        $defaultContext = (new InternalRequestContext())->withFrontendUserId($userId);
        WebTestCase::getClient()->setDefaultContext($defaultContext);
    }
}
