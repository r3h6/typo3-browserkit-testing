<?php

declare(strict_types=1);

namespace R3H6\WebTestCase\EventListener;

use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Extbase\Event\Mvc\AfterRequestDispatchedEvent;

class RedirectResponseHandler
{
    public function __invoke(AfterRequestDispatchedEvent $event): void
    {
        $response = $event->getResponse();
        $uri = $response->getHeaderLine('Location');
        if ($uri) {
            $response->getBody()->write('<meta http-equiv="refresh" content="0;url=' . $uri . '"/>');
        }
    }
}
