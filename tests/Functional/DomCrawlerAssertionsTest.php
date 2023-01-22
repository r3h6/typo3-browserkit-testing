<?php

declare(strict_types=1);

namespace R3H6\Typo3BrowserkitTesting\Tests\Functional;

use R3H6\Typo3BrowserkitTesting\BrowserKitTestCase;

class DomCrawlerAssertionsTest extends BrowserKitTestCase
{
    protected $coreExtensionsToLoad = [
        'fluid_styled_content',
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
        $this->importDataSet(__DIR__ . '/../../res/Fixtures/Database/tt_content.xml');
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
    public function foo(): void
    {
        $client = self::getClient($this);
        /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
        $crawler = $client->request('GET', '/page2');

        $formNamespace = 'tx_form_formframework[ext-form-simple-contact-form-example-2]';
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
}
