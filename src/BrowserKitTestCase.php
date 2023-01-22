<?php

declare(strict_types=1);

namespace R3H6\Typo3BrowserkitTesting;

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalResponse;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BrowserKitTestCase extends FunctionalTestCase
{
    use DomCrawlerAssertionsTrait;
    use MailerAssertionsTrait;
    use BrowserKitTrait;

    protected const MAIL_SETTINGS = [
        'transport' => TestTransport::class,
    ];

    /**
     * @var Client|null
     */
    protected static $client;

    public function executeFrontendRequest(
        InternalRequest $request,
        InternalRequestContext $context = null,
        bool $followRedirects = false
    ): InternalResponse {
        $doSubrequest = (new Typo3Version())->getMajorVersion() > 10;
        if ($doSubrequest) {
            return parent::executeFrontendSubRequest($request, $context, $followRedirects);
        }

        return parent::executeFrontendRequest($request, $context, $followRedirects);
    }

    public static function getClient(BrowserKitTestCase $testCase = null): Client
    {
        if (static::$client === null) {
            static::$client = new Client($testCase);
        }
        return static::$client;
    }

    protected function setUp(): void
    {
        parent::setUp();
        TestTransport::reset();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        static::$client = null;
    }
}
