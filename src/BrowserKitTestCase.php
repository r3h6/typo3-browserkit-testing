<?php

namespace R3H6\Typo3BrowserkitTesting;

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BrowserKitTestCase extends FunctionalTestCase implements BrowserKitAwareInterface
{
    use BrowserKitTrait;
    use DomCrawlerAssertionsTrait;
}
