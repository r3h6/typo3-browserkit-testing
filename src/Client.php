<?php

declare(strict_types=1);

namespace R3H6\Typo3BrowserkitTesting;

use GuzzleHttp\Psr7\Utils;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\History;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\DomCrawler\Crawler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

class Client extends AbstractBrowser
{
    /**
     * @var WebTestCase
     */
    protected $testCase;

    public function __construct(WebTestCase $testCase, History $history = null, CookieJar $cookieJar = null)
    {
        $this->testCase = $testCase;
        parent::__construct([], $history, $cookieJar);
    }

    /**
     * @param array<string, mixed> $fieldValues
     */
    public function clickButton(string $button, array $fieldValues = []): Crawler
    {
        $button = $this->crawler->selectButton($button);
        $buttonNode = $button->getNode(0);
        $name = (string)$buttonNode->getAttribute('name');
        if ($name !== '') {
            $fieldValues[$name] = $buttonNode->getAttribute('value');
        }
        $form = $button->form($fieldValues);
        return $this->submit($form);
    }

    /**
     * @param Request $request
     * @return Response
     */
    protected function doRequest($request)
    {
        // Convert TYPO3 request to Symfony request object
        $typo3Request = new InternalRequest($request->getUri());
        if ($request->getMethod() !== 'GET') {
            $typo3Request = $typo3Request->withMethod($request->getMethod());
        }
        if ($request->getMethod() === 'POST' && $request->getContent() === null) {
            $typo3Request = $typo3Request->withAddedHeader('Content-Type', 'application/x-www-form-urlencoded');
            $typo3Request = $typo3Request->withBody(Utils::streamFor(http_build_query($request->getParameters())));
            $typo3Request = $typo3Request->withParsedBody($request->getParameters());
            $GLOBALS['_POST'] = $request->getParameters(); // Issue with TYPO3 v11 and Test-Framework v7
        } elseif ($request->getContent()) {
            $typo3Request = $typo3Request->withBody(Utils::streamFor($request->getContent()));
        }

        $_COOKIE = $request->getCookies();
        $typo3Context = (new InternalRequestContext())->withGlobalSettings([
            '_COOKIE' => $_COOKIE,
        ]);

        $frontendUserId = $request->getServer()[ServerParameters::TYPO3_FEUSER] ?? null;
        if ($frontendUserId !== null) {
            $typo3Context = $typo3Context->withFrontendUserId((int)$frontendUserId);
        }

        // Execute subrequest, redirects are handled by Symfony
        $typo3Response = $this->testCase->executeFrontendRequest($typo3Request, $typo3Context, false);

        // Make sure everything is persisted for subrequests
        GeneralUtility::makeInstance(PersistenceManager::class)->persistAll();

        // Convert TYPO3 response to Symfony response object
        return new Response(
            (string)$typo3Response->getBody(),
            $typo3Response->getStatusCode(),
            $typo3Response->getHeaders()
        );
    }
}
