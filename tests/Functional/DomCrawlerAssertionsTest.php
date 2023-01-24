<?php

declare(strict_types=1);

namespace R3H6\Typo3BrowserkitTesting\Tests\Functional;

use R3H6\Typo3BrowserkitTesting\BrowserKitTestCase;
use R3H6\Typo3BrowserkitTesting\Client;
use R3H6\Typo3BrowserkitTesting\ServerParameters as ServerParameters;

class DomCrawlerAssertionsTest extends BrowserKitTestCase
{
    protected $coreExtensionsToLoad = [
        'fluid_styled_content',
        'felogin',
        'form',
    ];

    protected $configurationToUseInTestInstance = [
        'MAIL' => self::MAIL_SETTINGS,
    ];

    protected $pathsToLinkInTestInstance = [
        '../../../../../../res/Fixtures/Folder/fileadmin/form_definitions' => 'fileadmin/form_definitions'
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/../../res/Fixtures/Database/pages.xml');
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
        $this->importDataSet(__DIR__ . '/../../res/Fixtures/Database/form_framework.xml');

        $client = self::getClient($this);
        /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
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
        $crawler = $client->clickButton('Submit');


        $email = self::getMailerMessage();
        self::assertEmailHeaderSame($email, 'subject', 'Your message: Test subject');
    }

    /**
     * @test
     */
    public function login(): void
    {
        $this->importDataSet(__DIR__ . '/../../res/Fixtures/Database/felogin_login.xml');

        $client = self::getClient($this);
        /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
        $crawler = $client->request('GET', '/page2');
        $crawler = $client->clickButton('Login', [
            'user' => 'testuser',
            'pass' => 'password',
        ]);

        self::assertSelectorTextSame('.frame-type-felogin_login h3', 'Login successful');

        $crawler = $client->request('GET', '/page2');
        self::assertInputValueSame('logintype', 'logout', "Response:\n" . $client->getResponse());
    }

    /**
     * @test
     */
    public function accessRestrictedContent(): void
    {
        $this->importDataSet(__DIR__ . '/../../res/Fixtures/Database/accessRestrictedContent.xml');

        $client = self::getClient($this);
        /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
        $crawler = $client->request('GET', '/page2');
        self::assertSelectorTextNotContains('body', 'Only for your eyes');

        $client->setServerParameter(ServerParameters::TYPO3_FEUSER, '1');
        $crawler = $client->request('GET', '/page2');
        self::assertSelectorTextContains('body', 'Only for your eyes');
    }
}
