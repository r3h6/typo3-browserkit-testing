<?php

namespace R3H6\Typo3Webtesting;

use Symfony\Component\BrowserKit\History;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\BrowserKit\CookieJar;
use R3H6\Typo3Webtesting\BrowserKitTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

class Client extends AbstractBrowser
{
    /**
     * @var BrowserKitTestCase
     */
    protected $testCase;

    public function __construct(BrowserKitTestCase $testCase, History $history = null, CookieJar $cookieJar = null)
    {
        $this->testCase = $testCase;
        parent::__construct([], $history, $cookieJar);
    }

    /**
     * @param Symfony\Component\BrowserKit\Request $request
     * @return Symfony\Component\BrowserKit\Response
     */
    protected function doRequest($request)
    {
        // \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($request, '', 9, true);

        $typo3Request = new InternalRequest($request->getUri());
        $typo3Response = $this->testCase->executeFrontendRequest($typo3Request);


        return new Response(
            (string)$typo3Response->getBody(),
            $typo3Response->getStatusCode(),
            $typo3Response->getHeaders()
        );
    }
}