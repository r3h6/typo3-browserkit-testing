<?php

declare(strict_types=1);

namespace R3H6\WebTestCase\Middleware;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use TYPO3\CMS\Core\Core\Environment;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class WebTestCaseMiddleware implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {

        $request = $request->withCookieParams($_COOKIE);
        $this->logger->info('Request', [
            'uri' => (string) $request->getUri(),
            'method' => $request->getMethod(),
            'headers' => $request->getHeaders(),
            'body' => (string) $request->getBody(),
            '_COOKIE' => $_COOKIE,
            '_GET' => $_GET,
            '_POST' => $_POST,
        ]);

        ArrayUtility::mergeRecursiveWithOverrule($GLOBALS['TYPO3_CONF_VARS'], $GLOBALS['__TYPO3_CONF_VARS'] ?? []);

        $response = $handler->handle($request);

        if (!$response->hasHeader('Set-Cookie') && isset($GLOBALS['TSFE'])) {
            $response = $GLOBALS['TSFE']->fe_user->appendCookieToResponse($response);
        }

        $this->logger->info('Response', [
            'statusCode' => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
            '_COOKIE' => $_COOKIE,
        ]);

        return $response;
    }
}
