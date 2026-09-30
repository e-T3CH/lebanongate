<?php

declare(strict_types=1);

namespace BMMatic\Mail;

interface MailTransport
{
    /** Sends one message; throws MailException when it could not be delivered to the mail server. */
    public function send(MailMessage $message): void;
}
