<?php

declare(strict_types=1);

namespace App\Message;

class EmailQueueMessage
{
    public function __construct(
        private readonly string $type,
        private readonly string $recipientEmail,
        private readonly string $subject,
        private readonly string $body,
    ) {}

    public function getType(): string
    {
        return $this->type;
    }

    public function getRecipientEmail(): string
    {
        return $this->recipientEmail;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getBody(): string
    {
        return $this->body;
    }
}
