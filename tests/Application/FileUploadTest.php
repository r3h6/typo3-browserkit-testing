<?php

declare(strict_types=1);

namespace R3H6\Typo3BrowserkitTesting\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use R3H6\Typo3BrowserkitTesting\WebTestCase;
use Symfony\Component\Mailer\Transport\NullTransport;

class FileUploadTest extends WebTestCase
{
    protected bool $autoConfigure = false;
    protected array $coreExtensionsToLoad = [
        'fluid_styled_content',
    ];
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/example_extension',
    ];
    protected array $configurationToUseInTestInstance = [
        'MAIL' => [
            'transport' => NullTransport::class,
        ],
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/pages.csv');
        $this->setUpSites(1);
        $this->setUpFrontendRootPage(1, [
            'setup' => [
                'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
            ],
            'constants' => [
                'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript'
            ],
        ], [
            'config' => '
                page = PAGE
                page.10 =< styles.content.get
            '
        ]);
    }

    #[Test]
    public function uploadFileIsHandled(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/webtestcase_upload.csv');

        $client = $this->createClient();
        $client->request('GET', '/page2');

        $filePath = __DIR__ . '/Fixtures/Uploads/sample.txt';
        $client->setInputValue('input[name="tx_exampleextension_upload[uploadFile]"]', $filePath);
        $crawler = $client->clickButton('Upload');

        self::assertSelectorTextContains('.upload-result', basename($filePath));
        self::assertSelectorTextContains('.upload-result', (string)filesize($filePath));
        self::assertSelectorTextContains('.upload-result', sha1_file($filePath) ?: '');
    }
}
