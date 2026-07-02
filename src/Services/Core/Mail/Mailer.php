<?php

declare(strict_types=1);

namespace App\Services\Core\Mail;

use App\Params\Core\MailParams;
use App\Services\Core\Mail\Transport\EmailTransportInterface;

final readonly class Mailer
{
    public function __construct(
        private EmailRenderer $renderer,
        private EmailTransportInterface $transport,
        private MailParams $mailParams,
    ) {}

    public function send(EmailMessage $message): void
    {
        $this->transport->send($message->withDefaults($this->mailParams->fromEmail, $this->mailParams->fromName));
    }

    public function sendView(
        string $toEmail,
        ?string $toName,
        string $subject,
        string $view,
        array $parameters = [],
        ?string $textBody = null,
    ): void {
        $htmlBody = $this->renderer->render($view, [
            ...$parameters,
            'subject' => $subject,
        ]);

        $this->send(new EmailMessage(
            toEmail: $toEmail,
            toName: $toName,
            subject: $subject,
            htmlBody: $htmlBody,
            textBody: $textBody,
        ));
    }
}
