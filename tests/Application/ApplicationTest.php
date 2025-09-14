<?php

declare(strict_types=1);

namespace R3H6\Typo3BrowserkitTesting\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Package\PackageManager;
use R3H6\Typo3BrowserkitTesting\WebTestCase;
use R3H6\Typo3BrowserkitTesting\ApplicationTestCase;
use R3H6\Typo3BrowserkitTesting\Client\AnonymousVisitor;
use R3H6\Typo3BrowserkitTesting\Client\AuthenticatedUser;

class ApplicationTest extends WebTestCase
{
    protected array $configurationToUseInTestInstance = [
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
                    // 'Frontend' => [
                    //     'Authentication' => [
                            'writerConfiguration' => [
                                \TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
                                    \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [],
                                ],
                            ],
                    //     ],
                    // ],
                ],
            ],
        ],
    ];


    #[Test]
    public function testSite(): void
    { 
        $client = $this->createClient();
        $crawler = $client->request('GET', '/');
        
        // self::assertSame(' ', $crawler->outerHtml());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'TYPO3');
    }

    #[Test]
    public function auth(): void
    {
        $I = new AuthenticatedUser(1);
        $I->amOnPage('/cug');
        $I->see('CUG', 'h1');
    }

    #[Test]
    public function login(): void
    {
        $I = new AnonymousVisitor();
        $I->amOnUrl('/login');
        $I->see('Login', 'h1');
        $I->fillField('user', 'cug');
        $I->fillField('pass', 'Password$1');
        $I->click('Login', '.frame-type-felogin_login');
        $I->see('Login successful');
        $I->amOnPage('/cug');
        $I->see('CUG', 'h1');
    }

/*
    protected array $pathsToLinkInTestInstance = [
        '../../../../../../res/Fixtures/Folder/fileadmin/form_definitions' => 'fileadmin/form_definitions'
    ];


    protected array $fixturesToLoad = [
        __DIR__ . '/../../res/Fixtures/Database/pages.csv',
        __DIR__ . '/../../res/Fixtures/Database/sys_template.csv',
        __DIR__ . '/../../res/Fixtures/Database/form_framework.csv',
    ];

    public function testFoo(): void
    {
        $I = new AnonymousVisitor();
        $I->amOnUrl('https://typo3-browserkit-testing.ddev.site/');
        $I->amOnPage('/page2');
        $I->see('Dummy 1-2');
        $I->see('Dummy 2-2');
    }
        */
}
