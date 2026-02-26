<?php

declare(strict_types=1);

namespace R3H6\Typo3BrowserkitTesting;

use BlastCloud\Guzzler\Guzzler;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mailer\Transport\NullTransport;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * @method static ?Typo3Browser getClient(?Typo3Browser $newClient = null)
 */
abstract class WebTestCase extends FunctionalTestCase
{
    use BrowserKitAssertionsTrait;
    use DomCrawlerAssertionsTrait;
    use MailerAssertionsTrait;

    protected bool $autoConfigure = true;
    protected ?string $typo3DatabaseDump = null;

    public function setUp(): void
    {
        $this->setUpDefaultConfiguration();
        $this->createClient(); // Initialize client early for context setup

        if (!$this->autoConfigure) {
            parent::setUp();
            self::$container = $this->getContainer();
            return;
        }

        $this->setUpTestExtensionsToLoad();
        $this->setUpCoreExtensionsToLoad();
        $this->setUpSitesConfiguration();
        parent::setUp();
        $this->setUpDatabase();
        self::$container = $this->getContainer();
    }

    public function createClient(): Typo3Browser
    {
        $client = new Typo3Browser($this);
        return self::getClient($client);
    }

    public function doFrontendRequest(InternalRequest $request, InternalRequestContext $context): ResponseInterface
    {
        return $this->executeFrontendSubRequest($request, $context, false);
    }

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

    protected function importSQLDataSet(string $sqlFile): void
    {
        $content = file_get_contents($sqlFile);
        if ($content === false) {
            throw new \RuntimeException('Could not read SQL file: ' . $sqlFile);
        }

        // Split only on semicolon followed by a newline (optionally spaces/tabs)
        $parts = preg_split('/;[ \t]*\r?\n/', $content);
        $connection = $this->getConnectionPool()->getConnectionByName('Default');

        foreach ($parts as $part) {
            $statement = trim($part);
            if ($statement === '') {
                continue;
            }
            $connection->executeStatement($statement);
        }
    }

    private function setUpDatabase(): void
    {
        $pattern = $this->typo3DatabaseDump ?? '';
        if ($pattern === '') {
            return;
        }

        if (PathUtility::isAbsolutePath($pattern) === false) {
            $projectRoot = realpath(\Composer\InstalledVersions::getRootPackage()['install_path']);
            $pattern = $projectRoot . '/' . ltrim($pattern, '/\\');
        }

        $files = glob($pattern);
        if ($files === false || count($files) === 0) {
            return;
        }

        sort($files);
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            if (str_ends_with($file, '.csv')) {
                $this->importCSVDataSet($file);
            }
            if (str_ends_with($file, '.sql')) {
                $this->importSQLDataSet($file);
            }
        }
    }

    private function setUpDefaultConfiguration(): void
    {
        $defaultConfiguration = [
            'MAIL' => [
                'transport' => NullTransport::class,
            ],
        ];
        $this->configurationToUseInTestInstance = array_replace_recursive($defaultConfiguration, $this->configurationToUseInTestInstance);

        $this->typo3DatabaseDump = $this->typo3DatabaseDump
            ?? getenv('typo3DatabaseDump')
            ?: null;

        if (isset($this->guzzler) && $this->guzzler instanceof Guzzler) {
            $GLOBALS['__TYPO3_CONF_VARS']['HTTP']['handler']['mock'] = function () {
                return $this->guzzler->getHandlerStack();
            };
        }

        $this->testExtensionsToLoad = array_merge(
            [__DIR__ . '/../res/Extension/web_test_case'],
            $this->testExtensionsToLoad,
        );
    }

    private function setUpTestExtensionsToLoad(): void
    {
        $this->testExtensionsToLoad = array_merge(
            $this->getExtensionPaths('typo3-cms-extension'),
            $this->testExtensionsToLoad
        );
    }

    private function setUpCoreExtensionsToLoad(): void
    {
        $this->coreExtensionsToLoad = array_merge(
            $this->getExtensionPaths('typo3-cms-framework'),
            $this->coreExtensionsToLoad
        );
    }

    private function setUpSitesConfiguration(): void
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

    private function getExtensionPaths(string $packageType): array
    {
        $pathPrefix = match ($packageType) {
            'typo3-cms-extension' => 'typo3conf/ext/',
            'typo3-cms-framework' => 'typo3/sysext/',
            default => throw new \InvalidArgumentException('Unsupported package type: ' . $packageType),
        };
        $extensionsToLoad = [];
        $packages = \Composer\InstalledVersions::getInstalledPackagesByType($packageType);
        $projectRoot = realpath(\Composer\InstalledVersions::getRootPackage()['install_path']);
        $rootComposer = json_decode(file_get_contents($projectRoot . '/composer.json'), true);
        $vendorDir = realpath($projectRoot . '/' . ($rootComposer['config']['vendor-dir'] ?? 'vendor'));
        if (!is_dir($vendorDir)) {
            throw new \RuntimeException('Vendor directory not found: ' . $vendorDir);
        }
        $webDir = realpath($projectRoot . '/' . ($rootComposer['extra']['typo3/cms']['web-dir'] ?? 'public'));

        $fs = new Filesystem();
        foreach ($packages as $package) {
            $extensionPath = \Composer\InstalledVersions::getInstallPath($package);
            $composerJsonPath = realpath($extensionPath . '/composer.json');
            $finalPathPrefix = $pathPrefix;
            if (!str_starts_with($composerJsonPath, $vendorDir)) {
                $finalPathPrefix = $fs->makePathRelative(dirname(dirname($composerJsonPath)), $webDir);
            }
            $json = json_decode(file_get_contents($composerJsonPath), true);
            $extensionKey = $json['extra']['typo3/cms']['extension-key'] ?? null;
            if ($extensionKey === null) {
                continue;
            }
            $extensionsToLoad[] = $finalPathPrefix . $extensionKey;
        }
        return $extensionsToLoad;
    }
}
