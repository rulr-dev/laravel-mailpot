<?php

namespace Rulr\Mailpot;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Rulr\Mailpot\Support\Mailpot;
use Rulr\Mailpot\Support\Statistics;

class MailpotTransport extends AbstractTransport
{
    protected string $storagePath;

    public function __construct(string $dsn = null)
    {
        parent::__construct();

        $this->storagePath = Mailpot::ensureInboxDirectory();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = $message->getOriginalMessage();

        if (! $email instanceof Email) {
            throw new \RuntimeException('Only Email messages are supported by Mailpot.');
        }

        $data = [
            'from'    => $email->getFrom()[0]->toString(),
            'to'      => array_map(fn ($a) => $a->toString(), $message->getEnvelope()->getRecipients()),
            'subject' => $email->getSubject(),
            'html'    => $email->getHtmlBody(),
            'text'    => $email->getTextBody(),
            'date'    => now()->toDateTimeString(),
            'headers' => $email->getHeaders()->toArray(),
        ];

        $fileName = now()->format('Y-m-d_H-i-s') . '_' . Str::random(6) . '.json';

        File::put(
            $this->storagePath . DIRECTORY_SEPARATOR . $fileName,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        Statistics::update($this->storagePath);
    }

    public function __toString(): string
    {
        return 'mailpot';
    }
}
