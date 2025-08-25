<?php

declare(strict_types=1);

namespace R3H6\Typo3BrowserkitTesting;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use Symfony\Component\Filesystem\Filesystem;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalResponse;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

class WebTestCase extends FunctionalTestCase
{
    use DomCrawlerAssertionsTrait;
    use MailerAssertionsTrait;
    use BrowserKitTrait;

    protected bool $autoConfigureExtensions = true;
    protected array $fixturesToLoad = [];

    protected const MAIL_SETTINGS = [
        'transport' => TestTransport::class,
    ];

    protected static ?Typo3Client $typo3Client;

    public function executeTypo3FrontendRequest(
        InternalRequest $request,
        InternalRequestContext $context,
        bool $followRedirects
    ): ResponseInterface {
        return parent::executeFrontendSubRequest($request, $context, $followRedirects);
    }

    public static function getTypo3Client(): Typo3Client
    {
        return self::$typo3Client;
    }

    protected function setUp(): void
    {
        if ($this->autoConfigureExtensions) {
            $this->initializeTestExtensionsToLoad();
            $this->initializeCoreExtensionsToLoad();
            $this->initializeConfigurationToUseInTestInstance();
            $this->linkSitesToTestInstance();
        }
        parent::setUp();
        TestTransport::reset();
        self::$typo3Client = new Typo3Client($this);
        $this->linkTestExtensionsToInstance();

        foreach ($this->fixturesToLoad as $fixture) {
            $this->importCSVDataSet($fixture);
        }

        if (isset($this->guzzler)) {
            $GLOBALS['__TYPO3_CONF_VARS']['HTTP']['handler']['mock'] = function () {
                return $this->guzzler->getHandlerStack();
            };
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        self::$typo3Client = null;
    }

    private function initializeTestExtensionsToLoad(): void
    {
        $this->testExtensionsToLoad = array_merge(
            $this->getExtensionPaths('typo3-cms-extension', 'typo3conf/ext/'),
            $this->testExtensionsToLoad
        );
    }

    private function initializeCoreExtensionsToLoad(): void
    {
        $this->coreExtensionsToLoad = array_merge(
            $this->getExtensionPaths('typo3-cms-framework', ''),
            $this->coreExtensionsToLoad
        );
    }

    private function getExtensionPaths(string $packageType, string $pathPrefix): array
    {
        $extensionsToLoad = [];
        $packages = \Composer\InstalledVersions::getInstalledPackagesByType($packageType);
        foreach ($packages as $package) {
            $extensionPath = \Composer\InstalledVersions::getInstallPath($package);
            $composerJsonPath = $extensionPath . '/composer.json';
            $json = json_decode(file_get_contents($composerJsonPath), true);
            $extensionKey = $json['extra']['typo3/cms']['extension-key'] ?? null;
            if ($extensionKey === null) {
                continue;
            }
            $extensionsToLoad[] = $pathPrefix . $extensionKey;
        }
        return $extensionsToLoad;
    }

    private function initializeConfigurationToUseInTestInstance(): void
    {
        $this->configurationToUseInTestInstance = array_merge_recursive([
            'MAIL' => self::MAIL_SETTINGS,
        ], $this->configurationToUseInTestInstance);
    }

    private function linkSitesToTestInstance(): void
    {
        $fs = new Filesystem();

        $projectRoot = realpath(\Composer\InstalledVersions::getRootPackage()['install_path']);
        $sitesPath = $projectRoot . '/config/sites';
        $instancePath = self::getInstancePath();

        $relativeSitesPath = $fs->makePathRelative($sitesPath, $instancePath);

        $this->pathsToLinkInTestInstance = array_merge([
            $relativeSitesPath => 'typo3conf/sites',
        ], $this->pathsToLinkInTestInstance);
    }

    // private function setUpSitePackage(int $pageUid, string $sitePackage): void
    // {
    //     $this->setUpFrontendRootPage($pageUid, [], [
    //         'include_static_file' => 'EXT:' . $sitePackage . '/Configuration/TypoScript',
    //     ]);
    // }

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
