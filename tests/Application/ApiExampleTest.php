<?php

declare(strict_types=1);

namespace R3H6\Typo3BrowserkitTesting\Tests\Functional;

use GuzzleHttp\Psr7\Response;
use BlastCloud\Guzzler\UsesGuzzler;
use PHPUnit\Framework\Attributes\Test;
use R3H6\Typo3BrowserkitTesting\HttpBrowser;
use R3H6\Typo3BrowserkitTesting\WebTestCase;
use R3H6\Typo3BrowserkitTesting\TestTransport;
use Symfony\Component\Mailer\Transport\NullTransport;
use R3H6\Typo3BrowserkitTesting\ServerParameters as ServerParameters;

class ApiExampleTest extends WebTestCase
{
    use UsesGuzzler;

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
        'LOG' => [
            'R3H6' => [
                'WebTestCase' => [
                    'writerConfiguration' => [
                        \TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
                            \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [],
                        ],
                    ],
                ],
            ],
        ],
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../res/Fixtures/Database/pages.csv');
        $this->setUpSites(1);
        $this->setUpFrontendRootPage(1, [
            'setup' => [
                'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                'EXT:form/Configuration/TypoScript/setup.typoscript'
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
    public function mockApi(): void
    {
        $this->guzzler->expects($this->once())
            ->post('/posts')
            ->withJson([
                'title' => 'TYPO3',
                'body' => 'Inspire people to share',
                'userId' => 1,
            ])
            ->willRespond(new Response(200, [], 'GUZZLER'));

        $this->importCSVDataSet(__DIR__ . '/../../res/Fixtures/Database/webtestcase_api.csv');

        $client = $this->createClient();
        $crawler = $client->request('GET', '/page2');
        self::assertSelectorTextContains('body', 'GUZZLER');
    }
}
