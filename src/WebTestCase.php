<?php

declare(strict_types=1);

namespace R3H6\Typo3BrowserkitTesting;

use BlastCloud\Guzzler\Guzzler;
use Psr\Http\Message\ResponseInterface;
use R3H6\WebTestCase\EventListener\MessageLoggerListener;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Process\Process;
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
    private static string $projectRoot;

    public static function setUpBeforeClass(): void
    {
        self::$projectRoot = str_replace('/.Build', '', dirname(ORIGINAL_ROOT));
    }

    public function setUp(): void
    {
        MessageLoggerListener::setUp();
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
        if (!is_file($sqlFile) || !is_readable($sqlFile)) {
            throw new \RuntimeException('SQL file not readable: ' . $sqlFile);
        }

        $sqlContent = file_get_contents($sqlFile);
        if ($sqlContent === false) {
            throw new \RuntimeException('Could not read SQL file: ' . $sqlFile);
        }

        $params = $this->getConnectionPool()->getConnectionByName('Default')->getParams();
        $command = $this->buildMysqlCommand($params);
        $env = $this->buildMysqlEnvironment($params);

        $process = new Process($command, null, $env, $sqlContent);
        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(
                sprintf('MySQL import failed (%s): %s', $sqlFile, $process->getErrorOutput() ?: $process->getOutput())
            );
        }
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, string>
     */
    private function buildMysqlCommand(array $params): array
    {
        $database = $params['dbname'] ?? $params['database'] ?? null;
        if ($database === null) {
            throw new \RuntimeException('No database name configured for connection "Default".');
        }

        $command = ['mysql', '--default-character-set=utf8mb4'];

        if (!empty($params['unix_socket'])) {
            $command[] = '--socket=' . $params['unix_socket'];
        } else {
            $command[] = '--host=' . ($params['host'] ?? '127.0.0.1');
            if (!empty($params['port'])) {
                $command[] = '--port=' . $params['port'];
            }
        }

        if (!empty($params['user'])) {
            $command[] = '--user=' . $params['user'];
        }

        $command[] = $database;

        return $command;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, string>
     */
    private function buildMysqlEnvironment(array $params): array
    {
        $env = [];
        if (!empty($params['password'])) {
            $env['MYSQL_PWD'] = (string)$params['password'];
        }
        // Cast $_ENV to array to satisfy static analysis and keep runtime behavior.
        return $env + (array)$_ENV;
    }

    private function setUpDatabase(): void
    {
        $pattern = $this->typo3DatabaseDump ?? '';
        if ($pattern === '') {
            return;
        }

        if (PathUtility::isAbsolutePath($pattern) === false) {
            $pattern = self::$projectRoot . '/' . ltrim($pattern, '/\\');
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

        // Normalize environment value: getenv() can return false on failure.
        // Avoid complex ??/:? expressions to make intent explicit for static analysis.
        if ($this->typo3DatabaseDump === null) {
            $envValue = getenv('typo3DatabaseDump');
            $this->typo3DatabaseDump = ($envValue === false) ? null : $envValue;
        }

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
        // Don't override a site configuration the test already declared explicitly
        // (e.g. a dedicated fixture site instead of the real project sites) - both
        // would otherwise try to link to the same 'typo3conf/sites' destination.
        if (in_array('typo3conf/sites', $this->pathsToLinkInTestInstance, true)) {
            return;
        }

        $fs = new Filesystem();

        $sitesPath = self::$projectRoot . '/config/sites';
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
        $packageManager = new \TYPO3\TestingFramework\Composer\ComposerPackageManager();
        $packages = \Composer\InstalledVersions::getInstalledPackagesByType($packageType);
        foreach ($packages as $package) {
            $extensionPath = realpath(\Composer\InstalledVersions::getInstallPath($package));
            if ($extensionPath === false || !is_file($extensionPath . '/composer.json')) {
                continue;
            }
            $json = json_decode(file_get_contents($extensionPath . '/composer.json'), true);
            $extensionKey = $json['extra']['typo3/cms']['extension-key'] ?? null;
            if ($extensionKey === null) {
                continue;
            }
            // ComposerPackageManager silently drops any package from its extension-key lookup
            // map whose own composer "replace" entry targets its own package name (a common
            // TER-migration pattern, used by all intersim/* packages), because it treats that
            // entry the same as a package being replaced by another one. Both Testbase's
            // extension symlinking and its PackageStates.php generation resolve test extensions
            // through that map by extension key, so a dropped package would otherwise be
            // unresolvable there and fail with "Test extension path ... not found" or get
            // symlinked/registered under the wrong (directory-guessed) name. Resolving through
            // the absolute install path here forces ComposerPackageManager to register the
            // package under its real extension key as a side effect, healing the map before
            // any other code needs to look it up - so plain "typo3conf/ext/<key>" (or
            // "typo3/sysext/<key>") keeps working correctly everywhere afterwards, the same as
            // for any package the map already knew about.
            $packageManager->getPackageInfoWithFallback($extensionPath);
            $extensionsToLoad[] = $pathPrefix . $extensionKey;
        }
        return $extensionsToLoad;
    }
}
