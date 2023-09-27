<?php

declare(strict_types=1);

namespace R3H6\Typo3BrowserkitTesting;

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalResponse;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class WebTestCase extends FunctionalTestCase
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

    public static function getClient(WebTestCase $testCase = null): Client
    {
        if (self::$client === null) {
            self::$client = new Client($testCase);
        }
        return self::$client;
    }

    protected function setUp(): void
    {
        parent::setUp();
        TestTransport::reset();
        $this->linkTestExtensionsToInstance();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        self::$client = null;
    }

    private function linkTestExtensionsToInstance(): void
    {
        $absoluteExtensionPath = dirname(__DIR__) . '/res/Extension/web_test_case';
        $destinationPath = $this->instancePath . '/typo3conf/ext/' . basename($absoluteExtensionPath);

        if (file_exists($destinationPath)) {
            return;
        }

        $success = symlink($absoluteExtensionPath, $destinationPath);
        if (!$success) {
            throw new \Exception(
                'Can not link extension folder: ' . $absoluteExtensionPath . ' to ' . $destinationPath,
                1674680777613
            );
        }

        $extensionName = basename($absoluteExtensionPath);
        $packageStates = include $this->instancePath . '/typo3conf/PackageStates.php';
        $packageStates['packages'][$extensionName] = [
            'packagePath' => 'typo3conf/ext/' . $extensionName . '/',
        ];
        $result = file_put_contents(
            $this->instancePath . '/typo3conf/PackageStates.php',
            '<?php' . chr(10) .
            'return ' .
            ArrayUtility::arrayExport(
                $packageStates
            ) .
            ';'
        );

        if (!$result) {
            throw new \Exception('Can not write PackageStates', 1674684323839);
        }
    }
}
