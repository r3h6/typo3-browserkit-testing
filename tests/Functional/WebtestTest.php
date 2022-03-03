<?php

namespace R3H6\Typo3BrowserkitTesting\Tests\Functional;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use R3H6\Typo3BrowserkitTesting\BrowserKitTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

class WebtestTest extends BrowserKitTestCase
{
    protected $coreExtensionsToLoad = [
        'fluid_styled_content',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/pages.xml');
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/tt_content.xml');
        $this->setUpSites(1);
        $this->setUpFrontendRootPage(1, [
            'setup' => [ 'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript' ],
            'constants' => [ 'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript' ],
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
    public function foo()
    {

        $client = $this->createClient();
        /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
        $crawler = $client->request('GET', '/root');


        // $request = (new InternalRequest())->withPageId(1);
        // $response = $this->executeFrontendRequest($request);

        self::assertSame(' ', (string) $crawler->text());
    }



}
