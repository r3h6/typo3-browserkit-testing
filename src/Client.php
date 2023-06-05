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
use TYPO3\CMS\Core\Core\Environment;
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
        $redirects = [];
        do {
            $typo3Request = new InternalRequest($request->getUri());
            if ($request->getMethod() !== 'GET') {
                $typo3Request = $typo3Request->withMethod($request->getMethod());
            }
            if ($request->getMethod() === 'POST' && $request->getContent() === null) {
                $typo3Request = $typo3Request->withAddedHeader('Content-Type', 'application/x-www-form-urlencoded');
                $typo3Request = $typo3Request->withBody(Utils::streamFor(http_build_query($request->getParameters())));
                $typo3Request = $typo3Request->withParsedBody($request->getParameters());
            }

            $_COOKIE = $request->getCookies();
            $typo3Context = (new InternalRequestContext())->withGlobalSettings([
                '_COOKIE' => $_COOKIE,
            ]);

            $frontendUserId = $request->getServer()[ServerParameters::TYPO3_FEUSER] ?? null;
            if ($frontendUserId !== null) {
                $typo3Context = $typo3Context->withFrontendUserId((int)$frontendUserId);
            }

            $typo3Response = $this->testCase->executeFrontendRequest($typo3Request, $typo3Context, true);

            // \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($typo3Response, '', 3, true);

            $cookieFile = Environment::getVarPath() . '/cookie';
            if (file_exists($cookieFile)) {
                $cookie = json_decode(file_get_contents($cookieFile));
                // $typo3Response = $typo3Response->withAddedHeader
            }

            $request = null;
            // Handle extbase redirects
            if (preg_match('#<meta http-equiv="refresh" content="0;url=(?P<url>[^"]+)"/>#si', (string)$typo3Response->getBody(), $matches)) {
                $redirectUrl = rawurldecode(html_entity_decode($matches['url']));
                if (in_array($redirectUrl, $redirects, true)) {
                    throw new \RuntimeException('Loop detected for ' . $redirectUrl, 1646316092331);
                }
                $redirects[] = $redirectUrl;
                $request = new Request($redirectUrl, 'GET');
            }
        } while ($request);

        return new Response(
            (string)$typo3Response->getBody(),
            $typo3Response->getStatusCode(),
            $typo3Response->getHeaders()
        );
    }
}
