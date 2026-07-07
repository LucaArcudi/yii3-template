<?php

declare(strict_types=1);

namespace App\Shared\Services\Mail\Transport;

use App\Shared\Services\Mail\EmailMessage;

interface EmailTransportInterface
{
    public function send(EmailMessage $message): void;
}
