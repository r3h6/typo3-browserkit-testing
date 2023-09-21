<?php

declare(strict_types=1);

namespace R3H6\Typo3BrowserkitTesting\Tests\Functional;

use GuzzleHttp\Psr7\Response;
use BlastCloud\Guzzler\UsesGuzzler;
use R3H6\Typo3BrowserkitTesting\Client;
use R3H6\Typo3BrowserkitTesting\WebTestCase;
use R3H6\Typo3BrowserkitTesting\ServerParameters as ServerParameters;

class ApiExampleTest extends WebTestCase
{
    use UsesGuzzler;

    protected array $coreExtensionsToLoad = [
        'fluid_styled_content',
    ];
    protected array $configurationToUseInTestInstance = [
        'MAIL' => WebTestCase::MAIL_SETTINGS,
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
            'TYPO3' => [
                'CMS' => [
                    'Frontend' => [
                        'Authentication' => [
                            'writerConfiguration' => [
                                \TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
                                    \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    public function setUp(): void
    {
        $this->configurationToUseInTestInstance['HTTP']['handler']['guzzler'] = function (callable $handler)
        {
            return $this->guzzler->getHandlerStack();
        };

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

    /**
     * @test
     * @group api
     */
    public function mockApi(): void
    {
        $this->guzzler->expects($this->once())
            ->get('/posts')
            ->willRespond(new Response());

        $this->importCSVDataSet(__DIR__ . '/../../res/Fixtures/Database/webtestcase_api.csv');

        $client = self::getClient($this);
        $crawler = $client->request('GET', '/page2');


        $content = (string) $client->getResponse();
        self::assertSame('body', $content);



    }


}
