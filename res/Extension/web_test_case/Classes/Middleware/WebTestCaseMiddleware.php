<?php

declare(strict_types=1);

namespace R3H6\WebTestCase\Middleware;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use TYPO3\CMS\Core\Core\Environment;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
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
            'body' => (string) $request->getBody(),
            '_COOKIE' => $_COOKIE,
            '_GET' => $_GET,
            '_POST' => $_POST,
        ]);
        $response = $handler->handle($request);
        return $response;
    }
}
