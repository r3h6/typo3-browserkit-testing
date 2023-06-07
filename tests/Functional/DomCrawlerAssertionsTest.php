<?php

declare(strict_types=1);

namespace R3H6\Typo3BrowserkitTesting\Tests\Functional;

use R3H6\Typo3BrowserkitTesting\WebTestCase;
use R3H6\Typo3BrowserkitTesting\Client;
use R3H6\Typo3BrowserkitTesting\ServerParameters as ServerParameters;

class DomCrawlerAssertionsTest extends WebTestCase
{
    protected array $coreExtensionsToLoad = [
        'fluid_styled_content',
        'felogin',
        'form',
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

    protected array $pathsToLinkInTestInstance = [
        '../../../../../../res/Fixtures/Folder/fileadmin/form_definitions' => 'fileadmin/form_definitions'
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

    /**
     * @test
     */
    public function submitForm(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../res/Fixtures/Database/form_framework.csv');

        $client = self::getClient($this);
        $crawler = $client->request('GET', '/page2');

        $formNamespace = 'tx_form_formframework[ext-form-simple-contact-form-example-1]';
        $form = $crawler->selectButton('next Page')->form();
        $form->setValues([
            $formNamespace . '[name]' => 'Kasper Skårhøj',
            $formNamespace . '[subject]' => 'Test subject',
            $formNamespace . '[email]' => 'kasper@typo3.org',
            $formNamespace . '[message]' => 'Lorem ipsum...',
        ]);

        $crawler = $client->submit($form);
        self::assertSelectorTextSame('.frame-type-form_formframework legend', 'Summary page', "Response:\n" . $client->getResponse());

        $crawler = $client->clickButton('Submit');
        self::assertSelectorTextContains('body', 'Thank you!', "Response:\n" . $client->getResponse());

        $email = self::getMailerMessage();
        self::assertEmailHeaderSame($email, 'subject', 'Your message: Test subject');

    }

    /**
     * @test
     */
    public function login(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../res/Fixtures/Database/felogin_login.csv');

        $client = self::getClient($this);
        $crawler = $client->request('GET', '/page2');
        $crawler = $client->clickButton('Login', [
            'user' => 'testuser',
            'pass' => 'password',
        ]);

        self::assertSelectorTextSame('.frame-type-felogin_login h3', 'Login successful', "Response:\n" . $client->getResponse());

        $crawler = $client->request('GET', '/page2');
        self::assertInputValueSame('logintype', 'logout', "Response:\n" . $client->getResponse());
    }

    /**
     * @test
     */
    public function accessRestrictedContent(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../res/Fixtures/Database/accessRestrictedContent.csv');

        $client = self::getClient($this);
        $crawler = $client->request('GET', '/page2');
        self::assertSelectorTextNotContains('body', 'Only for your eyes', "Response:\n" . $client->getResponse());

        $client->setServerParameter(ServerParameters::TYPO3_FEUSER, '1');
        $crawler = $client->request('GET', '/page2');
        self::assertSelectorTextContains('body', 'Only for your eyes', "Response:\n" . $client->getResponse());
    }

    /**
     * @test
     */
    public function handleLegacyRedirect(): void
    {
        error_reporting(E_ALL & ~E_USER_DEPRECATED);
        $this->importCSVDataSet(__DIR__ . '/../../res/Fixtures/Database/webtestcase_redirect.csv');

        $client = self::getClient($this);
        $crawler = $client->request('GET', '/page2');
        self::assertSelectorTextContains('body', 'The show must go on', "Response:\n" . $client->getResponse());
    }

    /**
     * @test
     */
    public function handleResponseRedirect(): void
    {
        error_reporting(E_ALL & ~E_USER_DEPRECATED);
        $this->importCSVDataSet(__DIR__ . '/../../res/Fixtures/Database/webtestcase_response.csv');

        $client = self::getClient($this);
        $crawler = $client->request('GET', '/page2');
        self::assertSelectorTextContains('body', 'The show must go on', "Response:\n" . $client->getResponse());
    }

    /**
     * @test
     */
    public function handlePropagateExceptionRedirect(): void
    {
        error_reporting(E_ALL & ~E_USER_DEPRECATED);
        $this->importCSVDataSet(__DIR__ . '/../../res/Fixtures/Database/webtestcase_propagate.csv');

        $client = self::getClient($this);
        $crawler = $client->request('GET', '/page2');
        self::assertSelectorTextContains('body', 'The show must go on', "Response:\n" . $client->getResponse());
    }
}
