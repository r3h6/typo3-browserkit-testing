<?php

declare(strict_types=1);

namespace R3H6\WebTestCase\EventListener;

use Symfony\Component\Mailer\Envelope;
use TYPO3\CMS\Core\Http\RedirectResponse;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\Event\MessageEvents;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Mail\Event\BeforeMailerSentMessageEvent;
use TYPO3\CMS\Extbase\Event\Mvc\AfterRequestDispatchedEvent;

class MessageLoggerListener
{
    private static ?MessageEvents $events = null;

    public static function setUp(): void
    {
        self::$events = new MessageEvents();
    }

    public function __invoke(BeforeMailerSentMessageEvent $event): void
    {
        $transport = 'unknown';
        $mailer = $event->getMailer();
        if ($mailer instanceof \TYPO3\CMS\Core\Mail\Mailer) {
            $transport = (string) $mailer->getRealTransport();
        }

        $clonedMessage = clone $event->getMessage();
        $clonedEnvelope = $event->getEnvelope() ?
            clone $event->getEnvelope() :
            Envelope::create($clonedMessage);

        $symfonyEvent = new MessageEvent(
            $clonedMessage,
            $clonedEnvelope,
            $transport,
            false
        );

        $this->getEvents()->add($symfonyEvent);
    }

    public function getEvents(): MessageEvents
    {
        if (self::$events === null) {
            throw new \RuntimeException('MessageEvents not initialized. Call MessageLoggerListener::setUp() before using the listener.', 1776107085);
        }

        return self::$events;
    }
}
