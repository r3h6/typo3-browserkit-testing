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
        $response = $handler->handle($request);

        // $cookie = [
        //     'name' => $GLOBALS['TSFE']->fe_user->getCookieName(),
        //     'value' => $GLOBALS['TSFE']->fe_user->id,
        // ];

        // file_put_contents(Environment::getVarPath() . '/cookie', json_encode($cookie));

        return $response;
    }
}
