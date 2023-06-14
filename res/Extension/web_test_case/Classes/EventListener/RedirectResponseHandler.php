<?php

declare(strict_types=1);

namespace R3H6\WebTestCase\EventListener;

use TYPO3\CMS\Core\Http\RedirectResponse;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Extbase\Event\Mvc\AfterRequestDispatchedEvent;

class RedirectResponseHandler
{
    public function __invoke(AfterRequestDispatchedEvent $event): void
    {
        $response = $event->getResponse();
        $uri = $response->getHeaderLine('Location');
        if ($uri) {
            throw new PropagateResponseException(new RedirectResponse($uri), 1686774861414);
        }
    }
}
