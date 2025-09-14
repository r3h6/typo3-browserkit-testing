<?php

namespace R3H6\Typo3BrowserkitTesting\Client;

use R3H6\Typo3BrowserkitTesting\WebTestCase;
use TYPO3\CMS\Core\Http\Uri;

trait PhpBrowserTrait
{
    private string $baseUrl = '';

    public function amOnUrl(string $url): void
    {
        $this->baseUrl = $url;
        WebTestCase::getClient()->request('GET', $url);
    }

    public function amOnPage(string $page): void
    {
        WebTestCase::getClient()->request('GET', (string)$this->mergeUrl($page));
    }

    public function fillField(string $selector, string|int $value): void
    {
        WebTestCase::getClient()->setInputValue($selector, $value);
    }

    public function click(string $selector, ?string $context = null): void
    {
        WebTestCase::getClient()->clickElement($selector, $context);
    }

    public function selectOption(string ...$selectors): void
    {
        if (empty($selectors)) {
            throw new \RuntimeException('No selectors provided to selectOption');
        }
        foreach ($selectors as $selector) {
            $option = WebTestCase::getClient()->findElement($selector);
            $optionNode = $option->getNode(0);
            if (!$optionNode instanceof \DOMElement) {
                throw new \RuntimeException('The selected option is not a valid DOM element.');
            }
            $select = $option->closest('select');
            $selectNode = $select->getNode(0);
            if (!$selectNode instanceof \DOMElement) {
                throw new \RuntimeException('The option is not inside a select element.');
            }
            $multiple = $selectNode->hasAttribute('multiple');
            if ($multiple === false) {
                $options = $select->filter('option[selected]');
                foreach ($options as $i => $opt) {
                    if (!$opt instanceof \DOMElement) {
                        continue;
                    }
                    $opt->removeAttribute('selected');
                }
            }
            $optionNode->setAttribute('selected', 'selected');
        }
    }

    public function submitForm(string $selector, array $params = [], ?string $button = null): void
    {
        if ($button) {
            $button = WebTestCase::getClient()->findElement($button, $selector);
            WebTestCase::getClient()->clickButton($button, $params);
        }
        $form = WebTestCase::getClient()->getCrawler()->filter($selector)->form($params);
        WebTestCase::getClient()->submit($form);
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

    public function seeInTitle(string $text): void
    {
        WebTestCase::assertSelectorTextContains('html > head > title', $text);
    }

    public function dontSeeInTitle(string $text): void
    {
        WebTestCase::assertSelectorTextNotContains('html > head > title', $text);
    }

    public function seeInCurrentUrl(string $expected): void
    {
        $currentUrl = WebTestCase::getClient()->getRequest()->getUri();
        WebTestCase::assertStringContainsString($expected, $currentUrl);
    }

    public function dontSeeInCurrentUrl(string $expected): void
    {
        $currentUrl = WebTestCase::getClient()->getRequest()->getUri();
        WebTestCase::assertStringNotContainsString($expected, $currentUrl);
    }

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
