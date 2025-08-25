<?php

declare(strict_types=1);

namespace R3H6\Typo3BrowserkitTesting\Tests\Functional;

use TYPO3\CMS\Core\Package\PackageManager;
use R3H6\Typo3BrowserkitTesting\WebTestCase;
use R3H6\Typo3BrowserkitTesting\ApplicationTestCase;
use R3H6\Typo3BrowserkitTesting\Client\AnonymousVisitor;

class ApplicationTest extends WebTestCase
{

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
}
