<?php

namespace R3H6\Typo3BrowserkitTesting\Client;

use R3H6\Typo3BrowserkitTesting\WebTestCase;
use TYPO3\CMS\Core\Http\Uri;

trait PhpBrowserTrait
{
    private string $baseUrl;

    public function amOnUrl(string $url): void
    {
        $this->baseUrl = $url;
        WebTestCase::getTypo3Client()->request('GET', $url);
    }

    public function amOnPage(string $page): void
    {
        WebTestCase::getTypo3Client()->request('GET', (string)$this->mergeUrl($page));
    }

    public function fillField(string $selector, string|int $value): void {}

    public function click(string $selector, string $context = null): void
    {
        WebTestCase::getTypo3Client()->clickLink($selector);
    }

    public function selectOption(string $selector, string|int $value): void {}

    public function submitForm(string $selector, array $params = [], string $button = null): void
    {
        if ($button) {
            WebTestCase::getTypo3Client()->clickSubmitButton($button, $params);
        }
        $form = WebTestCase::getTypo3Client()->filter($selector)->form($params);
        WebTestCase::getTypo3Client()->submit($form);
    }

    public function see(string $text, string $selector = 'body'): void
    {
        WebTestCase::assertSelectorTextContains($selector, $text);
    }

    public function dontSee(string $text, string $selector = 'body'): void
    {
        WebTestCase::assertSelectorTextNotContains($selector, $text);
    }

    public function seeElement(string $selector): void
    {
        WebTestCase::assertSelectorExists($selector);
    }

    public function dontSeeElement(string $selector): void
    {
        WebTestCase::assertSelectorNotExists($selector);
    }

    public function seeInCurrentUrl(string $url): void {}

    public function dontSeeInCurrentUrl(string $url): void {}

    public function seeLink(string $link): void
    {
        WebTestCase::assertSelectorExists('a:contains("' . $link . '")');
    }

    public function dontSeeLink(string $link): void
    {
        WebTestCase::assertSelectorNotExists('a:contains("' . $link . '")');
    }

    public function grabFromCurrentPage(string $selector): void {}

    private function mergeUrl(string $url): Uri
    {
        $baseUri = new Uri($this->baseUrl);
        $uri = new Uri($url);
        $uri->getScheme() ?: $uri = $uri->withScheme($baseUri->getScheme());
        $uri->getHost() ?: $uri = $uri->withHost($baseUri->getHost());
        return $uri;
    }
}
