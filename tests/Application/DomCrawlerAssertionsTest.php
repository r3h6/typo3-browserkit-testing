<?php

declare(strict_types=1);

namespace R3H6\Typo3BrowserkitTesting\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use R3H6\Typo3BrowserkitTesting\WebTestCase;
use R3H6\Typo3BrowserkitTesting\TestTransport;
use R3H6\Typo3BrowserkitTesting\ServerParameters as ServerParameters;
use Symfony\Component\Mailer\Transport\NullTransport;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

class DomCrawlerAssertionsTest extends WebTestCase
{
    protected bool $autoConfigure = false;
    protected array $coreExtensionsToLoad = [
        'fluid_styled_content',
        'felogin',
        'form',
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
        '../../../../../../tests/Application/Fixtures/Folder/fileadmin/form_definitions' => 'fileadmin/form_definitions'
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/pages.csv');
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
    public function showAction(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/webtestcase_show.csv');

        $client = $this->createClient();
        $crawler = $client->request('GET', '/page2');
        self::assertSelectorTextContains('body', 'The show must go on', "Response:\n" . $client->getResponse());
    }

    #[Test]
    public function submitForm(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/form_framework.csv');

        $client = $this->createClient();
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

        $crawler = $client->clickElement('Submit');
        self::assertSelectorTextContains('body', 'Thank you!', "Response:\n" . $client->getResponse());

        $email = self::getMailerMessage();
        self::assertEmailHeaderSame($email, 'subject', 'Your message: Test subject');

    }

    #[Test]
    public function login(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/felogin_login.csv');

        $client = $this->createClient();
        $crawler = $client->request('GET', '/page2');
        $crawler = $client->clickButton('Login', [
            'user' => 'testuser',
            'pass' => 'password',
        ]);

        self::assertSelectorTextSame('.frame-type-felogin_login h3', 'Login successful', "Response:\n" . $client->getResponse());

        $crawler = $client->request('GET', '/page2');
        self::assertInputValueSame('logintype', 'logout', "Response:\n" . $client->getResponse());
    }

    #[Test]
    public function accessRestrictedContent(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/accessRestrictedContent.csv');

        $client = $this->createClient();
        $crawler = $client->request('GET', '/page2');
        self::assertSelectorTextNotContains('body', 'Only for your eyes', "Response:\n" . $client->getResponse());
        $context = (new InternalRequestContext())->withFrontendUserId(1);
        $client->setDefaultContext($context);
        $crawler = $client->request('GET', '/page2');
        self::assertSelectorTextContains('body', 'Only for your eyes', "Response:\n" . $client->getResponse());
    }

    #[Test]
    public function handleLegacyRedirect(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/webtestcase_redirect.csv');

        $client = $this->createClient();
        $crawler = $client->request('GET', '/page2');
        self::assertSelectorTextContains('body', 'The show must go on', "Response:\n" . $client->getResponse());
        self::assertSelectorTextContains('body', 'Redirected from', "Response:\n" . $client->getResponse());
    }

    #[Test]
    public function handleResponseRedirect(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/webtestcase_response.csv');

        $client = $this->createClient();
        $crawler = $client->request('GET', '/page2');
        self::assertSelectorTextContains('body', 'The show must go on', "Response:\n" . $client->getResponse());
        self::assertSelectorTextContains('body', 'Redirected from', "Response:\n" . $client->getResponse());
    }

    #[Test]
    public function handlePropagateExceptionRedirect(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/webtestcase_propagate.csv');

        $client = $this->createClient();
        $crawler = $client->request('GET', '/page2');
        self::assertSelectorTextContains('body', 'The show must go on', "Response:\n" . $client->getResponse());
        self::assertSelectorTextContains('body', 'Redirected from', "Response:\n" . $client->getResponse());
    }
}
