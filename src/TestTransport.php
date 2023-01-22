<?php

declare(strict_types=1);

namespace R3H6\Typo3BrowserkitTesting;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TestTransport extends AbstractTransport
{
    private const INBOX = 'typo3temp/inbox';

    public function __construct()
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $messages = self::getSentMessages();
        $messages[] = $message->getOriginalMessage();
        file_put_contents(self::getInbox(), serialize($messages));
    }

    public static function reset(): void
    {
        @unlink(self::getInbox());
    }

    /**
     * @return \TYPO3\CMS\Core\Mail\MailMessage[]
     */
    public static function getSentMessages(): array
    {
        $inbox = self::getInbox();
        return is_file($inbox) ? unserialize(file_get_contents($inbox)): [];
    }

    public function __toString(): string
    {
        return 'test://';
    }

    protected static function getInbox(): string
    {
        return GeneralUtility::getFileAbsFileName(self::INBOX);
    }
}
