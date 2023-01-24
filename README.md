# TYPO3 BrowserKit Testing

Brings [Symfony's BrowserKit Component](https://github.com/symfony/browser-kit) to [TYPO3 Testing Framework](https://github.com/TYPO3/testing-framework).

## Example

```php
class MyTestCase extends BrowserKitTestCase
{
    public function testExample()
    {
        $client = self::getClient($this);
        $crawler = $client->request('GET', '/');
        self::assertSelectorExists('.example');
    }
}
```

See also [tests/Functional/DomCrawlerAssertionsTest.php](tests/Functional/DomCrawlerAssertionsTest.php)

## Assertions

You can find details on [Symfony's Testing Documentation](https://symfony.com/doc/current/testing.html#testing-the-response-assertions).

❌ Response Assertions<br>
❌ Request Assertions<br>
❌ Browser Assertions<br>
✅ Crawler Assertions<br>
✅ Mailer Assertions<br>

## Known problems

- File upload not (yet) implemented
