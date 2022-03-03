<?php

namespace R3H6\Typo3BrowserkitTesting;

use Symfony\Component\BrowserKit\History;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\AbstractBrowser;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use function GuzzleHttp\Psr7\stream_for;

class Client extends AbstractBrowser
{
    /**
     * @var BrowserKitAwareInterface
     */
    protected $testCase;

    public function __construct(BrowserKitAwareInterface $testCase, History $history = null, CookieJar $cookieJar = null)
    {
        $this->testCase = $testCase;
        parent::__construct([], $history, $cookieJar);
    }

    /**
     * @param Request $request
     * @return Response
     */
    protected function doRequest($request)
    {
        $typo3Request = new InternalRequest($request->getUri());
        if ($request->getMethod() !== 'GET') {
            $typo3Request = $typo3Request->withMethod($request->getMethod());
        }
        if ($request->getMethod() === 'POST' && $request->getContent() === null) {
//            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($request, '', 99, true); exit;
            $typo3Request = $typo3Request->withAddedHeader('Content-Type', 'application/x-www-form-urlencoded');
            $typo3Request = $typo3Request->withBody(stream_for(http_build_query($request->getParameters())));
        }

        $typo3Response = $this->testCase->executeFrontendRequest($typo3Request, null);
\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($typo3Response->getHeaders(), '', 99, true);

        return new Response(
            (string)$typo3Response->getBody(),
            $typo3Response->getStatusCode(),
            $typo3Response->getHeaders()
        );
    }
}
